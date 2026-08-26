import json
import os
import tkinter as tk
from tkinter import ttk, messagebox

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CATEGORIES_DIR = os.path.join(SCRIPT_DIR, "categories")


AGENT_EXCEPTIONS = {
    #archery
    "archer": "archery",
    "archero": "archery",
    "archerry": "archery",
    "bow": "archery",
    "bowman": "archery",

    #baby
    "babysitter": "baby",
    "toddler": "baby",

    #ball
    "arkanoid": "ball",
    "ballz": "ball",
    "breakout": "ball",
    "pinball": "ball",
    "pong": "ball",

    #basketball
    "dunk": "basketball",

    #battle
    "battler": "battle",

    #bike
    "bicycle": "bike",
    "biker": "bike",
    "bmx": "bike",
    "cycling": "bike",
    "moto": "bike",
    "motocross": "bike",
    "motorbike": "bike",
    "motorcycle": "bike",

    #block
    "blockz": "block",
    "tetris": "block",
    "unblock": "block",

    #brain
    "logic": "brain",
    "logical": "brain",
    "mind": "brain",
    "riddle": "brain",
    "smart": "brain",

    #cat
    "kitten": "cat",
    "kitty": "cat",

    #christmas
    "christman": "christmas",
    "santa": "christmas",
    "santas": "christmas",
    "xmas": "christmas",

    #coloring
    "colouring": "coloring",

    #connect
    "conect": "connect",
    "onnect": "connect",

    #cooking
    "bake": "cooking",
    "baker": "cooking",
    "bakery": "cooking",
    "baking": "cooking",
    "bbq": "cooking",
    "chef": "cooking",
    "grill": "cooking",
    "kitchen": "cooking",
    "recipe": "cooking",
    "recipes": "cooking",

    #defense
    "defence": "defense",
    "defend": "defense",
    "defender": "defense",
    "protect": "defense",

    #differences
    "differs": "differences",
    "diffs": "differences",

    #doctor
    "dental": "doctor",
    "dentist": "doctor",
    "medical": "doctor",
    "surgeon": "doctor",
    "surgery": "doctor",

    #dog
    "doggy": "dog",
    "puppy": "dog",

    #drift
    "drifter": "drift",
    "drifty": "drift",

    #driving
    "rickshaw": "driving",

    #farming
    "farmer": "farming",

    #fashion
    "barber": "fashion",
    "braid": "fashion",
    "braided": "fashion",
    "dress": "fashion",
    "dressing": "fashion",
    "fashionista": "fashion",
    "haircut": "fashion",
    "hairstyle": "fashion",
    "outfit": "fashion",
    "tailor": "fashion",
    "wardrobe": "fashion",

    #fighting
    "boxing": "fighting",
    "brawler": "fighting",
    "fighter": "fighting",
    "gladiator": "fighting",
    "karate": "fighting",
    "samurai": "fighting",
    "sumo": "fighting",
    "sumos": "fighting",
    "wrestler": "fighting",
    "wrestling": "fighting",

    #fishing
    "angler": "fishing",
    "fisherman": "fishing",

    #flying
    "airplane": "flying",
    "aviation": "flying",
    "aviator": "flying",
    "glider": "flying",
    "helicopter": "flying",
    "pilot": "flying",
    "plane": "flying",
    "planes": "flying",

    #football
    "rugby": "football",
    "touchdown": "football",

    #golf
    "putt": "golf",

    #gun
    "bazooka": "gun",

    #halloween
    "hallowen": "halloween",
    "helloween": "halloween",

    #hero
    "superhero": "hero",
    "superheroes": "hero",

    #horror
    "haunted": "horror",
    "scary": "horror",
    "slendrina": "horror",
    "terror": "horror",

    #impostor
    "imposter": "impostor",

    #jumping
    "jumper": "jumping",
    "jumpy": "jumping",

    #kids
    "children": "kids",

    #mahjong
    "mahjongg": "mahjong",

    #makeup
    "manicure": "makeup",
    "pedicure": "makeup",

    #matching
    "onet": "matching",
    "pair": "matching",
    "pairs": "matching",

    #math
    "addition": "math",
    "arithmetic": "math",
    "counting": "math",
    "equations": "math",
    "mathematical": "math",
    "mathematics": "math",
    "multiplication": "math",

    #maze
    "mazes": "maze",

    #memory
    "memo": "memory",
    "memorize": "memory",
    "remember": "memory",

    #merge
    "combine": "merge",
    "fuse": "merge",
    "fusion": "merge",
    "merger": "merge",
    "merging": "merge",

    #offroad
    "jeep": "offroad",

    #pool
    "billiard": "pool",

    #puzzle
    "backgammon": "puzzle",
    "bolts": "puzzle",
    "checkers": "puzzle",
    "domino": "puzzle",
    "draughts": "puzzle",
    "lock": "puzzle",
    "ludo": "puzzle",
    "minesweeper": "puzzle",
    "nonogram": "puzzle",
    "plumber": "puzzle",
    "puzzlez": "puzzle",
    "screw": "puzzle",
    "sokoban": "puzzle",
    "tac": "puzzle",
    "tangram": "puzzle",
    "yatzy": "puzzle",

    #quiz
    "trivia": "quiz",

    #racing
    "formula": "racing",
    "karting": "racing",
    "racer": "racing",
    "rally": "racing",
    "speedway": "racing",

    #rescue
    "firefighter": "rescue",
    "rescuer": "rescue",

    #restaurant
    "cafe": "restaurant",
    "diner": "restaurant",
    "pizzeria": "restaurant",

    #rush
    "rushy": "rush",

    #shooter
    "commando": "shooter",
    "gunslinger": "shooter",
    "shooty": "shooter",
    "skeet": "shooter",
    "swat": "shooter",

    #simulator
    "sim": "simulator",
    "simulation": "simulator",

    #sniper
    "snipe": "sniper",

    #soccer
    "foosball": "soccer",
    "goalkeeper": "soccer",
    "penalty": "soccer",

    #solitaire
    "freecell": "solitaire",
    "patience": "solitaire",
    "tripeaks": "solitaire",

    #sorting
    "organize": "sorting",
    "organizer": "sorting",

    #space
    "asteroid": "space",
    "astronaut": "space",
    "cosmic": "space",
    "cosmos": "space",
    "galactic": "space",
    "mars": "space",
    "moon": "space",
    "spaceship": "space",
    "starship": "space",

    #sports
    "badminton": "sports",
    "bowling": "sports",
    "cricket": "sports",
    "darts": "sports",
    "hockey": "sports",
    "skate": "sports",
    "skateboard": "sports",
    "skater": "sports",
    "skates": "sports",
    "skating": "sports",
    "ski": "sports",
    "skiing": "sports",
    "snowboard": "sports",
    "volley": "sports",
    "volleyball": "sports",

    #stack
    "stacka": "stack",
    "stacky": "stack",

    #stunt
    "stuntz": "stunt",
    "wheelie": "stunt",

    #survival
    "survive": "survival",
    "survivor": "survival",

    #train
    "railroad": "train",

    #tycoon
    "business": "tycoon",
    "manager": "tycoon",

    #war
    "battlefield": "war",
    "warfare": "war",
    "warzone": "war",

    #word
    "crossword": "word",
    "hangman": "word",
    "letter": "word",
    "type": "word",
    "typing": "word",
    "wordy": "word",

}


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


