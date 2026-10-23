# Project Analysis

This project is a racing club website for RWITC (Royal Western India Turf Club) built with Next.js on the frontend and a PHP/MySQL backend running alongside it. The frontend is in this repository folder, while a lot of the real business logic, APIs, and data access live in the sibling folder `../rwitc_website_api`.

This document explains the project in plain language, what is working, what is risky, and what could be improved.

## 1. High-level project structure

### Frontend app

The main app is under:

- `./src/app/` — page routes and layout
- `./src/app/components/` — reusable page sections and headers
- `./src/services/` — API calls to the PHP backend
- `./src/security/` — browser-side security scripts
- `./src/styles/` — shared styling helpers

### Backend / data layer

The real backend is not inside this folder. It sits next to the frontend in:

- `../rwitc_website_api/`
- `../rwitc_website_api/config/config.php`
- `../rwitc_website_api/ApiSecurity.php`
- `../rwitc_website_api/*.php` for individual API endpoints

This is a common setup in older PHP projects: the site may be served from a legacy PHP project and a modern React/Next.js frontend is added on top, while both still talk to the same MySQL database and asset paths.

## 2. Frontend architecture

### Framework and stack

The app uses:

- Next.js 16
- React 19
- Tailwind CSS
- a set of custom components built in JavaScript
- `react-icons` for icons
- `swiper` and `lottie-react` for motion/media content

Key config:

- `./next.config.mjs` sets `output: 'export'`
- This means the app is intended to be built as a static export, not a server-rendered app with a live Node API on the same server

This is a very important design point: the site is mostly static HTML/CSS/JS, and live business data comes from backend APIs rather than from Node.js server logic.

### Main app pages

The home page is defined in:

- `./src/app/page.js`

It composes sections like:

- Top header
- Hero banner
- Race day buttons
- Media section
- News room
- Feature sections
- Sponsors
- Footer

Other routes exist such as:

- `./src/app/articles/page.js`
- `./src/app/contact/page.js`
- `./src/app/race_details/page.js`
- `./src/app/officialcontact/page.js`
- `./src/app/suggestion/page.js`
- `./src/app/menubar/...`

These are mostly UI screens and not full application logic pages.

### Important front-end files

- `./src/app/layout.js` sets up the overall HTML shell and injects a browser security script
- `./src/app/globals.css` defines a global CSS theme and basic body styling
- `./src/security/WebsiteSecurity.js` blocks copy, cut, paste, drag, and selection on the page
- `./src/services/api.js` defines the shared API URLs and site URLs

## 3. API and service layer

The frontend does not talk directly to a database. It calls PHP endpoints that sit in the legacy backend project.

Examples from the frontend service layer:

- `./src/services/newsService.js` calls `News_get_api.php` and `viewArticles_new.php`
- `./src/services/contactService.js` posts to `email_to_chairman.php`
- `./src/services/moneyLeadersService.js` calls ERP money leaders endpoints
- `./src/services/bannerService.js`, `clubService.js`, `raceResultService.js`, and many others follow the same pattern

The shared API config is in:

- `./src/services/api.js`

This file sets values such as:

- `API_URL`
- `SITE_URL`
- `UPLOAD_URL`
- `NEWS_URL`
- `STATIC_URL`
- `RUN_RACES_URL`

Those values are mostly based on environment variables with a fallback to public production URLs.

## 4. Backend architecture

The backend is a classic PHP application with many API files:

- `../rwitc_website_api/About_get_api.php`
- `../rwitc_website_api/News_get_api.php`
- `../rwitc_website_api/viewArticles_new.php`
- `../rwitc_website_api/email_to_chairman.php`
- `../rwitc_website_api/erp_money_leaders_get_api.php`
- `../rwitc_website_api/race_result_get_api_new.php`
- `../rwitc_website_api/record_timings_get_api.php`
- many others for forms, race cards, reports, clubs, media, and betting data

The pattern is consistent:

1. Read config
2. Connect to MySQL
3. Validate request
4. Run a query
5. Return JSON
6. Optionally cache or rate-limit the response

A useful example is:

- `../rwitc_website_api/viewArticles_new.php` — used to fetch article lists and a single article by `id`
- `../rwitc_website_api/email_to_chairman.php` — validates input, checks reCAPTCHA, saves to database, and sends email

## 5. Security layer

