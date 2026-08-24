# G DESIGN — Project Structure Report

Restructuring phase executed per `guide.txt`. Scope: architecture only. No PHP functionality, no MySQL schema, no auth, no admin dashboard was created.

---

## 1. Old structure

```
g-design/
├── index.html, 404.html, about.html, contact.html, quote.html
├── service.html
├── service-{branding,graphic-design,printing,content-creation,
│            creative-strategy,art-direction}.html      (6 files)
├── work.html, work-details.html
├── blog-standard.html
├── blog-{6 slugs}.html                                  (6 files)
├── guide.txt, BACKEND_PLAN.md
└── assets/            (css, scss, js, images, fonts)
    └── confirmed junk: "metismenu copy.css", "metismenu copy.js",
        "swiper copy.js", Thumbs.db, fa-duotone-900.html,
        fa-duotone-901.html, scss/header/_mobile-menu (0 bytes)
```

21 HTML pages flat in the repository root; single `assets/` tree.

## 2. New structure

```
g-design/
├── public/                 ← web-facing document root
│   ├── index.html          ← homepage
│   ├── 404.html
│   ├── about/index.html
│   ├── contact/index.html
│   ├── quote/index.html    ← quotation wizard (?service=&item= deep links preserved)
│   ├── services/index.html
│   │   └── branding/ graphic-design/ printing/ content-creation/
│   │       creative-strategy/ art-direction/   (each: index.html)
│   ├── work/index.html
│   │   └── case-study/index.html               (old work-details.html)
│   ├── blog/index.html                         (old blog-standard.html)
│   │   └── choosing-paper/ cohesive-identity/ color-perception/
│   │       content-brings-light/ print-vs-digital/ well-crafted-logo/
│   └── assets/             ← moved wholesale (css, scss, js, images, fonts)
├── backend/                ← RESERVED, empty (.gitkeep placeholders)
│   ├── api/  config/  core/  controllers/  models/  routes/  middleware/
├── database/               ← reserved (schema comes in M0 backend phase)
├── storage/
│   ├── uploads/  logs/  cache/
├── docs/
│   ├── PROJECT-STRUCTURE.md  (this file)
│   └── BACKEND_PLAN.md       (inspection + API/DB architecture from previous phase)
├── .env.example
├── .gitignore
├── README.md
├── composer.json           ← metadata only, no dependencies installed
└── guide.txt               ← phase instructions (untouched)
```

## 3. Page mapping

| Old path | New path | Public URL |
|---|---|---|
| `index.html` | `public/index.html` | `/` |
| `404.html` | `public/404.html` | `/404.html` |
| `about.html` | `public/about/index.html` | `/about/` |
| `contact.html` | `public/contact/index.html` | `/contact/` |
| `quote.html` | `public/quote/index.html` | `/quote/` |
| `service.html` | `public/services/index.html` | `/services/` |
| `service-branding.html` | `public/services/branding/index.html` | `/services/branding/` |
| `service-graphic-design.html` | `public/services/graphic-design/index.html` | `/services/graphic-design/` |
| `service-printing.html` | `public/services/printing/index.html` | `/services/printing/` |
| `service-content-creation.html` | `public/services/content-creation/index.html` | `/services/content-creation/` |
| `service-creative-strategy.html` | `public/services/creative-strategy/index.html` | `/services/creative-strategy/` |
| `service-art-direction.html` | `public/services/art-direction/index.html` | `/services/art-direction/` |
| `work.html` | `public/work/index.html` | `/work/` |
| `work-details.html` | `public/work/case-study/index.html` | `/work/case-study/` |
| `blog-standard.html` | `public/blog/index.html` | `/blog/` |
| `blog-choosing-paper.html` | `public/blog/choosing-paper/index.html` | `/blog/choosing-paper/` |
| `blog-cohesive-identity.html` | `public/blog/cohesive-identity/index.html` | `/blog/cohesive-identity/` |
| `blog-color-perception.html` | `public/blog/color-perception/index.html` | `/blog/color-perception/` |
| `blog-content-brings-light.html` | `public/blog/content-brings-light/index.html` | `/blog/content-brings-light/` |
| `blog-print-vs-digital.html` | `public/blog/print-vs-digital/index.html` | `/blog/print-vs-digital/` |
| `blog-well-crafted-logo.html` | `public/blog/well-crafted-logo/index.html` | `/blog/well-crafted-logo/` |

Service folder slugs deliberately match the wizard's catalog IDs (`?service=branding` etc.) so all existing deep links keep working.

## 4. Asset structure

`assets/` was moved **wholesale** to `public/assets/` — no internal file moved or renamed (except junk removal below). All CSS↔font/image relative relationships are therefore unchanged.

## 5. Backend structure (reserved)

`backend/{api,config,core,controllers,models,routes,middleware}` exist as empty directories with `.gitkeep`. Intended layout is documented in `docs/BACKEND_PLAN.md`. Nothing inside them executes yet.

## 6. Storage structure

`storage/uploads`, `storage/logs`, `storage/cache` created with `.gitkeep`; runtime contents are gitignored via `.gitignore`.

## 7. Files intentionally retained

| File | Reason |
|---|---|
| `guide.txt` | Active phase instructions (root, per workflow) |
| `docs/BACKEND_PLAN.md` | Phase-1..7 findings; basis for future M0 |
| `assets/js/plugins/theia-sticky.min.js` and `theia-sticky-sidebar.min.js` | Differ from each other; neither referenced by any HTML today — kept pending review |
| `assets/scss/elements/_blog copy.scss` | Differs from `_blog.scss`; not imported by style.scss — kept for review |
| `assets/scss/elements/_back-t0-top.scss` | Typo-named variant of `_back-to-top.scss`; differs in content; not imported — kept for review |
| All other plugins/vendor JS/CSS | Referenced by pages or main.js |

