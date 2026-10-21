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
- **M6 (document viewer)** — secure in-app PDF reader (BR-13): DocumentViewerScreen
  (bearer-auth stream download → sandboxed cache, native react-native-pdf
  rendering, page indicator in header, progress loader + retry, cache deleted
  on unmount, screenshot/screen-record blocked while open); NotesScreen opens
  accessible docs in the viewer; locked docs open a roll-ID unlock modal
  (in-place verification for students, account creation for guests). New deps:
  react-native-pdf, expo-file-system, expo-screen-capture — the dev-client APK
  must be rebuilt once (`eas build --profile development --platform android`)
  before these native modules load on the phone. Mobile tsc clean.
- **IELTS content (Reading Practice Test 1)** — the client's real test seeded
  (IeltsReadingTestSeeder): Subject "IELTS Academic" → Module "Reading Module"
  → "Reading Practice Test 1" (free, timed 60 min, published). 26 option-based
  questions transcribed from the client's question paper + official answer key:
  Q1–8 True/False/Not Given, Q14–21 paragraph heading matching, Q26 MCQ,
  Q27–31 TFNG, Q32–35 MCQs. New nullable `questions.passage_text` column holds
  each passage's full text on its section's first question (Q1/Q14/Q27) and is
  exposed in the test-start payload for the runner's passage view. The paper's
  14 typed completion questions (Q9–13, 22–25, 36–40) are NOT seeded yet —
  the engine has no text_input qtype; their texts + answers are recorded in
  the seeder for when it lands. Verified: seeder idempotent, catalog shows
  the IELTS branch, suite green (66 tests).
- **M3 Admin CMS (Filament) — pass 1** — Tayyab chose Filament over Next.js
  (2026-10-21; matches PRD §6 + Proposal baseline). Filament v5.10 installed
  inside smartprep-api (required enabling PHP's intl extension in the XAMPP
  php.ini). Panel at /admin ("SmartPrep Admin"), admins only (User implements
  FilamentUser; canAccessPanel = isAdmin). Dashboard stats: students, pending
  enrollments, slips awaiting verification, completed revenue. Resources:
  **Enrollment Requests** — the BR-02 approval queue (pending-by-default,
  Approve/Reject actions via EnrollmentReviewService; approve mirrors the
  auto-match semantics: claims roster row if unclaimed, flips is_enrolled);
  **Purchases** — bank-slip verification (View slip streams the private file
  via an admin-only route; Approve → PaymentManager::complete grants access,
  Reject → fail); Students (users, role select, safe password handling),
  Institute Roster, Subjects, Modules, Tests, Questions, Documents (PDF
  upload to storage/app/notes via a new `app_storage` disk — the layout the
  stream endpoint reads), Batches, Tutors, Tutor Slots. Tests: +8
  (EnrollmentReviewTest, AdminPanelAccessTest) — suite green at 74.

- **text_input question engine (Phase A #1)** — new qtype for typed
  sentence/summary completions: migration adds 'text_input' to the qtype
  enum (SQLite table rebuild, same pattern as purchases) + attempt_answers
  .text_answer. correct_options holds acceptable answer strings; scoring
  normalizes case/whitespace/hyphens (spelling NOT forgiven — the IELTS key
  requires it). Answers save/submit/results all carry text_answer; mobile
  runner renders a text field, results show typed vs correct answer;
  Filament question form offers the new type. Reading Practice Test 1 is
  now the full 40 questions / 40 marks (the 14 held completions seeded via
  firstOrCreate on sort_order — idempotent, existing DBs gain them on
  re-seed). Suite: 78 tests green (+4 TextInputEngineTest).

## Next
- Roster CSV import command (admin uploads the institute's roll list).
- Test engine endpoints: list tests, start attempt, submit answers, score report.
- Protected document downloads (signed URLs from storage/app/notes).

## Waiting on
- Client: JazzCash / EasyPaisa merchant credentials.
- Client: sign-off on data model v2 (design doc shared).
- Client: written payment-mix confirmation (M4 gate).
- Tayyab: `git push origin main` after each session (remote session can't reach the Windows credential store).
