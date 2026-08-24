# G DESIGN — Creative Agency Platform

Static marketing frontend (`public/`) with a reserved structure for a custom PHP + MySQL backend (no framework).

## Structure

- `public/` — website. Route-oriented folders: `/about/`, `/services/<slug>/`, `/work/`, `/blog/<slug>/`, `/quote/`, `/contact/`. Assets in `public/assets/`.
- `backend/` — reserved for custom PHP REST API (`api/ config/ core/ controllers/ models/ routes/ middleware/`). Empty by design.
- `database/` — schema/migrations will live here.
- `storage/` — `uploads/ logs/ cache/`.
- `docs/` — project documentation. Start with `docs/PROJECT-STRUCTURE.md`.

## Local preview

```bash
cd public && python3 -m http.server 8000
# open http://127.0.0.1:8000/
```

See `guide.txt` for the current phase instructions and `docs/BACKEND_PLAN.md` for the inspection findings and backend architecture plan.
