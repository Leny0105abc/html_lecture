# CodeLab on the computer-lab network

One PC runs PHP, this project, and its database. Every student opens the server's
LAN address in a browser. Lessons, logins, coding checks, quizzes, autosaves,
submissions, teacher feedback, and reports work without an internet connection.
The server must stay powered on and connected; students still need the local
network. This is not an offline copy on each student PC.

## Prepare the server once

Install PHP 8.3+, Composer, Node.js, and the project's dependencies while internet
is available. Follow the README installation instructions. Keep the database on
the server; clients do not need PHP, Node.js, or database access. This existing
installation keeps using its current database and accounts.

Build the frontend once (and again after frontend updates):

```powershell
npm run build
```

The build includes JavaScript, CSS, and fonts locally. Runtime does not require
npm or Vite. Stop `composer run dev` / `npm run dev` before starting LAN mode. If
`public/hot` remains after stopping Vite, remove only that file and rebuild.
The launcher refuses to run while that file exists, so clients cannot accidentally
receive localhost Vite URLs.

Use a DHCP reservation or a static address for the server. Use the actual Ethernet
or Wi-Fi address on the lab network, not the Herd `.test` name or `localhost`.
Change the seeded teacher password before students use the server. Back up the
database and uploaded files regularly. Do not rerun the full database seeder on
an existing lab database: it can reset development accounts.

## Try LAN access on Windows

From the project folder:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\start-lan.ps1
```

The launcher prints the student URL. If the server has multiple network adapters,
it asks you to select the address explicitly:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\start-lan.ps1 -ServerIP 192.168.1.10 -Port 8000
```

Replace the example IP with the server's address. Clients then open
`http://192.168.1.10:8000`. Keep the terminal open; Ctrl+C stops the server.
This launches PHP's built-in server for a connectivity trial. On Windows it
handles one request at a time, so use the web-server setup below for a full class.

The launcher uses process-only settings: debug output off, host-only sessions for
HTTP, offline mode on, email logged locally, and the LAN address as APP_URL. It
bypasses cached configuration without changing `.env` or clearing shared caches.
It does not install dependencies, migrate, seed, or change student records.

If Windows Firewall blocks access, run this once in **Administrator PowerShell**
on the server for a trusted lab network configured as Private:

```powershell
New-NetFirewallRule -DisplayName "CodeLab LAN 8000" -Direction Inbound -Action Allow -Protocol TCP -LocalPort 8000 -Profile Private -RemoteAddress LocalSubnet
```

Use the selected port if different. No router port forwarding is needed.
School IT may need to permit connections between lab clients and the server.

## Regular whole-class hosting

Use Apache or Nginx with PHP on the server, listening on the lab interface. Point
its document root at this project's **public** directory and route missing paths
to `public/index.php`. Never serve the project root, which contains `.env` and
database credentials. Apache must enable rewriting and honor `public/.htaccess`.
Refer to Laravel's deployment configuration:
https://laravel.com/framework/docs/deployment

On a dedicated lab installation, configure `.env` for the chosen address/port:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.10:8000
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
LAB_OFFLINE=true
MAIL_MAILER=log
```

Leave `ASSET_URL` unset. Keep the existing APP_KEY and database settings. Use
MySQL or PostgreSQL locally for a busy lab; do not expose the database port to
students. Run `php artisan config:cache` after configuration changes. The launcher
is not needed when Apache/Nginx serves the app. Use HTTPS and secure session
cookies if school IT provides a local certificate trusted by all clients.

## What still needs external resources

- AI tutoring is disabled in offline mode even if an API key exists.
- Email delivery is disabled; teachers can reset student credentials locally.
- The password breach lookup is skipped in offline mode; password length and
  complexity checks remain active.
- Built-in `placehold.co` sample images render as a locally served lesson image,
  including previews of older lessons and drafts. Saved source is preserved.
- External website links, student-added remote assets, and example media files
  such as `music.mp3` / `video.mp4` need actual accessible files. Add teacher-owned
  media under `public/media` and use paths such as `/media/music.mp3` in activities.

## Verify from a client PC

1. Open the printed URL and sign in as a student.
2. Open a lesson, run code, save, answer the quiz, and submit.
3. Sign in as the teacher on another PC and check that the submission appears.
4. Disconnect the internet uplink while keeping the lab network connected, then
   reload and repeat. Verify the local image in an image lesson too.

If access fails, try the same LAN URL on the server first. Then check the address,
firewall, web-server port, and Wi-Fi client isolation or VLAN rules. A `.test`
hostname will not resolve on other PCs without additional local DNS setup.
