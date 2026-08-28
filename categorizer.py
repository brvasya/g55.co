import json
import os
import tkinter as tk
from tkinter import ttk, messagebox

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")

AGENT_EXCEPTIONS_FILE = os.path.join(SCRIPT_DIR, "categorizer.txt")

def load_agent_exceptions(path: str) -> dict[str, str]:
    exceptions = {}

    with open(path, "r", encoding="utf-8-sig") as f:
        for line_number, raw_line in enumerate(f, 1):
            line = raw_line.strip()

            if not line or line.startswith("#"):
                continue

            if "=" not in line:
                raise ValueError(
                    f"{os.path.basename(path)}:{line_number}: expected token=category"
                )

            token, category = (part.strip().lower() for part in line.split("=", 1))

            if not token or not category:
                raise ValueError(
                    f"{os.path.basename(path)}:{line_number}: empty token or category"
                )

            if token in exceptions:
                raise ValueError(
                    f"{os.path.basename(path)}:{line_number}: duplicate token '{token}'"
                )

            exceptions[token] = category

    return exceptions

AGENT_EXCEPTIONS = load_agent_exceptions(AGENT_EXCEPTIONS_FILE)

def list_json_files(folder: str) -> list[str]:
    out = []
    try:
        for name in os.listdir(folder):
            if name.lower().endswith(".json") and os.path.isfile(os.path.join(folder, name)):
                out.append(name)
    except Exception:
        pass
    out.sort(key=lambda s: s.lower())
    return out

def load_json_any(path: str):
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)

def normalize_loaded_json(loaded):
    if isinstance(loaded, dict) and "pages" in loaded and isinstance(loaded["pages"], list):
        return loaded["pages"], loaded
    if isinstance(loaded, list):
        return loaded, None
    raise ValueError('Unsupported JSON format. Expected {"pages": [...]} or a list.')

def save_json_any(path: str, pages: list[dict], wrapper):
    if wrapper is None:
        payload = pages
    else:
        wrapper["pages"] = pages
        payload = wrapper
    with open(path, "w", encoding="utf-8") as f:
        json.dump(payload, f, ensure_ascii=False, indent=0)

def clean_page(it: dict) -> dict:
    page = {
        "id": str(it.get("id", "")).strip(),
        "title": str(it.get("title", "")).strip(),
        "iframe": str(it.get("iframe", "")).strip(),
    }
    if "creator" in it:
        page["creator"] = str(it.get("creator", "")).strip()
    page["description"] = str(it.get("description", "")).strip()
    return page

def raw_slug_from_filename(fn: str) -> str:
    base = fn[:-5] if fn.lower().endswith(".json") else fn
    return base.strip()

def split_camel_case_boundaries(s: str) -> str:
    s = s or ""
    out = []

    for i, ch in enumerate(s):
        if i > 0 and ch.isupper():
            prev = s[i - 1]
            next_ch = s[i + 1] if i + 1 < len(s) else ""
            starts_word = prev.islower() or prev.isdigit()
            ends_acronym = prev.isupper() and bool(next_ch) and next_ch.islower()
            if starts_word or ends_acronym:
                out.append(" ")
        out.append(ch)

    return "".join(out)

def tokenize_slug(s: str) -> list[str]:
    s = split_camel_case_boundaries((s or "").strip())
    s = s.lower().replace("-", " ")
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

def apply_agent_exceptions(tokens: list[str]) -> list[str]:
    out = []
    for token in tokens:
        token = (token or "").strip().lower()
        if not token:
            continue

        mapped = AGENT_EXCEPTIONS.get(token)
        if mapped is None:
            out.append(token)
            continue

        mapped_tokens = tokenize_slug(mapped)
        if mapped_tokens:
            out.extend(mapped_tokens)

    return out

def find_all_subseq_positions(tokens: list[str], key_tokens: list[str]) -> list[int]:
    if not tokens or not key_tokens:
        return []

    hits = set()
    k = len(key_tokens)
    if k <= len(tokens):
        for i in range(0, len(tokens) - k + 1):
            if tokens[i:i + k] == key_tokens:
                hits.add(i)

    compact_key = "".join(key_tokens)
    for i in range(len(tokens)):
        compact_title = ""
        for j in range(i, len(tokens)):
            compact_title += tokens[j]
            if compact_title == compact_key:
                hits.add(i)
                break
            if len(compact_title) >= len(compact_key):
                break
            if not compact_key.startswith(compact_title):
                break

    return sorted(hits)

