import json
import os
import tkinter as tk
from tkinter import ttk, messagebox

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")


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
        json.dump(payload, f, ensure_ascii=False, indent=4)


def clean_page(it: dict) -> dict:
    return {
        "id": str(it.get("id", "")).strip(),
        "title": str(it.get("title", "")).strip(),
        "iframe": str(it.get("iframe", "")).strip(),
        "description": str(it.get("description", "")).strip(),
    }


def slug_from_filename(fn: str) -> str:
    base = fn[:-5] if fn.lower().endswith(".json") else fn
    return base.strip().lower()


def tokenize_slug(s: str) -> list[str]:
    s = (s or "").strip().lower()
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


def maybe_singularize_tokens(tokens: list[str], enabled: bool) -> list[str]:
    if not enabled:
        return tokens
    return [singularize_token(x) for x in tokens]


def find_all_subseq_positions(tokens: list[str], key_tokens: list[str]) -> list[int]:
    if not tokens or not key_tokens or len(key_tokens) > len(tokens):
        return []
    hits = []
    k = len(key_tokens)
    for i in range(0, len(tokens) - k + 1):
        if tokens[i : i + k] == key_tokens:
            hits.append(i)
    return hits


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
            self.src_var.set(self.files[0])
            self.load_source()
        else:
            messagebox.showwarning("No JSON files", f"No .json files found in:\n{CATEGORIES_DIR}")

    def build_ui(self):
        top = ttk.Frame(self, padding=10)
        top.pack(fill="x")

        ttk.Label(top, text="Current category").pack(side="left")
        self.src_var = tk.StringVar(value=self.files[0] if self.files else "")
        self.src_combo = ttk.Combobox(top, textvariable=self.src_var, values=self.files, state="readonly", width=45)
        self.src_combo.pack(side="left", padx=8)
        self.src_combo.bind("<<ComboboxSelected>>", lambda e: self.load_source())

        self.only_unique_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Only unique best match", variable=self.only_unique_var).pack(side="left", padx=10)

        self.plurals_var = tk.BooleanVar(value=False)
        ttk.Checkbutton(top, text="Plural keywords", variable=self.plurals_var).pack(side="left", padx=10)

        self.min_tokens_var = tk.IntVar(value=1)
        ttk.Label(top, text="Min keyword tokens").pack(side="left", padx=(12, 4))
        ttk.Spinbox(top, from_=1, to=5, width=3, textvariable=self.min_tokens_var).pack(side="left")

        ttk.Button(top, text="Scan", command=self.scan).pack(side="left", padx=10)
        ttk.Button(top, text="Move selected", command=self.move_selected).pack(side="left", padx=6)
        ttk.Button(top, text="Move all", command=self.move_all).pack(side="left", padx=6)

        self.status_var = tk.StringVar(value="")
        ttk.Label(top, textvariable=self.status_var).pack(side="right")

        mid = ttk.Frame(self, padding=10)
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

        bottom = ttk.Frame(self, padding=10)
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

    def load_source(self):
        fn = self.src_var.get().strip()
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
            self.set_status("Load failed")

    def build_keyword_map(self, plural_enabled: bool):
        keywords = []
        for fn in self.files:
            if fn == self.current_file:
                continue
            slug = slug_from_filename(fn)
            if not slug:
                continue
            tokens = tokenize_slug(slug)
            tokens = maybe_singularize_tokens(tokens, plural_enabled)
            keywords.append({"file": fn, "slug": slug, "tokens": tokens})

        keywords.sort(key=lambda k: (len(k["tokens"]), len(k["slug"])), reverse=True)
        return keywords

    def scan(self):
        if not self.current_file:
            messagebox.showwarning("No source", "Select a source category first.")
            return

        min_tokens = int(self.min_tokens_var.get() or 1)
        only_unique = bool(self.only_unique_var.get())
        plural_enabled = bool(self.plurals_var.get())

        keywords = self.build_keyword_map(plural_enabled)
        if not keywords:
            messagebox.showinfo("No keywords", "No other categories found to use as keywords.")
            return

        current_slug = slug_from_filename(self.current_file)
        current_tokens = tokenize_slug(current_slug)
        current_tokens = maybe_singularize_tokens(current_tokens, plural_enabled)

        self.clear_results()

        candidates = 0
        skipped_self = 0

        for page in self.current_pages:
            gid = page.get("id", "")
            if not gid:
                continue

            id_tokens = tokenize_slug(gid)
            id_tokens = maybe_singularize_tokens(id_tokens, plural_enabled)

            if current_tokens and find_all_subseq_positions(id_tokens, current_tokens):
                skipped_self += 1
                continue

            best = None
            best_score = -1
            ties = 0

            for kw in keywords:
                kt = kw["tokens"]
                if len(kt) < min_tokens:
                    continue

                hits = find_all_subseq_positions(id_tokens, kt)
                if not hits:
                    continue

                score = (len(kt) * 100) + (len(hits) * 10)

                if score > best_score:
                    best_score = score
                    best = kw
                    ties = 0
                elif score == best_score:
                    ties += 1

            if best is None:
                continue
            if only_unique and ties > 0:
                continue

            self.tree.insert(
                "",
                "end",
                values=(gid, page.get("title", ""), best["slug"], best["file"], str(best_score)),
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

    def move_selected(self):
        sel = self.tree.selection()
        if not sel:
            messagebox.showwarning("No selection", "Select one or more candidates to move.")
            return

        moved = 0
        skipped = 0
        errors = 0

        for iid in list(sel):
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

        self.set_status(f"Move selected done. moved={moved} skipped={skipped} errors={errors}")

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