# Pro-LMS

Custom modular-monolith LMS built for the PoshProfs Trainers business (product display name **Pro-LMS**, changed 2026-09-11 — see `docs/phase0-architecture.md`'s top-of-file note). **Read [docs/phase0-architecture.md](docs/phase0-architecture.md) before proposing schema, API, or module structure changes** — it is the single source of truth for architecture decisions (Revision 3, final, approved). The underlying business/data model (courses, certificates, enrollments, etc.) is unaffected by the rename — only the on-screen product name and UI accent colors changed.

## Stack

- **Backend**: `backend/` — Laravel (PHP 8.3), PostgreSQL in production, Sanctum auth, REST API under `/api/v1`.
- **Frontend**: `frontend/` — Next.js (TypeScript, App Router), Tailwind CSS.
- **Local dev/test DB**: SQLite (no local PostgreSQL server available in this dev environment). Production/staging target is PostgreSQL only (DigitalOcean Managed, Bangalore) — never write migrations or queries that rely on SQLite-only behavior; stick to features both support (see `backend/database/migrations` for the partial-unique-index pattern used for certificates/enrollments, which works identically on both).
- **Video**: Bunny Stream. **Payments**: none in V1 — do not add Razorpay/orders/checkout code.

## Non-negotiable rules (from the architecture doc — do not silently redesign these)

1. **One authoritative service per business rule.** Enrollment access/expiry lives only in `EnrollmentAccessService`. Certificate eligibility lives only in `CertificateEligibilityService` + `CertificateIssuanceService`. Never duplicate this logic in a controller, another service, or the frontend.
2. **RBAC is `role` enum + Laravel Policies.** Three roles only: `admin`, `instructor`, `learner`. No permissions package. Every protected endpoint must have an explicit Policy check — no exceptions, no relying on the frontend hiding a button.
3. **Controllers stay thin**: validate via Form Requests → call a Service → return an API Resource.
4. **No payments code.** Enrollment is always `source = manual`, created by Admin.
5. **No Edmingle migration code or scripts.** Fresh start.

## Working rules for this repo

- Small, coherent commits/changes — do not attempt to build multiple modules in one pass.
- **Every module ships with tests**: happy path + the specific 403/IDOR case for each role. See `docs/phase0-architecture.md` §18 for what's expected per layer.
- No unnecessary abstraction, no speculative code for out-of-scope features (payments, WhatsApp provider, etc.) — see the architecture doc §6 for how the *extension points* for those are meant to look instead (an enum value, an interface — not a half-built subsystem).
- Before changing an existing file, read it first. Before adding a table/column, check whether the rule already has a home in an existing service.
