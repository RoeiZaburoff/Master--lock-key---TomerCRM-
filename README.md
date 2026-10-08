# MasterLocknKey CRM

PHP 8.3 + MySQL 8 CRM for a new MasterLocknKey business. Production serves **only public/**; code, database migrations, uploads and secrets live outside the web root. No Composer or Node runtime is needed on SiteGround.

- [הכנה ופריסה על SiteGround](docs/SITEGROUND-HE.md)
- [אתר WordPress והחיבור ל־CRM](docs/WORDPRESS-HE.md)
- [תשתית שיחות עתידית עם Retell AI](docs/VOICE-ARCHITECTURE-HE.md)
- [בדיקות והפעלת סביבת פיתוח](docs/DEVELOPMENT-HE.md)
- [תוצאות אימות](docs/VALIDATION-HE.md)
- [המלצות וחלופות ל־CRM](docs/RECOMMENDATIONS-HE.md)

Both integrations are disabled by default. No phone number is purchased, no voice agent is activated and no call is placed by this application. Incoming website requests and future call summaries enter an admin review inbox; they never automatically book a technician or issue an invoice.

This installation supports brands under one operator. It is not a tenant-isolated SaaS for unrelated businesses. Existing English/US address and invoice conventions remain; country-specific billing compliance and Hebrew PDF support require a separate adaptation.

## Local development

Use the existing checkout in the isolated task environment. Do not create a worktree unless explicitly requested.

```bash
cp .env.example .env
# Edit the two passwords in .env.
npm ci --ignore-scripts
npm run build:css
python3 bin/prepare-public.py
docker compose --env-file .env -f deploy/compose.yaml up -d --build
docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/migrate.php
```

See the development guide for administrator creation and tests. For shared hosting build the upload archives with `python3 bin/build-release.py`; the generated files are in `dist/` and are excluded from Git.
