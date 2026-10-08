# MasterLocknKey — מהשינויים בגיט לפריסה חדשה ב־SiteGround

היעד המתוכנן הוא אתר העסק `masterlocknkey.com`, ומערכת פנימית ב־`crm.masterlocknkey.com`. אלו כתובות יעד שבחרת; הן לא נרכשו או נוצרו דרך העבודה הזו. ההתקנה חדשה וריקה, ללא מעבר נתוני עסק קודם.

## א. הכנת החשבון לפני העלאת ה־CRM

1. רכוש או חבר את הדומיין לחשבון SiteGround. ודא שה־DNS מצביע לאתר הנכון ושכתובות www ומסלול הדומיין הראשי מוסדרים.
2. צור אתר WordPress עבור הדומיין הראשי באמצעות כלי ההתקנה בחשבון. אל תתקין את ה־CRM בתיקיית WordPress.
3. צור תת־דומיין `crm.masterlocknkey.com` ואפשר גם תת־דומיין בדיקות נפרד, למשל `crm-test.masterlocknkey.com`, לפי אפשרויות החבילה שלך.
4. ב־Site Tools חפש את PHP Manager ובחר PHP 8.3 עבור ה־CRM. בדוק גם PHP ב־CLI; שינוי גרסת הווב אינו בהכרח משנה את פקודת `php` ב־SSH.
5. דרוש MySQL 8 ומעלה והרחבות PHP: `pdo_mysql`, `mbstring`, `gd`, `zip`, `fileinfo`. בדוק באמצעות doctor אחרי התקנה. גרסאות אחרות לא אומתו כאן.
6. צור מסד **ריק ונפרד** ל־CRM ומשתמש מסד שמורשה רק אליו. השאר את מסד WordPress ומשתמשו נפרדים. רשום את שמות המסד והמשתמש כפי ש־SiteGround יצר, כולל קידומות.
7. הנפק תעודת SSL עבור הדומיין והתת־דומיין והפעל גישה ב־HTTPS. אל תפעיל טופס או webhook על HTTP.
8. הכין SFTP או SSH Key דרך החשבון, והתחבר בנתיב ובפורט שהחשבון מציג. אל תכניס סיסמאות ל־Git או לצ׳אט.
9. בדוק שיש אפשרות לעבוד עם תיקייה פרטית לצד `public_html`, והרשאה ל־Cron Jobs. אם File Manager לא מאפשר את הנתיב, השתמש ב־SFTP/SSH. אם החבילה אינה מאפשרת זאת, דרוש פתרון מהספק לפני פריסה; אל תעביר סודות אל public_html כדי לעקוף את ההגבלה.
10. כבה cache של תוכן דינמי ב־CRM, או החרג את כל מסכי המערכת, `/api/*`, `/health`, `/login` ו־`/storage/*`. עמודי ה־CRM שולחים `Cache-Control: no-store`; ודא שה־CDN/cache בחשבון מכבד זאת. באתר WordPress אל תגיש nonce של הטופס מתוך cache שפג תוקפו.

שמות המסכים הם הכוונה לכלים המקבילים בחשבון שלך. לא הייתה גישה לחשבון SiteGround ולא אומתה החבילה שלך. ניסיון לקרוא את עמודי התמיכה של SiteGround מהסביבה הזו החזיר 403; לכן נתיב בינארי PHP, תוכנית אחסון וגרסת MySQL בפועל חייבים להיבדק בחשבון.

## ב. מה יש בחבילת ההעלאה

```text
masterlocknkey-siteground.zip
├── public_html/
│   ├── index.php
│   ├── .htaccess
│   └── assets/              # CSS מוכן, JavaScript ותמונות ממשק
├── crm-private/
│   ├── app/, views/, libs/
│   ├── autoload.php, bootstrap.php
│   ├── config/
│   │   └── local.example.php
│   ├── database/migrations/
│   ├── bin/
│   └── storage/             # לוגואים, מלאי ו־PDF; מחוץ לתיקייה הציבורית
├── instructions/
├── BUILD-INFO.json
└── SHA256-MANIFEST.json
```

