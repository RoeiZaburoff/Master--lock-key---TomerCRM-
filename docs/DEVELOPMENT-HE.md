# MasterLocknKey — סביבת עבודה מקומית

השתמש בעותק הקיים של הרפוזיטורי. דרושים Docker עם Compose v2, Python 3 ו־Node 20 ומעלה לצורך בניית CSS בלבד. שרת הייצור אינו דורש Node או Composer.

## התקנה ראשונה

```bash
git clone https://github.com/RoeiZaburoff/Master--lock-key---TomerCRM-.git
cd Master--lock-key---TomerCRM-
cp .env.example .env
```

ערוך את שתי הסיסמאות ב־`.env`, לערכים מקומיים אקראיים שונים. זה קובץ פרטי שמוחרג מ־Git. Docker אינו דורש מפתחות SiteGround, Google או AI.

```bash
npm ci --ignore-scripts
npm run build:css
python3 bin/prepare-public.py
docker compose --env-file .env -f deploy/compose.yaml up -d --build
docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/migrate.php
docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/doctor.php
```

יצירת חשבון מנהל:

```bash
read -r -s -p 'Local administrator password: ' mlk_local_password
printf '\n'
printf '%s' "$mlk_local_password" | docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/create-admin.php admin
unset mlk_local_password
docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/create-company.php MasterLocknKey
```

פתח בדפדפן במחשב שלך את `http://127.0.0.1:8081/login`. HTTP מותר רק בהגדרת Docker המקומית, והפורט קשור ל־loopback. בייצור חובה HTTPS. המערכת מתחילה בלי לקוחות ועבודות ועם Main Warehouse אחד; לא מוזנים נתוני עסק מומצאים.

## שינוי קוד והפעלה חוזרת

התצורה הרגילה מעתיקה קוד לתמונה. אחרי שינוי PHP או views, הרץ שוב `up -d --build`. המסד והקבצים שהועלו נשמרים ב־Docker volumes. אחרי שינוי CSS בנה אותו מחדש לפני בניית התמונה. מיגרציות חדשות הן קבצים חדשים; אין לערוך קובץ שכבר הותקן.

```bash
docker compose --env-file .env -f deploy/compose.yaml logs --tail 50 web db
docker compose --env-file .env -f deploy/compose.yaml exec -T web php bin/migrate.php
docker compose --env-file .env -f deploy/compose.yaml down
```

`down` אינו מוחק את מסד הנתונים. `down -v` מוחק נתונים — אין להשתמש בו כפתרון תקלה בלי לבדוק גיבוי ומה יש במסד.

## בדיקות על מסד בדיקות בלבד

הסקריפטים `business.php`, `security.php` ו־`inbox.php` מוסיפים נתונים. הם דורשים `TEST_ALLOW_DB_WRITE=1` ו־HTTPS כבוי, אבל הדגל אינו מוכיח שהמסד ריק: בדוק בעצמך שזה מסד ניסויים שאינו משמש עסק. השתמש בפרויקט Compose נפרד או בסביבת CI. `security.php` ו־`smoke_http.py --security` משתמשים בשם operator-fixture; התחל במסד נקי לכל ריצת suite.

```bash
docker compose --env-file .env -f deploy/compose.yaml exec -T web php tests/signatures.php
docker compose --env-file .env -f deploy/compose.yaml exec -T -e TEST_ALLOW_DB_WRITE=1 web php tests/business.php
```

הבדיקה העסקית שומרת מזהים ב־`/tmp/mlk-test-ids.json` בתוך הקונטיינר. העתק אותו למחשב לפני בדיקת HTTP. שמור זמנית את סיסמת מנהל **הבדיקות** בקובץ מקומי פרטי עם הרשאות 600, והעבר את הנתיב בלבד:

```bash
docker compose --env-file .env -f deploy/compose.yaml cp web:/tmp/mlk-test-ids.json /tmp/mlk-test-ids.json
python3 tests/smoke_http.py --password-file /actual/private/test-password-file --settings-file /tmp/mlk-test-ids.json
```

בדיקת הרשאות נוספת:

```bash
docker compose --env-file .env -f deploy/compose.yaml exec -T -e TEST_ALLOW_DB_WRITE=1 web php tests/security.php < /actual/private/test-password-file
python3 tests/smoke_http.py --password-file /actual/private/test-password-file --settings-file /tmp/mlk-test-ids.json --security
```

לבדיקת integrations בלבד, הוסף ל־JSON המקומי `website_secret` ו־`retell_secret` אקראיים, וקובץ config מקומי הממפה wordpress-main ו־fixture-agent אל company_a. ה־HTTP suite שולח אירועים חתומים לשרת המקומי, עם מספרי 202-555 לדוגמה; אין API יוצא לספק קול. נדרשת הפעלה מחדש אחרי שינוי config כדי להימנע מ־cache PHP.

הרץ `smoke_http.py --integrations` ואז `php tests/inbox.php` עם דגל הכתיבה. לאחר מכן החזר שני מתאמים ל־false והפעל מחדש. אל תשתמש כאן במפתחות Retell אמיתיים. בדיקת `tests/wordpress.php` דורשת התקנת WordPress מבודדת ב־`/var/www/html`, את התוסף, `MLK_TEST_ALLOW_DB_WRITE=1` ו־constants של חיבור בדיקה; היא מדמה את תעבורת HTTP אך משתמשת בליבת WordPress ובמסד אמיתיים.

## התאמות בין חברות

- Settings → Companies: שם, לוגו, טלפון, מייל וכתובת חברה. company_id נבחר בעבודה.
- Lead Sources / Contractors: עמלות וחלוקת הכנסה; עמלה של עבודה שהושלמה נשמרת עם העבודה.
- Settings → General: עמלת אשראי. גם ערך זה נשמר במדיניות חישוב העבודה שהושלמה.
- config פרטי: מיתוג ברמת המערכת, אזור זמן, DB, מפתחות ומיפויי חיבורים.
- חשבונית שומרת snapshot של החברה והלקוח בעת ההנפקה. שינוי מותג אינו משכתב מסמך קודם.

אלו מותגים של מפעיל אחד. אין בידוד מלא של מסדים והרשאות בין עסקים עצמאיים, ומשתמש תפעולי עדיין יכול לראות פרטי עבודה פיננסיים במסכי לידים.

## אריזה

```bash
python3 bin/build-release.py
python3 tests/release.py
```

הארכיונים נוצרים ב־dist ונשארים מחוץ ל־Git. קוד וחבילות נבדקים בנפרד; פריסה בחשבון SiteGround נבדקת לפי המדריך שלו.
