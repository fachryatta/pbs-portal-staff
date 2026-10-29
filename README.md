# PBS Portal Staff

A staff portal for Permata Bangsa School, built to centralize staff account access and administrative workflows.

## Features

- Staff registration, login, email verification, and password recovery
- Staff profiles and profile photos
- Staff document upload, preview, and download
- Leave and permission requests with administrative review
- Salary-slip management and PDF generation
- Notifications for staff workflows
- Administrative staff and account management
- Optional Microsoft email integration

## Tech Stack

- PHP 8.2+
- CodeIgniter 4.7
- MySQL / MariaDB
- Dompdf
- SMTP email and optional Microsoft integration

## Requirements

- PHP 8.2 or newer with `intl`, `mbstring`, `mysqli`, and `openssl` extensions enabled
- Composer
- MySQL or MariaDB

## Local Setup

1. Clone this repository and enter the project directory.
2. Install dependencies:

   ```bash
   composer install
   ```

3. Copy `.env.example` to `.env` and configure the database, encryption key, email settings, and any Microsoft integration settings you need.
4. Create a MySQL database named `pbs_staff` (or update the database name in `.env`).
5. Run the database migrations:

   ```bash
   php spark migrate
   ```

6. Set the initial administrator details in `.env` using the `bootstrapAdmin.fullName`, `bootstrapAdmin.username`, `bootstrapAdmin.email`, and `bootstrapAdmin.password` keys. Then create the first administrator:

   ```bash
   php spark pbs:create-first-admin
   ```

7. Start the development server:

   ```bash
   php spark serve --host 127.0.0.1 --port 8080
   ```

8. Open `http://localhost:8080` in your browser.

## Optional Integrations

- Configure SMTP settings to enable verification and recovery emails.
- Configure Microsoft tenant/client settings and the matching redirect URI if Microsoft email integration is required.
- Keep all credentials in `.env`; do not commit secrets or uploaded staff documents.

## Notes

This repository contains application code only. It does not include a production database, staff records, uploaded documents, salary slips, or user accounts.
