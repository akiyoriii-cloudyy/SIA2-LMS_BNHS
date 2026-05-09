# BNHS RFID Attendance Monitoring Tracker (Android)

This Android app now follows your objective for BNHS RFID attendance monitoring:

- Two user roles:
  - Security Guard (RFID gate validation)
  - Teacher (attendance monitoring and history)
- RFID-based attendance logging (demo RFID UIDs included)
- Attendance monitoring and attendance history
- Push notifications:
  - Attendance confirmation notifications
  - Parent alert notifications for prolonged student absences

## Open in Android Studio

1. Open Android Studio.
2. Click **Open**.
3. Select this folder: `android-app`
4. Let Gradle sync finish.
5. Run on emulator or Android phone.

## Demo RFID UIDs

- `RFID-ANA-001`
- `RFID-BRY-002`
- `RFID-IVA-003`

Use these in Security Guard mode to simulate RFID scan attendance.

## Notification Behavior

- Marking a student as PRESENT logs attendance and sends an attendance confirmation.
- If a student reaches a prolonged absence streak, the app triggers a parent alert notification.

## Next Integration Step (Production)

Connect the app to your Laravel API + real RFID reader input + SMS gateway/Firebase push for live school deployment.
