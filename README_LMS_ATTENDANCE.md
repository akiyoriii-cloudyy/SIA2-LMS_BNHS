# BNHS Integrated LMS + Attendance Monitoring

This project now includes a single-school integrated LMS and mobile attendance monitoring backend sharing one database.

## Implemented Modules

- Web LMS base (courses, materials, assignments, submissions)
- Gradebook and report card automation
- Attendance monitoring (daily status per student)
- Weekly absence trigger (5 absences in one week)
- SMS notification integration (Twilio-ready)
- Offline-capable mobile sync API (batch sync when internet returns)
- Role-based access control (`admin`, `teacher`, `student`)
- Token-based mobile API authentication

## Key Routes

- Web login: `GET /login`
- Gradebook: `GET /gradebook`
- Attendance: `GET /attendance`
- Report cards: `GET /report-cards`
- Mobile demo page: `GET /mobile-attendance.html`

## Mobile API

- `POST /api/auth/login`
- `POST /api/auth/logout` (Bearer required)
- `GET /api/mobile/roster?school_year_id={id}&section_id={id}` (Bearer + teacher/admin)
- `POST /api/mobile/sync/attendance` (Bearer + teacher/admin)

## SMS Setup

Set these in `.env`:

```env
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM_NUMBER=+1XXXXXXXXXX
```

## Run

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Default demo accounts (password: `password`):

- `admin@bnhs.local` (admin)
- `teacher@bnhs.local` (teacher)
- `ana.santos@bnhs.local` (student sample)

## Weekly Absence Rule

When attendance is saved as `absent`, the system counts absences for the same Monday-Sunday week per enrollment.
If absences are `>= 5`, it sends SMS to linked guardians with `receive_sms = true`, logs result in `sms_logs`, and writes `notifications`.

