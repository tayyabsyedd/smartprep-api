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
- **M1g** — protected documents (BR-12/13): visibility-filtered Subject→Module→Chapter
  index (public for guests), gatekept PDF streaming from storage/app/notes
  (realpath traversal guard, inline disposition). Admin roster CSV import
  (batch upsert, claims preserved). Seed PDFs + DocumentSeeder. Feature tests:
  DocumentsTest + RosterImportTest green.
- **M4 (payments)** — gateway abstraction + checkout (BR-05/18/19/20/26):
  MockPaymentDriver (dev-only instant completion, entitlement grant);
  JazzCash sandbox-ready driver (HMAC-SHA256 per official spec, verified
  against their worked example); Easypaisa sandbox-ready driver
  (AES-128-ECB merchantHashedReq per merchant plugin spec); fixed IPN routes
  /webhooks/jazzcash + /webhooks/easypaisa (signature failures → 400,
  cross-driver reference replay rejected); POST /api/purchases/initiate,
  POST /{purchase}/upload-slip (5MB image/PDF → storage/app/private/payment_slips,
  owner-only), GET /my-purchases; pending_verification status; mobile
  PaywallModal checkout (dev-only Mock, JazzCash/Easypaisa 11-digit prompt,
  bank transfer details + receipt upload → pending banner). Suite: 66 tests /
  214 assertions green. Mobile tsc clean.

## Next
- Roster CSV import command (admin uploads the institute's roll list).
- Test engine endpoints: list tests, start attempt, submit answers, score report.
- Protected document downloads (signed URLs from storage/app/notes).

## Waiting on
- Client: JazzCash / EasyPaisa merchant credentials.
- Client: sign-off on data model v2 (design doc shared).
- Client: written payment-mix confirmation (M4 gate).
- Tayyab: `git push origin main` after each session (remote session can't reach the Windows credential store).
