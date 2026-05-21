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
import com.bnhs.edutrack.transaction.BusinessTransactionEntity;
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
public final class BusinessTransactionDao_Impl implements BusinessTransactionDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<BusinessTransactionEntity> __insertionAdapterOfBusinessTransactionEntity;

  private final SharedSQLiteStatement __preparedStmtOfUpdateCompletion;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public BusinessTransactionDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfBusinessTransactionEntity = new EntityInsertionAdapter<BusinessTransactionEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `business_transactions` (`id`,`tx_uuid`,`operation`,`entity_type`,`entity_id`,`status`,`actor_email_enc`,`summary_enc`,`error_enc`,`started_at`,`ended_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final BusinessTransactionEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getTxUuid());
        statement.bindString(3, entity.getOperation());
        statement.bindString(4, entity.getEntityType());
        if (entity.getEntityId() == null) {
          statement.bindNull(5);
        } else {
          statement.bindLong(5, entity.getEntityId());
        }
        statement.bindString(6, entity.getStatus());
        statement.bindString(7, entity.getActorEmailEnc());
        statement.bindString(8, entity.getSummaryEnc());
        statement.bindString(9, entity.getErrorEnc());
        statement.bindLong(10, entity.getStartedAt());
        if (entity.getEndedAt() == null) {
          statement.bindNull(11);
        } else {
          statement.bindLong(11, entity.getEndedAt());
        }
      }
    };
    this.__preparedStmtOfUpdateCompletion = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "UPDATE business_transactions SET status = ?, ended_at = ?, error_enc = ? WHERE id = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM business_transactions";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final BusinessTransactionEntity tx,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfBusinessTransactionEntity.insertAndReturnId(tx);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object updateCompletion(final long id, final String status, final long endedAt,
      final String errorEnc, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfUpdateCompletion.acquire();
        int _argIndex = 1;
        _stmt.bindString(_argIndex, status);
        _argIndex = 2;
        _stmt.bindLong(_argIndex, endedAt);
        _argIndex = 3;
        _stmt.bindString(_argIndex, errorEnc);
        _argIndex = 4;
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
          __preparedStmtOfUpdateCompletion.release(_stmt);
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
      final Continuation<? super List<BusinessTransactionEntity>> $completion) {
    final String _sql = "SELECT * FROM business_transactions ORDER BY started_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<BusinessTransactionEntity>>() {
      @Override
      @NonNull
      public List<BusinessTransactionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTxUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "tx_uuid");
          final int _cursorIndexOfOperation = CursorUtil.getColumnIndexOrThrow(_cursor, "operation");
          final int _cursorIndexOfEntityType = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_type");
          final int _cursorIndexOfEntityId = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_id");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfErrorEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "error_enc");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<BusinessTransactionEntity> _result = new ArrayList<BusinessTransactionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final BusinessTransactionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpTxUuid;
            _tmpTxUuid = _cursor.getString(_cursorIndexOfTxUuid);
            final String _tmpOperation;
            _tmpOperation = _cursor.getString(_cursorIndexOfOperation);
            final String _tmpEntityType;
            _tmpEntityType = _cursor.getString(_cursorIndexOfEntityType);
            final Long _tmpEntityId;
            if (_cursor.isNull(_cursorIndexOfEntityId)) {
              _tmpEntityId = null;
            } else {
              _tmpEntityId = _cursor.getLong(_cursorIndexOfEntityId);
            }
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final String _tmpErrorEnc;
            _tmpErrorEnc = _cursor.getString(_cursorIndexOfErrorEnc);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new BusinessTransactionEntity(_tmpId,_tmpTxUuid,_tmpOperation,_tmpEntityType,_tmpEntityId,_tmpStatus,_tmpActorEmailEnc,_tmpSummaryEnc,_tmpErrorEnc,_tmpStartedAt,_tmpEndedAt);
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
      final Continuation<? super List<BusinessTransactionEntity>> $completion) {
    final String _sql = "SELECT * FROM business_transactions WHERE started_at >= ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, since);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<BusinessTransactionEntity>>() {
      @Override
      @NonNull
      public List<BusinessTransactionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTxUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "tx_uuid");
          final int _cursorIndexOfOperation = CursorUtil.getColumnIndexOrThrow(_cursor, "operation");
          final int _cursorIndexOfEntityType = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_type");
          final int _cursorIndexOfEntityId = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_id");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfErrorEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "error_enc");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<BusinessTransactionEntity> _result = new ArrayList<BusinessTransactionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final BusinessTransactionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpTxUuid;
            _tmpTxUuid = _cursor.getString(_cursorIndexOfTxUuid);
            final String _tmpOperation;
            _tmpOperation = _cursor.getString(_cursorIndexOfOperation);
            final String _tmpEntityType;
            _tmpEntityType = _cursor.getString(_cursorIndexOfEntityType);
            final Long _tmpEntityId;
            if (_cursor.isNull(_cursorIndexOfEntityId)) {
              _tmpEntityId = null;
            } else {
              _tmpEntityId = _cursor.getLong(_cursorIndexOfEntityId);
            }
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final String _tmpErrorEnc;
            _tmpErrorEnc = _cursor.getString(_cursorIndexOfErrorEnc);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new BusinessTransactionEntity(_tmpId,_tmpTxUuid,_tmpOperation,_tmpEntityType,_tmpEntityId,_tmpStatus,_tmpActorEmailEnc,_tmpSummaryEnc,_tmpErrorEnc,_tmpStartedAt,_tmpEndedAt);
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
  public Object getAll(final Continuation<? super List<BusinessTransactionEntity>> $completion) {
    final String _sql = "SELECT * FROM business_transactions";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<BusinessTransactionEntity>>() {
      @Override
      @NonNull
      public List<BusinessTransactionEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfTxUuid = CursorUtil.getColumnIndexOrThrow(_cursor, "tx_uuid");
          final int _cursorIndexOfOperation = CursorUtil.getColumnIndexOrThrow(_cursor, "operation");
          final int _cursorIndexOfEntityType = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_type");
          final int _cursorIndexOfEntityId = CursorUtil.getColumnIndexOrThrow(_cursor, "entity_id");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfActorEmailEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "actor_email_enc");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfErrorEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "error_enc");
          final int _cursorIndexOfStartedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "started_at");
          final int _cursorIndexOfEndedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "ended_at");
          final List<BusinessTransactionEntity> _result = new ArrayList<BusinessTransactionEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final BusinessTransactionEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpTxUuid;
            _tmpTxUuid = _cursor.getString(_cursorIndexOfTxUuid);
            final String _tmpOperation;
            _tmpOperation = _cursor.getString(_cursorIndexOfOperation);
            final String _tmpEntityType;
            _tmpEntityType = _cursor.getString(_cursorIndexOfEntityType);
            final Long _tmpEntityId;
            if (_cursor.isNull(_cursorIndexOfEntityId)) {
              _tmpEntityId = null;
            } else {
              _tmpEntityId = _cursor.getLong(_cursorIndexOfEntityId);
            }
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpActorEmailEnc;
            _tmpActorEmailEnc = _cursor.getString(_cursorIndexOfActorEmailEnc);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final String _tmpErrorEnc;
            _tmpErrorEnc = _cursor.getString(_cursorIndexOfErrorEnc);
            final long _tmpStartedAt;
            _tmpStartedAt = _cursor.getLong(_cursorIndexOfStartedAt);
            final Long _tmpEndedAt;
            if (_cursor.isNull(_cursorIndexOfEndedAt)) {
              _tmpEndedAt = null;
            } else {
              _tmpEndedAt = _cursor.getLong(_cursorIndexOfEndedAt);
            }
            _item = new BusinessTransactionEntity(_tmpId,_tmpTxUuid,_tmpOperation,_tmpEntityType,_tmpEntityId,_tmpStatus,_tmpActorEmailEnc,_tmpSummaryEnc,_tmpErrorEnc,_tmpStartedAt,_tmpEndedAt);
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
