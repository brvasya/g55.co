import json
import os
import tkinter as tk
import webbrowser
from tkinter import ttk, messagebox

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")
ROOT_CATEGORIES_FILE = os.path.join(SCRIPT_DIR, "categories.json")

PAGE_TEMPLATE = {
    "id": "",
    "title": "",
    "iframe": "",
    "description": "",
}

CATEGORY_TEMPLATE = {
    "id": "",
    "name": "",
    "description": "",
}


def list_json_files(folder: str) -> list[str]:
    files = []
    try:
        for name in os.listdir(folder):
            if name.lower().endswith(".json") and os.path.isfile(os.path.join(folder, name)):
                files.append(name)
    except Exception:
        pass
    files.sort(key=lambda s: s.lower())
    return files


def list_all_editable_files() -> list[str]:
    files = []

    if os.path.isfile(ROOT_CATEGORIES_FILE):
        files.append("categories.json")

    files.extend(list_json_files(CATEGORIES_DIR))
    return files


def normalize_loaded_json(loaded, file_name: str):
    if file_name == "categories.json":
        if isinstance(loaded, dict) and "categories" in loaded and isinstance(loaded["categories"], list):
            return loaded["categories"], loaded, "categories"
        raise ValueError('Unsupported categories.json format. Expected {"categories": [...]}')

    if isinstance(loaded, dict) and "pages" in loaded and isinstance(loaded["pages"], list):
        return loaded["pages"], loaded, "pages"

    if isinstance(loaded, list):
        return loaded, None, "pages"

    raise ValueError('Unsupported JSON format. Expected {"pages": [...]} or a list.')


def load_json_file(path: str):
    file_name = os.path.basename(path)

    with open(path, "r", encoding="utf-8") as f:
        loaded = json.load(f)

    items, wrapper, mode = normalize_loaded_json(loaded, file_name)

    cleaned = []
    if mode == "categories":
        for it in items:
            if isinstance(it, dict):
                cleaned.append(
                    {
                        "id": str(it.get("id", "")).strip(),
                        "name": str(it.get("name", "")).strip(),
                        "description": str(it.get("description", "")).strip(),
                    }
                )
    else:
        for it in items:
            if isinstance(it, dict):
                cleaned.append(
                    {
                        "id": str(it.get("id", "")).strip(),
                        "title": str(it.get("title", "")).strip(),
                        "iframe": str(it.get("iframe", "")).strip(),
                        "description": str(it.get("description", "")).strip(),
                    }
                )

    return cleaned, wrapper, mode


def save_json_file(path: str, items: list, wrapper, mode: str):
    if wrapper is None:
        payload = items
    else:
        if mode == "categories":
            wrapper["categories"] = items
        else:
            wrapper["pages"] = items
        payload = wrapper

    with open(path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=4)


def category_keyword_from_filename(name: str) -> str:
    name = os.path.splitext((name or "").strip())[0]
    return name.lower().replace("-", " ").strip()


def tokenize_text(s: str) -> list[str]:
    s = (s or "").strip().lower().replace("-", " ")
    parts = []
    cur = ""
    for ch in s:
        if ch.isalnum():
            cur += ch
        else:
            if cur:
                parts.append(cur)
                cur = ""
    if cur:
        parts.append(cur)
    return parts


def singularize_token(t: str) -> str:
    t = (t or "").strip().lower()
    if len(t) < 4:
        return t

    if t.endswith("ies") and len(t) > 4:
        return t[:-3] + "y"

    if t.endswith("es") and len(t) > 4:
        base = t[:-2]
        if base.endswith(("s", "x", "z")) or base.endswith(("ch", "sh")):
            return base

    if t.endswith("s") and not t.endswith("ss") and len(t) > 3:
        return t[:-1]

    return t