## 8. Files identified as duplicates → deleted

Deleted only after byte-level confirmation:

| Deleted file | Evidence |
|---|---|
| `assets/css/plugins/metismenu copy.css` | identical to `metismenu.css` (`diff -q`) |
| `assets/js/plugins/metismenu copy.js` | identical to `metismenu.js` |
| `assets/js/plugins/swiper copy.js` | identical to `swiper.js` |
| `assets/images/Thumbs.db` | Windows thumbnail cache junk |
| `assets/fonts/fa-duotone-900.html` | Font Awesome vendor doc page; referenced only as an invalid font source (see §10) |
| `assets/fonts/fa-duotone-901.html` | same |
| `assets/scss/header/_mobile-menu` | 0-byte accident; real file `_mobile-menu.scss` intact and imported |

## 9. Files that require future review

1. **Both `theia-sticky*` plugin files** — unreferenced; decide delete vs keep.
2. **`_blog copy.scss`, `_back-t0-top.scss`** — unimported SCSS variants; confirm obsolete then remove.
3. **`assets/fonts/fa-duotone-900.html` references** — see §10 item 3.
4. **`action="mailer.php"` on 19 forms** — endpoint never existed; forms currently POST to `<route>/mailer.php` (e.g. `/about/mailer.php`). To be replaced by the real API during the backend phase; HTML edits deferred until then.

## 10. Broken references found

### Fixed by this restructure (were guaranteed to break otherwise)
- Every asset/link reference on depth-1 and depth-2 pages (rewritten: `assets/…` → `../assets/…` / `../../assets/…`; `.html` links → route URLs). 21 files modified.
- `quote.js`: six hardcoded card image paths `'assets/images/service/*.webp'` → `'../assets/images/service/*.webp'` (page now at `/quote/`).

### Pre-existing issues found (NOT caused by restructure; left untouched)
1. **122 missing CSS `url()` targets**, dominated by `style.css` demo backgrounds (`images/bg/bg-image-*.jpg/png`, 60 refs) and other template imagery that was never shipped with the project. Verified against git HEAD — identical before and after.
2. **`plugins/unicons.css`** expects `../font/unicons.svg` but `assets/css/font/` never existed.
3. **`plugins/fontawesome.min.css` @font-face for "Font Awesome 6/5 Duotone" points at `.html` doc files** instead of real font binaries. Those faces could never load (an HTML file is not a font), so rendering is unchanged after their deletion. The genuine `fa-duotone-*.woff2/.ttf` files remain in `assets/fonts/`.
4. **`mailer.php` missing** (19 forms) — pre-existing MISSING BACKEND ENDPOINT; handled in backend phase per plan.

## 11. Files moved / modified / deleted (summary)

- **Moved:** 21 HTML pages (see mapping), entire `assets/` tree, `BACKEND_PLAN.md` → `docs/`.
- **Modified:** 21 page files (path rewrites) + `public/assets/js/quote.js` (6 image paths). Zero visual/design changes.
- **Created:** directory skeleton (`backend/*`, `database/`, `storage/*`, `docs/`), `.gitignore`, `.env.example`, `README.md`, `composer.json`, `.gitkeep` placeholders.
- **Deleted:** 7 confirmed duplicate/junk files (§8).

## 12. Pages tested & results

Static server (`python3 -m http.server`) + automated crawler over all routes:

| Check | Result |
|---|---|
| All 21 pages return HTTP 200 with valid HTML | PASS (23 routes incl. `/quote/?service=…&item=…` deep-link variants) |
| Every local `href`/`src` on every page resolves (HTTP fetch) | PASS — 0 broken fetches across 129 unique URLs |
| Wizard deep links (`/quote/?service=branding&item=logo-design`) | PASS (HTTP level; query parsing untouched) |
| CSS `url()` targets before vs after (git HEAD diff) | 191 refs both sides; only delta = 4 refs to deleted invalid `.html` font docs (§10.3); no regressions |
| Fonts/images referenced from CSS | unchanged except pre-existing gaps (§10.1–10.3) |

## 13. Pre-existing working-tree deletions discovered in git

`git status` revealed four files tracked at HEAD but already deleted from the working tree **before** this restructuring session. They were left as-is (neither restored nor re-created):

| File | Notes |
|---|---|
| `mailer.php` | A basic `mail()` contact script (POST: `name`, `email`, `message`, optional `file`; plain-text responses; recipient placeholder `your@email.com`; **ignored the `service` field** the form sends). Useful reference for the backend phase's `/api/v1/contact` endpoint — do not resurrect as-is. |
| `blog-single.html`, `blog-two-columns.html`, `blog-three-columns.html` | Template layout variants; unreferenced by any current page (verified by link grep). Deletion from worktree predates this phase. |

## 14. Remaining issues / recommended manual checks

1. Do one manual browser pass (console + visuals) on a representative page set — home, one service page, blog post, `/work/case-study/`, full quote wizard flow — to double-confirm rendering parity (automated checks cover resource loading, not pixel output).
2. Decide fate of review-list items (§9).
3. Optional hygiene for a later phase: fix duotone `@font-face` sources, prune unused bg-image refs from style.css, repoint contact forms to the future `/api` endpoint.
4. Apache/Nginx should serve `public/` as document root; directory URLs rely on standard `index.html` directory-index behavior (no rewrite rules needed).
