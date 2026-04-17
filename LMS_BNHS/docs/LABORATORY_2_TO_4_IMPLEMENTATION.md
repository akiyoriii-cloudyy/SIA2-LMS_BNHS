# LMS BNHS Laboratory 2-4 Implementation

This document maps the LMS implementation to the requirements for Laboratory Exercises 2, 3, and 4.

## Laboratory 2 - Development Environment Setup

- Laravel project initialized with Composer and `artisan`.
- Database support configured through `.env` (`DB_*` values for MySQL).
- Version control active using Git repository.
- Structured directories already in place:
  - `app/` for core modules/controllers/models
  - `resources/views/` for UI templates
  - `routes/` for route definitions
  - `database/migrations/` and `database/seeders/` for schema/data setup
  - `tests/` for feature and unit tests
- Environment and setup commands:
  - `composer install`
  - `cp .env.example .env` (or manual copy on Windows)
  - `php artisan key:generate`
  - `php artisan migrate --seed`
  - `php artisan serve`
  - `php artisan test`

## Laboratory 3 - Login, Dashboard, and Records Module

- Login module:
  - Implemented in `AuthController` with validation and throttling.
  - Password reset flow implemented in `PasswordResetController`.
- Dashboard module:
  - Route: `/dashboard`
  - Protected by `auth`, `role`, and `permission` middleware.
- Records module:
  - Students and subjects CRUD endpoints in `StudentController` and `SubjectController`.
  - Gradebook, master sheet, attendance, and report card modules integrated.
  - Search/filter and validation are present in records-related controllers.
- Integration/testing:
  - Feature tests are present and passing for auth flow, RBAC, master sheet, report card alignment, and students table.

## Laboratory 4 - User Management and RBAC

- User management:
  - Admin UI at `/admin/users` supports account create/delete/restore/password update.
  - Admin can edit user role and profile details (first/middle/last/suffix, email, phone).
  - New normalized personal profile storage via `user_profiles` table.
  - User creation captures first/middle/last/suffix and composes display name.
- RBAC:
  - Roles and permissions schema exists (`roles`, `permissions`, pivot tables).
  - Route access control via middleware:
    - `role:...`
    - `permission:...`
- Database management/security:
  - Password hashing via Laravel user casting (`password => hashed`).
  - Soft deletes enabled for users.
  - Backup and restore helper scripts available:
    - `scripts/db-backup.ps1`
    - `scripts/db-restore.ps1`
  - RFID integration endpoint for mobile attendance:
    - `POST /api/mobile/rfid/scan`

## 3NF Normalization Notes

- Existing academic data model is mostly normalized (separate tables for students, teachers, sections, subjects, enrollments, assignments, grades, roles, and permissions).
- Personal name components for system users are now moved to a dedicated `user_profiles` relation:
  - avoids stuffing multiple attributes into one `users.name` column
  - keeps identity/profile details in one place per user
  - reduces update anomalies when user profile details change

## Quick Validation Checklist

- Run migrations: `php artisan migrate`
- Run tests: `php artisan test`
- Verify user management:
  - Add Admin/Teacher with first/middle/last name
  - Search users by name/email/phone
  - Confirm role-based route restrictions