def gerund_to_base_token(t: str) -> str:
    t = (t or "").strip().lower()
    if len(t) < 6 or not t.endswith("ing"):
        return t

    stem = t[:-3]
    if len(stem) < 3:
        return t

    if len(stem) >= 2 and stem[-1] == stem[-2] and stem[-1] not in "aeiou":
        stem = stem[:-1]

    if stem.endswith(("ac", "ag", "at", "iv", "iz", "us", "ov", "ul", "ur")):
        return stem + "e"

    return stem


def agent_noun_to_base_token(t: str) -> str:
    t = (t or "").strip().lower()
    if len(t) < 5:
        return t

    if t.endswith("ier") and len(t) > 4:
        return t[:-3] + "y"

    if t.endswith("er") and len(t) > 4:
        stem = t[:-2]

        if len(stem) >= 2 and stem[-1] == stem[-2] and stem[-1] not in "aeiou":
            stem = stem[:-1]

        if stem.endswith(("ac", "ag", "at", "iv", "iz", "us", "ov", "ul", "ur")):
            return stem + "e"

        return stem

    return t


def normalize_token_variants(t: str) -> set[str]:
    t = (t or "").strip().lower()
    if not t:
        return set()

    out = {t}

    s = singularize_token(t)
    out.add(s)

    g = gerund_to_base_token(t)
    out.add(g)
    out.add(singularize_token(g))

    a = agent_noun_to_base_token(t)
    out.add(a)
    out.add(singularize_token(a))

    return {x for x in out if x}


def normalized_forms_for_text(s: str) -> list[set[str]]:
    return [normalize_token_variants(tok) for tok in tokenize_text(s)]


def keyword_matches_title(title: str, keyword: str) -> bool:
    keyword_tokens = tokenize_text(keyword)
    title_forms = normalized_forms_for_text(title)

    if not keyword_tokens or not title_forms:
        return False

    if len(keyword_tokens) > len(title_forms):
        return False

    normalized_keyword_tokens = []
    for tok in keyword_tokens:
        variants = normalize_token_variants(tok)
        normalized_keyword_tokens.append(variants)

    k = len(normalized_keyword_tokens)
    for i in range(0, len(title_forms) - k + 1):
        ok = True
        for j in range(k):
            if title_forms[i + j].isdisjoint(normalized_keyword_tokens[j]):
                ok = False
                break
        if ok:
            return True

    return False


def count_title_keyword_matches(pages, keyword: str) -> int:
    keyword = (keyword or "").strip().lower().replace("-", " ")
    if not keyword:
        return 0

    count = 0
    for it in pages:
        title = str(it.get("title", "")).strip()
        if keyword_matches_title(title, keyword):
            count += 1
    return count


def title_matches_keyword(title: str, keyword: str) -> bool:
    keyword = (keyword or "").strip().lower().replace("-", " ")
    title = (title or "").strip()
    if not keyword:
        return False
    return keyword_matches_title(title, keyword)


def description_has_bullet(description: str) -> bool:
    return "•" in str(description or "")


def count_items_with_bullets(items) -> int:
    count = 0
    for it in items:
        if description_has_bullet(it.get("description", "")):
            count += 1
    return count


