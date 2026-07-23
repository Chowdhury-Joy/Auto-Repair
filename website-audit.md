# Website Audit — TrueWrench Auto Repair (Laravel/Filament/Livewire app)

> First-pass, intentionally messy. Not client-ready. Capturing everything now, refining later.
> Audited: 2026-07-23. Target: **both** — local codebase (`/Users/chowdhuryjoy/Auto Repair`) AND the live rendered app (ran `php artisan serve` locally on port 8123 against the existing seeded SQLite DB, drove it with a real browser).
> By: first-pass automated audit (Claude Code).
> Legend: `[ASSUMPTION]` `[UNVERIFIED]` `[NEEDS DATA]` `[COULDN'T CHECK]` `[QUESTION]` `[IDEA]` `[DUPE-OK]`

This is a Laravel 13 + Filament 4 (admin) + Livewire 3 (public booking flow + customer portal) app for a fictional auto shop, "TrueWrench." Three faces: marketing site, customer self-service portal, and a Filament staff/admin panel. Booking engine does real bay+mechanic availability + concurrency locking. Fairly small, well-organized codebase (~30 PHP classes), decent test coverage of the core booking logic. That said, I found **two critical, fully-verified security holes**, a **site-wide broken navigation bug**, a **completely dead contact form**, and a **stale CSS build that makes a real button invisible** — all confirmed by actually clicking through the live app, not just reading source.

---

## 0. Scope, method & caveats

- **Code read in full:** all of `app/` (Models, Enums, Services, Livewire, Filament Resources/Widgets, Providers), all of `resources/views/`, all migrations, all tests, routes, config, composer.json/package.json, seeders.
- **Live app:** spun up `php artisan serve` against the repo's existing (already-seeded) `database/database.sqlite`, and drove it in a real Chromium tab — home, /about, /contact, /services, /book (full 5-step flow, twice), /login, /portal/* (dashboard, vehicles, appointments, invoices), /admin/login, /admin dashboard, plus one deliberate "attacker" pass through the booking flow to test an account-takeover hypothesis (see F-001). Also resized to a 375px mobile viewport.
- **Cleaned up after myself:** the one test appointment + test vehicle I created while verifying F-001 were deleted afterward via tinker so the demo DB is back to its original seeded state. I also created a throwaway `.claude/launch.json` to run the dev server for testing and deleted it afterward — **repo is clean, no diffs left behind.**
- **What I could NOT check:** real Lighthouse/PageSpeed numbers `[NEEDS DATA]`, real screen-reader behavior (NVDA/VoiceOver) `[COULDN'T CHECK]`, production mail delivery (MAIL_MAILER=log, so no real emails are ever sent — see F-011), any of this against a real production build (`npm run build` fresh) since I didn't want to write build artifacts into the repo, actual keyboard-only navigation start-to-finish `[COULDN'T CHECK]`.
- **Big caveat:** no real analytics/traffic/conversion data — this is a demo/dev app (`APP_ENV=local`, `APP_DEBUG=true`, seeded with demo data `admin@truewrench.demo` / `password` etc.), so business-impact framing below is written as if this were about to go to production, per the brief ("check the full codebase thoroughly").

---

## 1. Running scratchpad / raw notes

- Stack: Laravel 13.8, Filament 4.0, Livewire (via Filament + custom components), Tailwind v4 (via `@tailwindcss/vite`), Pest 4 for tests, SQLite for dev DB.
- Routes are refreshingly simple — `routes/web.php` is ~60 lines, all closures + Livewire full-page components. No API routes at all.
- Domain model: Customer→Vehicle→Appointment→WorkOrder→Invoice, plus ServiceBay/Mechanic/ServiceType as the scheduling resources, OperationsAlert as a side-channel notification queue. Clean, sensible schema.
- `AppointmentAvailabilityService` is genuinely the most sophisticated part of the app — real overlap-checking, a cache lock for the booking race, and decent test coverage (`tests/Feature/AppointmentAvailabilityTest.php`). Good engineering here.
- The demo seeder (`TrueWrenchDemoSeeder`) gives every seeded user the password `password` — fine for a seeder, but see F-001/F-002, this exact same "password" convention is **also** what production code silently does for real guest bookings, which is not fine.
- Title-tag inconsistency: some pages "TrueWrench Auto Repair · X", others "X · TrueWrench" — no fixed convention (see 3.9 Content).
- Weirdest rabbit hole of the audit: I initially thought the live homepage didn't match the source I'd read. Turned out I'd misread `resources/views/welcome.blade.php` (dead file, different copy) as if it were `marketing/home.blade.php`. Which itself became a finding — see F-010 — two homepages-that-aren't-quite-the-same sitting in the repo.

---

## 2. Top-of-mind / gut reactions

- "Wait, I didn't give a password, why am I logged into Jane's account—" — the moment I realized the guest booking flow doesn't check whether a typed email belongs to someone else. This is the headline finding (F-001).
- The customer login button being **invisible** (white text, transparent background) on first screenshot was a great "wait, where's the button" moment — turned out to be a stale build, not a design choice, but a real user hitting this today would just... not see a way to log in.
- Global nav "About"/"Contact" going to homepage instead of the actual, fully-built `/about` and `/contact` pages is baffling — the pages exist, look fine, just aren't linked correctly from anywhere except a direct URL.
- The contact form is a corpse. Zero wiring. Not even a `mailto:` fallback.
- Genuinely nice touch: the multi-step booking wizard's progress bar, the live "Reserving your slot…" loading state on the confirm button, and the service-history timeline on the vehicle detail page. Real UX care went into the happy path.
- Whoever built this clearly wrote the booking *engine* carefully (locks, overlap checks, tests) and then ran low on time/attention for the *edges* (auth boundaries, nav wiring, CSS build, mobile nav). Very typical shape for a fast-moving MVP.

---

## 3. Findings by lens

### 3.1 UX
- **F-001** (below, full writeup) — guest booking silently attaches to someone else's account. The single biggest UX/trust violation possible: a customer could discover a stranger's appointment/vehicle sitting in their "My Garage."
- No confirmation email actually goes out — `MAIL_MAILER=log`, so "A confirmation has been sent to jane@example.com" (shown on the booking success screen, `resources/views/livewire/book-appointment.blade.php:326`) is **false** in this environment; nothing is sent anywhere except the log file. `[ASSUMPTION: production would set a real mailer]` but worth flagging since the UI unconditionally claims an email was sent.
- Booking flow has no "are you sure this is you" step when an email autofills into an existing account — see F-001.
- Cancel/Delete actions in the portal (`wire:confirm="..."`) do use browser-native `confirm()` dialogs for destructive actions (cancel appointment, remove vehicle) — good, reversibility is respected there.
- No "forgot password" anywhere (see F-003) — a customer who forgets password 'password' (unlikely, but if they change it) has zero self-service recovery path and no visible staff-assisted path either (F-013).

### 3.2 UI design
- Color system: `resources/css/app.css` defines a `brand-*` and `accent-*` scale via Tailwind v4 `@theme`, comment says `--color-brand-500` is "primary," but the whole app visually treats `brand-700`/`brand-800` as primary (header bg, all major CTAs' surrounding sections) — the comment doesn't match usage. Minor, but confusing for the next dev tuning the palette. `[DUPE-OK, also 3.21]`
- Multiple font-loading paths: Vite config loads "Instrument Sans" from Bunny Fonts CDN (`vite.config.js`), but `app.css`'s `@theme` sets `--font-display`/`--font-body` to `"Inter"` — and the Filament admin panel separately ships its own bundled Inter webfonts (`public/fonts/filament/...`). Two different font families are being loaded into the same app depending on which surface you're on (marketing/portal = Instrument-Sans-that-nothing-references-by-name + a mislabeled `Inter` variable that's actually never fetched as a webfont; admin = real Inter). `[UNVERIFIED — need to confirm Instrument Sans is actually applied anywhere since --font-body says "Inter" not "Instrument Sans"]`. Looks like a rename/refactor that didn't get finished.
- See F-004 for the concrete "invisible button" + "missing focus rings" bug — that's a UI-design bug with root cause in the build pipeline, cross-listed here.

### 3.3 Layout
- **F-002** (mobile nav) — header has zero responsive handling. Confirmed via `document.documentElement.scrollWidth` (570px) vs `clientWidth` (375px) on a 375px viewport — ~200px of horizontal overflow, "Book Now" CTA sits off-screen at `left: 503px`. This is the single highest-impact layout bug since it hits literally every page (shared `layouts/public.blade.php` + `components/layouts/public.blade.php`, both identical, both lacking any `md:hidden`/hamburger pattern).
- Portal layout has the same unresponsive nav pattern (`layouts/portal.blade.php`) — 5 nav items + Book Service CTA + Sign out, all inline, no wrap, no collapse. `[UNVERIFIED on mobile viewport specifically — didn't reload portal at 375px, but it's the same markup shape as the public nav so I'm confident this reproduces; flagging as high-confidence but technically COULDN'T CHECK live]`.
- `TodayScheduleWidget`'s custom table (`resources/views/filament/widgets/today-schedule.blade.php`) breaks its own column layout for longer bay names — verified live: "Bay 3 (Diagnostics)" runs directly into "No appointments" with zero visible gap. The `w-32` fixed-width first column plus a non-`table-fixed` `<table class="w-full text-sm">` lets long content blow past the intended column boundary. See F-009.

### 3.4 Visual hierarchy
- Homepage hero, CTA hierarchy (amber "Book an Appointment" primary vs. translucent "View Services & Pricing" secondary) is clean and correctly weighted — no complaints here.
- Booking review step (Step 4) buries the price under 4 other `<dt>/<dd>` rows with identical visual weight — "Estimated total" doesn't stand out despite being probably the #2 thing (after date/time) a customer cares about before confirming. `[IDEA: bold/larger treatment for the price row]`

### 3.5 Navigation
- **F-002** duplicate: primary nav "About"/"Contact" → `/#about` / `/#contact`, which don't exist as anchors on the homepage (only `#services` does) — clicking either from any page just dumps you on the homepage top. The real `/about` and `/contact` routes/pages are fully built and completely orphaned from nav. `[DUPE-OK, also under Information Architecture and Confusing Decisions]`
- Footer (`layouts/public.blade.php`) has **no navigation links at all** — just an address block. So there's no secondary path to About/Contact either. The only way to reach the real About/Contact pages is typing the URL directly, or possibly an old external link/bookmark from before this bug was introduced.
- No breadcrumbs anywhere — arguably fine for a site this shallow (2 levels deep max), not a real problem, but noting per the lens checklist.
- Admin panel nav (Filament sidebar) is well-organized into "Operations / Shop Resources / System" groups — no complaints on IA there.

