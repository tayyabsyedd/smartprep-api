# SmartPrep API — Progress

## Done
- **M0** — workspace: Tailscale SSH into tayyab-pc, 3 repos, Laravel 13 scaffold on GitHub.
- **M1a** — data model v2: 20 migrations + 19 models, `purchases` locked schema,
  `institute_roster`, `entitlements`; tutoring table is `tutoring_sessions`
  (Laravel reserves `sessions`).
- **M1b** — payment service layer: `PaymentDriver` contract + `PaymentManager`
  (complete/fail/refund, entitlement grant/revoke, idempotent webhooks);
  drivers: jazzcash/easypaisa stubs (need client merchant credentials),
  bank_transfer working, play_billing stubbed for post-beta.
- **M1c** — Sanctum auth: register / login (email or phone) / logout / me
  (profile + per-module free allowance + live entitlements).
- **M1d** — enrollment verification (BR-02): roster auto-match → instant enroll;
  no match / claimed → pending request for admin review.
- **M1e** — seeders: admin, demo enrolled + guest students, roster (ROLL-100…103),
  Physics → 2 modules → 3 tests (15 questions), 1 tutor + 3 slots.
- Feature tests: AuthTest + EnrollmentTest green.
- **M1f** — test engine (BR-03–BR-08): AccessService (free/entitlement/quota),
  test show/start (sanitized questions, resume in-progress), answer auto-save,
  submit with set-based scoring, results scorecard, test history. Feature tests:
  TestEngineTest (13 tests) green.

## Next
- Roster CSV import command (admin uploads the institute's roll list).
- Test engine endpoints: list tests, start attempt, submit answers, score report.
- Protected document downloads (signed URLs from storage/app/notes).

## Waiting on
- Client: JazzCash / EasyPaisa merchant credentials.
- Client: sign-off on data model v2 (design doc shared).
- Client: written payment-mix confirmation (M4 gate).
- Tayyab: `git push origin main` after each session (remote session can't reach the Windows credential store).
