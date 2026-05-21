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
import com.bnhs.edutrack.securityaudit.SecurityIncidentEntity;
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
public final class SecurityIncidentDao_Impl implements SecurityIncidentDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<SecurityIncidentEntity> __insertionAdapterOfSecurityIncidentEntity;

  private final SharedSQLiteStatement __preparedStmtOfAcknowledge;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public SecurityIncidentDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfSecurityIncidentEntity = new EntityInsertionAdapter<SecurityIncidentEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `security_incidents` (`id`,`incident_type`,`severity`,`description_enc`,`actor_email_enc`,`detected_at`,`acknowledged`) VALUES (nullif(?, 0),?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final SecurityIncidentEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getIncidentType());
        statement.bindString(3, entity.getSeverity());
        statement.bindString(4, entity.getDescriptionEnc());
        statement.bindString(5, entity.getActorEmailEnc());
        statement.bindLong(6, entity.getDetectedAt());
        final int _tmp = entity.getAcknowledged() ? 1 : 0;
        statement.bindLong(7, _tmp);
      }
    };
    this.__preparedStmtOfAcknowledge = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "UPDATE security_incidents SET acknowledged = 1 WHERE id = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM security_incidents";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final SecurityIncidentEntity incident,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfSecurityIncidentEntity.insertAndReturnId(incident);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object acknowledge(final long id, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfAcknowledge.acquire();
        int _argIndex = 1;
        _stmt.bindLong(_argIndex, id);
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
          __preparedStmtOfAcknowledge.release(_stmt);
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
      final Continuation<? super List<SecurityIncidentEntity>> $completion) {
    final String _sql = "SELECT * FROM security_incidents ORDER BY detected_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityIncidentEntity>>() {
      @Override
      @NonNull
      public List<SecurityIncidentEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfIncidentType = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_type");
          final int _cursorIndexOfSeverity = CursorUtil.getColumnIndexOrThrow(_cursor, "severity");
          final int _cursorIndexOfDescriptionEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "description_enc");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetectedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "detected_at");
          final int _cursorIndexOfAcknowledged = CursorUtil.getColumnIndexOrThrow(_cursor, "acknowledged");
          final List<SecurityIncidentEntity> _result = new ArrayList<SecurityIncidentEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityIncidentEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpIncidentType;
            _tmpIncidentType = _cursor.getString(_cursorIndexOfIncidentType);
            final String _tmpSeverity;
            _tmpSeverity = _cursor.getString(_cursorIndexOfSeverity);
            final String _tmpDescriptionEnc;
            _tmpDescriptionEnc = _cursor.getString(_cursorIndexOfDescriptionEnc);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final long _tmpDetectedAt;
            _tmpDetectedAt = _cursor.getLong(_cursorIndexOfDetectedAt);
            final boolean _tmpAcknowledged;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfAcknowledged);
            _tmpAcknowledged = _tmp != 0;
            _item = new SecurityIncidentEntity(_tmpId,_tmpIncidentType,_tmpSeverity,_tmpDescriptionEnc,_tmpActorEmailEnc,_tmpDetectedAt,_tmpAcknowledged);
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
      final Continuation<? super List<SecurityIncidentEntity>> $completion) {
    final String _sql = "SELECT * FROM security_incidents WHERE detected_at >= ? ORDER BY detected_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, since);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityIncidentEntity>>() {
      @Override
      @NonNull
      public List<SecurityIncidentEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfIncidentType = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_type");
          final int _cursorIndexOfSeverity = CursorUtil.getColumnIndexOrThrow(_cursor, "severity");
          final int _cursorIndexOfDescriptionEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "description_enc");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetectedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "detected_at");
          final int _cursorIndexOfAcknowledged = CursorUtil.getColumnIndexOrThrow(_cursor, "acknowledged");
          final List<SecurityIncidentEntity> _result = new ArrayList<SecurityIncidentEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityIncidentEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpIncidentType;
            _tmpIncidentType = _cursor.getString(_cursorIndexOfIncidentType);
            final String _tmpSeverity;
            _tmpSeverity = _cursor.getString(_cursorIndexOfSeverity);
            final String _tmpDescriptionEnc;
            _tmpDescriptionEnc = _cursor.getString(_cursorIndexOfDescriptionEnc);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final long _tmpDetectedAt;
            _tmpDetectedAt = _cursor.getLong(_cursorIndexOfDetectedAt);
            final boolean _tmpAcknowledged;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfAcknowledged);
            _tmpAcknowledged = _tmp != 0;
            _item = new SecurityIncidentEntity(_tmpId,_tmpIncidentType,_tmpSeverity,_tmpDescriptionEnc,_tmpActorEmailEnc,_tmpDetectedAt,_tmpAcknowledged);
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
  public Object unacknowledged(
      final Continuation<? super List<SecurityIncidentEntity>> $completion) {
    final String _sql = "SELECT * FROM security_incidents WHERE acknowledged = 0 ORDER BY detected_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityIncidentEntity>>() {
      @Override
      @NonNull
      public List<SecurityIncidentEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfIncidentType = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_type");
          final int _cursorIndexOfSeverity = CursorUtil.getColumnIndexOrThrow(_cursor, "severity");
          final int _cursorIndexOfDescriptionEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "description_enc");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetectedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "detected_at");
          final int _cursorIndexOfAcknowledged = CursorUtil.getColumnIndexOrThrow(_cursor, "acknowledged");
          final List<SecurityIncidentEntity> _result = new ArrayList<SecurityIncidentEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityIncidentEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpIncidentType;
            _tmpIncidentType = _cursor.getString(_cursorIndexOfIncidentType);
            final String _tmpSeverity;
            _tmpSeverity = _cursor.getString(_cursorIndexOfSeverity);
            final String _tmpDescriptionEnc;
            _tmpDescriptionEnc = _cursor.getString(_cursorIndexOfDescriptionEnc);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final long _tmpDetectedAt;
            _tmpDetectedAt = _cursor.getLong(_cursorIndexOfDetectedAt);
            final boolean _tmpAcknowledged;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfAcknowledged);
            _tmpAcknowledged = _tmp != 0;
            _item = new SecurityIncidentEntity(_tmpId,_tmpIncidentType,_tmpSeverity,_tmpDescriptionEnc,_tmpActorEmailEnc,_tmpDetectedAt,_tmpAcknowledged);
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
  public Object getAll(final Continuation<? super List<SecurityIncidentEntity>> $completion) {
    final String _sql = "SELECT * FROM security_incidents";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityIncidentEntity>>() {
      @Override
      @NonNull
      public List<SecurityIncidentEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfIncidentType = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_type");
          final int _cursorIndexOfSeverity = CursorUtil.getColumnIndexOrThrow(_cursor, "severity");
          final int _cursorIndexOfDescriptionEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "description_enc");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfDetectedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "detected_at");
          final int _cursorIndexOfAcknowledged = CursorUtil.getColumnIndexOrThrow(_cursor, "acknowledged");
          final List<SecurityIncidentEntity> _result = new ArrayList<SecurityIncidentEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityIncidentEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpIncidentType;
            _tmpIncidentType = _cursor.getString(_cursorIndexOfIncidentType);
            final String _tmpSeverity;
            _tmpSeverity = _cursor.getString(_cursorIndexOfSeverity);
            final String _tmpDescriptionEnc;
            _tmpDescriptionEnc = _cursor.getString(_cursorIndexOfDescriptionEnc);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final long _tmpDetectedAt;
            _tmpDetectedAt = _cursor.getLong(_cursorIndexOfDetectedAt);
            final boolean _tmpAcknowledged;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfAcknowledged);
            _tmpAcknowledged = _tmp != 0;
            _item = new SecurityIncidentEntity(_tmpId,_tmpIncidentType,_tmpSeverity,_tmpDescriptionEnc,_tmpActorEmailEnc,_tmpDetectedAt,_tmpAcknowledged);
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