קבצי config בפועל, סיסמאות, מפתחות, נתוני לקוחות, `.git` ו־node_modules אינם כלולים. `public_html` ו־`crm-private` חייבים להיות **אחים באותה תיקייה**. אין להעלות את כל הרפוזיטורי לתוך public_html.

לדוגמה בלבד, בהתאם לנתיב בפועל בחשבון:

```text
/home/customer/www/crm.masterlocknkey.com/
├── public_html/
└── crm-private/
```

העלה את תוכן public_html לתיקייה הציבורית של תת־הדומיין. העלה crm-private לצידה, לא בתוכה. שמור את instructions ואת manifest מחוץ לתיקייה הציבורית. ודא שגם `.htaccess` הועלה — כלי העברה לעיתים מסתירים קבצים המתחילים בנקודה.

## ג. יצירת הגדרות פרטיות

העתק `crm-private/config/local.example.php` אל `crm-private/config/local.php`. ערוך רק את העותק הפרטי:

- `APP_NAME`: `MasterLocknKey CRM`.
- `APP_URL`: `https://crm.masterlocknkey.com`.
- `APP_TIMEZONE`: אזור הפעילות האמיתי; ברירת המחדל כרגע `America/New_York`.
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`: הערכים מהחשבון.
- `REQUIRE_HTTPS=true`, `APP_DEBUG=false`.
- `WEBSITE_ENABLED=false` עד בדיקת החיבור לאתר.
- `RETELL_ENABLED=false`, ללא API key וללא agent mapping. אין להפעיל בשלב זה.
- Google Places הוא אופציונלי. המפתח מגיע לדפדפן, ולכן הגבל אותו לפי דומיינים ו־API ומכסות בחשבון Google. אין צורך בו לצורך כתובת ידנית.

קובץ ההגדרות הוא PHP שמחזיר array. בדוק תחביר לפני השמירה. הרשאות מומלצות: תיקיות 755, קבצי קוד 644, ו־local.php 600 אם משתמש PHP הוא הבעלים, או 640 עם קבוצה מתאימה. storage צריך להיות כתיב למשתמש PHP. אין להשתמש ב־777.

## ד. הכנת המסד והחשבון הראשון

דרך SSH, מצא את פקודת PHP המתאימה וודא שהיא 8.3:

```bash
command -v php
php -v
```

בפקודות הבאות החלף את `php` בנתיב הבינארי לגרסה הנכונה אם דרוש. מתוך התיקייה הפרטית:

```bash
cd /actual/path/to/crm-private
php bin/migrate.php
php bin/doctor.php
```

המיגרציה בודקת שמסד ללא ניהול גרסאות הוא ריק. היא מסרבת להפעיל התקנה חדשה על מסד עם טבלאות קיימות. כל מיגרציה מסומנת עם checksum, כך שהרצה חוזרת אחרי הצלחה מדלגת עליה. DDL של MySQL אינו transaction מלא: אם הייתה שגיאה באמצע, עצור וקרא את השגיאה; אל תמחק טבלאות כדי "לנסות שוב" בלי אבחון.

יצירת מנהל חדש עם סיסמה של לפחות 12 תווים:

```bash
read -r -s -p 'New administrator password: ' mlk_admin_password
printf '\n'
printf '%s' "$mlk_admin_password" | php bin/create-admin.php admin
unset mlk_admin_password
php bin/create-company.php MasterLocknKey
```

הפקודה אינה מאפסת משתמש קיים. רשום את Company ID שהיא מחזירה; בהתקנה נקייה הוא אמור להיות 1, אך השתמש תמיד במזהה שנוצר בפועל.

לאחר כניסה דרך `/login`, מלא ב־Settings → Companies את הטלפון, כתובת החברה, המייל והלוגו האמיתיים. הזן מקורות לידים, טכנאים, עמלות ומלאי התחלתי. לא הוזנו מספר טלפון, כתובת עסק או הבטחות שירות מומצאות.

## ה. Cron Jobs

בכלי Cron Jobs בחשבון, הגדר הפעלות קצרות, ללא daemon או שרת Node קבוע:

- כל דקה, או המרווח הקצר שהחבילה מאפשרת: `bin/process-events.php 25`.
- פעם ביום: `bin/cleanup-events.php`.

דוגמה בלבד — החלף את שני הנתיבים בנתיבים שנבדקו בחשבון:

```cron
* * * * * /actual/path/to/php83 /actual/path/to/crm-private/bin/process-events.php 25 >> /actual/private/path/event-worker.log 2>&1
15 3 * * * /actual/path/to/php83 /actual/path/to/crm-private/bin/cleanup-events.php >> /actual/private/path/event-cleanup.log 2>&1
```

תזמון Cron לפי אזור הזמן של השרת; בדוק אותו בחשבון. שמור לוגים מחוץ ל־public_html והגדר סיבוב/ניקוי. כישלון worker מחזיר exit code שונה מאפס; אירוע כושל ננסה עד חמש פעמים ונראה במונה במסך Website & Calls. לאחר אבחון אפשר להחזיר אירוע לתור עם `php bin/retry-event.php EVENT_ID`.

## ו. בדיקות לפני פתיחה

1. `doctor.php` עובר; `/health` מחזיר `status: ok` דרך HTTPS בלבד.
2. כניסה, יציאה וסיסמה שגויה; מסכים מוגנים אינם נגישים ללא כניסה.
3. לקוח → ליד → תזמון → חומר → השלמה → חשבונית PDF → דוח.
4. שינוי שם/לוגו החברה אינו משנה חשבונית שכבר נוצרה. שינוי פרופיל עמלה אינו משנה עבודה שהושלמה.
5. פרטי config, bin, SQL ו־storage אינם נגישים כקבצים ציבוריים. העלאת תמונה פועלת דרך הנתיבים המזוהים של האפליקציה.
6. חבר את WordPress לפי המדריך הנפרד. שלח פנייה אחת; המתן לתורים; אשר במסך Website & Calls; בדוק שנוצר ליד יחיד.
7. השאר Retell כבוי. בקשה אל endpoint השיחות צריכה להחזיר integration_disabled.
8. הגדר גיבוי מסד ו־storage מחוץ לשרת, ובדוק שחזור למסד אחר. הורדת גיבוי מהמערכת כוללת נתונים וקבצים שהועלו, אך אינה כוללת סודות שרת.

הבדיקות כאן אינן אישור חוקי למסמכי מס במדינת הפעילות. המערכת נשארת באנגלית ו־PDF עברי דורש התאמה נפרדת.

## ז. עבודה דרך Git ועדכונים

GitHub הוא מקור הקוד; אין חובה לחבר אותו אוטומטית ל־SiteGround. ברירת המחדל היא פריסה ידנית של release שנבדק. אין לשמור local.php, סיסמאות WordPress או גיבויי לקוחות ב־Git.

במחשב העבודה:

```bash
git pull --ff-only origin main
npm ci --ignore-scripts
npm run build:css
python3 bin/prepare-public.py
# הרץ את הבדיקות המתאימות על מסד מקומי נפרד.
git status --short
git add <files-you-reviewed>
git commit -m "Describe the change"
git push origin main
python3 bin/build-release.py
```

לאחר מכן: גיבוי → סביבת בדיקות → העלאת קוד מאותה גרסה → מיגרציות → doctor ובדיקת תהליך → ייצור. אל תדרוס local.php או storage בזמן עדכון. BUILD-INFO מציין את commit שנארז. שחזור קוד בלבד אינו מחזיר שינויי מסד; תכנן תאימות או שחזור מסד יחד עם כל מיגרציה.
