# CodeLab Academy

CodeLab Academy is a responsive HTML and CSS learning platform for teachers and students. Students follow a locked learning path, write code in a live workspace, preview it in a sandboxed iframe, save drafts, submit activities, and receive feedback. Teachers create accounts, monitor progress, review versioned submissions, unlock lessons, and export reports.

## Features

- Username-based authentication with rate limiting, secure password hashing, disabled-account checks, and role authorization
- Teacher-generated student usernames and cryptographically secure five-letter temporary passwords
- 15 seeded lessons: five HTML-only Basic lessons, five HTML-only Moderate lessons, and five Advanced HTML/CSS lessons
- Five-question quiz per lesson; a score of at least 4/5 plus a passing coding check unlocks the next lesson
- Lesson-specific, non-exact-match HTML/CSS checks with actionable feedback
- Four editable HTML files plus a stylesheet in the final lesson
- Sequential server-enforced lesson and case-study unlocking
- Responsive HTML/CSS editor with isolated output, save, debounced autosave, reset, submit, and fullscreen preview
- Optimistic save versioning to prevent stale drafts from overwriting newer work
- Five progressive case studies with persistent workspaces and rubrics
- Versioned submissions, teacher feedback, completed/revision review states, and activity history
- Teacher dashboard, student dashboard, individual reports, CSV export, and printable reports
- Optional server-side AI tutor with a graceful disabled state when no API key is configured

## Technology

- PHP 8.3+, Laravel 13, Fortify, Inertia.js
- React 19, TypeScript, Tailwind CSS 4, Radix UI
- SQLite by default; MySQL and PostgreSQL are supported through Laravel configuration
- Pest for backend and authorization tests

## Local installation

```bash
composer install
copy .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
npm run build
composer run dev
```

Open the configured `APP_URL`. In Laravel Herd, this project is available at `http://studproj_html_css.test/`.

## Database

The default `.env.example` uses SQLite. This local project is configured for MySQL. To use MySQL on a new installation, create a database and database user, then set:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=studproj_html_css
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password
```

On Hostinger, use the full database and username (including their account prefixes) and the database host shown in the hosting dashboard. Configure the hosted `.env` separately from the local `.env`. Neither credentials nor local database backups belong in Git.

To use PostgreSQL instead, set:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=codelab
DB_USERNAME=postgres
DB_PASSWORD=
```

Then run:

```bash
php artisan migrate --seed
```

The schema covers users, lessons, case studies, lesson and case-study progress, versioned submissions, teacher feedback, and activity logs. Foreign keys, unique constraints, indexes, and transactions protect progress integrity.

To update an existing installation without recreating accounts or student work, run:

```bash
php artisan migrate --force
php artisan db:seed --class=CurriculumSeeder --force
npm run build
```

The curriculum seeder updates lesson numbers 1–15 in place. It does not seed users or delete progress, saved code, or submissions. Previously completed lessons remain completed. Students with older in-progress code can use Reset in the editor to start from the new lesson's starter page.

## Environment variables

```env
APP_NAME="CodeLab Academy"
APP_URL=http://studproj_html_css.test
DEV_TEACHER_PASSWORD=teacher123
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4.1-mini
```

`OPENAI_API_KEY` is optional and is only read by the server. Never prefix it with `VITE_` or commit a real key.

## Development accounts

After seeding, the teacher username is `teacher`. Its development password comes from `DEV_TEACHER_PASSWORD`. The `mia.student` password is generated randomly during every seed and printed once in the seed command output. Production credentials are never hard-coded.

## Quality checks

```bash
npm run types:check
npm run build
vendor/bin/pint --test
php artisan test
```

Tests cover authentication, authorization, secure account generation, unique usernames, lesson prerequisites, unlocking, code ownership isolation, profiles, and security settings.

## Production and Vercel

Build frontend assets with `npm run build`, configure a production database, set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, trusted mail/session settings, and run `php artisan migrate --force` during release.

Laravel requires a persistent PHP runtime. For Vercel, deploy through a supported Laravel PHP runtime or container adapter and use a managed PostgreSQL provider. Configure all environment variables in the deployment dashboard, set the public entry point to `public/index.php`, and never deploy the SQLite development file. A conventional PHP host, Laravel Cloud, or container platform is also suitable.

## Security notes

- Student HTML is rendered only inside an iframe without script permissions.
- Every student save and submission query is scoped to the authenticated user.
- Teacher routes require the teacher role; student code routes require the student role.
- CSRF protection, validation, login throttling, hashed passwords, secure random temporary passwords, transactions, and stale-save rejection are enabled.
- AI keys and database credentials remain server-side.

## AI tutor

Set `OPENAI_API_KEY` to enable tutoring. The tutor receives the active lesson, activity, HTML, CSS, and student question. Its server prompt prioritizes explanation, hints, and debugging without returning a full graded solution. Without a key, the panel explains that AI is unavailable while all other features continue working.
