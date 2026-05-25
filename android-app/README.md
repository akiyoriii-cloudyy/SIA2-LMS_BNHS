# BNHS RFID Attendance Monitoring Tracker (Android)

Kotlin + Jetpack Compose app for BNHS attendance monitoring, integrated with the Laravel LMS API.

## Login module (laboratory activity)

| Requirement | Implementation |
|-------------|----------------|
| User authentication | **Token-based (Bearer JWT)** via `POST /api/auth/login` |
| Validate against database | Credentials checked on Laravel `users` table (hashed passwords) |
| Login error handling | Wrong password (401), no portal access (403), network/validation messages in UI |
| Password recovery | **Forgot password** → `POST /api/auth/forgot-password` (email reset link) |
| Session persistence | Encrypted `SessionStore` (token + user profile) |
| Security testing | See **Test plan** below |

### Demo accounts (after `php artisan migrate` + `db:seed`)

Each account opens its **own dashboard** (no shared role picker):

| Email | Password | Mobile dashboard |
|-------|----------|------------------|
| `admin@bnhs.local` | `password` | Overview, **Records** (CRUD), system/audit |
| `security@bnhs.local` | `password` | RFID gate scan + gate log only |
| `adviser@bnhs.local` | `password` | Class roster, history, **monthly reports** (server sync), absence alerts, parents |

## Open in Android Studio

1. Open Android Studio → **Open** → select folder `android-app`
2. Gradle sync → Run on emulator or phone
3. Ensure **XAMPP Apache + MySQL** are running and the LMS works in a browser

### API URL (Server settings on login screen)

| Device | API base URL |
|--------|----------------|
| **Android Emulator** | `http://10.0.2.2/LMS_BNHS/public/api/` (default) |
| **Physical phone** (same Wi‑Fi as PC) | `http://YOUR_PC_IP/LMS_BNHS/public/api/` |

Replace `YOUR_PC_IP` with your computer’s LAN address (e.g. `192.168.1.10`).

## Adviser monthly reports (app ↔ web ↔ email)

1. Daily attendance is saved on the **web** and **mobile** (`POST /api/mobile/sync/attendance`).
2. The server builds **monthly reports** (adviser web: Attendance Reports).
3. Reports can be **emailed** to the adviser (PHPMailer on Laravel).
4. In the app, open adviser bottom nav **Reports** to load the same reports from:
   - `GET /api/mobile/attendance/monthly-reports`
   - `GET /api/mobile/attendance/monthly-reports/{id}` (student absence table per month)
5. **Edit on Web** / **Print** buttons open the linked LMS URLs in the browser.

## App flow after login

1. **Sign in** with the account for your job role
2. App routes automatically by Laravel role (`admin`, `security_guard`, `adviser`)
3. Security RFID demo UIDs: `RFID-ANA-001`, `RFID-BRY-002`, `RFID-IVA-003`
4. **Sign out** from the header menu revokes the token when online

## Test plan (login & security)

1. **Valid login** — `admin@bnhs.local` / `password` → main portal appears, name shown in header.
2. **Invalid password** — wrong password → “Invalid email or password” (no access).
3. **Unknown email** — non-existent email → same generic invalid message (401).
4. **No portal permission** — user role without `lms.portal` → “Access denied”.
5. **Offline** — stop Apache → connection error message (no fake success).
6. **Session restore** — login, close app, reopen → still signed in (encrypted token).
7. **Logout** — Account sign out → login screen; token cleared locally.
8. **Forgot password** — enter registered email → success message; check email for reset link (SMTP must be configured in Laravel `.env`).
9. **Forgot password (unknown email)** — still shows generic success text (does not reveal if email exists).

### Run Android unit test

```bash
cd android-app
./gradlew test
```

### Run Laravel API tests

```bash
php artisan test --filter=ApiAuthTest
```

## Records module (laboratory activity)

