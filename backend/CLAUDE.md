# Backend (Laravel API)

Project-wide rules live in [../CLAUDE.md](../CLAUDE.md) — read that first. This file only adds backend-specific notes.

- API-only: no Blade views, no Vite frontend build here. The UI is `../frontend` (Next.js).
- Local PHP/Composer for this dev environment live outside the repo at `C:\tools` (not portable — see the main README for how a real machine should set this up via a normal PHP install).
- Local dev/test DB is SQLite (`database/database.sqlite`); production target is PostgreSQL only — see `../docs/phase0-architecture.md` §3 and §16.
