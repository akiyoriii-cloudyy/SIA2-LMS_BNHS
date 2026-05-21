package com.bnhs.edutrack.data;

import android.database.Cursor;
import android.os.CancellationSignal;
import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import com.bnhs.edutrack.tracking.UserSessionEntity;
import java.lang.Class;
import java.lang.Exception;
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
public final class UserSessionDao_Impl implements UserSessionDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<UserSessionEntity> __insertionAdapterOfUserSessionEntity;

  private final SharedSQLiteStatement __preparedStmtOfTouch;

  private final SharedSQLiteStatement __preparedStmtOfEnd;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public UserSessionDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfUserSessionEntity = new EntityInsertionAdapter<UserSessionEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `user_sessions` (`id`,`session_uuid`,`user_id`,`user_email_enc`,`user_name`,`roles`,`status`,`started_at`,`last_activity_at`,`ended_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final UserSessionEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getSessionUuid());
        statement.bindLong(3, entity.getUserId());
        statement.bindString(4, entity.getUserEmailEnc());
        statement.bindString(5, entity.getUserName());
        statement.bindString(6, entity.getRoles());
        statement.bindString(7, entity.getStatus());
        statement.bindLong(8, entity.getStartedAt());
        statement.bindLong(9, entity.getLastActivityAt());
        if (entity.getEndedAt() == null) {
          statement.bindNull(10);
        } else {
          statement.bindLong(10, entity.getEndedAt());
        }
      }
    };
    this.__preparedStmtOfTouch = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "UPDATE user_sessions SET last_activity_at = ? WHERE session_uuid = ?";
        return _query;
      }
    };
    this.__preparedStmtOfEnd = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "UPDATE user_sessions SET status = 'ENDED', ended_at = ? WHERE session_uuid = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM user_sessions";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final UserSessionEntity session,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfUserSessionEntity.insertAndReturnId(session);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object touch(final String uuid, final long at,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfTouch.acquire();
        int _argIndex = 1;
        _stmt.bindLong(_argIndex, at);
        _argIndex = 2;
        _stmt.bindString(_argIndex, uuid);
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
          __preparedStmtOfTouch.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object end(final String uuid, final long at,
      final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfEnd.acquire();
        int _argIndex = 1;
        _stmt.bindLong(_argIndex, at);
        _argIndex = 2;
        _stmt.bindString(_argIndex, uuid);
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
          __preparedStmtOfEnd.release(_stmt);
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
  public Object findByUuid(final String uuid,
      final Continuation<? super UserSessionEntity> $completion) {
    final String _sql = "SELECT * FROM user_sessions WHERE session_uuid = ? LIMIT 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindString(_argIndex, uuid);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<UserSessionEntity>() {
      @Override
      @Nullable
      public UserSessionEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfUserEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "user_email_enc");
          final int _cursorIndexOfUserName = CursorUtil.getColumnIndexOrThrow(_cursor, "user_name");
          final int _cursorIndexOfRoles = CursorUtil.getColumnIndexOrThrow(_cursor, "roles");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfLastActivityAt = CursorUtil.getColumnIndexOrThrow(_cursor, "last_activity_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final UserSessionEntity _result;
          if (_cursor.moveToFirst()) {
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            final long _tmpUserId;
            _tmpUserId = _cursor.getLong(_cursorIndexOfUserId);
            final String _tmpUserEmailEnc;
            _tmpUserEmailEnc = _cursor.getString(_cursorIndexOfUserEmailEnc);
            final String _tmpUserName;
            _tmpUserName = _cursor.getString(_cursorIndexOfUserName);
            final String _tmpRoles;
            _tmpRoles = _cursor.getString(_cursorIndexOfRoles);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final long _tmpLastActivityAt;
            _tmpLastActivityAt = _cursor.getLong(_cursorIndexOfLastActivityAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _result = new UserSessionEntity(_tmpId,_tmpSessionUuid,_tmpUserId,_tmpUserEmailEnc,_tmpUserName,_tmpRoles,_tmpStatus,_tmpStartedAt,_tmpLastActivityAt,_tmpEndedAt);
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
  public Object getActive(final Continuation<? super List<UserSessionEntity>> $completion) {
    final String _sql = "SELECT * FROM user_sessions WHERE status = 'ACTIVE' ORDER BY last_activity_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<UserSessionEntity>>() {
      @Override
      @NonNull
      public List<UserSessionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfUserEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "user_email_enc");
          final int _cursorIndexOfUserName = CursorUtil.getColumnIndexOrThrow(_cursor, "user_name");
          final int _cursorIndexOfRoles = CursorUtil.getColumnIndexOrThrow(_cursor, "roles");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfLastActivityAt = CursorUtil.getColumnIndexOrThrow(_cursor, "last_activity_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<UserSessionEntity> _result = new ArrayList<UserSessionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final UserSessionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            final long _tmpUserId;
            _tmpUserId = _cursor.getLong(_cursorIndexOfUserId);
            final String _tmpUserEmailEnc;
            _tmpUserEmailEnc = _cursor.getString(_cursorIndexOfUserEmailEnc);
            final String _tmpUserName;
            _tmpUserName = _cursor.getString(_cursorIndexOfUserName);
            final String _tmpRoles;
            _tmpRoles = _cursor.getString(_cursorIndexOfRoles);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final long _tmpLastActivityAt;
            _tmpLastActivityAt = _cursor.getLong(_cursorIndexOfLastActivityAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new UserSessionEntity(_tmpId,_tmpSessionUuid,_tmpUserId,_tmpUserEmailEnc,_tmpUserName,_tmpRoles,_tmpStatus,_tmpStartedAt,_tmpLastActivityAt,_tmpEndedAt);
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
  public Object recent(final int limit,
      final Continuation<? super List<UserSessionEntity>> $completion) {
    final String _sql = "SELECT * FROM user_sessions ORDER BY started_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<UserSessionEntity>>() {
      @Override
      @NonNull
      public List<UserSessionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfUserEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "user_email_enc");
          final int _cursorIndexOfUserName = CursorUtil.getColumnIndexOrThrow(_cursor, "user_name");
          final int _cursorIndexOfRoles = CursorUtil.getColumnIndexOrThrow(_cursor, "roles");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfLastActivityAt = CursorUtil.getColumnIndexOrThrow(_cursor, "last_activity_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<UserSessionEntity> _result = new ArrayList<UserSessionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final UserSessionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            final long _tmpUserId;
            _tmpUserId = _cursor.getLong(_cursorIndexOfUserId);
            final String _tmpUserEmailEnc;
            _tmpUserEmailEnc = _cursor.getString(_cursorIndexOfUserEmailEnc);
            final String _tmpUserName;
            _tmpUserName = _cursor.getString(_cursorIndexOfUserName);
            final String _tmpRoles;
            _tmpRoles = _cursor.getString(_cursorIndexOfRoles);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final long _tmpLastActivityAt;
            _tmpLastActivityAt = _cursor.getLong(_cursorIndexOfLastActivityAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new UserSessionEntity(_tmpId,_tmpSessionUuid,_tmpUserId,_tmpUserEmailEnc,_tmpUserName,_tmpRoles,_tmpStatus,_tmpStartedAt,_tmpLastActivityAt,_tmpEndedAt);
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
  public Object getAll(final Continuation<? super List<UserSessionEntity>> $completion) {
    final String _sql = "SELECT * FROM user_sessions";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<UserSessionEntity>>() {
      @Override
      @NonNull
      public List<UserSessionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfUserId = CursorUtil.getColumnIndexOrThrow(_cursor, "user_id");
          final int _cursorIndexOfUserEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "user_email_enc");
          final int _cursorIndexOfUserName = CursorUtil.getColumnIndexOrThrow(_cursor, "user_name");
          final int _cursorIndexOfRoles = CursorUtil.getColumnIndexOrThrow(_cursor, "roles");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfLastActivityAt = CursorUtil.getColumnIndexOrThrow(_cursor, "last_activity_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<UserSessionEntity> _result = new ArrayList<UserSessionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final UserSessionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            final long _tmpUserId;
            _tmpUserId = _cursor.getLong(_cursorIndexOfUserId);
            final String _tmpUserEmailEnc;
            _tmpUserEmailEnc = _cursor.getString(_cursorIndexOfUserEmailEnc);
            final String _tmpUserName;
            _tmpUserName = _cursor.getString(_cursorIndexOfUserName);
            final String _tmpRoles;
            _tmpRoles = _cursor.getString(_cursorIndexOfRoles);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final long _tmpLastActivityAt;
            _tmpLastActivityAt = _cursor.getLong(_cursorIndexOfLastActivityAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new UserSessionEntity(_tmpId,_tmpSessionUuid,_tmpUserId,_tmpUserEmailEnc,_tmpUserName,_tmpRoles,_tmpStatus,_tmpStartedAt,_tmpLastActivityAt,_tmpEndedAt);
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
