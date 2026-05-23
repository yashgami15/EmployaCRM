# Employa HR - PHP + Bootstrap Candidate Tracker

## Features
- User authentication (login, register, logout)
- Home module with graph + hiring stages + reminders + notifications
- Candidate module with full detailed profile form, resume upload, smart auto-matching suggestions
- Client module with complete CRM fields, follow-up tracking, status updates and reminder creation
- Interview module with interview scheduling + stage tracking
- CSV import and CSV export (filtered/selected candidates)
- Fully responsive sidebar layout and mobile-friendly views
- SQLite database auto-setup with sample seed records and reminders

## Tech Stack
- HTML + CSS + JavaScript
- Bootstrap 5
- PHP 8+
- SQLite (file database)

## Run Project
1. Open terminal in project root.
2. Run:
   ```bash
   php -S localhost:5173 -t public
   ```
3. Open: `http://localhost:5173`

## Subdomain / Hosting Setup
1. Upload all project files (`app`, `config`, `data`, `public`) to one folder (example: `public_html/crm`).
2. Set subdomain document root to the `public` folder:
   - example: `public_html/crm/public`
3. If document root cannot be changed, create a root loader file that includes `public/index.php`.

## Default Admin Login
- Company Name: `employahr`
- Email: `admin@employahr.com`
- Password: `admin@123`

## Database File
- Auto-created at: `data/app.db`

## Optional Branding
- Upload your logo at one of these paths:
  - `public/assets/employa-logo.png` (recommended)
  - `public/assets/logo.png`
  - `public/assets/logo.jpg`
  - `public/assets/logo.webp`
- It will be auto-used in login and sidebar.

## Reminder Notifications
- Website notifications are built-in.
- Email notifications use PHP `mail()` function (requires server mail configuration).
- SMS notifications work when `SMS_WEBHOOK_URL` environment variable is configured.
- For accurate scheduled delivery, add cron on server:
  - URL: `https://your-subdomain/cron-reminders.php?token=YOUR_TOKEN`
  - and set environment variable `CRON_TOKEN=YOUR_TOKEN` for protection.