There is a security wrapper in:

- `../rwitc_website_api/ApiSecurity.php`

This class provides:

- IP-based rate limiting
- file-based response caching
- JSON response formatting
- helper methods for consistent error handling

This is a good sign because it shows the backend has at least some protection for public APIs.

The project also has a browser-side anti-copy script:

- `./src/security/WebsiteSecurity.js`

This prevents copy, paste, drag, and selection. It is mostly a content protection feature, not a real security layer.

## 6. Database and data flow

The database connection is configured in:

- `../rwitc_website_api/config/config.php`

This file:

- connects to MySQL with `mysqli`
- sets UTF-8
- loads environment variables from `.env.local`
- reads AWS credentials and other config
- defines connection constants and API base URL

The database is named:

- `rwitc_website`

The backend uses raw SQL queries rather than an ORM or migration system. This is common in older PHP projects but means:

- developer discipline matters a lot
- query safety must be handled manually
- schema changes are harder to track
- the system is more fragile than modern ORM-based apps

The project clearly uses tables such as:

- `articles`
- `email_to_chairman`
- `statt`
- many racing, club, and report tables

## 7. What the user workflow looks like

The user journey is straightforward:

1. The browser loads the Next.js static pages
2. Page components fetch data from PHP endpoints using `fetch()`
3. PHP APIs hit MySQL, S3, or local page files
4. Data is returned as JSON or HTML
5. The frontend renders the result in components

Examples:

- Articles list page loads a list from `viewArticles_new.php`
- Article detail page loads a single article by `id`
- Contact page posts a message and verifies reCAPTCHA before sending mail
- Money leaders page can pull a static file or a structured data response

## 8. Good things in the project

There are several positive points:

- Clear separation of front-end and back-end responsibilities
- Reusable API service layer in `src/services/`
- Rate limiting and caching in the PHP API layer
- reCAPTCHA on the public contact form
- Use of environment configuration instead of hardcoding everything into source code
- Static export setup works well for a public information site
- CSS and component structure is fairly organized for a small-to-medium website

## 9. Potential problems and risks

### A. Secrets are easy to expose

The project includes an environment file:

- `./.env.local`

This file contains production-like credentials for:

- database access
- AWS / S3 access
- site keys
- reCAPTCHA secrets

Even if the file is ignored by Git in some setups, it is still a risk because it sits in a project folder and is loaded by the server. If someone accesses the environment file or deploys the project without proper secret management, the app may leak credentials.

### B. Hardcoded production secrets in PHP files

`../rwitc_website_api/email_to_chairman.php` contains live SMTP credentials and email addresses inline. That is a major operations risk.

This should be moved to environment variables or a secure config file that is not committed to source control.

### C. Public APIs are exposed without strong authentication

Many endpoints in the PHP backend are public and are intended for website content. That is usually fine for content pages, but the same pattern is risky for anything that is sensitive or should not be publicly scraped.

The use of `Access-Control-Allow-Origin: *` on many endpoints means the backend is open to cross-origin requests from any site.

### D. Raw HTML rendering is dangerous

In:

- `./src/app/articles/Article.js`

the project uses `dangerouslySetInnerHTML` to render article content. The code fixes image paths, but it does not sanitize user-generated HTML before inserting it into the page.

If untrusted content ever gets stored in the database, this could create:

- script injection
- malicious image code
- unsafe links
- DOM-based attacks

This is one of the biggest front-end security concerns in the project.

### E. Some legacy code still has security smell

The PHP backend has already been improved in some areas, but there are still signs of older practices:

- broad public API exposure
- direct use of `$_POST` / `$_GET`
- direct file access patterns for static content
- older config assumptions
- dependency on server-local files

The project is not completely modernized.

### F. Lack of automated tests

There is no obvious test suite for:

- API contract validation
- database query safety
- frontend rendering
- broken route behavior
- regression checks for forms and pages

This makes future changes more dangerous.

### G. No strict frontend validation on API responses

The frontend service layer often assumes that the API response has the expected shape.

Examples:

- `data.data || []`
- `json.success` or `json.error`
- `response.ok`

This is fine for quick development, but it is not a fully robust contract. A missing field or malformed response can fail silently or produce a poor user experience.

### H. Client-side fetching can cause slow or flash-of-empty states