def build_match_priority(hits: list[int], key_tokens: list[str], slug: str) -> tuple[int, int, int, int]:
    first_hit = min(hits)
    return (
        len(key_tokens),
        -first_hit,
        len(hits),
        len(slug),
    )

def build_display_score(priority: tuple[int, int, int, int]) -> int:
    token_count, negative_first_hit, hit_count, slug_len = priority
    first_hit = -negative_first_hit
    return (token_count * 1000) + (hit_count * 50) - first_hit + slug_len

class CategorizerApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("Category Candidate Finder")
        self.state("zoomed")

        self.files = list_json_files(CATEGORIES_DIR)

        self.current_file = None
        self.current_pages = []
        self.current_wrapper = None

        self.target_cache = {}
        self.build_ui()

        if self.files:
            self.select_category_by_index(0)
        else:
            messagebox.showwarning("No JSON files", f"No .json files found in:\n{CATEGORIES_DIR}")

    def build_ui(self):
        outer = ttk.Frame(self, padding=10)
        outer.pack(fill="both", expand=True)

        paned = ttk.Panedwindow(outer, orient="horizontal")
        paned.pack(fill="both", expand=True)

        left = ttk.Frame(paned, padding=(0, 0, 10, 0))
        paned.add(left, weight=0)

        ttk.Label(left, text="Categories").pack(anchor="w")

        list_frame = ttk.Frame(left)
        list_frame.pack(fill="both", expand=True, pady=(6, 0))

        self.cat_listbox = tk.Listbox(list_frame, activestyle="none", exportselection=False)
        self.cat_listbox.pack(side="left", fill="both", expand=True)

        lscroll = ttk.Scrollbar(list_frame, orient="vertical", command=self.cat_listbox.yview)
        lscroll.pack(side="right", fill="y")
        self.cat_listbox.configure(yscrollcommand=lscroll.set)

        for fn in self.files:
            self.cat_listbox.insert("end", fn)

        self.cat_listbox.bind("<<ListboxSelect>>", self.on_category_select)
        self.cat_listbox.bind("<Return>", self.on_category_select)
        self.cat_listbox.bind("<Double-Button-1>", self.on_category_select)

        right = ttk.Frame(paned)
        paned.add(right, weight=1)

        top = ttk.Frame(right, padding=(0, 0, 0, 10))
        top.pack(fill="x")

        self.current_label_var = tk.StringVar(value="Current category: ")
        ttk.Label(top, textvariable=self.current_label_var).pack(side="left")

        self.agent_exceptions_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Agent Exceptions", variable=self.agent_exceptions_var).pack(side="left", padx=10)

        ttk.Button(top, text="Scan", command=self.scan).pack(side="left", padx=10)
        ttk.Button(top, text="Move all", command=self.move_all).pack(side="left", padx=6)

        self.status_var = tk.StringVar(value="")
        ttk.Label(top, textvariable=self.status_var).pack(side="right")

        mid = ttk.Frame(right)
        mid.pack(fill="both", expand=True)

        cols = ("id", "title", "match_keyword", "suggested_file", "score")
        self.tree = ttk.Treeview(mid, columns=cols, show="headings", height=25)
        self.tree.heading("id", text="id")
        self.tree.heading("title", text="title")
        self.tree.heading("match_keyword", text="matched keyword")
        self.tree.heading("suggested_file", text="suggested category file")
        self.tree.heading("score", text="score")

        self.tree.column("id", width=260, anchor="w")
        self.tree.column("title", width=320, anchor="w")
        self.tree.column("match_keyword", width=200, anchor="w")
        self.tree.column("suggested_file", width=220, anchor="w")
        self.tree.column("score", width=80, anchor="center")

        self.tree.pack(side="left", fill="both", expand=True)

        yscroll = ttk.Scrollbar(mid, orient="vertical", command=self.tree.yview)
        yscroll.pack(side="right", fill="y")
        self.tree.configure(yscrollcommand=yscroll.set)

        bottom = ttk.Frame(right, padding=(0, 10, 0, 0))
        bottom.pack(fill="x")

        self.preview_var = tk.StringVar(value="Click a row to preview the suggested category")
        ttk.Label(bottom, textvariable=self.preview_var).pack(anchor="w")

        self.tree.bind("<<TreeviewSelect>>", self.on_select)

        self.set_status("Ready")

    def set_status(self, text: str):
        self.status_var.set(text)

    def clear_results(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

    def get_selected_category_filename(self) -> str:
        sel = self.cat_listbox.curselection()
        if not sel:
            return ""
        idx = int(sel[0])
        if idx < 0 or idx >= len(self.files):
            return ""
        return self.files[idx]

    def select_category_by_index(self, idx: int):
        if not self.files:
            return
        idx = max(0, min(idx, len(self.files) - 1))
        self.cat_listbox.selection_clear(0, "end")
        self.cat_listbox.selection_set(idx)
        self.cat_listbox.activate(idx)
        self.cat_listbox.see(idx)
        self.load_source(self.files[idx])

    def on_category_select(self, event=None):
        fn = self.get_selected_category_filename()
        if not fn:
            return
        if fn == self.current_file:
            return
        self.load_source(fn)

    def load_source(self, fn: str):
        fn = (fn or "").strip()
        if not fn:
            return
        path = os.path.join(CATEGORIES_DIR, fn)
        try:
            loaded = load_json_any(path)
            pages, wrapper = normalize_loaded_json(loaded)
            cleaned = [clean_page(p) for p in pages if isinstance(p, dict)]

            self.current_file = fn
            self.current_pages = cleaned
            self.current_wrapper = wrapper
            self.target_cache = {}

            self.current_label_var.set(f"Current category: {fn}")
            self.preview_var.set("Click a row to preview the suggested category")

            self.scan()

        except Exception as e:
            messagebox.showerror("Load failed", f"Could not load source JSON:\n{e}")
            self.current_file = None
            self.current_pages = []
            self.current_wrapper = None
            self.target_cache = {}
            self.clear_results()
            self.preview_var.set("")
            self.current_label_var.set("Current category: ")
            self.set_status("Load failed")

    def build_keyword_map(self):
        keywords = []
        for fn in self.files:
            if fn == self.current_file:
                continue

            raw_slug = raw_slug_from_filename(fn)
            slug = raw_slug.lower()
            if not slug:
                continue

            tokens = tokenize_slug(raw_slug)
            if not tokens:
                continue

            keywords.append({"file": fn, "slug": slug, "tokens": tokens})

        return keywords

    def find_best_keyword_match(
        self,
        title_tokens: list[str],
        keywords,
    ):
        best = None
        best_priority = None
        best_score = -1
        for kw in keywords:
            kt = kw["tokens"]
            hits = find_all_subseq_positions(title_tokens, kt)
            if not hits:
                continue

            priority = build_match_priority(hits, kt, kw["slug"])
            score = build_display_score(priority)

            if best_priority is None or priority > best_priority:
                best_priority = priority
                best_score = score
                best = kw

        return best, best_priority, best_score

    def scan(self):
        if not self.current_file:
            messagebox.showwarning("No source", "Select a source category first.")
            return

        agent_exceptions_enabled = bool(self.agent_exceptions_var.get())

        keywords = self.build_keyword_map()
        if not keywords:
            messagebox.showinfo("No keywords", "No other categories found to use as keywords.")
            return

        current_raw_slug = raw_slug_from_filename(self.current_file)
        current_slug = current_raw_slug.lower()
        current_tokens = tokenize_slug(current_raw_slug)

        self.clear_results()

        candidates = 0
        skipped_self = 0

        for page in self.current_pages:
            gid = page.get("id", "")
            title = page.get("title", "")
            if not title:
                continue

            direct_title_tokens = tokenize_slug(title)

            best, best_priority, best_score = self.find_best_keyword_match(
                direct_title_tokens,
                keywords,
            )

            current_priority = None
            if current_tokens:
                current_hits = find_all_subseq_positions(direct_title_tokens, current_tokens)
                if current_hits:
                    current_priority = build_match_priority(
                        current_hits, current_tokens, current_slug
                    )

            if best is None:
                if current_priority is not None:
                    skipped_self += 1
                    continue

                if agent_exceptions_enabled:
                    mapped_title_tokens = apply_agent_exceptions(direct_title_tokens)

                    best, best_priority, best_score = self.find_best_keyword_match(
                        mapped_title_tokens,
                        keywords,
                    )

                    current_priority = None
                    if current_tokens:
                        current_hits = find_all_subseq_positions(
                            mapped_title_tokens, current_tokens
                        )
                        if current_hits:
                            current_priority = build_match_priority(
                                current_hits, current_tokens, current_slug
                            )

            if best is None:
                if current_priority is not None:
                    skipped_self += 1
                    continue

                if self.current_file.lower() != "casual.json":
                    casual_fn = next(
                        (fn for fn in self.files if fn.lower() == "casual.json"),
                        "",
                    )
                    if casual_fn:
                        self.tree.insert(
                            "",
                            "end",
                            values=(gid, title, "orphan", casual_fn, "0"),
                        )
                        candidates += 1
                continue

            if current_priority is not None and current_priority >= best_priority:
                skipped_self += 1
                continue

            self.tree.insert(
                "",
                "end",
                values=(gid, title, best["slug"], best["file"], str(best_score)),
            )
            candidates += 1

        self.set_status(f"Scan done. candidates={candidates} skipped_current={skipped_self}")

    def on_select(self, event=None):
        sel = self.tree.selection()
        if not sel:
            return
        vals = self.tree.item(sel[0], "values")
        if not vals:
            return
        gid = vals[0]
        target = vals[3]
        self.preview_var.set(f"Selected id: {gid}  suggested: {target}")

    def load_target(self, fn: str):
        if fn in self.target_cache:
            return self.target_cache[fn]
        path = os.path.join(CATEGORIES_DIR, fn)
        loaded = load_json_any(path)
        pages, wrapper = normalize_loaded_json(loaded)
        cleaned = [clean_page(p) for p in pages if isinstance(p, dict)]
        self.target_cache[fn] = (cleaned, wrapper)
        return cleaned, wrapper

    def page_exists_by_id(self, pages: list[dict], gid: str) -> bool:
        gid = (gid or "").strip()
        if not gid:
            return False
        for p in pages:
            if (p.get("id", "") or "").strip() == gid:
                return True
        return False

    def remove_from_current_by_id(self, gid: str):
        for i, p in enumerate(self.current_pages):
            if (p.get("id", "") or "").strip() == gid:
                return self.current_pages.pop(i)
        return None

    def move_one(self, gid: str, target_fn: str):
        gid = (gid or "").strip()
        if not gid:
            return False, "empty id"
        if not target_fn or target_fn == self.current_file:
            return False, "invalid target"

        try:
            target_pages, target_wrapper = self.load_target(target_fn)
        except Exception as e:
            return False, f"target load failed: {e}"

        if self.page_exists_by_id(target_pages, gid):
            return False, "duplicate id in target"

        page = self.remove_from_current_by_id(gid)
        if page is None:
            return False, "id not found in current"

        target_pages.insert(0, page)

        try:
            target_path = os.path.join(CATEGORIES_DIR, target_fn)
            save_json_any(target_path, target_pages, target_wrapper)

            current_path = os.path.join(CATEGORIES_DIR, self.current_file)
            save_json_any(current_path, self.current_pages, self.current_wrapper)

            self.target_cache[target_fn] = (target_pages, target_wrapper)
            return True, "moved"
        except Exception as e:
            return False, f"save failed: {e}"

    def move_all(self):
        items = self.tree.get_children()
        if not items:
            messagebox.showinfo("Nothing to move", "No candidates in the list.")
            return

        if not messagebox.askyesno("Move all", "Move all candidates to their suggested categories?"):
            return

        moved = 0
        skipped = 0
        errors = 0

        for iid in list(items):
            vals = self.tree.item(iid, "values")
            if not vals:
                continue
            gid = vals[0]
            target_fn = vals[3]

            ok, reason = self.move_one(gid, target_fn)
            if ok:
                moved += 1
                self.tree.delete(iid)
            else:
                if reason in ("duplicate id in target", "id not found in current", "invalid target", "empty id"):
                    skipped += 1
                else:
                    errors += 1

        self.set_status(f"Move all done. moved={moved} skipped={skipped} errors={errors}")

if __name__ == "__main__":
    app = CategorizerApp()
    app.mainloop()
