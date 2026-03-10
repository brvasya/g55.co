import os
import re
import json
import tkinter as tk
from tkinter import ttk, messagebox


SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")

STOPWORDS = {
    "a", "an", "and", "are", "as", "at", "be", "best", "by", "for", "free",
    "from", "fun", "game", "games", "html5", "in", "into", "is", "it", "its",
    "new", "of", "on", "online", "or", "play", "the", "to", "with", "your",
    "my", "you", "super", "ultimate", "crazy", "cool", "classic", "pro",
    "master", "hero", "world", "adventure"
}

MIN_WORD_LEN = 3


def normalize_text(text: str) -> str:
    text = (text or "").strip().lower()
    text = text.replace("-", " ")
    text = re.sub(r"[^a-z0-9\s]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def category_keyword_from_filename(filename: str) -> str:
    name = os.path.splitext(os.path.basename(filename))[0].strip().lower()
    return name.replace("-", " ")


def load_json_pages(path: str):
    with open(path, "r", encoding="utf-8") as f:
        data = json.load(f)

    if isinstance(data, list):
        pages = data
    elif isinstance(data, dict) and isinstance(data.get("pages"), list):
        pages = data["pages"]
    else:
        return []

    clean_pages = []
    for item in pages:
        if isinstance(item, dict):
            clean_pages.append({
                "id": str(item.get("id", "")).strip(),
                "title": str(item.get("title", "")).strip(),
            })
    return clean_pages


def list_json_files(folder: str):
    if not os.path.isdir(folder):
        return []
    out = []
    for name in os.listdir(folder):
        path = os.path.join(folder, name)
        if os.path.isfile(path) and name.lower().endswith(".json"):
            out.append(path)
    out.sort(key=lambda p: os.path.basename(p).lower())
    return out


def should_skip_single(word: str) -> bool:
    if len(word) < MIN_WORD_LEN:
        return True
    if word in STOPWORDS:
        return True
    if word.isdigit():
        return True
    return False


def should_skip_phrase(words) -> bool:
    for w in words:
        if should_skip_single(w):
            return True
    return False


class KeywordMinerApp(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title("G55 Category Keyword Miner")
        self.state("zoomed")

        self.mode_var = tk.StringVar(value="all")
        self.min_count_var = tk.StringVar(value="5")
        self.limit_var = tk.StringVar(value="20")
        self.scan_words_var = tk.BooleanVar(value=True)
        self.scan_phrases_var = tk.BooleanVar(value=True)
        self.only_new_var = tk.BooleanVar(value=True)

        self.results = []
        self.build_ui()

        if not os.path.isdir(DEFAULT_CATEGORIES_DIR):
            self.stats_var.set(f"categories folder not found: {DEFAULT_CATEGORIES_DIR}")

    def build_ui(self):
        opts = ttk.LabelFrame(self, text="Scan options", padding=10)
        opts.pack(fill="x", padx=10, pady=10)

        ttk.Radiobutton(
            opts,
            text="Scan all categories",
            variable=self.mode_var,
            value="all"
        ).grid(row=0, column=0, sticky="w")

        ttk.Radiobutton(
            opts,
            text="Scan casual.json only",
            variable=self.mode_var,
            value="casual"
        ).grid(row=1, column=0, sticky="w")

        ttk.Checkbutton(
            opts,
            text="Single words",
            variable=self.scan_words_var
        ).grid(row=0, column=1, sticky="w", padx=(20, 0))

        ttk.Checkbutton(
            opts,
            text="Two word phrases",
            variable=self.scan_phrases_var
        ).grid(row=1, column=1, sticky="w", padx=(20, 0))

        ttk.Checkbutton(
            opts,
            text="Only new category ideas",
            variable=self.only_new_var
        ).grid(row=0, column=2, sticky="w", padx=(20, 0))

        ttk.Label(opts, text="Min count").grid(row=1, column=2, sticky="e", padx=(20, 6))
        ttk.Entry(opts, textvariable=self.min_count_var, width=6).grid(row=1, column=3, sticky="w")

        ttk.Label(opts, text="Top limit").grid(row=0, column=3, sticky="e", padx=(20, 6))
        ttk.Entry(opts, textvariable=self.limit_var, width=6).grid(row=0, column=4, sticky="w")

        ttk.Button(opts, text="Scan", command=self.run_scan).grid(row=0, column=5, rowspan=2, padx=(20, 0))

        self.stats_var = tk.StringVar(value="Ready")
        ttk.Label(self, textvariable=self.stats_var).pack(anchor="w", padx=10)

        wrap = ttk.Frame(self)
        wrap.pack(fill="both", expand=True, padx=10, pady=10)

        columns = ("keyword", "type", "count", "exists", "category_file", "samples")
        self.tree = ttk.Treeview(wrap, columns=columns, show="headings")
        self.tree.pack(side="left", fill="both", expand=True)

        yscroll = ttk.Scrollbar(wrap, orient="vertical", command=self.tree.yview)
        yscroll.pack(side="right", fill="y")
        self.tree.configure(yscrollcommand=yscroll.set)

        for c in columns:
            self.tree.heading(c, text=c)

        self.tree.column("keyword", width=220, anchor="w")
        self.tree.column("type", width=90, anchor="center")
        self.tree.column("count", width=80, anchor="center")
        self.tree.column("exists", width=120, anchor="center")
        self.tree.column("category_file", width=180, anchor="w")
        self.tree.column("samples", width=900, anchor="w")

        bottom = ttk.Frame(self)
        bottom.pack(fill="x", padx=10, pady=10)

        ttk.Button(bottom, text="Copy keyword", command=self.copy_keyword).pack(side="left")
        ttk.Button(bottom, text="Clear", command=self.clear_results).pack(side="left", padx=10)

    def clear_results(self):
        self.results = []
        for item in self.tree.get_children():
            self.tree.delete(item)
        self.stats_var.set("Cleared")

    def get_selected_files(self, folder):
        files = list_json_files(folder)
        if self.mode_var.get() == "casual":
            files = [f for f in files if os.path.basename(f).lower() == "casual.json"]
        return files

    def run_scan(self):
        folder = DEFAULT_CATEGORIES_DIR

        if not os.path.isdir(folder):
            messagebox.showwarning(
                "Missing categories folder",
                f"Expected folder not found:\n{folder}\n\nPlace a 'categories' folder next to this script."
            )
            return

        try:
            min_count = int(self.min_count_var.get().strip())
            limit = int(self.limit_var.get().strip())
        except ValueError:
            messagebox.showwarning("Invalid number", "Min count and Top limit must be numbers.")
            return

        if limit <= 0:
            messagebox.showwarning("Invalid limit", "Top limit must be greater than 0.")
            return

        if not self.scan_words_var.get() and not self.scan_phrases_var.get():
            messagebox.showwarning("Nothing selected", "Enable at least one scan type.")
            return

        files = self.get_selected_files(folder)
        if not files:
            messagebox.showwarning("No JSON files", "No matching JSON files found.")
            return

        existing_categories = {
            category_keyword_from_filename(p): os.path.basename(p)
            for p in list_json_files(folder)
        }

        counts = {}
        samples = {}
        total_titles = 0

        for path in files:
            try:
                pages = load_json_pages(path)
            except Exception:
                continue

            for page in pages:
                title = page["title"].strip()
                if not title:
                    continue

                total_titles += 1
                tokens = normalize_text(title).split()
                if not tokens:
                    continue

                if self.scan_words_var.get():
                    seen_words = set()
                    for w in tokens:
                        if should_skip_single(w):
                            continue
                        if w in seen_words:
                            continue
                        seen_words.add(w)
                        counts[w] = counts.get(w, 0) + 1
                        samples.setdefault(w, [])
                        if title not in samples[w] and len(samples[w]) < 4:
                            samples[w].append(title)

                if self.scan_phrases_var.get():
                    seen_phrases = set()
                    for i in range(len(tokens) - 1):
                        pair = (tokens[i], tokens[i + 1])
                        if should_skip_phrase(pair):
                            continue
                        phrase = " ".join(pair)
                        if phrase in seen_phrases:
                            continue
                        seen_phrases.add(phrase)
                        counts[phrase] = counts.get(phrase, 0) + 1
                        samples.setdefault(phrase, [])
                        if title not in samples[phrase] and len(samples[phrase]) < 4:
                            samples[phrase].append(title)

        results = []
        for keyword, count in counts.items():
            if count < min_count:
                continue

            exists = "yes" if keyword in existing_categories else "no"
            if self.only_new_var.get() and exists == "yes":
                continue

            cat_file = existing_categories.get(keyword, keyword.replace(" ", "-") + ".json")
            row = (
                keyword,
                "phrase" if " " in keyword else "word",
                count,
                exists,
                cat_file,
                " | ".join(samples.get(keyword, []))
            )
            results.append(row)

        results.sort(key=lambda x: (-x[2], x[0]))
        results = results[:limit]

        self.results = results
        self.clear_results()

        for r in results:
            tags = ()
            if r[3] == "no":
                tags = ("newcat",)
            self.tree.insert("", "end", values=r, tags=tags)

        self.tree.tag_configure("newcat", background="#e8ffe8")

        mode_label = "casual.json only" if self.mode_var.get() == "casual" else "all categories"
        scope_label = "new category ideas only" if self.only_new_var.get() else "all keywords"
        self.stats_var.set(
            f"Scanned {total_titles} titles from {mode_label}, showing top {len(results)} results, {scope_label}"
        )

    def copy_keyword(self):
        sel = self.tree.selection()
        if not sel:
            return
        keyword = self.tree.item(sel[0])["values"][0]
        self.clipboard_clear()
        self.clipboard_append(keyword)
        self.update()
        self.stats_var.set(f"Copied keyword: {keyword}")


if __name__ == "__main__":
    app = KeywordMinerApp()
    app.mainloop()