Many pages start by rendering a loading state and then call remote APIs. This is acceptable for a content site, but the app is highly dependent on network speed and API uptime.

If the backend is slow or down, users may see blank sections or failed components instead of a graceful fallback page.

### I. Some code still depends on very specific server folder layouts

The backend expects files and folders to exist in a certain structure, including paths like:

- `run_races` data folders
- S3-uploaded assets
- static HTML files
- local config paths

This makes migration or deployment harder. The project is tightly coupled to the hosting environment.

## 10. Areas for improvement

### Security improvements

- Move all secrets to a secure deployment environment or secret manager
- Remove hardcoded SMTP credentials and email addresses from source files
- Restrict CORS to known domains instead of `*`
- Add validation and sanitization for all public endpoints
- Sanitize article HTML before rendering
- Review all public endpoints for authentication or signed access if needed

### Code quality improvements

- Add automated tests for frontend and API behavior
- Define a stable response schema for API calls
- Add logging and monitoring for API failures
- Standardize error handling across all service files
- Centralize endpoint definitions instead of repeating literal URLs in many files

### Frontend improvements

- Improve loading and error states
- Add skeleton loaders or fallback content for each section
- Reduce dependency on fragile browser-only features
- Better support for accessibility and keyboard navigation
- Better performance optimization for large pages and media

### Deployment improvements

- Improve environment separation between local, staging, and production
- Document required backend folder layout and external services
- Add CI/CD checks for linting, build validation, and basic API smoke tests
- Audit which parts of the site are static and which ones are dynamic

## 11. Final assessment

This is a real-world website project with a practical architecture: a modern Next.js frontend for public browsing and an older PHP/MySQL API layer for dynamic data. It is functional and organized around a familiar content-site pattern, but there are important risks around:

- security hardening
- secret management
- unsafe HTML rendering
- backend exposure
- lack of automated testing

Overall:

- Good for a publishing website with a relatively simple content model
- Not yet fully hardened for production-grade security and maintainability
- Worth refactoring gradually into safer, more standardized patterns

## 12. Summary

The project is not a single app in one codebase. It is really two connected systems:

- a Next.js front-end for pages and UI
- a PHP backend for data and forms

They communicate over HTTP, and the PHP backend reads from MySQL and external assets. The project is workable and serviceable, but it still contains several operational and security risks that should be addressed before it is treated as fully production-safe.

This analysis was created for review only and does not modify any source code.

7. Newsroom – Top Stories UI Alignment: Adjusted the story content and navigation buttons to reduce excessive white space and maintain balanced vertical spacing for a cleaner, more user-friendly layout.

8. Hinglish Text Cleanup Audit: Identified transliterated Hindi/Hinglish comments and inline text that should be rewritten in standard English without changing code logic or functionality.

- File: `rwitc_website_api/trainer_statistics_get_api.php`
  - Original Hinglish: `// Heading + table dono HTML me hote hain, isliye as_on null.`
  - English replacement: `// The heading and table both appear in the HTML, so as_on is null.`

- File: `rwitc_website_api/viewArticles_new.php`
  - Original Hinglish: `// ------------------ LIST CASE (existing code same rahega) ------------------`
  - English replacement: `// ------------------ LIST CASE (existing code will remain the same) ------------------`

- File: `rwitc_website_api/erp_handcaps_get_api.php`
  - Original Hinglish: `// Agar already valid UTF-8 hai to touch mat karo`
  - English replacement: `// If it is already valid UTF-8, do not modify it.`
  - Original Hinglish: `// ---------- 1. OLD LOCAL .htm FILE (pehle yahi check hoga) ----------`
  - English replacement: `// ---------- 1. OLD LOCAL .htm FILE (this is checked here first) ----------`

- File: `rwitc_website_api/acceptance_get_api.php`
  - Original Hinglish: `// Agar already valid UTF-8 hai to touch mat karo`
  - English replacement: `// If it is already valid UTF-8, do not modify it.`
  - Original Hinglish: `// Koi .html fallback nahi hai.`
  - English replacement: `// There is no .html fallback.`

- File: `rwitc_website_api/declarations_get_api.php`
  - Original Hinglish: `// Agar already valid UTF-8 hai to touch mat karo`
  - English replacement: `// If it is already valid UTF-8, do not modify it.`
  - Original Hinglish: `// Koi .html fallback nahi hai.`
  - English replacement: `// There is no .html fallback.`

