package com.bnhs.edutrack.data;

import android.database.Cursor;
import android.os.CancellationSignal;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityDeletionOrUpdateAdapter;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import java.lang.Class;
import java.lang.Exception;
import java.lang.Integer;
import java.lang.Long;
import java.lang.Object;
import java.lang.Override;
import java.lang.String;
import java.lang.SuppressWarnings;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Callable;
import javax.annotation.processing.Generated;
import kotlin.Unit;
import kotlin.coroutines.Continuation;

@Generated("androidx.room.RoomProcessor")
@SuppressWarnings({"unchecked", "deprecation"})
public final class AttendanceDao_Impl implements AttendanceDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<AttendanceEntity> __insertionAdapterOfAttendanceEntity;

  private final EntityDeletionOrUpdateAdapter<AttendanceEntity> __deletionAdapterOfAttendanceEntity;

  private final EntityDeletionOrUpdateAdapter<AttendanceEntity> __updateAdapterOfAttendanceEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteForStudentOnDate;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAllForRole;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public AttendanceDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfAttendanceEntity = new EntityInsertionAdapter<AttendanceEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `attendance_records` (`id`,`student_id`,`date`,`logged_at`,`status`,`logged_by`,`created_at`) VALUES (nullif(?, 0),?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AttendanceEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindLong(2, entity.getStudentId());
        statement.bindString(3, entity.getDate());
        statement.bindString(4, entity.getLoggedAt());
        statement.bindString(5, entity.getStatus());
        statement.bindString(6, entity.getLoggedBy());
        statement.bindLong(7, entity.getCreatedAt());
      }
    };
    this.__deletionAdapterOfAttendanceEntity = new EntityDeletionOrUpdateAdapter<AttendanceEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "DELETE FROM `attendance_records` WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AttendanceEntity entity) {
        statement.bindLong(1, entity.getId());
      }
    };
    this.__updateAdapterOfAttendanceEntity = new EntityDeletionOrUpdateAdapter<AttendanceEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "UPDATE OR ABORT `attendance_records` SET `id` = ?,`student_id` = ?,`date` = ?,`logged_at` = ?,`status` = ?,`logged_by` = ?,`created_at` = ? WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AttendanceEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindLong(2, entity.getStudentId());
        statement.bindString(3, entity.getDate());
        statement.bindString(4, entity.getLoggedAt());
        statement.bindString(5, entity.getStatus());
        statement.bindString(6, entity.getLoggedBy());
        statement.bindLong(7, entity.getCreatedAt());
        statement.bindLong(8, entity.getId());
      }
    };
    this.__preparedStmtOfDeleteForStudentOnDate = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM attendance_records WHERE student_id = ? AND date = ? AND logged_by = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAllForRole = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM attendance_records WHERE logged_by = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM attendance_records";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final AttendanceEntity record,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfAttendanceEntity.insertAndReturnId(record);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object delete(final AttendanceEntity record,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __deletionAdapterOfAttendanceEntity.handle(record);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object update(final AttendanceEntity record,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __updateAdapterOfAttendanceEntity.handle(record);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteForStudentOnDate(final long studentId, final String date,
      final String loggedBy, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteForStudentOnDate.acquire();
        int _argIndex = 1;
        _stmt.bindLong(_argIndex, studentId);
        _argIndex = 2;
        _stmt.bindString(_argIndex, date);
        _argIndex = 3;
        _stmt.bindString(_argIndex, loggedBy);
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteForStudentOnDate.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAllForRole(final String loggedBy,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteAllForRole.acquire();
        int _argIndex = 1;
        _stmt.bindString(_argIndex, loggedBy);
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteAllForRole.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteAll(final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteAll.acquire();
        try {
          __db.beginTransaction();
          try {
            _stmt.executeUpdateDelete();
            __db.setTransactionSuccessful();
            return Unit.INSTANCE;
          } finally {
            __db.endTransaction();
          }
        } finally {
          __preparedStmtOfDeleteAll.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object getAll(final Continuation<? super List<AttendanceEntity>> $completion) {
    final String _sql = "SELECT * FROM attendance_records ORDER BY date DESC, logged_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<AttendanceEntity>>() {
      @Override
      @NonNull
      public List<AttendanceEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfDate = CursorUtil.getColumnIndexOrThrow(_cursor, "date");
          final int _cursorIndexOfLoggedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_at");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfLoggedBy = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_by");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<AttendanceEntity> _result = new ArrayList<AttendanceEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AttendanceEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpDate;
            _tmpDate = _cursor.getString(_cursorIndexOfDate);
            final String _tmpLoggedAt;
            _tmpLoggedAt = _cursor.getString(_cursorIndexOfLoggedAt);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpLoggedBy;
            _tmpLoggedBy = _cursor.getString(_cursorIndexOfLoggedBy);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new AttendanceEntity(_tmpId,_tmpStudentId,_tmpDate,_tmpLoggedAt,_tmpStatus,_tmpLoggedBy,_tmpCreatedAt);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @Override
  public Object getById(final long id, final Continuation<? super AttendanceEntity> $completion) {
    final String _sql = "SELECT * FROM attendance_records WHERE id = ? LIMIT 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, id);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<AttendanceEntity>() {
      @Override
      @Nullable
      public AttendanceEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfDate = CursorUtil.getColumnIndexOrThrow(_cursor, "date");
          final int _cursorIndexOfLoggedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_at");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfLoggedBy = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_by");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final AttendanceEntity _result;
          if (_cursor.moveToFirst()) {
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpDate;
            _tmpDate = _cursor.getString(_cursorIndexOfDate);
            final String _tmpLoggedAt;
            _tmpLoggedAt = _cursor.getString(_cursorIndexOfLoggedAt);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpLoggedBy;
            _tmpLoggedBy = _cursor.getString(_cursorIndexOfLoggedBy);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _result = new AttendanceEntity(_tmpId,_tmpStudentId,_tmpDate,_tmpLoggedAt,_tmpStatus,_tmpLoggedBy,_tmpCreatedAt);
          } else {
            _result = null;
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @Override
  public Object count(final Continuation<? super Integer> $completion) {
    final String _sql = "SELECT COUNT(*) FROM attendance_records";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<Integer>() {
      @Override
      @NonNull
      public Integer call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final Integer _result;
          if (_cursor.moveToFirst()) {
            final int _tmp;
            _tmp = _cursor.getInt(0);
            _result = _tmp;
          } else {
            _result = 0;
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @Override
  public Object listByRole(final String loggedBy, final String date, final String status,
      final Continuation<? super List<AttendanceEntity>> $completion) {
    final String _sql = "\n"
            + "        SELECT * FROM attendance_records\n"
            + "        WHERE logged_by = ?\n"
            + "        AND (? = '' OR date = ?)\n"
            + "        AND (? = '' OR status = ?)\n"
            + "        ORDER BY logged_at DESC\n"
            + "        ";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 5);
    int _argIndex = 1;
    _statement.bindString(_argIndex, loggedBy);
    _argIndex = 2;
    _statement.bindString(_argIndex, date);
    _argIndex = 3;
    _statement.bindString(_argIndex, date);
    _argIndex = 4;
    _statement.bindString(_argIndex, status);
    _argIndex = 5;
    _statement.bindString(_argIndex, status);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<AttendanceEntity>>() {
      @Override
      @NonNull
      public List<AttendanceEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfDate = CursorUtil.getColumnIndexOrThrow(_cursor, "date");
          final int _cursorIndexOfLoggedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_at");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfLoggedBy = CursorUtil.getColumnIndexOrThrow(_cursor, "logged_by");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<AttendanceEntity> _result = new ArrayList<AttendanceEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AttendanceEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpDate;
            _tmpDate = _cursor.getString(_cursorIndexOfDate);
            final String _tmpLoggedAt;
            _tmpLoggedAt = _cursor.getString(_cursorIndexOfLoggedAt);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpLoggedBy;
            _tmpLoggedBy = _cursor.getString(_cursorIndexOfLoggedBy);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new AttendanceEntity(_tmpId,_tmpStudentId,_tmpDate,_tmpLoggedAt,_tmpStatus,_tmpLoggedBy,_tmpCreatedAt);
            _result.add(_item);
          }
          return _result;
        } finally {
          _cursor.close();
          _statement.release();
        }
      }
    }, $completion);
  }

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
