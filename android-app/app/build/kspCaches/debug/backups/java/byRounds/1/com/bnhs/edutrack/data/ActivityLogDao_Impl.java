package com.bnhs.edutrack.data;

import android.database.Cursor;
import android.os.CancellationSignal;
import androidx.annotation.NonNull;
import androidx.room.CoroutinesRoom;
import androidx.room.EntityInsertionAdapter;
import androidx.room.RoomDatabase;
import androidx.room.RoomSQLiteQuery;
import androidx.room.SharedSQLiteStatement;
import androidx.room.util.CursorUtil;
import androidx.room.util.DBUtil;
import androidx.sqlite.db.SupportSQLiteStatement;
import com.bnhs.edutrack.tracking.ActivityLogEntity;
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
public final class ActivityLogDao_Impl implements ActivityLogDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<ActivityLogEntity> __insertionAdapterOfActivityLogEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public ActivityLogDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfActivityLogEntity = new EntityInsertionAdapter<ActivityLogEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `activity_logs` (`id`,`session_uuid`,`category`,`action`,`success`,`actor_email_enc`,`details_enc`,`created_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ActivityLogEntity entity) {
        statement.bindLong(1, entity.getId());
        if (entity.getSessionUuid() == null) {
          statement.bindNull(2);
        } else {
          statement.bindString(2, entity.getSessionUuid());
        }
        statement.bindString(3, entity.getCategory());
        statement.bindString(4, entity.getAction());
        final int _tmp = entity.getSuccess() ? 1 : 0;
        statement.bindLong(5, _tmp);
        statement.bindString(6, entity.getActorEmailEnc());
        statement.bindString(7, entity.getDetailsEnc());
        statement.bindLong(8, entity.getCreatedAt());
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM activity_logs";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final ActivityLogEntity log, final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfActivityLogEntity.insertAndReturnId(log);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
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
  public Object recent(final int limit,
      final Continuation<? super List<ActivityLogEntity>> $completion) {
    final String _sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ActivityLogEntity>>() {
      @Override
      @NonNull
      public List<ActivityLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfCategory = CursorUtil.getColumnIndexOrThrow(_cursor, "category");
          final int _cursorIndexOfAction = CursorUtil.getColumnIndexOrThrow(_cursor, "action");
          final int _cursorIndexOfSuccess = CursorUtil.getColumnIndexOrThrow(_cursor, "success");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetailsEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "details_enc");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<ActivityLogEntity> _result = new ArrayList<ActivityLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ActivityLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            if (_cursor.isNull(_cursorIndexOfSessionUuid)) {
              _tmpSessionUuid = null;
            } else {
              _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            }
            final String _tmpCategory;
            _tmpCategory = _cursor.getString(_cursorIndexOfCategory);
            final String _tmpAction;
            _tmpAction = _cursor.getString(_cursorIndexOfAction);
            final boolean _tmpSuccess;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSuccess);
            _tmpSuccess = _tmp != 0;
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpDetailsEnc;
            _tmpDetailsEnc = _cursor.getString(_cursorIndexOfDetailsEnc);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new ActivityLogEntity(_tmpId,_tmpSessionUuid,_tmpCategory,_tmpAction,_tmpSuccess,_tmpActorEmailEnc,_tmpDetailsEnc,_tmpCreatedAt);
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
  public Object since(final long since,
      final Continuation<? super List<ActivityLogEntity>> $completion) {
    final String _sql = "SELECT * FROM activity_logs WHERE created_at >= ? ORDER BY created_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, since);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ActivityLogEntity>>() {
      @Override
      @NonNull
      public List<ActivityLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfCategory = CursorUtil.getColumnIndexOrThrow(_cursor, "category");
          final int _cursorIndexOfAction = CursorUtil.getColumnIndexOrThrow(_cursor, "action");
          final int _cursorIndexOfSuccess = CursorUtil.getColumnIndexOrThrow(_cursor, "success");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetailsEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "details_enc");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<ActivityLogEntity> _result = new ArrayList<ActivityLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ActivityLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            if (_cursor.isNull(_cursorIndexOfSessionUuid)) {
              _tmpSessionUuid = null;
            } else {
              _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            }
            final String _tmpCategory;
            _tmpCategory = _cursor.getString(_cursorIndexOfCategory);
            final String _tmpAction;
            _tmpAction = _cursor.getString(_cursorIndexOfAction);
            final boolean _tmpSuccess;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSuccess);
            _tmpSuccess = _tmp != 0;
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpDetailsEnc;
            _tmpDetailsEnc = _cursor.getString(_cursorIndexOfDetailsEnc);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new ActivityLogEntity(_tmpId,_tmpSessionUuid,_tmpCategory,_tmpAction,_tmpSuccess,_tmpActorEmailEnc,_tmpDetailsEnc,_tmpCreatedAt);
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
  public Object getAll(final Continuation<? super List<ActivityLogEntity>> $completion) {
    final String _sql = "SELECT * FROM activity_logs";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ActivityLogEntity>>() {
      @Override
      @NonNull
      public List<ActivityLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfSessionUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "session_uuid");
          final int _cursorIndexOfCategory = CursorUtil.getColumnIndexOrThrow(_cursor, "category");
          final int _cursorIndexOfAction = CursorUtil.getColumnIndexOrThrow(_cursor, "action");
          final int _cursorIndexOfSuccess = CursorUtil.getColumnIndexOrThrow(_cursor, "success");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetailsEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "details_enc");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<ActivityLogEntity> _result = new ArrayList<ActivityLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ActivityLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpSessionUuid;
            if (_cursor.isNull(_cursorIndexOfSessionUuid)) {
              _tmpSessionUuid = null;
            } else {
              _tmpSessionUuid = _cursor.getString(_cursorIndexOfSessionUuid);
            }
            final String _tmpCategory;
            _tmpCategory = _cursor.getString(_cursorIndexOfCategory);
            final String _tmpAction;
            _tmpAction = _cursor.getString(_cursorIndexOfAction);
            final boolean _tmpSuccess;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfSuccess);
            _tmpSuccess = _tmp != 0;
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpDetailsEnc;
            _tmpDetailsEnc = _cursor.getString(_cursorIndexOfDetailsEnc);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new ActivityLogEntity(_tmpId,_tmpSessionUuid,_tmpCategory,_tmpAction,_tmpSuccess,_tmpActorEmailEnc,_tmpDetailsEnc,_tmpCreatedAt);
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