- File: `rwitc_website_api/raceResults_post_race_get_api.php`
  - Original Hinglish: `// ---------- 1. OLD LOCAL .htm FILE (pehle yahi check hoga) ----------`
  - English replacement: `// ---------- 1. OLD LOCAL .htm FILE (this is checked here first) ----------`

- File: `rwitc_website_api/jockey_statistics_get_api.php`
  - Original Hinglish: `// Heading + table dono HTML me hote hain, isliye as_on null.`
  - English replacement: `// The heading and table both appear in the HTML, so as_on is null.`

- File: `rwitc_website_api/jockey_riding_weight_get_api.php`
  - Original Hinglish: `// 1) Pehle rwitc_upload/static/RIDINGWEIGHT.HTM (old page jaisa)`
  - English replacement: `// 1) First, the rwitc_upload/static/RIDINGWEIGHT.HTM file (as in the old page)`
  - Original Hinglish: `//    Is file me heading + saari tables already hoti hain,`
  - English replacement: `//    This file already contains the heading and all tables,`
  - Original Hinglish: `//    isliye as_on null bhejte hain.`
  - English replacement: `//    so we send as_on as null.`

- File: `rwitc_website_api/erp_ratingchange_get_api.php`
  - Original Hinglish: `// ratings_change table se is date ki saari filenames (latest pehle)`
  - English replacement: `// all filenames for this date from the ratings_change table (latest first)`

- File: `rwitc_website_nextjs/src/app/race_details/components/TrackWork.js`
  - Original Hinglish: `// CHANGE 1: URL me date nahi hai to EMPTY rahega (auto aaj ki date nahi)`
  - English replacement: `// CHANGE 1: If there is no date in the URL, it will remain empty (not automatically set to today's date)`
  - Original Hinglish: `// CHANGE 2: date ya id nahi hai to fetch hi mat karo`
  - English replacement: `// CHANGE 2: If there is no date or ID, do not fetch at all.`
  - Original Hinglish: `// CHANGE 3: horse search ka API wala part alag function (router.push ke bina)`
  - English replacement: `// CHANGE 3: the horse search API portion is in a separate function (without router.push)`
  - Original Hinglish: `// CHANGE 4: URL me ?q=byhorse&horsename=xxx ho to page load pe list auto aa jaye`
  - English replacement: `// CHANGE 4: if the URL contains ?q=byhorse&horsename=xxx, the list should appear automatically on page load`
  - Original Hinglish: `// CHANGE 5: horse search pe purana selected trackwork hata do, // sirf dates ki list dikhe`
  - English replacement: `// CHANGE 5: clear the previously selected trackwork on horse search and show only the list of dates`

- File: `rwitc_website_nextjs/src/app/race_details/components/Race_card.js`
  - Original Hinglish: `/* Koi bhi date format -> YYYY-MM-DD */`
  - English replacement: `/* Any date format -> YYYY-MM-DD */`
  - Original Hinglish: `/* Performance profile page ke race result jaisa hi URL banata hai:`
  - English replacement: `/* Builds a URL similar to the race result page for the performance profile:`

- File: `rwitc_website_nextjs/src/app/menubar/horse_race/RecordTimings.js`
  - Original Hinglish: `/* Notes: (bracket wala hissa) bold, baaki normal. Plain text, koi HTML inject nahi hota. */`
  - English replacement: `/* Notes: (the bracketed section) is bold, the rest is normal. Plain text; no HTML injection occurs. */`

- File: `rwitc_website_nextjs/src/app/components/mediasection/MediaSection.css`
  - Original Hinglish: `/* click nahi hoga, sirf UI */`
  - English replacement: `/* click will not happen; only the UI */`

- File: `rwitc_website_nextjs/src/app/components/racedaybuttons/RaceDayButtons.js`
  - Original Hinglish: `// Ye race day pe kabhi nahi dikhenge.`
  - English replacement: `// These will never be shown on race day.`
  - Original Hinglish: `// RACE DAY ke sirf 3 buttons`
  - English replacement: `// Only 3 buttons on race day`

This audit is a documentation-only review and does not change any runtime logic, functionality, or content in the application.

9. Jockey Riding Weight – Comment Cleanup: The remaining Hinglish fallback comments were translated into standard English while preserving the database logic and API behavior.
