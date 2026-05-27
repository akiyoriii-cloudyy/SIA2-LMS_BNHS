## Laboratory Exercise 5 — Advanced User Management and Security Enhancements

This document describes the security enhancements implemented for **Laboratory Exercise 5** in the BNHS LMS.

### A. Advanced Authentication System (Member 1)

#### 1) Multi-Factor Authentication (MFA)
- **Type**: Time-based One-Time Passwords (TOTP) via authenticator apps.
- **Flow**:
  - User enables MFA in **Settings → Manage MFA**.
  - System generates a TOTP secret and displays:
    - a QR code (SVG) for scanning
    - the manual secret + OTPAuth URI
  - User verifies setup by providing a **6-digit code**.
  - On next login, after email/password validation, user is redirected to MFA challenge.
  - User may authenticate using either:
    - a 6-digit TOTP code, or
    - a one-time recovery code.
- **Files**:
  - `app/Http/Controllers/MfaController.php`
  - `resources/views/auth/mfa-challenge.blade.php`
  - `resources/views/settings-mfa.blade.php`
  - `routes/web.php`

#### 2) Token-based authentication (JWT for API)
- **Type**: JSON Web Token (JWT) bearer tokens (HS256).
- **Flow**:
  - Client calls `POST /api/auth/login` with email/password.
  - Server returns `token` + `token_type=Bearer`.
  - Client calls protected endpoints with `Authorization: Bearer <token>`.
- **Files**:
  - `app/Services/JwtService.php`
  - `app/Http/Middleware/JwtAuthMiddleware.php`
  - `bootstrap/app.php` (alias `auth.api`)
  - `app/Http/Controllers/Api/AuthController.php`
  - `routes/api.php`

#### 3) Password hashing
- **Mechanism**: Laravel hashing (bcrypt/argon via framework configuration).
- **Evidence**:
  - `User` model uses Laravel hashed casting for `password`.
  - Seeders and flows rely on `Hash::make(...)` and Laravel authentication hashing.
- **Files**:
  - `app/Models/User.php`
  - `database/seeders/*`

#### 4) Session security handling
- Session fixation protection via:
  - session regeneration after successful login
  - session invalidation + CSRF token regeneration on logout
- **File**: `app/Http/Controllers/AuthController.php`

---

### B. Access Control & Security Policies (Member 2)

#### RBAC + permissions
- Roles: `admin`, `adviser`, `subject_teacher`, `user`
- Permissions enforced using route middleware:
  - `role:...`
  - `permission:...`
- **Files**:
  - `database/seeders/RbacSeeder.php`
  - `app/Http/Middleware/RoleMiddleware.php`
  - `app/Http/Middleware/PermissionMiddleware.php`
  - `routes/web.php`, `routes/api.php`

---

### C. Database Security & Backup Management (Member 3)

#### 1) Normalized schema
- Assignments and mappings are stored in separate tables:
  - `subject_assignments` for **section + subject + school year**
  - `teacher_subjects` normalized to include `school_year_id` and `section_id`

#### 2) Encryption of sensitive user data
- **User phone** stored using Laravel encrypted casting.
- **Student RFID UID** stored using Laravel encrypted casting.
- **MFA secrets & recovery codes** stored encrypted at rest:
  - `mfa_secret` encrypted
  - `mfa_recovery_codes` encrypted array
- **Files**:
  - `app/Models/User.php`
  - `app/Models/Student.php`
  - migration: `database/migrations/2026_04_09_210000_add_mfa_fields_to_users_table.php`

#### 3) Backup and restoration scripts
- PowerShell scripts for MySQL backup/restore:
  - `scripts/db-backup.ps1`
  - `scripts/db-restore.ps1`
  - `scripts/README.md`

---

### D. System Integration & Functionality
- Web login integrates with MFA challenge.
- API endpoints integrate with JWT middleware.
- RBAC and permissions continue to restrict access across web and API routes.

---

### E. Version Control
- All Lab 5 changes are intended to be committed and pushed to the project repository for review.

