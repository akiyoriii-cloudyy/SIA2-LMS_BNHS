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
public final class RecordAuditLogDao_Impl implements RecordAuditLogDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<RecordAuditLogEntity> __insertionAdapterOfRecordAuditLogEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public RecordAuditLogDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfRecordAuditLogEntity = new EntityInsertionAdapter<RecordAuditLogEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `record_audit_logs` (`id`,`action`,`entity_type`,`entity_id`,`actor_email`,`summary`,`created_at`) VALUES (nullif(?, 0),?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final RecordAuditLogEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getAction());
        statement.bindString(3, entity.getEntityType());
        statement.bindLong(4, entity.getEntityId());
        statement.bindString(5, entity.getActorEmail());
        statement.bindString(6, entity.getSummary());
        statement.bindLong(7, entity.getCreatedAt());
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM record_audit_logs";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final RecordAuditLogEntity log,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfRecordAuditLogEntity.insertAndReturnId(log);
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
  public Object getAll(final Continuation<? super List<RecordAuditLogEntity>> $completion) {
    final String _sql = "SELECT * FROM record_audit_logs ORDER BY created_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<RecordAuditLogEntity>>() {
      @Override
      @NonNull
      public List<RecordAuditLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfAction = CursorUtil.getColumnIndexOrThrow(_cursor, "action");
          final int _cursorIndexOfEntityType = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_type");
          final int _cursorIndexOfEntityId = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_id");
          final int _cursorIndexOfActorEmail = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email");
          final int _cursorIndexOfSummary = CursorUtil.getColumnIndexOrThrow(_cursor, "summary");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<RecordAuditLogEntity> _result = new ArrayList<RecordAuditLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final RecordAuditLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpAction;
            _tmpAction = _cursor.getString(_cursorIndexOfAction);
            final String _tmpEntityType;
            _tmpEntityType = _cursor.getString(_cursorIndexOfEntityType);
            final long _tmpEntityId;
            _tmpEntityId = _cursor.getLong(_cursorIndexOfEntityId);
            final String _tmpActorEmail;
            _tmpActorEmail = _cursor.getString(_cursorIndexOfActorEmail);
            final String _tmpSummary;
            _tmpSummary = _cursor.getString(_cursorIndexOfSummary);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new RecordAuditLogEntity(_tmpId,_tmpAction,_tmpEntityType,_tmpEntityId,_tmpActorEmail,_tmpSummary,_tmpCreatedAt);
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
      final Continuation<? super List<RecordAuditLogEntity>> $completion) {
    final String _sql = "SELECT * FROM record_audit_logs ORDER BY created_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<RecordAuditLogEntity>>() {
      @Override
      @NonNull
      public List<RecordAuditLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfAction = CursorUtil.getColumnIndexOrThrow(_cursor, "action");
          final int _cursorIndexOfEntityType = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_type");
          final int _cursorIndexOfEntityId = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_id");
          final int _cursorIndexOfActorEmail = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email");
          final int _cursorIndexOfSummary = CursorUtil.getColumnIndexOrThrow(_cursor, "summary");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<RecordAuditLogEntity> _result = new ArrayList<RecordAuditLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final RecordAuditLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpAction;
            _tmpAction = _cursor.getString(_cursorIndexOfAction);
            final String _tmpEntityType;
            _tmpEntityType = _cursor.getString(_cursorIndexOfEntityType);
            final long _tmpEntityId;
            _tmpEntityId = _cursor.getLong(_cursorIndexOfEntityId);
            final String _tmpActorEmail;
            _tmpActorEmail = _cursor.getString(_cursorIndexOfActorEmail);
            final String _tmpSummary;
            _tmpSummary = _cursor.getString(_cursorIndexOfSummary);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new RecordAuditLogEntity(_tmpId,_tmpAction,_tmpEntityType,_tmpEntityId,_tmpActorEmail,_tmpSummary,_tmpCreatedAt);
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
