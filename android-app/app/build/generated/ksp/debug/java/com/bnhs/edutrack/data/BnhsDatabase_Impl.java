package com.bnhs.edutrack.data;

import androidx.annotation.NonNull;
import androidx.room.DatabaseConfiguration;
import androidx.room.InvalidationTracker;
import androidx.room.RoomDatabase;
import androidx.room.RoomOpenHelper;
import androidx.room.migration.AutoMigrationSpec;
import androidx.room.migration.Migration;
import androidx.room.util.DBUtil;
import androidx.room.util.TableInfo;
import androidx.sqlite.db.SupportSQLiteDatabase;
import androidx.sqlite.db.SupportSQLiteOpenHelper;
import java.lang.Class;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import javax.annotation.processing.Generated;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class BnhsDatabase_Impl extends BnhsDatabase {
  private volatile StudentDao _studentDao;

  private volatile ParentDao _parentDao;

  private volatile AttendanceDao _attendanceDao;

  private volatile UserAccountDao _userAccountDao;

  private volatile BackupMetaDao _backupMetaDao;

  private volatile UserSessionDao _userSessionDao;

  private volatile ActivityLogDao _activityLogDao;

  private volatile SecurityIncidentDao _securityIncidentDao;

  private volatile SecurityAuditReportDao _securityAuditReportDao;

  private volatile BusinessTransactionDao _businessTransactionDao;

  private volatile AlertLogDao _alertLogDao;

  private volatile RecordAuditLogDao _recordAuditLogDao;

  @Override
  @NonNull
  protected SupportSQLiteOpenHelper createOpenHelper(@NonNull final DatabaseConfiguration config) {
    final SupportSQLiteOpenHelper.Callback _openCallback = new RoomOpenHelper(config, new RoomOpenHelper.Delegate(8) {
      @Override
      public void createAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("CREATE TABLE IF NOT EXISTS `students` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `name` TEXT NOT NULL, `lrn` TEXT NOT NULL, `rfid_uid` TEXT NOT NULL, `grade_level` TEXT NOT NULL, `section` TEXT NOT NULL, `status` TEXT NOT NULL, `sex` TEXT NOT NULL, `enrollment_id` INTEGER, `server_student_id` INTEGER, `created_at` INTEGER NOT NULL, `updated_at` INTEGER NOT NULL)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_students_lrn` ON `students` (`lrn`)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_students_rfid_uid` ON `students` (`rfid_uid`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `parents` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `student_id` INTEGER NOT NULL, `name` TEXT NOT NULL, `contact` TEXT NOT NULL, `email` TEXT NOT NULL, `relationship` TEXT NOT NULL, `is_primary` INTEGER NOT NULL, `created_at` INTEGER NOT NULL, `updated_at` INTEGER NOT NULL, FOREIGN KEY(`student_id`) REFERENCES `students`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE )");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_parents_student_id` ON `parents` (`student_id`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `attendance_records` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `student_id` INTEGER NOT NULL, `date` TEXT NOT NULL, `logged_at` TEXT NOT NULL, `status` TEXT NOT NULL, `logged_by` TEXT NOT NULL, `created_at` INTEGER NOT NULL, FOREIGN KEY(`student_id`) REFERENCES `students`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE )");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_attendance_records_student_id_date` ON `attendance_records` (`student_id`, `date`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_attendance_records_date` ON `attendance_records` (`date`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `user_accounts` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `username` TEXT NOT NULL, `password_hash` TEXT NOT NULL, `password_salt` TEXT NOT NULL, `role` TEXT NOT NULL, `display_name` TEXT NOT NULL, `assignment` TEXT NOT NULL, `last_login_at` INTEGER, `created_at` INTEGER NOT NULL)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_user_accounts_username` ON `user_accounts` (`username`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `alert_logs` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `student_id` INTEGER NOT NULL, `alert_type` TEXT NOT NULL, `message` TEXT NOT NULL, `recipient_name` TEXT NOT NULL, `recipient_contact` TEXT NOT NULL, `status` TEXT NOT NULL, `error_detail` TEXT, `sent_at` INTEGER NOT NULL, FOREIGN KEY(`student_id`) REFERENCES `students`(`id`) ON UPDATE NO ACTION ON DELETE CASCADE )");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_alert_logs_student_id` ON `alert_logs` (`student_id`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `record_audit_logs` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `action` TEXT NOT NULL, `entity_type` TEXT NOT NULL, `entity_id` INTEGER NOT NULL, `actor_email` TEXT NOT NULL, `summary` TEXT NOT NULL, `created_at` INTEGER NOT NULL)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_record_audit_logs_entity_id` ON `record_audit_logs` (`entity_id`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_record_audit_logs_created_at` ON `record_audit_logs` (`created_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `backup_history` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `file_name` TEXT NOT NULL, `file_path` TEXT NOT NULL, `record_count` INTEGER NOT NULL, `checksum_sha256` TEXT NOT NULL, `created_at` INTEGER NOT NULL)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_backup_history_created_at` ON `backup_history` (`created_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `user_sessions` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `session_uuid` TEXT NOT NULL, `user_id` INTEGER NOT NULL, `user_email_enc` TEXT NOT NULL, `user_name` TEXT NOT NULL, `roles` TEXT NOT NULL, `status` TEXT NOT NULL, `started_at` INTEGER NOT NULL, `last_activity_at` INTEGER NOT NULL, `ended_at` INTEGER)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_user_sessions_session_uuid` ON `user_sessions` (`session_uuid`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_user_sessions_status` ON `user_sessions` (`status`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_user_sessions_last_activity_at` ON `user_sessions` (`last_activity_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `activity_logs` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `session_uuid` TEXT, `category` TEXT NOT NULL, `action` TEXT NOT NULL, `success` INTEGER NOT NULL, `actor_email_enc` TEXT NOT NULL, `details_enc` TEXT NOT NULL, `created_at` INTEGER NOT NULL)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_activity_logs_session_uuid` ON `activity_logs` (`session_uuid`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_activity_logs_category` ON `activity_logs` (`category`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_activity_logs_created_at` ON `activity_logs` (`created_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `security_incidents` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `incident_type` TEXT NOT NULL, `severity` TEXT NOT NULL, `description_enc` TEXT NOT NULL, `actor_email_enc` TEXT NOT NULL, `detected_at` INTEGER NOT NULL, `acknowledged` INTEGER NOT NULL)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_security_incidents_incident_type` ON `security_incidents` (`incident_type`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_security_incidents_detected_at` ON `security_incidents` (`detected_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `security_audit_reports` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `period_label` TEXT NOT NULL, `risk_level` TEXT NOT NULL, `summary_enc` TEXT NOT NULL, `failed_login_count` INTEGER NOT NULL, `incident_count` INTEGER NOT NULL, `successful_login_count` INTEGER NOT NULL, `generated_at` INTEGER NOT NULL)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_security_audit_reports_generated_at` ON `security_audit_reports` (`generated_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS `business_transactions` (`id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, `tx_uuid` TEXT NOT NULL, `operation` TEXT NOT NULL, `entity_type` TEXT NOT NULL, `entity_id` INTEGER, `status` TEXT NOT NULL, `actor_email_enc` TEXT NOT NULL, `summary_enc` TEXT NOT NULL, `error_enc` TEXT NOT NULL, `started_at` INTEGER NOT NULL, `ended_at` INTEGER)");
        db.execSQL("CREATE UNIQUE INDEX IF NOT EXISTS `index_business_transactions_tx_uuid` ON `business_transactions` (`tx_uuid`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_business_transactions_status` ON `business_transactions` (`status`)");
        db.execSQL("CREATE INDEX IF NOT EXISTS `index_business_transactions_started_at` ON `business_transactions` (`started_at`)");
        db.execSQL("CREATE TABLE IF NOT EXISTS room_master_table (id INTEGER PRIMARY KEY,identity_hash TEXT)");
        db.execSQL("INSERT OR REPLACE INTO room_master_table (id,identity_hash) VALUES(42, '51cd04313632756dd2cf2eac3620506b')");
      }

      @Override
      public void dropAllTables(@NonNull final SupportSQLiteDatabase db) {
        db.execSQL("DROP TABLE IF EXISTS `students`");
        db.execSQL("DROP TABLE IF EXISTS `parents`");
        db.execSQL("DROP TABLE IF EXISTS `attendance_records`");
        db.execSQL("DROP TABLE IF EXISTS `user_accounts`");
        db.execSQL("DROP TABLE IF EXISTS `alert_logs`");
        db.execSQL("DROP TABLE IF EXISTS `record_audit_logs`");
        db.execSQL("DROP TABLE IF EXISTS `backup_history`");
        db.execSQL("DROP TABLE IF EXISTS `user_sessions`");
        db.execSQL("DROP TABLE IF EXISTS `activity_logs`");
        db.execSQL("DROP TABLE IF EXISTS `security_incidents`");
        db.execSQL("DROP TABLE IF EXISTS `security_audit_reports`");
        db.execSQL("DROP TABLE IF EXISTS `business_transactions`");
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onDestructiveMigration(db);
          }
        }
      }

      @Override
      public void onCreate(@NonNull final SupportSQLiteDatabase db) {
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onCreate(db);
          }
        }
      }

      @Override
      public void onOpen(@NonNull final SupportSQLiteDatabase db) {
        mDatabase = db;
        db.execSQL("PRAGMA foreign_keys = ON");
        internalInitInvalidationTracker(db);
        final List<? extends RoomDatabase.Callback> _callbacks = mCallbacks;
        if (_callbacks != null) {
          for (RoomDatabase.Callback _callback : _callbacks) {
            _callback.onOpen(db);
          }
        }
      }

      @Override
      public void onPreMigrate(@NonNull final SupportSQLiteDatabase db) {
        DBUtil.dropFtsSyncTriggers(db);
      }

      @Override
      public void onPostMigrate(@NonNull final SupportSQLiteDatabase db) {
      }

      @Override
      @NonNull
      public RoomOpenHelper.ValidationResult onValidateSchema(
          @NonNull final SupportSQLiteDatabase db) {
        final HashMap<String, TableInfo.Column> _columnsStudents = new HashMap<String, TableInfo.Column>(12);
        _columnsStudents.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("name", new TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("lrn", new TableInfo.Column("lrn", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("rfid_uid", new TableInfo.Column("rfid_uid", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("grade_level", new TableInfo.Column("grade_level", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("section", new TableInfo.Column("section", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("sex", new TableInfo.Column("sex", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("enrollment_id", new TableInfo.Column("enrollment_id", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("server_student_id", new TableInfo.Column("server_student_id", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsStudents.put("updated_at", new TableInfo.Column("updated_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysStudents = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesStudents = new HashSet<TableInfo.Index>(2);
        _indicesStudents.add(new TableInfo.Index("index_students_lrn", true, Arrays.asList("lrn"), Arrays.asList("ASC")));
        _indicesStudents.add(new TableInfo.Index("index_students_rfid_uid", true, Arrays.asList("rfid_uid"), Arrays.asList("ASC")));
        final TableInfo _infoStudents = new TableInfo("students", _columnsStudents, _foreignKeysStudents, _indicesStudents);
        final TableInfo _existingStudents = TableInfo.read(db, "students");
        if (!_infoStudents.equals(_existingStudents)) {
          return new RoomOpenHelper.ValidationResult(false, "students(com.bnhs.edutrack.data.StudentEntity).\n"
                  + " Expected:\n" + _infoStudents + "\n"
                  + " Found:\n" + _existingStudents);
        }
        final HashMap<String, TableInfo.Column> _columnsParents = new HashMap<String, TableInfo.Column>(9);
        _columnsParents.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("student_id", new TableInfo.Column("student_id", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("name", new TableInfo.Column("name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("contact", new TableInfo.Column("contact", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("email", new TableInfo.Column("email", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("relationship", new TableInfo.Column("relationship", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("is_primary", new TableInfo.Column("is_primary", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsParents.put("updated_at", new TableInfo.Column("updated_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysParents = new HashSet<TableInfo.ForeignKey>(1);
        _foreignKeysParents.add(new TableInfo.ForeignKey("students", "CASCADE", "NO ACTION", Arrays.asList("student_id"), Arrays.asList("id")));
        final HashSet<TableInfo.Index> _indicesParents = new HashSet<TableInfo.Index>(1);
        _indicesParents.add(new TableInfo.Index("index_parents_student_id", false, Arrays.asList("student_id"), Arrays.asList("ASC")));
        final TableInfo _infoParents = new TableInfo("parents", _columnsParents, _foreignKeysParents, _indicesParents);
        final TableInfo _existingParents = TableInfo.read(db, "parents");
        if (!_infoParents.equals(_existingParents)) {
          return new RoomOpenHelper.ValidationResult(false, "parents(com.bnhs.edutrack.data.ParentEntity).\n"
                  + " Expected:\n" + _infoParents + "\n"
                  + " Found:\n" + _existingParents);
        }
        final HashMap<String, TableInfo.Column> _columnsAttendanceRecords = new HashMap<String, TableInfo.Column>(7);
        _columnsAttendanceRecords.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("student_id", new TableInfo.Column("student_id", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("date", new TableInfo.Column("date", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("logged_at", new TableInfo.Column("logged_at", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("logged_by", new TableInfo.Column("logged_by", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAttendanceRecords.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysAttendanceRecords = new HashSet<TableInfo.ForeignKey>(1);
        _foreignKeysAttendanceRecords.add(new TableInfo.ForeignKey("students", "CASCADE", "NO ACTION", Arrays.asList("student_id"), Arrays.asList("id")));
        final HashSet<TableInfo.Index> _indicesAttendanceRecords = new HashSet<TableInfo.Index>(2);
        _indicesAttendanceRecords.add(new TableInfo.Index("index_attendance_records_student_id_date", true, Arrays.asList("student_id", "date"), Arrays.asList("ASC", "ASC")));
        _indicesAttendanceRecords.add(new TableInfo.Index("index_attendance_records_date", false, Arrays.asList("date"), Arrays.asList("ASC")));
        final TableInfo _infoAttendanceRecords = new TableInfo("attendance_records", _columnsAttendanceRecords, _foreignKeysAttendanceRecords, _indicesAttendanceRecords);
        final TableInfo _existingAttendanceRecords = TableInfo.read(db, "attendance_records");
        if (!_infoAttendanceRecords.equals(_existingAttendanceRecords)) {
          return new RoomOpenHelper.ValidationResult(false, "attendance_records(com.bnhs.edutrack.data.AttendanceEntity).\n"
                  + " Expected:\n" + _infoAttendanceRecords + "\n"
                  + " Found:\n" + _existingAttendanceRecords);
        }
        final HashMap<String, TableInfo.Column> _columnsUserAccounts = new HashMap<String, TableInfo.Column>(9);
        _columnsUserAccounts.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("username", new TableInfo.Column("username", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("password_hash", new TableInfo.Column("password_hash", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("password_salt", new TableInfo.Column("password_salt", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("role", new TableInfo.Column("role", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("display_name", new TableInfo.Column("display_name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("assignment", new TableInfo.Column("assignment", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("last_login_at", new TableInfo.Column("last_login_at", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserAccounts.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysUserAccounts = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesUserAccounts = new HashSet<TableInfo.Index>(1);
        _indicesUserAccounts.add(new TableInfo.Index("index_user_accounts_username", true, Arrays.asList("username"), Arrays.asList("ASC")));
        final TableInfo _infoUserAccounts = new TableInfo("user_accounts", _columnsUserAccounts, _foreignKeysUserAccounts, _indicesUserAccounts);
        final TableInfo _existingUserAccounts = TableInfo.read(db, "user_accounts");
        if (!_infoUserAccounts.equals(_existingUserAccounts)) {
          return new RoomOpenHelper.ValidationResult(false, "user_accounts(com.bnhs.edutrack.data.UserAccountEntity).\n"
                  + " Expected:\n" + _infoUserAccounts + "\n"
                  + " Found:\n" + _existingUserAccounts);
        }
        final HashMap<String, TableInfo.Column> _columnsAlertLogs = new HashMap<String, TableInfo.Column>(9);
        _columnsAlertLogs.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("student_id", new TableInfo.Column("student_id", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("alert_type", new TableInfo.Column("alert_type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("message", new TableInfo.Column("message", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("recipient_name", new TableInfo.Column("recipient_name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("recipient_contact", new TableInfo.Column("recipient_contact", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("error_detail", new TableInfo.Column("error_detail", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsAlertLogs.put("sent_at", new TableInfo.Column("sent_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysAlertLogs = new HashSet<TableInfo.ForeignKey>(1);
        _foreignKeysAlertLogs.add(new TableInfo.ForeignKey("students", "CASCADE", "NO ACTION", Arrays.asList("student_id"), Arrays.asList("id")));
        final HashSet<TableInfo.Index> _indicesAlertLogs = new HashSet<TableInfo.Index>(1);
        _indicesAlertLogs.add(new TableInfo.Index("index_alert_logs_student_id", false, Arrays.asList("student_id"), Arrays.asList("ASC")));
        final TableInfo _infoAlertLogs = new TableInfo("alert_logs", _columnsAlertLogs, _foreignKeysAlertLogs, _indicesAlertLogs);
        final TableInfo _existingAlertLogs = TableInfo.read(db, "alert_logs");
        if (!_infoAlertLogs.equals(_existingAlertLogs)) {
          return new RoomOpenHelper.ValidationResult(false, "alert_logs(com.bnhs.edutrack.data.AlertLogEntity).\n"
                  + " Expected:\n" + _infoAlertLogs + "\n"
                  + " Found:\n" + _existingAlertLogs);
        }
        final HashMap<String, TableInfo.Column> _columnsRecordAuditLogs = new HashMap<String, TableInfo.Column>(7);
        _columnsRecordAuditLogs.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("action", new TableInfo.Column("action", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("entity_type", new TableInfo.Column("entity_type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("entity_id", new TableInfo.Column("entity_id", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("actor_email", new TableInfo.Column("actor_email", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("summary", new TableInfo.Column("summary", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsRecordAuditLogs.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysRecordAuditLogs = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesRecordAuditLogs = new HashSet<TableInfo.Index>(2);
        _indicesRecordAuditLogs.add(new TableInfo.Index("index_record_audit_logs_entity_id", false, Arrays.asList("entity_id"), Arrays.asList("ASC")));
        _indicesRecordAuditLogs.add(new TableInfo.Index("index_record_audit_logs_created_at", false, Arrays.asList("created_at"), Arrays.asList("ASC")));
        final TableInfo _infoRecordAuditLogs = new TableInfo("record_audit_logs", _columnsRecordAuditLogs, _foreignKeysRecordAuditLogs, _indicesRecordAuditLogs);
        final TableInfo _existingRecordAuditLogs = TableInfo.read(db, "record_audit_logs");
        if (!_infoRecordAuditLogs.equals(_existingRecordAuditLogs)) {
          return new RoomOpenHelper.ValidationResult(false, "record_audit_logs(com.bnhs.edutrack.data.RecordAuditLogEntity).\n"
                  + " Expected:\n" + _infoRecordAuditLogs + "\n"
                  + " Found:\n" + _existingRecordAuditLogs);
        }
        final HashMap<String, TableInfo.Column> _columnsBackupHistory = new HashMap<String, TableInfo.Column>(6);
        _columnsBackupHistory.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBackupHistory.put("file_name", new TableInfo.Column("file_name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBackupHistory.put("file_path", new TableInfo.Column("file_path", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBackupHistory.put("record_count", new TableInfo.Column("record_count", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBackupHistory.put("checksum_sha256", new TableInfo.Column("checksum_sha256", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBackupHistory.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysBackupHistory = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesBackupHistory = new HashSet<TableInfo.Index>(1);
        _indicesBackupHistory.add(new TableInfo.Index("index_backup_history_created_at", false, Arrays.asList("created_at"), Arrays.asList("ASC")));
        final TableInfo _infoBackupHistory = new TableInfo("backup_history", _columnsBackupHistory, _foreignKeysBackupHistory, _indicesBackupHistory);
        final TableInfo _existingBackupHistory = TableInfo.read(db, "backup_history");
        if (!_infoBackupHistory.equals(_existingBackupHistory)) {
          return new RoomOpenHelper.ValidationResult(false, "backup_history(com.bnhs.edutrack.data.BackupMetaEntity).\n"
                  + " Expected:\n" + _infoBackupHistory + "\n"
                  + " Found:\n" + _existingBackupHistory);
        }
        final HashMap<String, TableInfo.Column> _columnsUserSessions = new HashMap<String, TableInfo.Column>(10);
        _columnsUserSessions.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("session_uuid", new TableInfo.Column("session_uuid", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("user_id", new TableInfo.Column("user_id", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("user_email_enc", new TableInfo.Column("user_email_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("user_name", new TableInfo.Column("user_name", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("roles", new TableInfo.Column("roles", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("started_at", new TableInfo.Column("started_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("last_activity_at", new TableInfo.Column("last_activity_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsUserSessions.put("ended_at", new TableInfo.Column("ended_at", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysUserSessions = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesUserSessions = new HashSet<TableInfo.Index>(3);
        _indicesUserSessions.add(new TableInfo.Index("index_user_sessions_session_uuid", true, Arrays.asList("session_uuid"), Arrays.asList("ASC")));
        _indicesUserSessions.add(new TableInfo.Index("index_user_sessions_status", false, Arrays.asList("status"), Arrays.asList("ASC")));
        _indicesUserSessions.add(new TableInfo.Index("index_user_sessions_last_activity_at", false, Arrays.asList("last_activity_at"), Arrays.asList("ASC")));
        final TableInfo _infoUserSessions = new TableInfo("user_sessions", _columnsUserSessions, _foreignKeysUserSessions, _indicesUserSessions);
        final TableInfo _existingUserSessions = TableInfo.read(db, "user_sessions");
        if (!_infoUserSessions.equals(_existingUserSessions)) {
          return new RoomOpenHelper.ValidationResult(false, "user_sessions(com.bnhs.edutrack.tracking.UserSessionEntity).\n"
                  + " Expected:\n" + _infoUserSessions + "\n"
                  + " Found:\n" + _existingUserSessions);
        }
        final HashMap<String, TableInfo.Column> _columnsActivityLogs = new HashMap<String, TableInfo.Column>(8);
        _columnsActivityLogs.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("session_uuid", new TableInfo.Column("session_uuid", "TEXT", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("category", new TableInfo.Column("category", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("action", new TableInfo.Column("action", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("success", new TableInfo.Column("success", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("actor_email_enc", new TableInfo.Column("actor_email_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("details_enc", new TableInfo.Column("details_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsActivityLogs.put("created_at", new TableInfo.Column("created_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysActivityLogs = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesActivityLogs = new HashSet<TableInfo.Index>(3);
        _indicesActivityLogs.add(new TableInfo.Index("index_activity_logs_session_uuid", false, Arrays.asList("session_uuid"), Arrays.asList("ASC")));
        _indicesActivityLogs.add(new TableInfo.Index("index_activity_logs_category", false, Arrays.asList("category"), Arrays.asList("ASC")));
        _indicesActivityLogs.add(new TableInfo.Index("index_activity_logs_created_at", false, Arrays.asList("created_at"), Arrays.asList("ASC")));
        final TableInfo _infoActivityLogs = new TableInfo("activity_logs", _columnsActivityLogs, _foreignKeysActivityLogs, _indicesActivityLogs);
        final TableInfo _existingActivityLogs = TableInfo.read(db, "activity_logs");
        if (!_infoActivityLogs.equals(_existingActivityLogs)) {
          return new RoomOpenHelper.ValidationResult(false, "activity_logs(com.bnhs.edutrack.tracking.ActivityLogEntity).\n"
                  + " Expected:\n" + _infoActivityLogs + "\n"
                  + " Found:\n" + _existingActivityLogs);
        }
        final HashMap<String, TableInfo.Column> _columnsSecurityIncidents = new HashMap<String, TableInfo.Column>(7);
        _columnsSecurityIncidents.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("incident_type", new TableInfo.Column("incident_type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("severity", new TableInfo.Column("severity", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("description_enc", new TableInfo.Column("description_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("actor_email_enc", new TableInfo.Column("actor_email_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("detected_at", new TableInfo.Column("detected_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityIncidents.put("acknowledged", new TableInfo.Column("acknowledged", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysSecurityIncidents = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesSecurityIncidents = new HashSet<TableInfo.Index>(2);
        _indicesSecurityIncidents.add(new TableInfo.Index("index_security_incidents_incident_type", false, Arrays.asList("incident_type"), Arrays.asList("ASC")));
        _indicesSecurityIncidents.add(new TableInfo.Index("index_security_incidents_detected_at", false, Arrays.asList("detected_at"), Arrays.asList("ASC")));
        final TableInfo _infoSecurityIncidents = new TableInfo("security_incidents", _columnsSecurityIncidents, _foreignKeysSecurityIncidents, _indicesSecurityIncidents);
        final TableInfo _existingSecurityIncidents = TableInfo.read(db, "security_incidents");
        if (!_infoSecurityIncidents.equals(_existingSecurityIncidents)) {
          return new RoomOpenHelper.ValidationResult(false, "security_incidents(com.bnhs.edutrack.securityaudit.SecurityIncidentEntity).\n"
                  + " Expected:\n" + _infoSecurityIncidents + "\n"
                  + " Found:\n" + _existingSecurityIncidents);
        }
        final HashMap<String, TableInfo.Column> _columnsSecurityAuditReports = new HashMap<String, TableInfo.Column>(8);
        _columnsSecurityAuditReports.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("period_label", new TableInfo.Column("period_label", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("risk_level", new TableInfo.Column("risk_level", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("summary_enc", new TableInfo.Column("summary_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("failed_login_count", new TableInfo.Column("failed_login_count", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("incident_count", new TableInfo.Column("incident_count", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("successful_login_count", new TableInfo.Column("successful_login_count", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsSecurityAuditReports.put("generated_at", new TableInfo.Column("generated_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysSecurityAuditReports = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesSecurityAuditReports = new HashSet<TableInfo.Index>(1);
        _indicesSecurityAuditReports.add(new TableInfo.Index("index_security_audit_reports_generated_at", false, Arrays.asList("generated_at"), Arrays.asList("ASC")));
        final TableInfo _infoSecurityAuditReports = new TableInfo("security_audit_reports", _columnsSecurityAuditReports, _foreignKeysSecurityAuditReports, _indicesSecurityAuditReports);
        final TableInfo _existingSecurityAuditReports = TableInfo.read(db, "security_audit_reports");
        if (!_infoSecurityAuditReports.equals(_existingSecurityAuditReports)) {
          return new RoomOpenHelper.ValidationResult(false, "security_audit_reports(com.bnhs.edutrack.securityaudit.SecurityAuditReportEntity).\n"
                  + " Expected:\n" + _infoSecurityAuditReports + "\n"
                  + " Found:\n" + _existingSecurityAuditReports);
        }
        final HashMap<String, TableInfo.Column> _columnsBusinessTransactions = new HashMap<String, TableInfo.Column>(11);
        _columnsBusinessTransactions.put("id", new TableInfo.Column("id", "INTEGER", true, 1, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("tx_uuid", new TableInfo.Column("tx_uuid", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("operation", new TableInfo.Column("operation", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("entity_type", new TableInfo.Column("entity_type", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("entity_id", new TableInfo.Column("entity_id", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("status", new TableInfo.Column("status", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("actor_email_enc", new TableInfo.Column("actor_email_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("summary_enc", new TableInfo.Column("summary_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("error_enc", new TableInfo.Column("error_enc", "TEXT", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("started_at", new TableInfo.Column("started_at", "INTEGER", true, 0, null, TableInfo.CREATED_FROM_ENTITY));
        _columnsBusinessTransactions.put("ended_at", new TableInfo.Column("ended_at", "INTEGER", false, 0, null, TableInfo.CREATED_FROM_ENTITY));
        final HashSet<TableInfo.ForeignKey> _foreignKeysBusinessTransactions = new HashSet<TableInfo.ForeignKey>(0);
        final HashSet<TableInfo.Index> _indicesBusinessTransactions = new HashSet<TableInfo.Index>(3);
        _indicesBusinessTransactions.add(new TableInfo.Index("index_business_transactions_tx_uuid", true, Arrays.asList("tx_uuid"), Arrays.asList("ASC")));
        _indicesBusinessTransactions.add(new TableInfo.Index("index_business_transactions_status", false, Arrays.asList("status"), Arrays.asList("ASC")));
        _indicesBusinessTransactions.add(new TableInfo.Index("index_business_transactions_started_at", false, Arrays.asList("started_at"), Arrays.asList("ASC")));
        final TableInfo _infoBusinessTransactions = new TableInfo("business_transactions", _columnsBusinessTransactions, _foreignKeysBusinessTransactions, _indicesBusinessTransactions);
        final TableInfo _existingBusinessTransactions = TableInfo.read(db, "business_transactions");
        if (!_infoBusinessTransactions.equals(_existingBusinessTransactions)) {
          return new RoomOpenHelper.ValidationResult(false, "business_transactions(com.bnhs.edutrack.transaction.BusinessTransactionEntity).\n"
                  + " Expected:\n" + _infoBusinessTransactions + "\n"
                  + " Found:\n" + _existingBusinessTransactions);
        }
        return new RoomOpenHelper.ValidationResult(true, null);
      }
    }, "51cd04313632756dd2cf2eac3620506b", "e3c0c3ebf286e97013ac2b4053cda8a5");
    final SupportSQLiteOpenHelper.Configuration _sqliteConfig = SupportSQLiteOpenHelper.Configuration.builder(config.context).name(config.name).callback(_openCallback).build();
    final SupportSQLiteOpenHelper _helper = config.sqliteOpenHelperFactory.create(_sqliteConfig);
    return _helper;
  }

  @Override
  @NonNull
  protected InvalidationTracker createInvalidationTracker() {
    final HashMap<String, String> _shadowTablesMap = new HashMap<String, String>(0);
    final HashMap<String, Set<String>> _viewTables = new HashMap<String, Set<String>>(0);
    return new InvalidationTracker(this, _shadowTablesMap, _viewTables, "students","parents","attendance_records","user_accounts","alert_logs","record_audit_logs","backup_history","user_sessions","activity_logs","security_incidents","security_audit_reports","business_transactions");
  }

  @Override
  public void clearAllTables() {
    super.assertNotMainThread();
    final SupportSQLiteDatabase _db = super.getOpenHelper().getWritableDatabase();
    final boolean _supportsDeferForeignKeys = android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.LOLLIPOP;
    try {
      if (!_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA foreign_keys = FALSE");
      }
      super.beginTransaction();
      if (_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA defer_foreign_keys = TRUE");
      }
      _db.execSQL("DELETE FROM `students`");
      _db.execSQL("DELETE FROM `parents`");
      _db.execSQL("DELETE FROM `attendance_records`");
      _db.execSQL("DELETE FROM `user_accounts`");
      _db.execSQL("DELETE FROM `alert_logs`");
      _db.execSQL("DELETE FROM `record_audit_logs`");
      _db.execSQL("DELETE FROM `backup_history`");
      _db.execSQL("DELETE FROM `user_sessions`");
      _db.execSQL("DELETE FROM `activity_logs`");
      _db.execSQL("DELETE FROM `security_incidents`");
      _db.execSQL("DELETE FROM `security_audit_reports`");
      _db.execSQL("DELETE FROM `business_transactions`");
      super.setTransactionSuccessful();
    } finally {
      super.endTransaction();
      if (!_supportsDeferForeignKeys) {
        _db.execSQL("PRAGMA foreign_keys = TRUE");
      }
      _db.query("PRAGMA wal_checkpoint(FULL)").close();
      if (!_db.inTransaction()) {
        _db.execSQL("VACUUM");
      }
    }
  }

  @Override
  @NonNull
  protected Map<Class<?>, List<Class<?>>> getRequiredTypeConverters() {
    final HashMap<Class<?>, List<Class<?>>> _typeConvertersMap = new HashMap<Class<?>, List<Class<?>>>();
    _typeConvertersMap.put(StudentDao.class, StudentDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(ParentDao.class, ParentDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(AttendanceDao.class, AttendanceDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(UserAccountDao.class, UserAccountDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(BackupMetaDao.class, BackupMetaDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(UserSessionDao.class, UserSessionDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(ActivityLogDao.class, ActivityLogDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(SecurityIncidentDao.class, SecurityIncidentDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(SecurityAuditReportDao.class, SecurityAuditReportDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(BusinessTransactionDao.class, BusinessTransactionDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(AlertLogDao.class, AlertLogDao_Impl.getRequiredConverters());
    _typeConvertersMap.put(RecordAuditLogDao.class, RecordAuditLogDao_Impl.getRequiredConverters());
    return _typeConvertersMap;
  }

  @Override
  @NonNull
  public Set<Class<? extends AutoMigrationSpec>> getRequiredAutoMigrationSpecs() {
    final HashSet<Class<? extends AutoMigrationSpec>> _autoMigrationSpecsSet = new HashSet<Class<? extends AutoMigrationSpec>>();
    return _autoMigrationSpecsSet;
  }

  @Override
  @NonNull
  public List<Migration> getAutoMigrations(
      @NonNull final Map<Class<? extends AutoMigrationSpec>, AutoMigrationSpec> autoMigrationSpecs) {
    final List<Migration> _autoMigrations = new ArrayList<Migration>();
    return _autoMigrations;
  }

  @Override
  public StudentDao studentDao() {
    if (_studentDao != null) {
      return _studentDao;
    } else {
      synchronized(this) {
        if(_studentDao == null) {
          _studentDao = new StudentDao_Impl(this);
        }
        return _studentDao;
      }
    }
  }

  @Override
  public ParentDao parentDao() {
    if (_parentDao != null) {
      return _parentDao;
    } else {
      synchronized(this) {
        if(_parentDao == null) {
          _parentDao = new ParentDao_Impl(this);
        }
        return _parentDao;
      }
    }
  }

  @Override
  public AttendanceDao attendanceDao() {
    if (_attendanceDao != null) {
      return _attendanceDao;
    } else {
      synchronized(this) {
        if(_attendanceDao == null) {
          _attendanceDao = new AttendanceDao_Impl(this);
        }
        return _attendanceDao;
      }
    }
  }

  @Override
  public UserAccountDao userAccountDao() {
    if (_userAccountDao != null) {
      return _userAccountDao;
    } else {
      synchronized(this) {
        if(_userAccountDao == null) {
          _userAccountDao = new UserAccountDao_Impl(this);
        }
        return _userAccountDao;
      }
    }
  }

  @Override
  public BackupMetaDao backupMetaDao() {
    if (_backupMetaDao != null) {
      return _backupMetaDao;
    } else {
      synchronized(this) {
        if(_backupMetaDao == null) {
          _backupMetaDao = new BackupMetaDao_Impl(this);
        }
        return _backupMetaDao;
      }
    }
  }

  @Override
  public UserSessionDao userSessionDao() {
    if (_userSessionDao != null) {
      return _userSessionDao;
    } else {
      synchronized(this) {
        if(_userSessionDao == null) {
          _userSessionDao = new UserSessionDao_Impl(this);
        }
        return _userSessionDao;
      }
    }
  }

  @Override
  public ActivityLogDao activityLogDao() {
    if (_activityLogDao != null) {
      return _activityLogDao;
    } else {
      synchronized(this) {
        if(_activityLogDao == null) {
          _activityLogDao = new ActivityLogDao_Impl(this);
        }
        return _activityLogDao;
      }
    }
  }

  @Override
  public SecurityIncidentDao securityIncidentDao() {
    if (_securityIncidentDao != null) {
      return _securityIncidentDao;
    } else {
      synchronized(this) {
        if(_securityIncidentDao == null) {
          _securityIncidentDao = new SecurityIncidentDao_Impl(this);
        }
        return _securityIncidentDao;
      }
    }
  }

  @Override
  public SecurityAuditReportDao securityAuditReportDao() {
    if (_securityAuditReportDao != null) {
      return _securityAuditReportDao;
    } else {
      synchronized(this) {
        if(_securityAuditReportDao == null) {
          _securityAuditReportDao = new SecurityAuditReportDao_Impl(this);
        }
        return _securityAuditReportDao;
      }
    }
  }

  @Override
  public BusinessTransactionDao businessTransactionDao() {
    if (_businessTransactionDao != null) {
      return _businessTransactionDao;
    } else {
      synchronized(this) {
        if(_businessTransactionDao == null) {
          _businessTransactionDao = new BusinessTransactionDao_Impl(this);
        }
        return _businessTransactionDao;
      }
    }
  }

  @Override
  public AlertLogDao alertLogDao() {
    if (_alertLogDao != null) {
      return _alertLogDao;
    } else {
      synchronized(this) {
        if(_alertLogDao == null) {
          _alertLogDao = new AlertLogDao_Impl(this);
        }
        return _alertLogDao;
      }
    }
  }

  @Override
  public RecordAuditLogDao recordAuditLogDao() {
    if (_recordAuditLogDao != null) {
      return _recordAuditLogDao;
    } else {
      synchronized(this) {
        if(_recordAuditLogDao == null) {
          _recordAuditLogDao = new RecordAuditLogDao_Impl(this);
        }
        return _recordAuditLogDao;
      }
    }
  }
}
