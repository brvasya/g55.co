import json
import os
import re
import tkinter as tk
import webbrowser
from openai import OpenAI
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




def slugify(text: str) -> str:
    text = str(text or "").strip().lower()
    text = re.sub(r"[^\w\s-]", "", text, flags=re.UNICODE)
    text = re.sub(r"[\s_]+", "-", text)
    text = re.sub(r"-{2,}", "-", text)
    return text.strip("-")


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
    items_to_save = items
    if mode == "categories":
        items_to_save = sorted(
            items,
            key=lambda it: str(it.get("name", "")).strip().lower()
        )

    if wrapper is None:
        payload = items_to_save
    else:
        if mode == "categories":
            wrapper["categories"] = items_to_save
        else:
            wrapper["pages"] = items_to_save
        payload = wrapper

    with open(path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=0)


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


def category_description_has_link(description: str) -> bool:
    description = str(description or "").lower()
    return "<a href=" in description


def count_categories_with_links(items) -> int:
    count = 0
    for it in items:
        if category_description_has_link(it.get("description", "")):
            count += 1
    return count


def normalize_text_for_duplicate_check(value: str) -> str:
    return " ".join(str(value or "").strip().lower().split())


def find_duplicate_field_indexes(items, field_name: str) -> set[int]:
    groups = {}
    for idx, it in enumerate(items):
        value = normalize_text_for_duplicate_check(it.get(field_name, ""))
        if not value:
            continue
        groups.setdefault(value, []).append(idx)

    out = set()
    for indexes in groups.values():
        if len(indexes) > 1:
            out.update(indexes)
    return out


def find_duplicate_title_indexes(items) -> set[int]:
    return find_duplicate_field_indexes(items, "title")


def find_duplicate_iframe_indexes(items) -> set[int]:
    return find_duplicate_field_indexes(items, "iframe")


def count_duplicate_titles(items) -> int:
    return len(find_duplicate_title_indexes(items))