class JsonGui(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("JSON Pages Editor")
        self.state("zoomed")

        self.current_file = None
        self.items = []
        self.wrapper = None
        self.mode = "pages"
        self.selected_index = None

        self.search_matches = []
        self.search_pos = -1
        self.last_search_query = ""

        self.files = list_all_editable_files()

        self.file_var = tk.StringVar(value="")
        self.status_var = tk.StringVar(value="")
        self.build_ui()

        if self.files:
            self.select_category_by_name(self.files[0])
        else:
            messagebox.showwarning("No JSON files", f"No editable .json files found in:\n{SCRIPT_DIR}")
            self.set_status("No JSON files found")

    def build_ui(self):
        top = ttk.Frame(self, padding=10)
        top.pack(fill="x")

        ttk.Button(top, text="Reload list", command=self.reload_list).pack(side="left")

        ttk.Label(top, text="Current file").pack(side="left", padx=(16, 6))
        ttk.Label(top, textvariable=self.file_var).pack(side="left")

        main = ttk.Frame(self, padding=10)
        main.pack(fill="both", expand=True)

        sidebar = ttk.Frame(main)
        sidebar.pack(side="left", fill="y")

        mid = ttk.Frame(main)
        mid.pack(side="left", fill="both", expand=True, padx=(12, 0))

        right = ttk.Frame(main)
        right.pack(side="right", fill="y", padx=(12, 0))

        ttk.Label(sidebar, text="Files").pack(anchor="w")

        cat_frame = ttk.Frame(sidebar)
        cat_frame.pack(fill="y", expand=True, pady=(6, 0))

        self.cat_listbox = tk.Listbox(cat_frame, height=28, exportselection=False, width=34)
        self.cat_listbox.pack(side="left", fill="y", expand=False)
        self.cat_listbox.bind("<<ListboxSelect>>", lambda e: self.on_category_click())

        cat_scroll = ttk.Scrollbar(cat_frame, orient="vertical", command=self.cat_listbox.yview)
        cat_scroll.pack(side="right", fill="y")
        self.cat_listbox.config(yscrollcommand=cat_scroll.set)

        ttk.Label(mid, text="Items").pack(anchor="w")

        list_frame = ttk.Frame(mid)
        list_frame.pack(fill="both", expand=True, pady=(6, 0))

        self.listbox = tk.Listbox(list_frame, height=24, exportselection=False)
        self.listbox.pack(side="left", fill="both", expand=True)
        self.listbox.bind("<<ListboxSelect>>", lambda e: self.pick_from_list())

        scroll = ttk.Scrollbar(list_frame, orient="vertical", command=self.listbox.yview)
        scroll.pack(side="right", fill="y")
        self.listbox.config(yscrollcommand=scroll.set)

        form = ttk.LabelFrame(right, text="Editor", padding=10)
        form.pack(fill="x")

        self.name_title_label = ttk.Label(form, text="Title")
        self.name_title_label.grid(row=0, column=0, sticky="w")

        self.title_var = tk.StringVar()
        self.title_entry = ttk.Entry(form, textvariable=self.title_var, width=42)
        self.title_entry.grid(row=1, column=0, sticky="we", pady=(0, 8), padx=(0, 6))

        self.copy_title_btn = ttk.Button(form, text="Copy", command=self.copy_title, width=10)
        self.copy_title_btn.grid(row=1, column=1, sticky="e", pady=(0, 8))

        ttk.Label(form, text="Id").grid(row=2, column=0, sticky="w")
        self.id_var = tk.StringVar()
        ttk.Entry(form, textvariable=self.id_var, width=48).grid(
            row=3, column=0, columnspan=2, sticky="we", pady=(0, 8)
        )

        self.iframe_label = ttk.Label(form, text="Iframe")
        self.iframe_label.grid(row=4, column=0, sticky="w")
        self.iframe_var = tk.StringVar()
        self.iframe_entry = ttk.Entry(form, textvariable=self.iframe_var, width=42)
        self.iframe_entry.grid(row=5, column=0, sticky="we", pady=(0, 8), padx=(0, 6))

        self.open_iframe_btn = ttk.Button(form, text="Open", command=self.open_iframe_url, width=10)
        self.open_iframe_btn.grid(row=5, column=1, sticky="e", pady=(0, 8))

        self.desc_label = ttk.Label(form, text="Description")
        self.desc_label.grid(row=6, column=0, sticky="w")
        self.desc_text = tk.Text(form, width=48, height=9, wrap="word")
        self.desc_text.grid(row=7, column=0, columnspan=2, sticky="we", pady=(0, 8))

        btn_row = ttk.Frame(form)
        btn_row.grid(row=8, column=0, columnspan=2, sticky="we")

        ttk.Button(btn_row, text="New", command=self.new_template).pack(side="left")
        ttk.Button(btn_row, text="Add", command=self.add_item).pack(side="left", padx=6)
        ttk.Button(btn_row, text="Update", command=self.update_item).pack(side="left", padx=6)
        ttk.Button(btn_row, text="Delete", command=self.delete_item).pack(side="left", padx=6)

        search = ttk.LabelFrame(right, text="Find by title", padding=10)
        search.pack(fill="x", pady=(10, 0))

        ttk.Label(search, text="Title").grid(row=0, column=0, sticky="w")
        self.search_title_var = tk.StringVar()
        search_entry = ttk.Entry(search, textvariable=self.search_title_var, width=36)
        search_entry.grid(row=1, column=0, sticky="we", padx=(0, 6))
        search_entry.bind("<Return>", lambda e: self.search_by_title())
        ttk.Button(search, text="Find", command=self.search_by_title).grid(row=1, column=1, sticky="e")

        movef = ttk.LabelFrame(right, text="Move to category", padding=10)
        movef.pack(fill="x", pady=(10, 0))

        ttk.Label(movef, text="Target file").grid(row=0, column=0, sticky="w")

        self.move_file_var = tk.StringVar(value="")
        self.move_combo = ttk.Combobox(
            movef,
            textvariable=self.move_file_var,
            values=[],
            state="readonly",
            width=40,
        )
        self.move_combo.grid(row=1, column=0, sticky="we", padx=(0, 6))
        self.move_btn = ttk.Button(movef, text="Move", command=self.move_selected_page)
        self.move_btn.grid(row=1, column=1, sticky="e")
        self.move_unmatched_btn = ttk.Button(
            movef,
            text="Move all unmatched to casual",
            command=self.move_all_unmatched_to_casual
        )
        self.move_unmatched_btn.grid(row=2, column=0, columnspan=2, sticky="we", pady=(8, 0))

        ttk.Label(right, textvariable=self.status_var).pack(anchor="w", pady=(10, 0))

        form.columnconfigure(0, weight=1)

        self.refresh_category_list()
        self.set_status("Ready")
        self.new_template()
        self.update_mode_ui()

    def set_status(self, text: str):
        self.status_var.set(text)

    def copy_title(self):
        value = self.title_var.get().strip()
        if not value:
            self.set_status("Title is empty")
            return

        try:
            self.clipboard_clear()
            self.clipboard_append(value)
            self.update_idletasks()
            self.set_status("Title copied")
        except Exception:
            self.set_status("Copy failed")

    def open_iframe_url(self):
        if self.is_root_categories_mode():
            self.set_status("Open is unavailable in categories mode")
            return

        url = self.iframe_var.get().strip()
        if not url:
            self.set_status("Iframe is empty")
            return

        if not (url.startswith("http://") or url.startswith("https://")):
            url = "https://" + url

        try:
            webbrowser.open_new_tab(url)
            self.set_status("Opened iframe URL")
        except Exception:
            self.set_status("Open failed")

    def is_root_categories_mode(self) -> bool:
        return self.mode == "categories"

    def update_mode_ui(self):
        if self.is_root_categories_mode():
            self.name_title_label.config(text="Name")
            self.iframe_label.grid_remove()
            self.iframe_entry.grid_remove()
            self.open_iframe_btn.grid_remove()
            self.desc_label.grid()
            self.desc_text.grid()
            self.move_btn.state(["disabled"])
            self.move_unmatched_btn.state(["disabled"])
        else:
            self.name_title_label.config(text="Title")
            self.iframe_label.grid()
            self.iframe_entry.grid()
            self.open_iframe_btn.grid()
            self.desc_label.grid()
            self.desc_text.grid()
            self.move_btn.state(["!disabled"])
            self.move_unmatched_btn.state(["!disabled"])

    def update_page_match_status(self, prefix: str = ""):
        total = len(self.items)
        completed = count_items_with_bullets(self.items)

        if self.is_root_categories_mode():
            text = f"Descriptions: {completed}/{total}"
            self.set_status(f"{prefix}  {text}" if prefix else text)
            return

        file_name = os.path.basename(self.current_file) if self.current_file else self.file_var.get()
        keyword = category_keyword_from_filename(file_name)
        matched = count_title_keyword_matches(self.items, keyword)

        text = f"Descriptions: {completed}/{total}   Titles: {matched}/{total}"
        self.set_status(f"{prefix}  {text}" if prefix else text)

    def refresh_category_list(self):
        current_name = os.path.basename(self.current_file) if self.current_file else ""
        self.cat_listbox.delete(0, tk.END)
        for name in self.files:
            self.cat_listbox.insert(tk.END, name)
        if current_name and current_name in self.files:
            self.select_category_by_name(current_name, load=False)

    def select_category_by_name(self, name: str, load: bool = True):
        if name not in self.files:
            return
        idx = self.files.index(name)
        self.cat_listbox.selection_clear(0, tk.END)
        self.cat_listbox.selection_set(idx)
        self.cat_listbox.see(idx)
        if load:
            self.load_selected(name)

    def on_category_click(self):
        sel = self.cat_listbox.curselection()
        if not sel:
            return
        name = self.files[int(sel[0])] if int(sel[0]) < len(self.files) else ""
        if not name:
            return
        if self.current_file and os.path.basename(self.current_file) == name:
            return
        self.load_selected(name)

    def reload_list(self):
        keep = os.path.basename(self.current_file) if self.current_file else ""
        self.files = list_all_editable_files()

        self.refresh_category_list()

        if not self.files:
            self.file_var.set("")
            self.current_file = None
            self.items = []
            self.wrapper = None
            self.mode = "pages"
            self.selected_index = None
            self.search_matches = []
            self.search_pos = -1
            self.last_search_query = ""
            self.refresh_list()
            self.new_template()
            self.set_status("No JSON files found")
            return

        if keep and keep in self.files:
            self.select_category_by_name(keep)
        else:
            self.select_category_by_name(self.files[0])

    def path_for_name(self, name: str):
        name = (name or "").strip()
        if not name:
            return None
        if name == "categories.json":
            return ROOT_CATEGORIES_FILE
        return os.path.join(CATEGORIES_DIR, name)

    def load_selected(self, file_name: str):
        path = self.path_for_name(file_name)
        if not path:
            return
        try:
            cleaned, wrapper, mode = load_json_file(path)
            self.items = cleaned
            self.wrapper = wrapper
            self.mode = mode
            self.current_file = path
            self.selected_index = None
            self.search_matches = []
            self.search_pos = -1
            self.last_search_query = ""
            self.file_var.set(os.path.basename(self.current_file))
            self.refresh_list()
            self.new_template()
            self.update_move_dropdown()
            self.update_mode_ui()
            self.update_page_match_status("Loaded")
        except Exception as e:
            self.items = []
            self.wrapper = None
            self.mode = "pages"
            self.current_file = path
            self.selected_index = None
            self.search_matches = []
            self.search_pos = -1
            self.last_search_query = ""
            self.file_var.set(os.path.basename(self.current_file))
            self.refresh_list()
            self.new_template()
            self.update_mode_ui()
            messagebox.showerror("Load failed", f"Could not load JSON:\n{e}")
            self.set_status("Load failed")

    def save_json(self, silent: bool = True) -> bool:
        if not self.current_file:
            return False
        try:
            save_json_file(self.current_file, self.items, self.wrapper, self.mode)
            self.set_status("Auto saved")
            return True
        except Exception:
            self.set_status("Save failed")
            return False

    def autosave(self) -> bool:
        return self.save_json(silent=True)

    def refresh_list(self):
        self.listbox.delete(0, tk.END)

        if self.is_root_categories_mode():
            for idx, it in enumerate(self.items):
                label = it.get("name") or it.get("id") or "(empty)"
                self.listbox.insert(tk.END, label)

                if not description_has_bullet(it.get("description", "")):
                    self.listbox.itemconfig(idx, bg="#e6e6e6", fg="#555555")
            return

        file_name = os.path.basename(self.current_file) if self.current_file else self.file_var.get()
        keyword = category_keyword_from_filename(file_name)

        for idx, it in enumerate(self.items):
            label = it.get("title") or it.get("id") or "(empty)"
            self.listbox.insert(tk.END, label)

            has_title_match = title_matches_keyword(it.get("title", ""), keyword)
            has_bullet = description_has_bullet(it.get("description", ""))

            if not has_title_match:
                self.listbox.itemconfig(idx, bg="#ffe5e5", fg="#a00000")
            elif not has_bullet:
                self.listbox.itemconfig(idx, bg="#e6e6e6", fg="#555555")

    def read_form(self):
        if self.is_root_categories_mode():
            return {
                "id": self.id_var.get().strip(),
                "name": self.title_var.get().strip(),
                "description": self.desc_text.get("1.0", "end").strip(),
            }

        return {
            "id": self.id_var.get().strip(),
            "title": self.title_var.get().strip(),
            "iframe": self.iframe_var.get().strip(),
            "description": self.desc_text.get("1.0", "end").strip(),
        }

    def write_form(self, it):
        self.id_var.set(it.get("id", ""))
        if self.is_root_categories_mode():
            self.title_var.set(it.get("name", ""))
        else:
            self.title_var.set(it.get("title", ""))

        self.iframe_var.set(it.get("iframe", ""))
        self.desc_text.delete("1.0", "end")
        self.desc_text.insert("1.0", it.get("description", ""))

    def new_template(self):
        self.selected_index = None
        if self.is_root_categories_mode():
            self.write_form(CATEGORY_TEMPLATE.copy())
            self.update_page_match_status("New category")
        else:
            self.write_form(PAGE_TEMPLATE.copy())
            self.update_page_match_status("New page")

    def find_duplicate_id(self, item_id, ignore_index=None):
        for idx, it in enumerate(self.items):
            if ignore_index is not None and idx == ignore_index:
                continue
            if str(it.get("id", "")).strip() == item_id:
                return idx
        return None

    def goto_index(self, idx: int):
        if idx < 0 or idx >= len(self.items):
            return
        self.listbox.selection_clear(0, tk.END)
        self.listbox.selection_set(idx)
        self.listbox.see(idx)
        self.selected_index = idx
        self.write_form(self.items[idx])

        if self.is_root_categories_mode():
            self.update_page_match_status(f"Selected category {idx + 1} of {len(self.items)}")
        else:
            self.update_page_match_status(f"Selected page {idx + 1} of {len(self.items)}")

    def get_search_label(self, item):
        if self.is_root_categories_mode():
            return str(item.get("name", "")).strip().lower()
        return str(item.get("title", "")).strip().lower()

    def search_by_title(self):
        title_query = self.search_title_var.get().strip().lower()
        if not title_query:
            messagebox.showwarning("Missing title", "Enter a title to search.")
            return

        if title_query != self.last_search_query:
            self.search_matches = [
                idx for idx, it in enumerate(self.items)
                if title_query in self.get_search_label(it)
            ]
            self.search_pos = -1
            self.last_search_query = title_query

        if not self.search_matches:
            messagebox.showinfo("Not found", f"No item found containing:\n{title_query}")
            self.set_status("No matches found")
            return

        self.search_pos = (self.search_pos + 1) % len(self.search_matches)
        target_idx = self.search_matches[self.search_pos]
        self.goto_index(target_idx)

        self.set_status(
            f"Found {len(self.search_matches)} match(es)   Showing {self.search_pos + 1}/{len(self.search_matches)}"
        )

    def update_move_dropdown(self):
        if not hasattr(self, "move_combo"):
            return
        if not self.files or not self.current_file or self.is_root_categories_mode():
            self.move_combo["values"] = []
            self.move_file_var.set("")
            return

        current_name = os.path.basename(self.current_file)
        choices = [f for f in self.files if f != current_name and f != "categories.json"]
        self.move_combo["values"] = choices

        cur = self.move_file_var.get().strip()
        if cur not in choices:
            self.move_file_var.set(choices[0] if choices else "")

    def move_selected_page(self):
        if self.is_root_categories_mode():
            return

        if not self.current_file:
            messagebox.showwarning("No file", "Select a category file first.")
            return
        if self.selected_index is None:
            messagebox.showwarning("Nothing selected", "Select a page to move.")
            return

        target_name = self.move_file_var.get().strip()
        if not target_name:
            messagebox.showwarning("No target", "Choose a target category file.")
            return

        current_name = os.path.basename(self.current_file)
        if target_name == current_name:
            messagebox.showwarning("Same target", "Target file is the same as the current file.")
            return

        target_path = os.path.join(CATEGORIES_DIR, target_name)

        page = self.items[self.selected_index]
        page_id = str(page.get("id", "")).strip()
        if not page_id:
            messagebox.showwarning("Missing id", "Selected page has no id.")
            return

        try:
            target_pages, target_wrapper, target_mode = load_json_file(target_path)

            for it in target_pages:
                if str(it.get("id", "")).strip() == page_id:
                    messagebox.showerror("Duplicate id", f"Target file already contains id:\n{page_id}")
                    return

            target_pages.insert(0, page)
            save_json_file(target_path, target_pages, target_wrapper, target_mode)

            del self.items[self.selected_index]
            self.selected_index = None
            save_json_file(self.current_file, self.items, self.wrapper, self.mode)

            self.refresh_list()
            self.new_template()
            self.update_move_dropdown()
            self.update_page_match_status(f"Moved id {page_id} to {target_name}")
            messagebox.showinfo("Moved", f"Moved:\n{page_id}\n\nTo:\n{target_name}")
        except Exception as e:
            messagebox.showerror("Move failed", f"Could not move page:\n{e}")
            self.set_status("Move failed")

    def move_all_unmatched_to_casual(self):
        if self.is_root_categories_mode():
            return

        if not self.current_file:
            messagebox.showwarning("No file", "Select a category file first.")
            return

        current_name = os.path.basename(self.current_file)
        if current_name.lower() == "casual.json":
            messagebox.showwarning("Already casual", "You are already viewing casual.json.")
            return

        casual_path = os.path.join(CATEGORIES_DIR, "casual.json")
        if not os.path.isfile(casual_path):
            messagebox.showerror("Missing casual.json", f"Could not find:\n{casual_path}")
            return

        keyword = category_keyword_from_filename(current_name)
        unmatched_indexes = [
            idx for idx, it in enumerate(self.items)
            if not title_matches_keyword(it.get("title", ""), keyword)
        ]

        if not unmatched_indexes:
            messagebox.showinfo("Nothing to move", "No unmatched pages found in the current category.")
            return

        if not messagebox.askyesno(
            "Move all unmatched",
            f"Move {len(unmatched_indexes)} unmatched page(s) from\n{current_name}\n\nto\ncasual.json?"
        ):
            return

        try:
            casual_pages, casual_wrapper, casual_mode = load_json_file(casual_path)
            existing_ids = {str(it.get("id", "")).strip() for it in casual_pages if str(it.get("id", "")).strip()}

            pages_to_move = []
            duplicate_count = 0

            for idx in unmatched_indexes:
                page = self.items[idx]
                page_id = str(page.get("id", "")).strip()

                if page_id and page_id in existing_ids:
                    duplicate_count += 1
                    continue

                pages_to_move.append(page)
                if page_id:
                    existing_ids.add(page_id)

            if not pages_to_move:
                messagebox.showinfo(
                    "Nothing moved",
                    f"All unmatched pages were skipped because their ids already exist in casual.json.\n\nSkipped duplicates: {duplicate_count}"
                )
                return

            casual_pages = pages_to_move + casual_pages
            save_json_file(casual_path, casual_pages, casual_wrapper, casual_mode)

            moved_ids = {id(page) for page in pages_to_move}
            self.items = [page for page in self.items if id(page) not in moved_ids]
            self.selected_index = None
            save_json_file(self.current_file, self.items, self.wrapper, self.mode)

            self.refresh_list()
            self.new_template()
            self.update_move_dropdown()
            self.update_page_match_status(f"Moved {len(pages_to_move)} unmatched to casual.json")

            msg = f"Moved: {len(pages_to_move)} unmatched page(s) to casual.json"
            if duplicate_count:
                msg += f"\nSkipped duplicate ids: {duplicate_count}"
            messagebox.showinfo("Done", msg)

        except Exception as e:
            messagebox.showerror("Move failed", f"Could not move unmatched pages:\n{e}")
            self.set_status("Move failed")

    def add_item(self):
        it = self.read_form()
        label_value = it.get("name", "") if self.is_root_categories_mode() else it.get("title", "")

        if not it["id"] or not label_value:
            messagebox.showwarning("Missing fields", "Please fill Id and Name/Title.")
            return

        dup = self.find_duplicate_id(it["id"])
        if dup is not None:
            messagebox.showerror("Duplicate id", f"An item with this id already exists at index {dup + 1}.")
            return

        self.items.insert(0, it)
        self.refresh_list()
        self.goto_index(0)

        if not self.autosave():
            messagebox.showerror("Auto save failed", "Could not auto save after add.")
            return

        if self.is_root_categories_mode():
            self.update_page_match_status("Added category and auto saved")
        else:
            self.update_page_match_status("Added page and auto saved")

    def update_item(self):
        if self.selected_index is None:
            messagebox.showwarning("Nothing selected", "Select an item to update.")
            return

        it = self.read_form()
        label_value = it.get("name", "") if self.is_root_categories_mode() else it.get("title", "")

        if not it["id"] or not label_value:
            messagebox.showwarning("Missing fields", "Please fill Id and Name/Title.")
            return

        dup = self.find_duplicate_id(it["id"], ignore_index=self.selected_index)
        if dup is not None:
            messagebox.showerror("Duplicate id", f"Another item already uses this id at index {dup + 1}.")
            return

        self.items[self.selected_index] = it
        keep = self.selected_index
        self.refresh_list()
        self.goto_index(keep)

        if not self.autosave():
            messagebox.showerror("Auto save failed", "Could not auto save after update.")
            return

        if self.is_root_categories_mode():
            self.update_page_match_status("Updated category and auto saved")
        else:
            self.update_page_match_status("Updated page and auto saved")

    def delete_item(self):
        if self.selected_index is None:
            messagebox.showwarning("Nothing selected", "Select an item to delete.")
            return

        idx = self.selected_index
        title = (
            self.items[idx].get("name")
            if self.is_root_categories_mode()
            else self.items[idx].get("title")
        ) or self.items[idx].get("id")

        if not messagebox.askyesno("Delete", f"Delete selected item?\n\n{title}"):
            return

        del self.items[idx]
        self.selected_index = None
        self.refresh_list()
        self.new_template()

        if not self.autosave():
            messagebox.showerror("Auto save failed", "Could not auto save after delete.")
            return

        if self.is_root_categories_mode():
            self.update_page_match_status("Deleted category and auto saved")
        else:
            self.update_page_match_status("Deleted page and auto saved")

    def pick_from_list(self):
        sel = self.listbox.curselection()
        if not sel:
            return
        idx = int(sel[0])
        if idx < len(self.items):
            self.selected_index = idx
            self.write_form(self.items[idx])

            if self.is_root_categories_mode():
                self.update_page_match_status(f"Selected category {idx + 1} of {len(self.items)}")
            else:
                self.update_page_match_status(f"Selected page {idx + 1} of {len(self.items)}")


if __name__ == "__main__":
    app = JsonGui()
    app.mainloop()