| Requirement | Implementation |
|-------------|----------------|
| Structured database schema | Room SQLite: `students`, `parents`, `record_audit_logs` (v2) |
| CRUD | **Admin** → student records · **Security** → gate logs · **Adviser** → attendance logs |
| Search & filter | Search bar + grade, section, active/archived filters |
| Validation | `RecordValidator` — LRN, RFID, grade, phone (`09` + 9 digits) |
| Security | Admin-only UI; unique LRN/RFID checks; audit log with actor email |

Laravel API (optional sync): `GET/POST/PUT/DELETE /api/mobile/records/students` (`records.manage` permission).

## RBAC module (laboratory activity)

| Requirement | Implementation |
|-------------|----------------|
| Role hierarchies | **Admin (300)** → **Editor/Adviser (200)** → **User/Security (150)** → **Limited user (100)** — shown in Admin **RBAC** tab |
| Permissions by role | `records.manage`, `attendance.manage`, `users.manage`, `lms.portal`, etc. — returned on login and stored in session |
| Secure endpoints | Laravel `permission:*` middleware on `/api/mobile/*` routes |
| Restrict UI by role | `RbacEnforcer` hides tabs/actions; `RbacAccessDenied` when permission missing |

### Permission → mobile feature

| Permission | Who gets it | Mobile feature |
|------------|-------------|----------------|
| `lms.portal` | admin, adviser, security_guard | Sign-in allowed |
| `dashboard.view` | all staff roles | Admin overview |
| `records.manage` | admin, adviser | Admin student records CRUD |
| `attendance.manage` | admin, adviser, security_guard | Gate scan, gate logs, adviser roster/attendance |
| `users.manage` | admin only | Shown on Admin RBAC tab (web LMS) |

### RBAC test plan

1. **Admin** — login → Overview, Records, RBAC tabs visible; System tab lists role hierarchy and your permissions.
2. **Security** — login → Scan, Records, Log only (no admin student records).
3. **Adviser** — login → Roster + attendance CRUD; no user-management UI.
4. **API** — `GET /api/auth/rbac` with Bearer token returns `your_permissions` (run `php artisan test --filter=ApiAuthTest`).
5. **Android unit tests** — `./gradlew test` includes `RbacEnforcerTest`.

## Role dashboards

| Role | Functions (not shared with other roles) |
|------|----------------------------------------|
| **Admin** | Overview + **Student records CRUD** (full schema) + RBAC/system/audit |
| **Security** | RFID scan + **Gate access records CRUD** (create/read/update/delete) |
| **Adviser** | Class roster + **Attendance records CRUD** (create/read/update/delete + parent) |

## Notification behavior

- Present at gate → attendance confirmation notification
- 7 consecutive absent days → parent SMS + alert (permissions required)

## Session tracking & activity logging (Member 1 lab)

| Requirement | Implementation |
|-------------|----------------|
| Monitor active sessions | `user_sessions` table + `SessionTracker` (ACTIVE/ENDED, last activity) |
| Activity logging | `activity_logs` — login, logout, password reset, student CRUD, attendance, backup/restore |
| Secure log storage | Actor email & details encrypted with **AES-256-GCM** (`FieldEncryptor`) |

### Logged actions (examples)

| Category | Actions |
|----------|---------|
| **AUTH** | `LOGIN_SUCCESS`, `LOGIN_FAILURE`, `LOGOUT`, `SESSION_RESTORE` |
| **ACCOUNT** | `PASSWORD_RESET_REQUEST`, `GUARDIAN_UPDATE` |
| **RECORDS** | `STUDENT_CREATE/UPDATE/DELETE`, `ATTENDANCE_UPSERT/DELETE` |
| **SYSTEM** | `DATABASE_BACKUP`, `DATABASE_RESTORE` |

### Test steps

1. **Failed login** — wrong password → Admin **RBAC** tab → **Refresh logs** → see `AUTH/LOGIN_FAILURE`.
2. **Successful login** — see `LOGIN_SUCCESS` and **Active sessions: 1**.
3. **Account change** — edit a student record → see `RECORDS/STUDENT_UPDATE`.
4. **Logout** — session marked ended; `AUTH/LOGOUT` logged.