def count_duplicate_iframes(items) -> int:
    return len(find_duplicate_iframe_indexes(items))


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
        self.last_search_field = "title"

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
        self.title_entry.bind("<KeyRelease>", self.on_title_change)

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
        ttk.Button(btn_row, text="Generate Desc", command=self.generate_description_with_openai).pack(side="left", padx=6)
        ttk.Button(btn_row, text="Batch Generate Missing", command=self.batch_generate_missing_descriptions).pack(side="left", padx=6)

        search = ttk.LabelFrame(right, text="Find", padding=10)
        search.pack(fill="x", pady=(10, 0))

        ttk.Label(search, text="Query").grid(row=0, column=0, sticky="w")
        self.search_title_var = tk.StringVar()
        search_entry = ttk.Entry(search, textvariable=self.search_title_var, width=36)
        search_entry.grid(row=1, column=0, sticky="we", padx=(0, 6))
        search_entry.bind("<Return>", lambda e: self.search_current_field())

        self.search_field_var = tk.StringVar(value="title")
        self.search_field_combo = ttk.Combobox(
            search,
            textvariable=self.search_field_var,
            values=["title", "iframe"],
            state="readonly",
            width=10,
        )
        self.search_field_combo.grid(row=1, column=1, sticky="e", padx=(0, 6))
        self.search_field_combo.bind("<<ComboboxSelected>>", lambda e: self.reset_search_state())

        ttk.Button(search, text="Find", command=self.search_current_field).grid(row=1, column=2, sticky="e")

        ttk.Label(right, textvariable=self.status_var).pack(anchor="w", pady=(10, 0))

        form.columnconfigure(0, weight=1)
        search.columnconfigure(0, weight=1)

        self.refresh_category_list()
        self.set_status("Ready")
        self.new_template()
        self.update_mode_ui()

    def set_status(self, text: str):
        self.status_var.set(text)

    def reset_search_state(self):
        self.search_matches = []
        self.search_pos = -1
        self.last_search_query = ""
        self.last_search_field = self.search_field_var.get().strip().lower() or "title"

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
            self.search_field_combo["values"] = ["title"]
            self.search_field_var.set("title")
        else:
            self.name_title_label.config(text="Title")
            self.iframe_label.grid()
            self.iframe_entry.grid()
            self.open_iframe_btn.grid()
            self.desc_label.grid()
            self.desc_text.grid()
            self.search_field_combo["values"] = ["title", "iframe"]
            if self.search_field_var.get().strip().lower() not in {"title", "iframe"}:
                self.search_field_var.set("title")
        self.reset_search_state()

    def update_page_match_status(self, prefix: str = ""):
        total = len(self.items)

        if self.is_root_categories_mode():
            completed = count_categories_with_links(self.items)
            text = f"Descriptions: {completed}/{total}"
            self.set_status(f"{prefix}  {text}" if prefix else text)
            return

        completed = count_items_with_bullets(self.items)
        file_name = os.path.basename(self.current_file) if self.current_file else self.file_var.get()
        keyword = category_keyword_from_filename(file_name)
        matched = count_title_keyword_matches(self.items, keyword)
        duplicate_titles = count_duplicate_titles(self.items)
        duplicate_iframes = count_duplicate_iframes(self.items)

        text = f"Descriptions: {completed}/{total}   Titles: {matched}/{total}   Dup titles: {duplicate_titles}   Dup iframes: {duplicate_iframes}"
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
            self.reset_search_state()
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
            self.reset_search_state()
            self.file_var.set(os.path.basename(self.current_file))
            self.refresh_list()
            self.new_template()
            self.update_mode_ui()
            self.update_page_match_status("Loaded")
        except Exception as e:
            self.items = []
            self.wrapper = None
            self.mode = "pages"
            self.current_file = path
            self.selected_index = None
            self.reset_search_state()
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

                if not category_description_has_link(it.get("description", "")):
                    self.listbox.itemconfig(idx, bg="#e6e6e6", fg="#555555")
            return

        file_name = os.path.basename(self.current_file) if self.current_file else self.file_var.get()
        keyword = category_keyword_from_filename(file_name)
        duplicate_title_indexes = find_duplicate_title_indexes(self.items)
        duplicate_iframe_indexes = find_duplicate_iframe_indexes(self.items)
        duplicate_indexes = duplicate_title_indexes | duplicate_iframe_indexes

        for idx, it in enumerate(self.items):
            label = it.get("title") or it.get("id") or "(empty)"
            self.listbox.insert(tk.END, label)

            has_title_match = title_matches_keyword(it.get("title", ""), keyword)
            has_bullet = description_has_bullet(it.get("description", ""))
            is_duplicate = idx in duplicate_indexes

            if is_duplicate:
                self.listbox.itemconfig(idx, bg="#fff1b8", fg="#7a5200")
            elif not has_title_match:
                self.listbox.itemconfig(idx, bg="#ffe5e5", fg="#a00000")
            elif not has_bullet:
                self.listbox.itemconfig(idx, bg="#e6e6e6", fg="#555555")


    def on_title_change(self, event=None):
        if self.selected_index is not None:
            return

        current_id = self.id_var.get().strip()
        if current_id:
            return

        if self.is_root_categories_mode():
            value = self.title_var.get().strip()
        else:
            value = self.title_var.get().strip()

        if not value:
            return

        self.id_var.set(slugify(value))

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

    def get_search_value(self, item, field_name: str) -> str:
        field_name = (field_name or "title").strip().lower()
        if self.is_root_categories_mode():
            return str(item.get("name", "")).strip().lower()
        if field_name == "iframe":
            return str(item.get("iframe", "")).strip().lower()
        return str(item.get("title", "")).strip().lower()

    def search_current_field(self):
        query = self.search_title_var.get().strip().lower()
        if not query:
            messagebox.showwarning("Missing query", "Enter text to search.")
            return

        search_field = self.search_field_var.get().strip().lower() or "title"
        if self.is_root_categories_mode():
            search_field = "title"

        if query != self.last_search_query or search_field != self.last_search_field:
            self.search_matches = [
                idx for idx, it in enumerate(self.items)
                if query in self.get_search_value(it, search_field)
            ]
            self.search_pos = -1
            self.last_search_query = query
            self.last_search_field = search_field

        if not self.search_matches:
            messagebox.showinfo("Not found", f"No item found in {search_field} containing:\n{query}")
            self.set_status("No matches found")
            return

        self.search_pos = (self.search_pos + 1) % len(self.search_matches)
        target_idx = self.search_matches[self.search_pos]
        self.goto_index(target_idx)

        self.set_status(
            f"Found {len(self.search_matches)} match(es) in {search_field}   Showing {self.search_pos + 1}/{len(self.search_matches)}"
        )

    def build_game_description_rule(self, game_title: str, category: str) -> str:
        return f"""
MASTER INDIVIDUAL GAME DESCRIPTION GENERATOR RULE V2 (PRODUCTION)

Goal
Generate handcrafted editorial quality descriptions for individual browser games on G55.CO.
Descriptions must improve SEO uniqueness, gameplay clarity, and engagement.
Style must resemble high quality gaming portal editorial content.

INPUT VARIABLES
- Game title: {game_title}
- Primary category: {category}
- Core mechanic: unknown
- Theme or setting: unknown
- Player goal: unknown
- Audience: casual gamers, teens, kids

OUTPUT STRUCTURE (STRICT)

1 Intro paragraph
2 "Key Features" section
3 Bullet list

Optional
Short second sentence allowed inside the paragraph for progression or mode.

INTRO PARAGRAPH RULES

• Write exactly ONE paragraph
• Length: 40 to 70 words
• First sentence MUST start with gameplay action
• Clearly describe what the player does in THIS game
• Mention the core mechanic naturally
• Mention the theme or setting when relevant
• Include the main objective or challenge
• Avoid repeating the game title more than once
• Avoid marketing tone
• Avoid filler phrases
• Avoid formal language
• Do not use bold text
• Do not use dashes in prose
• Must sound like human gaming portal editor

FIRST SENTENCE GAMEPLAY ACTION RULE

Start with verbs or direct gameplay description.

GOOD
Drive powerful tanks across battlefield missions

BAD
War Tanks Simulation is an exciting game

GAME SPECIFICITY RULE

Paragraph must include at least ONE unique gameplay signal such as:

vehicle type
weapon system
movement style
level progression
enemy type
mechanic variation

Avoid generic genre description.

SOFT ADJECTIVE CONTROL RULE

Limit words such as:

fun
exciting
amazing
friendly

Use maximum 1 soft adjective.

KEY FEATURES SECTION RULES

Title must be exactly:

Key Features

• Include exactly 5 bullets
• Ideal bullet length 3 to 6 words
• Bullets must describe real gameplay elements
• Focus on mechanics, systems, progression, controls
• Avoid repeating the game title
• Avoid marketing language
• Avoid generic phrases
• Avoid full sentences

BULLET LINGUISTIC STRUCTURE

Prefer concise mechanic entities.

GOOD
Tank combat mission objectives
Realistic vehicle control physics

BAD
You will control tanks in missions

BULLET SEMANTIC DISTRIBUTION MODEL

Each game bullet set should cover:

1 Core mechanic
2 Player action or control
3 Challenge or progression
4 Optional mode or system
5 Optional theme signal

SEMANTIC ENRICHMENT RULE

Include at least one long tail gameplay entity when natural.

Examples
Arena survival scoring system
Upgrade based weapon progression
Physics driven vehicle handling

STYLE MODEL

Write like concise professional gaming portal editor.
Not blog style.
Not marketing copy.
Not repetitive template.

OUTPUT FORMAT EXAMPLE

{{PARAGRAPH}}

Key Features

• feature
• feature
• feature
• feature
• feature

QUALITY VALIDATION CHECK

Before output ensure:

• First sentence gameplay focused
• Paragraph describes THIS game uniquely
• Length within limits
• Bullets reflect real mechanics
• Tone natural and editorial
• No filler or repetition

END RULE
""".strip()

    def generate_description_text(self, client, game_title: str, category: str) -> str:
        rule = self.build_game_description_rule(game_title, category)
        response = client.responses.create(
            model="gpt-4.1-mini",
            input=rule,
        )
        return (response.output_text or "").strip()

    def batch_generate_missing_descriptions(self):
        if self.is_root_categories_mode():
            messagebox.showwarning("Wrong mode", "This function is only for individual game pages.")
            return

        api_key = os.environ.get("OPENAI_API_KEY", "").strip()
        if not api_key:
            messagebox.showerror("Missing API key", "Set OPENAI_API_KEY first.")
            return

        targets = [
            idx for idx, it in enumerate(self.items)
            if str(it.get("title", "")).strip() and not description_has_bullet(it.get("description", ""))
        ]

        if not targets:
            messagebox.showinfo("Nothing to generate", "No pages found where description does not contain •")
            return

        if not messagebox.askyesno(
            "Batch generate",
            f"Generate descriptions for {len(targets)} page(s) where description does not contain •?"
        ):
            return

        category = category_keyword_from_filename(os.path.basename(self.current_file or ""))
        client = OpenAI(api_key=api_key)
        generated = 0

        for pos, idx in enumerate(targets, start=1):
            game_title = str(self.items[idx].get("title", "")).strip()
            if not game_title:
                continue

            self.set_status(f"Generating {pos}/{len(targets)}: {game_title}")
            self.update_idletasks()

            try:
                text = self.generate_description_text(client, game_title, category)
            except Exception as e:
                messagebox.showerror(
                    "Batch generation stopped",
                    f"Failed on:\n{game_title}\n\n{e}"
                )
                self.refresh_list()
                self.update_page_match_status(f"Generated {generated}/{len(targets)} before stop")
                return

            if not text:
                messagebox.showerror("Batch generation stopped", f"No text returned for:\n{game_title}")
                self.refresh_list()
                self.update_page_match_status(f"Generated {generated}/{len(targets)} before stop")
                return

            self.items[idx]["description"] = text
            generated += 1

            if not self.autosave():
                messagebox.showerror("Auto save failed", f"Generated but could not auto save after:\n{game_title}")
                self.refresh_list()
                self.update_page_match_status(f"Generated {generated}/{len(targets)} before stop")
                return

        self.refresh_list()
        if self.selected_index is not None and 0 <= self.selected_index < len(self.items):
            self.goto_index(self.selected_index)
        self.update_page_match_status(f"Batch generated {generated} page(s) and auto saved")
        messagebox.showinfo("Done", f"Generated and auto saved {generated} page(s).")

    def generate_description_with_openai(self):
        if self.is_root_categories_mode():
            messagebox.showwarning("Wrong mode", "This function is only for individual game pages.")
            return

        item = self.read_form()
        game_title = item.get("title", "").strip()
        category = category_keyword_from_filename(os.path.basename(self.current_file or ""))

        if not game_title:
            messagebox.showwarning("Missing title", "Enter game title first.")
            return

        api_key = os.environ.get("OPENAI_API_KEY", "").strip()
        if not api_key:
            messagebox.showerror("Missing API key", "Set OPENAI_API_KEY first.")
            return

        try:
            client = OpenAI(api_key=api_key)
            text = self.generate_description_text(client, game_title, category)
        except Exception as e:
            messagebox.showerror("OpenAI error", str(e))
            return

        if not text:
            messagebox.showerror("Generation failed", "No text returned from OpenAI.")
            return

        self.desc_text.delete("1.0", "end")
        self.desc_text.insert("1.0", text)

        if self.selected_index is not None:
            self.items[self.selected_index]["description"] = text
            if not self.autosave():
                messagebox.showerror("Auto save failed", "Description was generated but could not be auto saved.")
                return
            self.refresh_list()
            self.goto_index(self.selected_index)
            self.update_page_match_status("Generated description and auto saved")
        else:
            self.update_page_match_status("Generated description")

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