def singularize_token(t: str) -> str:
    t = (t or "").strip().lower()
    if len(t) < 4:
        return t

    ies_keep_e = {
        "zombies": "zombie",
        "heroes": "hero",
    }
    if t in ies_keep_e:
        return ies_keep_e[t]

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


def apply_agent_exception_token(t: str) -> str:
    t = (t or "").strip().lower()
    return AGENT_EXCEPTIONS.get(t, t)


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


def maybe_normalize_tokens(
    tokens: list[str],
    plural_enabled: bool,
    gerund_enabled: bool,
    agent_enabled: bool,
    agent_exceptions_enabled: bool,
) -> list[str]:
    out = []
    for x in tokens:
        t = x
        if agent_exceptions_enabled:
            t = apply_agent_exception_token(t)
        if gerund_enabled:
            t = gerund_to_base_token(t)
        if agent_enabled:
            t = agent_noun_to_base_token(t)
        if plural_enabled:
            t = singularize_token(t)
        if agent_exceptions_enabled:
            t = apply_agent_exception_token(t)
        out.append(t)
    return out


def plural_surface_forms(t: str) -> set[str]:
    t = (t or "").strip().lower()
    if not t:
        return set()

    if t.endswith("y") and len(t) > 1 and t[-2] not in "aeiou":
        return {t[:-1] + "ies"}
    if t.endswith(("s", "x", "z", "ch", "sh")):
        return {t + "es"}
    return {t + "s"}