## Security auditing & intrusion detection (Member 2 lab)

| Requirement | Implementation |
|-------------|----------------|
| Audit failed logins & breaches | `security_incidents` + rules in `IntrusionDetector` |
| Alerts for suspicious activity | High-priority **Security alerts** notification channel |
| Periodic audit reports | Auto 24h report when stale + manual **Report 24h** / **Report 7d** buttons |

### Detection rules (examples)

| Trigger | Incident type | Severity |
|---------|---------------|----------|
| 3+ failed logins in 15 min (same email) | `BRUTE_FORCE` | HIGH |
| 5+ failed logins in 1 hour | `EXCESSIVE_LOGIN_FAILURES` | MEDIUM |
| 3+ password reset requests in 30 min | `SUSPICIOUS_PASSWORD_RESET` | MEDIUM |
| Database restore | `UNAUTHORIZED_RESTORE` | HIGH |

### Test steps

1. Admin → **RBAC** tab → **Security audit & intrusion detection** panel.
2. On login screen, enter wrong password **3 times** → notification **Security alert** + incident list.
3. Tap **Report 24h** → summary + notification “Security audit report ready”.
4. Risk level turns **YELLOW** or **RED** when thresholds are exceeded.

## CRUD business transactions & ACID (Member 3 lab)

| ACID property | How the app implements it |
|---------------|---------------------------|
| **Atomicity** | Student + parent + audit in one `withTransaction { }` block |
| **Consistency** | Validation + unique LRN/RFID before transaction starts |
| **Isolation** | Room serializes writes on the SQLite connection |
| **Durability** | SQLite WAL; `business_transactions` log survives commit/rollback |

### Commit / rollback

- **COMMITTED** — all steps succeeded; log updated with end time
- **ROLLED_BACK** — any exception rolls back business tables automatically; log stores error message

### Test steps

1. Admin → **Records** → create a student → **RBAC** → **ACID business transactions** shows `STUDENT_CREATE · COMMITTED`.
2. **Rollback demo:** create student with RFID `TX-ROLLBACK` → error message + `ROLLED_BACK` in transaction log (no partial student left).
3. Edit attendance as adviser — see `ATTENDANCE_ADVISER_UPSERT · COMMITTED`.

## Database security & backup (Member 3 lab)

| Requirement | Implementation |
|-------------|----------------|
| Optimized secure schema | Room **v4**: unique LRN/RFID/username, FK cascades, `backup_history` audit table |
| Encryption for sensitive data | **AES-256-GCM** for parent contacts & alert phone numbers; **salted SHA-256** for local `user_accounts` passwords; **EncryptedSharedPreferences** for API tokens |
| Automated backup | Admin **RBAC** tab → **Create backup** (JSON snapshot + SHA-256 checksum) |
| Restoration | **Restore latest** with confirmation dialog |
| Server scripts | `php artisan db:backup` / `db:restore` · `scripts/database-backup.ps1` · `scripts/database-backup.sh` |

### Android test steps

1. Login as **admin@bnhs.local** → open **RBAC** tab (shield icon).
2. Tap **Create backup** — status shows file name and record count.
3. Change a student record, then **Restore latest** — data reverts to backup.
4. Run unit tests: `./gradlew test` (`PasswordHasherTest`).

### Laravel backup (XAMPP)

```powershell
cd C:\xampp\htdocs\LMS_BNHS
.\scripts\database-backup.ps1
# Restore:
php artisan db:restore storage/app/backups/lms_bnhs_YYYYMMDD_HHMMSS.sql
```

Optional `.env`: `MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe`, `MYSQL_PATH=C:\xampp\mysql\bin\mysql.exe`

## Production next steps

- Sync roster/attendance from `/api/mobile/*` endpoints using stored Bearer token
- Real RFID hardware input
- HTTPS API URL (remove cleartext for release builds)