### 3.6 Information architecture
- `/about` and `/contact` exist as real, complete pages but are unreachable via any in-app link (see 3.5). From an IA standpoint the site's actual reachable graph is: Home ⇄ Services ⇄ Book ⇄ Login/Portal, with About/Contact silently pruned. `[DUPE-OK]`
- `resources/views/welcome.blade.php` and `resources/views/invoices/show.blade.php` are both live files that render real Blade (not stubs) but are **not wired to any route** — see F-010. From an IA audit standpoint these are landmines for future devs: they look like they should be "the" homepage / "the" invoice page, but aren't.

### 3.7 User journeys
Traced two:
1. **First-time visitor → book appointment (happy path):** Home → Book Now → pick service → pick date/time → enter vehicle+contact → review → confirm → success screen. Smooth, no dead ends, good state feedback (`wire:loading` on submit). No friction found on the happy path itself.
2. **First-time visitor → book with a stranger's email (adversarial path, deliberately tested):** Same flow, but at the contact-email field enter an email you don't own. Flow completes with **zero friction, zero warning, zero verification** — and the resulting appointment/vehicle is now attached to the real account owner. See F-001. This is the journey that should have a wall in it and doesn't.
3. **Returning customer → login → check repair status:** `/login` → dashboard → vehicle detail → status timeline. Works, decent "what stage is my car at" visualization (`vehicle-detail.blade.php`'s stepper). Only friction: the Sign-in button is currently invisible (F-004), so a first-time visitor to this exact flow may not realize there's a submit button at all below "Remember me."

### 3.8 Messaging
- Homepage value prop ("Auto repair you can actually trust" / ASE-certified / real-time tracking / upfront pricing) is clear inside 5 seconds. Good.
- Messaging is **inconsistent** between the live homepage (`marketing/home.blade.php`: "Auto repair you can actually trust") and the dead `welcome.blade.php` ("Honest Auto Repair, Guaranteed Availability" + a 3rd differentiator, "Expert Mechanics," that doesn't appear on the live page at all). If `welcome.blade.php` is an earlier draft, fine — but it should be deleted, not left to confuse the next person editing "the homepage." `[DUPE-OK with F-010]`

### 3.9 Content
- Title tag convention is inconsistent: `layouts/public.blade.php` default is `'TrueWrench Auto Repair'`, `marketing/home.blade.php` sets `'Honest Auto Repair in Portland · TrueWrench'` (brand last), while `about.blade.php`/`contact.blade.php`/`services.blade.php`/`book-appointment.blade.php`/`customer-login.blade.php` all do `'X · TrueWrench Auto Repair'` or `'X · TrueWrench'` (brand last but two different suffixes: "TrueWrench" vs "TrueWrench Auto Repair"). Small thing, but SEO/brand consistency nit — pick one pattern. `[DUPE-OK, also SEO 3.18]`
- Copy quality is otherwise good — no lorem ipsum, no placeholder text, tone is consistent ("honest," "transparent," "upfront" repeated deliberately across pages).
- `about.blade.php` has one very casual line — "zero-bullshit customer service" — sitting next to otherwise fairly polished, professional marketing copy. `[QUESTION: intentional brand-voice choice, or slipped through?]` Flagging since it's a genuine tonal outlier and some shop owners/clients would want it toned down.

### 3.10 CTAs
- "Book Now" / "Book an Appointment" / "Book Service" appear with 3 slightly different labels across surfaces (public nav = "Book Now", portal nav = "Book Service", homepage hero = "Book an Appointment"). Not wrong, but a *little* inconsistent; low severity.
- Contact page CTA ("Send Message") is a dead button — see F-005. This is the one CTA on the whole site that flatly does not work.

### 3.11 Conversion
- Booking funnel is 4 real steps (Services → Date/Time → Vehicle/Contact → Review) plus a 5th confirmation — reasonable for the amount of info needed (nothing feels padded). No unnecessary fields spotted; "current mileage" and "license plate" are correctly optional.
- No urgency/scarcity messaging (e.g., "3 slots left today") — not necessarily bad (the brief explicitly sells "transparent," and fake urgency would undercut that), just noting it's absent. `[IDEA, low priority]`
- Guest checkout silently becomes an account (F-001/F-002 territory) with zero disclosure — a privacy/consent angle worth naming separately: the guest is never told "this creates you a password-protected account," so they don't know to expect a portal login later, and they never chose that password. That's a conversion-adjacent trust issue even setting the security bug aside.

### 3.12 Trust & credibility
- Real address, real-looking phone number, real hours repeated consistently across footer/contact/about — good, consistent trust signals.
- Zero testimonials, reviews, or "as seen in" logos anywhere. `[IDEA: add a review strip once there's real review data — don't fabricate]`
- "ASE-Certified Technicians" badge/claim appears multiple places with no verification/link — fine for a demo, but `[NEEDS DATA]` in a real deployment (should be substantiated, e.g., certificate numbers or a real badge from ASE).
- The public invoice page (`/invoices/{token}`) uses an unguessable random token (`bin2hex(random_bytes(16))`, 128 bits) — that part is done right, good unguessable-URL pattern for a no-auth-required invoice view.

### 3.13 Accessibility (WCAG-ish — eyeballed/DOM-inspected, not a real screen-reader pass)
- **F-004**: Missing `focus:ring-*` utility (`ring-brand-500`) across every form on the site (login email/password, add-vehicle fields, book-appointment vehicle/contact fields) because the CSS class was never compiled — means **no visible focus indicator for keyboard users** on any of these inputs, a real WCAG 2.4.7 (Focus Visible) failure as currently deployed. This is fixable by rebuilding assets, but as-shipped right now, it's broken.
- Single `<h1>` per page, sensible heading order on every page I checked — good baseline structure.
- Icons (SVG wrench logo, feature-card icons) have no `aria-label`/`<title>`, and are almost certainly decorative-only (adjacent text conveys the same info), so this is probably fine, but `[COULDN'T CHECK with a screen reader]`.
- Form labels are proper `<label>` elements associated by visual proximity but I did not verify `for=`/`id` pairing in the DOM `[COULDN'T CHECK precisely — spot check suggests plain sibling markup without explicit `for`, e.g. `resources/views/livewire/portal/vehicles.blade.php` uses bare `<label class="...">Make</label>` next to an `<input wire:model="make">` with no `id`/`for` link at all]`. That's a real, if minor, a11y gap: screen readers relying on programmatic label association (not just visual adjacency) won't get the label read with the field.
- `wire:confirm="..."` (native browser confirm) is used for destructive actions instead of a styled modal — accessible by default (native dialog), a reasonable and correctly-chosen pattern here, not a complaint.
- Color contrast: didn't measure with a real tool, but eyeballing the confirmed-working part of the palette (`brand-700` navy on white, white on `brand-700`/`brand-800`) looks comfortably AA. The *broken* elements (invisible button, missing focus rings) are the real contrast failures here, and they're build artifacts, not the intentional palette. `[UNVERIFIED — eyeballed, not measured]`

### 3.14 Mobile responsiveness
- **F-002**, again, is the headline: no hamburger menu, header overflows horizontally at 375px, primary CTA pushed off-screen. This is the single most important mobile finding and it's sitewide (shared layout).
- Didn't get to fully audit the booking wizard's step 1-4 forms at 375px specifically (ran out of test budget after confirming the nav-level bug) — `[COULDN'T CHECK: card/grid reflow inside the wizard steps on narrow viewports]`. Given the service-selection cards and date/time button grids use responsive Tailwind classes (`sm:grid-cols-6`, etc.) in the source, they're *probably* fine content-wise, but the surrounding header bug would greet a mobile user on every single page regardless.

### 3.15 Forms
- Book-appointment guest fields (`contactName`, `contactEmail`, `contactPhone`, `newVehicleMake/Model/Year/Plate/Mileage`) have **zero server-side validation** (`app/Livewire/BookAppointment.php` — no `$rules`, no `$this->validate()` calls anywhere in the class) — contrast with `app/Livewire/Portal/Vehicles.php`, which does define `protected $rules = [...]` for the exact same kind of vehicle fields. Inconsistent validation posture between two Livewire components doing near-identical work. See F-006.
- Because of the above, you can submit an appointment with a garbage (non-email-shaped) "email" and it'll happily do `User::create(['email' => $this->contactEmail, ...])` — Laravel/MySQL won't stop you (email column has no format constraint, `database/migrations/0001_01_01_000000_create_users_table.php` just uses `string('email')->unique()`), so junk accounts with invalid emails are fully possible.
- `Vehicles.php`'s `$rules` do exist and are sensible (`year` required, `size:4`, mileage `integer|min:0`) — good, just wish `BookAppointment.php` matched.
- No client-side `required` attributes on the book-appointment vehicle/contact `<input>` fields either (checked the blade — plain `wire:model`, no `required`), so there isn't even a browser-native nudge.

### 3.16 Interactions & micro-interactions
- `wire:loading.attr="disabled"` + swapped label text ("Reserving your slot…") on the Confirm Booking button is a genuinely nice touch — prevents double-submit and gives honest feedback. Good pattern, should be the template for other async buttons in the app (e.g., "Add Vehicle" save button has no loading state at all, `[IDEA]`).
- Step-1 service checkboxes: while clicking through them manually with mouse coordinates, I observed the checked state occasionally land on a different row than intended between the raw click coordinate and the post-`wire:model.live` re-render. `[UNVERIFIED / low confidence — could easily have been a testing-tool artifact rather than a real user-facing bug; flagging as a "worth a manual click-through" item rather than a confirmed defect since I couldn't reliably reproduce it a second time]`.
- Vehicle "Remove" and appointment "Cancel" both use `wire:confirm` (native `confirm()`) — consistent, accessible, fine.

### 3.17 Empty / loading / error / success states — checked each explicitly
- **Empty:** "No vehicles yet," "No appointments yet" (+ CTA to book), "No invoices yet," "No open slots in the next two weeks. Please call us" — all present and reasonably helpful. Good coverage.
- **Loading:** Confirm-booking button has a loading state (see 3.16). Vehicle-detail page has `wire:poll.30s` for auto-refresh (nice for watching repair status live) but **no visible "last updated" or polling indicator** — a user staring at the page has no way to know it's live-updating vs. static. `[IDEA: small "updated Xs ago" or pulse indicator]`
- **Error:** Booking flow surfaces service-layer errors via `session()->flash('error', ...)` for "slot no longer available," "vehicle already scheduled," etc. — good, specific, actionable messages, not generic "something went wrong." Login shows a proper field-level error ("credentials do not match") without leaking whether the *email* specifically exists (good — no user enumeration via error message wording, though see F-001 for a much worse enumeration/impersonation vector via the *booking* form instead of the *login* form).
- **404 / 500:** `[COULDN'T CHECK — didn't hit a real 404 or force a 500 during this pass]`. Given `APP_DEBUG=true` in `.env`, an unhandled exception in production-like conditions would leak a full Laravel debug page (stack trace, env vars in some cases) — `.env` shipped in the repo has `APP_DEBUG=true` and `APP_ENV=local`, which is correct *for local dev*, but is exactly the kind of setting that's catastrophic if it ever leaks into a real deployment unchanged. `[ASSUMPTION: a real deploy would flip these — flagging only because it's committed as the default in this repo's .env and worth a deploy-checklist callout]`.
- **Success:** Booking confirmation screen (step 5) is warm and complete — next steps, what-to-bring checklist, bay/mechanic named. Genuinely good success-state design.

### 3.18 SEO
- No `<meta name="description">` on any page — every layout file only sets `<title>`. `[Missing — easy win]`
- No Open Graph / Twitter card tags anywhere.
- No `sitemap.xml`. `robots.txt` exists (`public/robots.txt`) — `[COULDN'T CHECK contents in this pass, but noting it's present]`.
- No structured data (JSON-LD `LocalBusiness`/`AutoRepair` schema would be a natural, high-value fit given the real address/hours/phone already in the footer). `[IDEA]`
- Title tag inconsistency noted in 3.9 also matters for SEO (duplicate-ish title patterns).
- Canonical tags: none seen. Low priority for a site this shallow, but noting for completeness.

### 3.19 Performance
- No real Lighthouse run performed `[NEEDS DATA]`.
- Font loading: Bunny Fonts (privacy-friendly Google Fonts alternative, good choice) for Instrument Sans via `laravel-vite-plugin/fonts` — but see 3.2, unclear if this font is actually referenced by name anywhere (`app.css` theme variables say "Inter"). If it's being fetched and never used, that's wasted bytes. `[UNVERIFIED]`
- Images: no `<img>`-based content in the templates I read (all icons are inline SVG) — so no low-hanging "unoptimized image" fruit here, which is good.
- `public/build` assets are small (one JS bundle, one CSS bundle, self-hosted fonts) — reasonably lean footprint for what's essentially a server-rendered app with light Livewire/Alpine sprinkling. No obvious bloat.
- The stale-CSS issue (F-004) is a *correctness* bug more than a performance one, but the underlying process gap (asset build not part of a deploy/CI step apparently) is worth a `[QUESTION]`: is `npm run build` wired into any deploy pipeline? `[COULDN'T CHECK — no CI config found in the repo at all, see 3.23]`.

### 3.20 Technical issues
- **F-004**: stale/incomplete Tailwind build — the biggest technical finding, full writeup below.
- No console errors observed on any page visited (checked via `read_console_messages`) — whatever *does* render, renders without JS errors.
- `config('services.truewrench.webhook_url')` (`app/Services/OperationsAlertService.php:92`) has no corresponding `'truewrench' => [...]` entry in `config/services.php` — always resolves to `null`, meaning the webhook-dispatch code path (`Http::timeout(5)->post($webhookUrl, ...)`) is **permanently dead** in this codebase as shipped; alerts always take the "logged only" branch. Not a crash (Laravel returns `null` gracefully for missing config keys) but a real "half-wired feature" — see F-012.
- `robots.txt`/favicon present but unaudited in depth `[COULDN'T CHECK]`.
- `.env` committed values are dev-appropriate but see 3.17's caveat about `APP_DEBUG`.

### 3.21 Design-system consistency
- Color-scale doc/usage mismatch (brand-500 labeled "primary" in a comment, brand-700/800 actually used as primary) — see 3.2. `[DUPE-OK]`
- Two near-identical pairs of layout files (`layouts/*` vs `components/layouts/*`) — see F-007. One pair (public) is legitimately dual-used by two different Blade calling conventions (Livewire's `->layout()` vs `<x-layouts.public>` component tag) and both copies are currently byte-identical, but that's exactly the kind of thing that silently drifts the next time someone edits the header in only one place. The portal pair is worse: `components/layouts/portal.blade.php` has **zero references anywhere in the codebase** — pure dead duplicate.
- Buttons: primary CTA styling (`bg-brand-700 hover:bg-brand-800` or `bg-accent-500 hover:bg-accent-600`) is applied ad hoc via repeated utility strings on every single button across ~10 files rather than a shared Blade component/class — classic Tailwind copy-paste drift risk. Nothing currently *broken* here besides F-004, but there's no `<x-button>` component to update in one place. `[IDEA: extract a button component]`

### 3.22 Repeated components
- Every page's `<header>`/`<nav>`/`<footer>` markup is copy-pasted verbatim across `layouts/public.blade.php`, `components/layouts/public.blade.php`, `layouts/portal.blade.php`, and `components/layouts/portal.blade.php` (4 files, effectively 2 unique layouts, each duplicated once). See F-007.
- The "status badge" pattern (`<span class="px-3 py-1 rounded-full text-xs font-semibold ...">`) with a `match($status->color())` conditional class chain is re-implemented slightly differently in at least 4 places (`appointments.blade.php`, `invoices.blade.php` portal view, `public/invoice.blade.php`, `invoices/show.blade.php` (dead), `dashboard.blade.php`) instead of a shared `<x-status-badge>` component. Not broken, just a consolidation opportunity — `[IDEA]`.

### 3.23 Code quality (codebase mode)
- `App\Models\WorkOrder::invoice()` is declared `HasMany` (`app/Models/WorkOrder.php:56`) but every call site treats it as effectively one-to-one (`->invoice()->doesntExist()`, `->invoice->first()`) — works, but the name/type mismatch (singular name, plural relation type) is a real footgun for the next dev who might reasonably call `$workOrder->invoice` expecting a single model and get a collection instead. See F-008.
- `WorkOrderCompletionService::complete()` (`app/Services/WorkOrderCompletionService.php:18`) uses `Cache::lock('invoice_generation', 5)` — a single **global** lock name, not scoped per work order. This means only one work order in the *entire shop* can be completed-and-invoiced at any given instant, system-wide, even across unrelated bays/mechanics. For a single-shop demo this is invisible, but it's an artificial serialization bottleneck that would start mattering with real concurrent staff usage. Should be `Cache::lock("invoice_generation:{$workOrder->id}", 5)` or similar. See F-014.
- `AppointmentAvailabilityService`'s work-order-blocking heuristic (`hasFreePair`/`findFreePairInTransaction`) hardcodes "an in-progress work order blocks its bay/mechanic for at least 1 more hour from **now**," regardless of how long the job has actually been open or how much longer it's estimated to take. Crude proxy, no test coverage for this specific branch (`AppointmentAvailabilityTest.php` only tests the appointment-vs-appointment overlap cases, not the work-order-blocks-availability path). `[IDEA: either drive this off `ServiceType.duration_minutes` estimates, or accept the heuristic but add a test for it]`.
- Inconsistent import style within `AppointmentAvailabilityService.php`: most classes are `use`-imported at the top, but `WorkOrder` and `WorkOrderStatus` are referenced fully-qualified inline (`\App\Models\WorkOrder::query()`, `\App\Enums\WorkOrderStatus::InProgress`) three separate times in the same file that already imports sibling classes properly. Pure style nit, zero functional impact.
- No `.php-cs-fixer.php`/explicit Pint config beyond the default (Pint is a dev dependency in `composer.json` but I didn't verify it's wired into CI since there's no CI config at all — see below).
- **No CI configuration found anywhere in the repo** (`[COULDN'T CHECK further — searched for .github/workflows, no results]`) — tests exist and look decent, but nothing appears to run them automatically on push/PR. `[QUESTION: is there CI configured somewhere outside this repo, e.g. in a platform dashboard?]`
- Good: enums are used consistently and idiomatically throughout (`AppointmentStatus`, `WorkOrderStatus`, `InvoiceStatus`, `UserRole`, `AlertType`) with `label()`/`color()` helpers — clean, readable, no magic strings scattered around models. Genuinely well done.
- Good: the booking + work-order + invoice services are properly wrapped in `DB::transaction()` where money/state consistency matters.
- Test coverage: `AppointmentAvailabilityTest` (4 tests, good overlap-logic coverage), `BookAppointmentTest` (1 happy-path Livewire test), `WorkOrderOperationsTest` (3 tests covering check-in, completion+invoice, alerts). **Zero tests for**: the guest-booking account-resolution logic (F-001 would have been caught immediately by a test asserting "booking with an existing user's email must not attach to their account without auth"), the customer login component, any Filament resource, any validation edge case. `[NEEDS: regression test for F-001 before anything else]`.

### 3.24 Missing pages or states
- No `/privacy` or `/terms` page — `[ASSUMPTION: not yet needed for a demo, but a real business collecting names/emails/vehicle data (PII) via a public form should have both before launch]`.
- No password-reset flow (see F-003) despite Laravel shipping the `password_reset_tokens` migration by default (`database/migrations/0001_01_01_000001_create_cache_table.php` region — `[COULDN'T CHECK exact migration filename for password_reset_tokens table, but the standard Laravel starter table is present per default migrations]`) and `config/auth.php` having a fully-configured `'passwords'` broker ready to go — the plumbing exists, the UI/routes don't.
- No admin-side User/Customer management resource in Filament (no `UserResource`/`CustomerResource`) — staff can't view a customer's profile, edit contact info, or reset a password from the admin panel at all. See F-013.
- No true 404/500 custom page checked `[COULDN'T CHECK]`.
- No visible cookie-consent banner `[N/A — no visible use of tracking cookies/analytics scripts observed, so likely not legally required as-is; would need revisiting if analytics get added]`.

### 3.25 Confusing decisions
- Why does the primary nav point to homepage anchors that don't all exist, when the actual destination pages are fully built? Feels like the nav was written against an earlier single-page version of the site and never updated when About/Contact became real routes. See F-002.
- Why does guest booking silently log a stranger into (well — attach data to) an existing account instead of either (a) requiring login to book under an existing email, or (b) at minimum warning "an account already exists for this email, please log in"? See F-001.
- Two "layouts" directories (`layouts/` and `components/layouts/`) for what is visually one design — presumably an artifact of mixing Livewire full-page `->layout()` components with Blade anonymous `<x-layouts.*>` components, but nothing forces the duplication; a single file could serve both patterns. See F-007.
- `resources/views/welcome.blade.php` — Laravel's stock starter file, left in place but rewritten with real (if now-outdated) marketing copy, then apparently superseded by `marketing/home.blade.php` without deleting the original. See F-010.

### 3.26 Potential opportunities
- Quick wins: fix the nav anchors (F-002), rebuild CSS (F-004), wire up or remove the contact form (F-005), delete the two dead view files (F-010), brand the Filament panel (F-015).
- Bigger bets: password-reset flow + basic account self-service (change password, update contact info) now that customers have real accounts; a `CustomerResource`/`UserResource` in Filament so staff aren't helpless when a customer calls in locked out; consolidate the 4 layout files into 2; add a regression test suite around the auth/account-resolution boundary given how close F-001 was to shipping unnoticed.
- `[IDEA]` LocalBusiness JSON-LD schema (free SEO win given the real NAP data already sitting in the footer).
- `[IDEA]` A shared `<x-button>`/`<x-status-badge>` component pair — would have prevented some of the F-004 blast radius (fewer places for a class typo/staleness to hide) and cuts the copy-paste count meaningfully.

---

## 4. Full write-ups — the findings that most warrant the full shape

#### [F-001] Guest booking silently hijacks any existing customer's account — CRITICAL, verified live
- **What I noticed:** The public `/book` flow, when used without logging in, resolves a "customer" purely by matching the typed email address against existing `users` rows — with **no password, no verification, no login required.** If the email belongs to an existing customer, the new appointment *and* any new vehicle are attached straight to that real customer's account.
- **Where:** `app/Livewire/BookAppointment.php:230-254` (`resolveCustomer()`), specifically `User::where('email', $this->contactEmail)->first()` followed by using that user's `->customer` with zero auth check. Reachable via `routes/web.php:34` (`/book`, no auth middleware — intentionally public, that's fine, but the account-resolution logic inside is the bug).
- **Why it's a problem:** Anyone who knows (or guesses, or scrapes, or is simply an ex-partner/coworker/neighbor of) an existing customer's email address can, with zero credentials, create appointments and vehicles under that person's real account, see the "you're booked" confirmation addressed to them by name, and have all of it show up in that customer's actual portal next time they log in.
- **User impact:** A real customer opens "My Garage" and finds a stranger's fake vehicle and an appointment they never made. Directly damages trust in the exact "transparent tracking" promise the marketing copy is built around.
- **Business impact:** This is an account-integrity/authorization vulnerability (roughly an IDOR/broken-authentication pattern) in a system that already handles names, emails, phone numbers, vehicle VINs/plates, and — per F-002 — auto-provisions passworded accounts. In a real deployment this is a reportable security incident, not a UX nit.
- **Severity:** Critical.
- **Verified live:** Logged out, went to `/book`, selected Oil Change, picked a real open slot, then on the contact step entered the **seed data's real customer email** `jane@example.com` (name "Not Jane," a made-up vehicle "2024 Sneaky Intruder") and completed the booking with no login prompt at any point. Confirmed via `php artisan tinker` before/after: Jane's `Customer` record (id 3) gained vehicle #4 ("2024 Sneaky Intruder") and appointment #1 (2026-07-23 09:00, status `scheduled`) — both fully attached to her real account. Cleaned up both test records afterward (`Appointment::find(1)->delete()`, `Vehicle::find(4)->delete()`) to restore the seeded DB state.
- **Possible solution:** If the typed email already belongs to a `User`, do **not** silently attach — either (a) require the guest to log in / verify via a magic link or one-time code sent to that email before proceeding, or (b) create the appointment against a *new, separate* guest record and let staff manually merge/reconcile if it's truly the same person, or (c) at minimum block the flow with "An account already exists for this email — please log in to continue" and redirect to `/login` with the return-to-booking intent preserved.
- **Implementation notes:** The fix belongs in `resolveCustomer()`. Also add a Pest test asserting that booking as a guest with an existing user's email either fails, requires auth, or does not attach to that user's `Customer` without verification — this is exactly the kind of regression a test would catch permanently.
- **Else to investigate:** Does the same unauth'd email-matching pattern exist anywhere else (e.g., any other guest-facing form)? I didn't find another instance, but worth a targeted `grep` for `User::where('email'` across the codebase as a follow-up.

#### [F-002] No mobile navigation — header overflows horizontally on every page — HIGH, verified live
- **What I noticed:** At a 375px mobile viewport, the shared header (logo + 3-4 nav links + CTA button, sometimes + auth links) does not wrap, collapse, or hide behind a hamburger menu. It simply overflows the viewport horizontally.
- **Where:** `resources/views/layouts/public.blade.php` and its byte-identical twin `resources/views/components/layouts/public.blade.php` (both `<nav class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">` with no responsive classes on the nav items); same shape in `layouts/portal.blade.php` / `components/layouts/portal.blade.php`.
- **Why it's a problem:** No `md:hidden`/hamburger pattern, no `flex-wrap`, and the container is a plain flex row of unbounded-width children — completely rigid regardless of viewport.
- **User impact:** On a real phone, a visitor arriving at the homepage sees "TrueWrench" and "Services" text visually running together, and the "Book Now" button — the site's single most important CTA — sits off-screen and can only be reached by scrolling the whole page sideways, which most mobile users will never think to do.
- **Business impact:** Given the marketing pitch is "book online in 60 seconds," and a large fraction of real traffic to a local auto shop is mobile, this bug directly blocks the primary conversion action for mobile visitors on every single page of the site.
- **Severity:** High (borderline Critical given it's the primary CTA and it's sitewide).
- **Verified live:** Resized the browser to the 375×812 mobile preset, reloaded `/`. Screenshot shows "TrueWrenchServices" running together with no gap, "About"/"Contact"/"Book Now" pushed off-canvas. Confirmed programmatically: `document.documentElement.scrollWidth` = 570 vs `clientWidth` = 375 (≈195px of forced horizontal overflow); the "Book Now" link's bounding rect reports `left: 503.7`, well outside the 375px viewport.
- **Possible solution:** Standard responsive header pattern — collapse nav links + secondary CTAs behind a hamburger/disclosure below a breakpoint (e.g., `md:`), keep logo + hamburger + maybe the primary CTA visible at all times.
- **Implementation notes:** Since this markup is duplicated across 4 files (see F-007), fix needs to land in all 4 unless the duplication is resolved first — resolving F-007 first would mean this fix only needs to happen once.
- **Else to investigate:** Didn't get to test the booking wizard's internal step content (service cards, date/time grid) at 375px specifically — `[COULDN'T CHECK]` — worth a follow-up pass once the header itself is fixed, since right now the header bug would dominate any mobile session before a user even gets that far.

#### [F-003] No password-reset flow, plus predictable default passwords — HIGH
- **What I noticed:** Two compounding issues: (1) guest bookings that create a new account use the literal hardcoded password `'password'` (`app/Livewire/BookAppointment.php:245`, `'password' => bcrypt('password'), // default password for demo`); (2) there is no forgot-password / reset-password route, controller, or view anywhere in the app, despite `config/auth.php` having a fully-configured `passwords.users` broker ready to use.
- **Where:** `app/Livewire/BookAppointment.php:239-251` (account creation); absence confirmed via `grep` across `routes/web.php` and `resources/views` for any reset/forgot-password reference — none found.
- **Why it's a problem:** Every guest-created account starts with a publicly-known, hardcoded password and there is no login-attempt throttling (see F-003b below) and no way to ever change it via the app.
- **User impact:** Any of the many customers who book as a guest (likely the majority of first-time bookings, per the "no phone calls required" pitch) get an account secured only by a password that's identical for every such customer and is written directly into the app's source code.
- **Business impact:** Trivial account compromise across the entire customer base; combined with F-001, an attacker doesn't even need the password to reach someone else's data, but for accounts *the attacker itself* creates as "a customer," they always know the password too — and so does anyone who reads this repo (which, again, is a public-source demo pattern, but the exact same code would ship the exact same weakness to production untouched).
- **Severity:** High (Critical if combined with real production traffic and no one noticing before launch).
- **Possible solution:** Generate a random password + send a "set your password" email (or magic-link) instead of a fixed string; implement Laravel's built-in password-reset flow (the broker config is already there, just needs routes/controllers/views, which is a small, standard lift); add login throttling (`ThrottleRequests` or a custom rate limiter keyed by email+IP) to `CustomerLogin::login()`.
- **Implementation notes:** `composer.json` doesn't include Laravel Breeze/Fortify/Jetstream — this was hand-rolled auth via a bare Livewire component + `Auth::attempt()`, so the reset flow needs to be hand-built too (or add one of those packages).
- **Else to investigate — F-003b, folded in here:** `app/Livewire/Auth/CustomerLogin.php::login()` has zero rate limiting on failed attempts (no `RateLimiter::tooManyAttempts`, no `Illuminate\Auth\Events\Lockout`, nothing). Combined with a known-default password for a large share of accounts, this is a straightforward brute-force/credential-stuffing target as shipped.

#### [F-004] Stale/incomplete compiled CSS — the login button is invisible and multiple focus states are missing — HIGH, verified live + root-caused
- **What I noticed:** The Customer Portal Login "Sign in" button renders with a **transparent background and white text** — i.e., invisible on the white card behind it. Confirmed via computed styles: `background-color: rgba(0, 0, 0, 0)`, `color: rgb(255, 255, 255)`, even though the element's class list correctly includes `bg-brand-600`.
- **Where:** Visually at `/login` (`resources/views/livewire/auth/customer-login.blade.php:23`). Root cause is in the **build output**, not the template: `public/build/assets/app-DKS4LxoP.css` (Vite/Tailwind v4 JIT build) is missing the compiled rules for `bg-brand-600`, `bg-accent-600`, `border-brand-300`, `ring-brand-500`, `text-accent-700`, and `text-brand-900` — verified by diffing every `*-(brand|accent)-<N>` utility class actually referenced across `resources/views/**/*.blade.php` + `app/**/*.php` against every such class that's actually present in the compiled CSS. 6 classes are used in source but entirely absent from the built stylesheet. File mtimes confirm the story: the CSS bundle was last built at 23:20, but `customer-login.blade.php` (and other templates using these classes) was last edited at 23:29 — the build is simply out of date relative to the templates.
- **Why it's a problem:** Tailwind v4's JIT compiler only emits CSS for classes it can find referenced in scanned files *as of build time*. If a template using a new custom-color utility is added/edited after the last `npm run build`, that utility silently never gets emitted — no build error, no console warning, just missing CSS.
- **User impact:** The single "Sign in" button on the whole customer-facing login page is not visible to a normal user (white-on-white/transparent). They'd have to already know where to click. Separately, `ring-brand-500` (the focus ring shown on `:focus`) is missing everywhere it's used — login email/password, the "Add Vehicle" form fields, all three vehicle/contact fields in the booking wizard — so **keyboard users get no visible focus indicator on any of these inputs.**
- **Business impact:** A real visitor trying to log into the customer portal on a fresh, unlucky build could plausibly not find the submit button at all (it's there, it's clickable, it's just invisible) and bounce. The missing focus rings are a genuine accessibility regression (WCAG 2.4.7).
- **Severity:** High (the specific invisible-button instance is close to Critical for usability; scoping to High because it's a build/process bug, not a permanent code defect — a rebuild very likely fixes it).
- **Verified live:** Screenshot of `/login` shows the button-shaped empty box with no visible label; `getComputedStyle` confirms transparent background; confirmed the missing-utility list via a source/compiled-CSS diff (see above); confirmed the fix is a rebuild, not a template edit, by inspecting file mtimes.
- **Possible solution:** Run `npm run build` (or better, make the asset build part of the deploy pipeline so this class of bug can't reach a live environment un-rebuilt again) and rebuild before every deploy. Given no CI was found in this repo (3.23), that's probably the actual root fix: wire asset building into whatever deploy process exists.
- **Implementation notes:** I deliberately did **not** run `npm run build` myself during this audit, to keep the codebase read-only per the audit's own ground rules — this is a one-command fix (`npm run build`) for whoever picks this up, just flagging that I verified the cause but left the repo untouched.
- **Else to investigate:** Same class of bug could be hiding elsewhere for any other custom utility that was added to a template between builds — worth a full rebuild + re-diff as a sanity check rather than assuming this list of 6 is exhaustive over time.

#### [F-005] Contact page "Send Message" form is completely non-functional
- **What I noticed:** The `/contact` page's message form has no backend at all — no Livewire component, no form `action`, no route, and the submit control is a plain `<button type="button">` (not even `type="submit"`), so it doesn't even trigger native browser form validation/submission.
- **Where:** `resources/views/marketing/contact.blade.php:26-42`.
- **Why it's a problem:** There is nothing on the page or in the codebase that processes this form in any way.
- **User impact:** A visitor fills in name/email/message, clicks "Send Message," and — verified live — literally nothing happens. No error, no success state, no navigation, no network request. They have no way of knowing whether it worked (it didn't) and no feedback telling them so.
- **Business impact:** This is the site's only non-booking contact channel besides the phone number; a visitor with a "custom request" (the page's own words) has no working way to reach the shop except calling.
- **Severity:** High (dead CTA on a page whose entire purpose is that CTA).
- **Verified live:** Loaded `/contact`, clicked "Send Message" — no network request fired, no visual change, page state unchanged.
- **Possible solution:** Either wire this up (simplest: a small Livewire component or a plain POST route that emails the shop / creates a lightweight `ContactMessage` record visible in the admin panel) or, if not ready to build it yet, replace it with a `mailto:` link or remove the form and just leave phone/address as the contact method so nothing on the page pretends to work when it doesn't.
- **Implementation notes:** Given the app already has `MAIL_MAILER=log` wired and a Filament admin panel, the cheapest real fix is probably: a `ContactMessage` model + migration, a Livewire form here, and a very simple read-only Filament resource for staff to see submissions.

#### [F-006] Book-appointment guest form has no validation at all (contrast with the portal's Vehicles form, which does)
- **What I noticed:** `app/Livewire/BookAppointment.php` defines no `$rules` and calls `$this->validate()` nowhere in the class, so `contactName`, `contactEmail`, `contactPhone`, `newVehicleMake`, `newVehicleModel`, `newVehicleYear`, `newVehiclePlate`, `newVehicleMileage` can all be submitted blank or malformed and will be written straight to the database.
- **Where:** `app/Livewire/BookAppointment.php` (whole class) vs. `app/Livewire/Portal/Vehicles.php:23-29`, which defines sensible `$rules` for the same conceptual fields (`required|string|max:50`, `size:4` for year, `integer|min:0` for mileage) on what is essentially the same data.
- **Why it's a problem:** Two Livewire components creating the same kind of `Vehicle`/contact data have completely different validation postures for no apparent reason.
- **User impact:** A user could submit a blank make/model, a garbage "year," or a non-email string as their contact email and the booking would still succeed (I did exactly this in the F-001 test — the vehicle "2024 Sneaky Intruder" required no format checking to create).
- **Business impact:** Data quality — staff-facing tables in the admin panel (`AppointmentResource`, `WorkOrderResource`) would show garbage vehicle/contact data with no way to have prevented it at entry.
- **Severity:** Medium.
- **Possible solution:** Port the same `$rules` shape from `Portal\Vehicles` into `BookAppointment`, plus an actual `email` format rule for `contactEmail` (currently has none at all, anywhere).
- **Implementation notes:** Straightforward Livewire `$rules` + `$this->validate()` addition; should also add `required` HTML attributes as a first line of client-side defense.

#### [F-007] Duplicate layout files (public pair actively dual-used; portal-component copy is pure dead code)
- **What I noticed:** `resources/views/layouts/public.blade.php` and `resources/views/components/layouts/public.blade.php` are byte-identical (confirmed via `diff`); same for the `portal.blade.php` pair. But they're not *equally* used: Livewire full-page components call `->layout('layouts.public'/'layouts.portal')` (resolves to `resources/views/layouts/*`), while every static Blade page uses the `<x-layouts.public>` component tag (resolves to `resources/views/components/layouts/*`). The **portal** component copy, though, `resources/views/components/layouts/portal.blade.php`, has zero `<x-layouts.portal>` references anywhere in the codebase — it's simply unused.
- **Where:** the 4 files listed above; usage confirmed via `grep -rn "layout('layouts" app/Livewire` (7 hits, all `layouts.public`/`layouts.portal`, the dotted-path convention) and `grep -rln "x-layouts.public"` (6 hits, all static Blade pages) vs. zero hits for `x-layouts.portal`.
- **Why it's a problem:** Any header/footer/nav change (like fixing F-002) needs to be applied in the *public* pair twice to keep both code paths in sync, and the *portal* component copy can be deleted outright since nothing references it.
- **User impact:** None directly today (they're currently identical), but this is exactly the kind of duplication that silently drifts — someone fixes the mobile nav in one copy, forgets the other, and now half the site has the fix and half doesn't.
- **Business impact:** Maintenance cost / regression risk, not a live user-facing bug today.
- **Severity:** Medium.
- **Possible solution:** Delete `resources/views/components/layouts/portal.blade.php` outright (unused). For the public pair, pick one Blade convention and stop duplicating — e.g., make `resources/views/layouts/public.blade.php` itself the single source, and have `<x-layouts.public>` (an anonymous component) just be a thin wrapper/alias for it, or standardize all the Livewire `->layout()` calls onto the dotted path that already matches the component's actual location.
- **Implementation notes:** Small, mechanical refactor; do it *before* fixing F-002 so the mobile-nav fix only has to be written once.

#### [F-008] `WorkOrder::invoice()` is a `HasMany` relation used everywhere as if it were one-to-one
- **What I noticed:** `public function invoice(): HasMany { return $this->hasMany(Invoice::class); }` — singular relation *name*, plural relation *type*. Every call site compensates by manually calling `->first()` or `->doesntExist()`.
- **Where:** `app/Models/WorkOrder.php:56-59`; consumed at `app/Filament/Resources/WorkOrderResource.php:116` (`$r->invoice()->doesntExist()`), `resources/views/livewire/portal/vehicle-detail.blade.php:85,110-112` (`$wo->invoice->first()`).
- **Why it's a problem:** Nothing in the schema (`invoices` table has no unique constraint on `work_order_id`) actually prevents a `WorkOrder` from ending up with 2+ invoices, so `HasMany` is technically "correct" for what the DB allows — but the business rule (one work order, one invoice) is enforced only by application logic (`visible(fn (WorkOrder $r) => ... && $r->invoice()->doesntExist())` gating the "Complete & Invoice" button), not by the data model. Combine that with F-014's global (non-per-work-order) cache lock and there's a plausible (if narrow) race where the invariant could be violated.
- **User impact:** Low today (would require a specific race condition to hit), but if it ever did happen, `->invoice->first()` would silently show only one of two invoices to a customer, hiding a billing bug rather than surfacing it.
- **Business impact:** Billing data integrity risk, low probability but high consequence if it ever occurs (a customer under- or over-billed / confused about which invoice is real).
- **Severity:** Medium.
- **Possible solution:** Rename to `invoices(): HasMany` for honesty, *or* change to a real `hasOne()` and add a DB-level unique index on `invoices.work_order_id` so the one-invoice-per-work-order rule is actually enforced, not just conventionally assumed.
- **Implementation notes:** If switching to `hasOne()`, audit the few call sites listed above to drop the now-unnecessary `->first()`.

#### [F-009] `TodayScheduleWidget` table column layout breaks on longer bay names
- **What I noticed:** In the Filament admin dashboard's "Today's Schedule" widget, the row for "Bay 3 (Diagnostics)" visually runs directly into its "No appointments" cell with no gap — verified live screenshot.
- **Where:** `resources/views/filament/widgets/today-schedule.blade.php:9-11` — `<table class="w-full text-sm">` (no `table-fixed`) with `<th class="py-2 pr-4 w-32">Bay</th>`; the `w-32` is only a *suggested* width on an auto-layout table, so a longer bay name simply expands the column instead of wrapping/truncating, and the second column has no matching left padding/border to compensate visually.
- **Why it's a problem:** Purely a custom-Blade-widget layout bug — the rest of the Filament panel uses Filament's own themed table components, which don't have this issue; this widget hand-rolls its own `<table>` and inherited none of that polish.
- **User impact:** Minor readability annoyance for shop staff glancing at today's board — the bay name and status text visually blur together for the one longer bay label.
- **Business impact:** Low — cosmetic, on an internal staff-only tool, but a "front counter" dashboard that staff might glance at all day.
- **Severity:** Low.
- **Possible solution:** Add `pr-4` (already present on the `<th>` and first `<td>`... `[recheck: the first `<td>` does have `py-3 pr-4`, so the real cause is more likely the `w-32` not being enforced without `table-fixed` — worth a quick `table-fixed` + explicit `<colgroup>` fix]`) or simpler: swap to Filament's own table/section primitives instead of a hand-rolled `<table>`.
- **Implementation notes:** Small CSS fix (`table-fixed` + explicit column widths, or just add more `pr-` spacing) — 10-minute fix.

#### [F-010] Two "dead" but fully-written Blade views left in the repo (content-drift risk)
- **What I noticed:** `resources/views/welcome.blade.php` (Laravel's default starter view, but rewritten with alternate real marketing copy — different headline, different 3-feature-grid content than the actual live homepage) and `resources/views/invoices/show.blade.php` (a nicely-designed, fully-wired-looking public invoice template using the site's normal header/nav/footer chrome) are **both unreferenced by any route** — confirmed via `grep -rn "view('welcome'" `and checking `routes/web.php`'s actual `/invoices/{token}` handler, which renders `public.invoice` (a different, bare-HTML-document template), not `invoices.show`.
- **Where:** `resources/views/welcome.blade.php`, `resources/views/invoices/show.blade.php`; live route is `routes/web.php:55-61` → `view('public.invoice', ...)` → `resources/views/public/invoice.blade.php`.
- **Why it's a problem:** Both dead files look intentional and complete enough that a future developer could easily believe one of them is "the" current homepage or "the" current invoice page, edit it, and be confused when their change never shows up live.
- **User impact:** None directly (unreachable), but indirectly risky for future changes.
- **Business impact:** Wasted future dev time; content/design drift (the "real" homepage and invoice page could diverge further from these orphans over time, making the confusion worse the longer they're left in).
- **Severity:** Medium (process/maintainability, not user-facing today).
- **Possible solution:** Delete both, or if one is actually preferred (e.g., `invoices/show.blade.php`'s design, which reuses the site's normal chrome, arguably looks *more* on-brand than the bare-document `public/invoice.blade.php` that's actually live), swap the route to use it and delete the other.
- **Implementation notes:** Quick — just confirm neither is referenced anywhere else (I checked `routes/web.php` and did a repo-wide `grep` for `view('welcome'` / `view('invoices.show'` and found no call sites) before deleting.

#### [F-011] Booking confirmation claims an email was sent — it wasn't (and can't be, as configured)
- **What I noticed:** The final booking-success screen says "A confirmation has been sent to `{{ $appt->customer?->user?->email }}`." No `Mail::` / `Notification::` send call exists anywhere in `BookAppointment.php` or the `AppointmentAvailabilityService`.
- **Where:** `resources/views/livewire/book-appointment.blade.php:326-328`; `.env` has `MAIL_MAILER=log`, meaning even if a send *were* attempted, it would only write to a log file, never actually deliver.
- **Why it's a problem:** The UI asserts something happened that provably did not.
- **User impact:** Customer expects an email confirmation, waits for one, never gets it, may worry the booking didn't actually go through (undercutting the "transparent" brand promise).
- **Business impact:** Support-ticket generator ("I never got my confirmation email") once real users hit this.
- **Severity:** Medium.
- **Possible solution:** Either implement the actual notification (Laravel `Notification`/`Mailable`, straightforward given the data already available) or soften the copy until it's real ("You'll see this appointment in your portal" instead of promising an email).
- **Implementation notes:** `[ASSUMPTION: production would swap MAIL_MAILER to something real — but the missing send-call is a code gap regardless of mailer config]`.

#### [F-012] Ops-alert webhook is wired to a config key that doesn't exist — feature is permanently a no-op
- **What I noticed:** `OperationsAlertService::dispatchPendingAlerts()` reads `config('services.truewrench.webhook_url')`, but `config/services.php` has no `'truewrench'` array at all.
- **Where:** `app/Services/OperationsAlertService.php:92`; `config/services.php` (whole file, no matching key).
- **Why it's a problem:** Not a crash (Laravel returns `null` for a missing nested config key), but it means the "send this alert to an external system" code path can never actually run as shipped — every alert silently takes the "no webhook configured, log only" branch, forever, unless someone manually adds the config + `.env` var later and happens to know the exact key name expected.
- **User/business impact:** Ops alerts (unconfirmed-appointment / stuck-work-order notifications) never leave the app's log file — no Slack ping, no email, no external paging — even though the code is clearly written to support exactly that. Staff relying on "I'll get pinged if a car's been stuck 8 hours" would not, in fact, get pinged.
- **Severity:** Medium.
- **Possible solution:** Add the `services.truewrench.webhook_url` (or whatever the intended integration is — Slack incoming webhook? generic HTTP endpoint?) entry to `config/services.php` reading from a new `.env` key, and document it in `.env.example`.
- **Implementation notes:** One-line config addition once the intended destination is known; `[QUESTION: was this meant to hit Slack specifically? config/services.php already has an unrelated 'slack' => ['notifications' => [...]] block from Laravel's default scaffold that's otherwise unused — could this have been the intended target instead of a generic webhook?]`.

#### [F-013] No way for staff to manage customer/user accounts from the admin panel
- **What I noticed:** Filament's registered resources are Appointment, Invoice, Mechanic, OperationsAlert, ServiceBay, ServiceType, ShopHour, WorkOrder — no `UserResource` or `CustomerResource`.
- **Where:** `app/Filament/Resources/*` (full directory listing) + `AdminPanelProvider`'s `discoverResources()`.
- **Why it's a problem:** Combined with F-003 (no password reset) and F-001's account-hijack risk, staff have no operational lever at all to look up a customer's account, fix a bad email, or help someone locked out.
- **User impact:** A customer who calls the shop with an account problem cannot be helped by anyone at the shop through the software.
- **Business impact:** Support burden falls entirely outside the system (manual DB edits via `tinker`, presumably), which doesn't scale past a demo.
- **Severity:** Medium.
- **Possible solution:** Add a read/edit `CustomerResource` (and maybe a restricted `UserResource` for admins only) with at least: view contact info, edit address/phone, trigger a password reset email.
- **Implementation notes:** Should be `Admin`-only (gate via `UserRole::Admin`), since it'd expose PII.

#### [F-014] Global (non-scoped) cache lock serializes all work-order completions shop-wide
- Already detailed under 3.23 — folding the "else to investigate" here: worth checking whether the same global-vs-scoped-lock issue exists anywhere else that uses `Cache::lock(...)` — I found exactly one other lock usage (`AppointmentAvailabilityService::book()`, `'truewrench:booking:{date}'`) and that one **is** correctly scoped per day. So this pattern is done right in one place and wrong in another — inconsistent, not a systemic habit.

#### [F-015] Filament admin panel is unbranded ("Laravel" instead of "TrueWrench")
- **What I noticed:** The admin panel's sidebar brand text and the browser tab title both read "Laravel" — verified live at `/admin/login` and post-login dashboard.
- **Where:** `app/Providers/Filament/AdminPanelProvider.php` — no `->brandName(...)` or `->brandLogo(...)`/`->favicon(...)` call; only `->colors(['primary' => Color::Amber])` was customized.
- **Why it's a problem:** Every other surface of the app (marketing, portal, public invoice) is fully "TrueWrench"-branded; the staff-facing admin panel is the one place that still says "Laravel."
- **User impact:** Low (internal tool, staff know what app they're using), mostly a polish/professionalism gap.
- **Business impact:** Minor, but noticeable if a shop owner ever screenshots or demos the admin panel.
- **Severity:** Low.
- **Possible solution:** `->brandName('TrueWrench')` (one line) in `AdminPanelProvider::panel()`.

---

## 5. Code-vs-live mismatches
- The most significant one: **source defines `bg-brand-600`/`ring-brand-500`/etc. correctly, but the currently-built CSS doesn't contain them** — see F-004. Source is "right," the build artifact is stale. This is the textbook case for this section.
- `resources/views/welcome.blade.php` reads like a homepage but is not the live homepage — see F-010. Source exists and is well-formed; it's simply never rendered by any route.
- `resources/views/invoices/show.blade.php` is a nicer-chrome invoice page that is not the live invoice page (`public/invoice.blade.php` is) — same shape as above.

## 6. Missing pages & states inventory
| Page/state | Present? | Notes |
|---|---|---|
| Home | ✅ | `marketing/home.blade.php` |
| About | ✅ (but orphaned from nav) | see F-002 |
| Services | ✅ | linked correctly from home + nav |
| Contact | ✅ (but orphaned from nav, and form is dead) | see F-002, F-005 |
| Booking flow | ✅ | 5-step wizard, solid |
| Customer login | ✅ (button invisible due to F-004) | |
| Password reset | ❌ | see F-003 |
| Customer registration (explicit) | ❌ (implicit only, via guest booking) | see F-001/F-003 |
| Customer portal (dashboard/vehicles/appointments/invoices) | ✅ | all 4 present and working |
| Public invoice view | ✅ | token-based, unguessable URL — good |
| Privacy policy | ❌ | flagged as a pre-launch gap given PII collection |
| Terms of service | ❌ | same |
| 404 page | `[COULDN'T CHECK]` | didn't force one |
| 500/error page | `[COULDN'T CHECK]` | `APP_DEBUG=true` in shipped `.env` — flag for deploy checklist |
| Admin panel (Filament) | ✅ | unbranded, see F-015; no User/Customer resource, see F-013 |
| Empty states (vehicles/appointments/invoices/slots) | ✅ | all present, good copy |
| Mobile nav | ❌ | see F-002, sitewide |

## 7. Open questions & things to verify
- `[QUESTION]` Is there CI anywhere outside this repo that runs the Pest suite / Pint / an asset rebuild? Found nothing in-repo.
- `[QUESTION]` Was the `config('services.truewrench.webhook_url')` meant to be a Slack webhook, given the otherwise-unused `services.slack` block already sitting in `config/services.php`? (F-012)
- `[QUESTION]` Is "zero-bullshit customer service" on the About page an intentional brand-voice choice or an oversight? (3.9)
- `[NEEDS DATA]` Real Lighthouse/PageSpeed numbers — not measured.
- `[NEEDS DATA]` Real color-contrast measurements (eyeballed only).
- `[COULDN'T CHECK]` Full screen-reader pass (NVDA/VoiceOver).
- `[COULDN'T CHECK]` 404/500 custom error pages.
- `[COULDN'T CHECK]` Booking wizard's internal step content at a 375px viewport (only checked the header/nav chrome at mobile width).
- `[COULDN'T CHECK]` Whether Filament's `InvoiceResource` (no `create` page registered) shows a broken/dead "New" button in the list header, or whether Filament's default `canCreate()` correctly infers there's no create route and hides it. Worth a 30-second manual check in the admin panel.
- `[UNVERIFIED]` Whether "Instrument Sans" (loaded via Bunny Fonts in `vite.config.js`) is actually applied anywhere, given `app.css`'s theme variables reference "Inter" by name instead.
- `[UNVERIFIED]` The step-1 service-checkbox click-target oddity noted in 3.16 — possibly a testing-tool artifact, not a real bug; worth a manual click-through to confirm one way or the other.

## 8. Rough opportunities pile
- Fix nav anchors, rebuild CSS, wire/remove contact form, delete dead views, brand the admin panel — all quick wins, all independent of each other, all could ship same-day.
- Consolidate the 4 layout files into 2 (or 1 with two thin call-site wrappers) before doing any more header/nav work, so fixes don't need to be applied twice.
- Add a regression test for F-001 before anything else on this list — it's the one finding that actually warrants stopping other work for.
- Password-reset flow + a `CustomerResource` in Filament would close out most of the "customer got stuck, staff can't help" gap in one project.
- Extract `<x-button>` / `<x-status-badge>` components — cuts future class-drift risk (would have shrunk F-004's blast radius) and repeated markup (3.22).
- LocalBusiness JSON-LD schema — free SEO, all the source data (address/hours/phone) already exists in the footer.
- Meta descriptions + OG tags on every page — currently entirely absent.
- A "last updated Xs ago" indicator on the `wire:poll`-driven vehicle-detail page, so users know it's live.

## 9. Assumptions log
- Assumed a real deployment would flip `APP_ENV`/`APP_DEBUG` appropriately — flagged only because the shipped `.env` defaults to the insecure-for-prod values and that's exactly the kind of default that "forgets" to get changed.
- Assumed the seeded demo data (`admin@truewrench.demo`, etc.) is intentional and fine as-is for a dev/demo environment — not treated as a finding on its own, only the *pattern* it shares with production code paths (F-001/F-003) is.
- Assumed business-impact severity as if this were about to go live for a real shop, per the audit request ("check the full codebase thoroughly ... find each and every issue") — a few of these (F-003, F-013) would be lower priority if this is explicitly staying a portfolio/demo project forever.
- Treated the two duplicate-but-currently-identical layout file pairs as a maintainability risk rather than a live bug, since they render identically today.