def gerund_surface_forms(t: str) -> set[str]:
    t = (t or "").strip().lower()
    if not t:
        return set()

    if t.endswith("ie") and len(t) > 2:
        return {t[:-2] + "ying"}
    if t.endswith("e") and not t.endswith(("ee", "ye")):
        return {t[:-1] + "ing"}
    if (
        len(t) >= 3
        and t[-1] not in "aeiouwxy"
        and t[-2] in "aeiou"
        and t[-3] not in "aeiou"
    ):
        return {t + t[-1] + "ing"}
    return {t + "ing"}


def agent_surface_forms(t: str) -> set[str]:
    t = (t or "").strip().lower()
    if not t:
        return set()

    if t.endswith("y") and len(t) > 1 and t[-2] not in "aeiou":
        return {t[:-1] + "ier"}
    if t.endswith("e"):
        return {t + "r"}
    if (
        len(t) >= 3
        and t[-1] not in "aeiouwxy"
        and t[-2] in "aeiou"
        and t[-3] not in "aeiou"
    ):
        return {t + t[-1] + "er"}
    return {t + "er"}


def is_usable_fused_surface(surface: str) -> bool:
    surface = (surface or "").strip().lower()
    if not surface or not surface.isalnum():
        return False
    return len(surface) >= 3 or surface in {"2d", "3d", "io", "vs"}


def build_fused_lexicon(
    slugs: list[str],
    plural_enabled: bool,
    gerund_enabled: bool,
    agent_enabled: bool,
    agent_exceptions_enabled: bool,
) -> dict[str, list[tuple[str, str]]]:
    surface_to_canonical: dict[str, set[str]] = {}

    for slug in slugs:
        for raw in tokenize_slug(slug):
            normalized = maybe_normalize_tokens(
                [raw], plural_enabled, gerund_enabled, agent_enabled, agent_exceptions_enabled
            )[0]
            if not normalized:
                continue

            surfaces = {raw, normalized}
            if plural_enabled:
                surfaces.update(plural_surface_forms(normalized))
            if gerund_enabled:
                surfaces.update(gerund_surface_forms(normalized))
            if agent_enabled:
                surfaces.update(agent_surface_forms(normalized))

            for surface in surfaces:
                if is_usable_fused_surface(surface):
                    surface_to_canonical.setdefault(surface, set()).add(normalized)

    by_first: dict[str, list[tuple[str, str]]] = {}
    for surface, canonicals in surface_to_canonical.items():
        for canonical in canonicals:
            by_first.setdefault(surface[0], []).append((surface, canonical))

    for first in by_first:
        by_first[first].sort(key=lambda pair: (-len(pair[0]), pair[0], pair[1]))

    return by_first


def split_fused_token(
    token: str,
    fused_lexicon: dict[str, list[tuple[str, str]]],
) -> list[str]:
    token = (token or "").strip().lower()
    if len(token) < 6 or not token.isalnum() or not fused_lexicon:
        return []

    memo = {}

    def solve(pos: int):
        if pos == len(token):
            return [], []
        if pos in memo:
            return memo[pos]

        best = None
        best_rank = None

        for surface, canonical in fused_lexicon.get(token[pos], []):
            if not token.startswith(surface, pos):
                continue

            if pos == 0 and len(surface) == len(token):
                continue

            rest = solve(pos + len(surface))
            if rest is None:
                continue

            rest_tokens, rest_lengths = rest
            candidate_tokens = [canonical] + rest_tokens
            candidate_lengths = [len(surface)] + rest_lengths

            rank = (
                -len(candidate_tokens),
                sum(length * length for length in candidate_lengths),
                tuple(candidate_lengths),
                tuple(candidate_tokens),
            )
            if best_rank is None or rank > best_rank:
                best_rank = rank
                best = candidate_tokens, candidate_lengths

        memo[pos] = best
        return best

    result = solve(0)
    if result is None or len(result[0]) < 2:
        return []
    return result[0]


def tokenize_for_matching(
    text: str,
    plural_enabled: bool,
    gerund_enabled: bool,
    agent_enabled: bool,
    agent_exceptions_enabled: bool,
    fused_lexicon: dict[str, list[tuple[str, str]]],
) -> list[str]:
    out = []
    for raw in tokenize_slug(text):
        fused_parts = split_fused_token(raw, fused_lexicon)
        if fused_parts:
            out.extend(fused_parts)
        else:
            out.extend(
                maybe_normalize_tokens(
                    [raw], plural_enabled, gerund_enabled, agent_enabled, agent_exceptions_enabled
                )
            )
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

        self.only_unique_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Unique", variable=self.only_unique_var).pack(side="left", padx=10)

        self.plurals_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Plural", variable=self.plurals_var).pack(side="left", padx=10)

        self.gerunds_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Gerund", variable=self.gerunds_var).pack(side="left", padx=10)

        self.agent_nouns_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Er", variable=self.agent_nouns_var).pack(side="left", padx=10)

        self.agent_exceptions_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(top, text="Agent Exceptions", variable=self.agent_exceptions_var).pack(side="left", padx=10)

        self.min_tokens_var = tk.IntVar(value=1)
        ttk.Label(top, text="Min keyword tokens").pack(side="left", padx=(12, 4))
        ttk.Spinbox(top, from_=1, to=5, width=3, textvariable=self.min_tokens_var).pack(side="left")

        ttk.Button(top, text="Scan", command=self.scan).pack(side="left", padx=10)
        ttk.Button(top, text="Move selected", command=self.move_selected).pack(side="left", padx=6)
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

    def build_keyword_map(
        self,
        plural_enabled: bool,
        gerund_enabled: bool,
        agent_enabled: bool,
        agent_exceptions_enabled: bool,
        fused_lexicon: dict[str, list[tuple[str, str]]],
    ):
        keywords = []
        for fn in self.files:
            if fn == self.current_file:
                continue
            raw_slug = raw_slug_from_filename(fn)
            slug = raw_slug.lower()
            if not slug:
                continue
            tokens = tokenize_for_matching(
                raw_slug,
                plural_enabled,
                gerund_enabled,
                agent_enabled,
                agent_exceptions_enabled,
                fused_lexicon,
            )
            keywords.append({"file": fn, "slug": slug, "tokens": tokens})

        keywords.sort(key=lambda k: (len(k["tokens"]), len(k["slug"])), reverse=True)
        return keywords

    def find_best_keyword_match(
        self,
        title_tokens: list[str],
        keywords,
        min_tokens: int,
    ):
        best = None
        best_priority = None
        best_score = -1
        ties = 0

        for kw in keywords:
            kt = kw["tokens"]
            if len(kt) < min_tokens:
                continue

            hits = find_all_subseq_positions(title_tokens, kt)
            if not hits:
                continue

            priority = build_match_priority(hits, kt, kw["slug"])
            score = build_display_score(priority)

            if best_priority is None or priority > best_priority:
                best_priority = priority
                best_score = score
                best = kw
                ties = 0
            elif priority == best_priority:
                ties += 1

        return best, best_priority, best_score, ties

    def scan(self):
        if not self.current_file:
            messagebox.showwarning("No source", "Select a source category first.")
            return

        min_tokens = int(self.min_tokens_var.get() or 1)
        only_unique = bool(self.only_unique_var.get())
        plural_enabled = bool(self.plurals_var.get())
        gerund_enabled = bool(self.gerunds_var.get())
        agent_enabled = bool(self.agent_nouns_var.get())
        agent_exceptions_enabled = bool(self.agent_exceptions_var.get())

        all_slugs = [raw_slug_from_filename(fn) for fn in self.files]

        direct_fused_lexicon = build_fused_lexicon(
            all_slugs,
            plural_enabled,
            gerund_enabled,
            agent_enabled,
            False,
        )
        direct_keywords = self.build_keyword_map(
            plural_enabled,
            gerund_enabled,
            agent_enabled,
            False,
            direct_fused_lexicon,
        )
        if not direct_keywords:
            messagebox.showinfo("No keywords", "No other categories found to use as keywords.")
            return

        semantic_fused_lexicon = direct_fused_lexicon
        semantic_keywords = direct_keywords
        if agent_exceptions_enabled:
            semantic_fused_lexicon = build_fused_lexicon(
                all_slugs,
                plural_enabled,
                gerund_enabled,
                agent_enabled,
                True,
            )
            semantic_keywords = self.build_keyword_map(
                plural_enabled,
                gerund_enabled,
                agent_enabled,
                True,
                semantic_fused_lexicon,
            )

        current_raw_slug = raw_slug_from_filename(self.current_file)
        current_slug = current_raw_slug.lower()

        direct_current_tokens = tokenize_for_matching(
            current_raw_slug,
            plural_enabled,
            gerund_enabled,
            agent_enabled,
            False,
            direct_fused_lexicon,
        )
        semantic_current_tokens = direct_current_tokens
        if agent_exceptions_enabled:
            semantic_current_tokens = tokenize_for_matching(
                current_raw_slug,
                plural_enabled,
                gerund_enabled,
                agent_enabled,
                True,
                semantic_fused_lexicon,
            )

        self.clear_results()

        candidates = 0
        skipped_self = 0

        for page in self.current_pages:
            gid = page.get("id", "")
            title = page.get("title", "")
            if not title:
                continue

            direct_title_tokens = tokenize_for_matching(
                title,
                plural_enabled,
                gerund_enabled,
                agent_enabled,
                False,
                direct_fused_lexicon,
            )

            best, best_priority, best_score, ties = self.find_best_keyword_match(
                direct_title_tokens,
                direct_keywords,
                min_tokens,
            )

            direct_current_priority = None
            if direct_current_tokens:
                direct_current_hits = find_all_subseq_positions(direct_title_tokens, direct_current_tokens)
                if direct_current_hits:
                    direct_current_priority = build_match_priority(
                        direct_current_hits, direct_current_tokens, current_slug
                    )

            title_tokens = direct_title_tokens
            current_tokens = direct_current_tokens
            current_priority = direct_current_priority

            if best is None:
                if direct_current_priority is not None:
                    skipped_self += 1
                    continue

                if agent_exceptions_enabled:
                    title_tokens = tokenize_for_matching(
                        title,
                        plural_enabled,
                        gerund_enabled,
                        agent_enabled,
                        True,
                        semantic_fused_lexicon,
                    )
                    current_tokens = semantic_current_tokens
                    best, best_priority, best_score, ties = self.find_best_keyword_match(
                        title_tokens,
                        semantic_keywords,
                        min_tokens,
                    )

                    current_priority = None
                    if current_tokens:
                        current_hits = find_all_subseq_positions(title_tokens, current_tokens)
                        if current_hits:
                            current_priority = build_match_priority(
                                current_hits, current_tokens, current_slug
                            )

            if best is None:
                continue

            if current_priority is not None:
                if current_priority >= best_priority:
                    skipped_self += 1
                    continue

            if only_unique and ties > 0:
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
