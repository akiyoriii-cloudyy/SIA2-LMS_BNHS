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
public final class AlertLogDao_Impl implements AlertLogDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<AlertLogEntity> __insertionAdapterOfAlertLogEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public AlertLogDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfAlertLogEntity = new EntityInsertionAdapter<AlertLogEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `alert_logs` (`id`,`student_id`,`alert_type`,`message`,`recipient_name`,`recipient_contact`,`status`,`error_detail`,`sent_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final AlertLogEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindLong(2, entity.getStudentId());
        statement.bindString(3, entity.getAlertType());
        statement.bindString(4, entity.getMessage());
        statement.bindString(5, entity.getRecipientName());
        statement.bindString(6, entity.getRecipientContact());
        statement.bindString(7, entity.getStatus());
        if (entity.getErrorDetail() == null) {
          statement.bindNull(8);
        } else {
          statement.bindString(8, entity.getErrorDetail());
        }
        statement.bindLong(9, entity.getSentAt());
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM alert_logs";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final AlertLogEntity log, final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfAlertLogEntity.insertAndReturnId(log);
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
  public Object getAll(final Continuation<? super List<AlertLogEntity>> $completion) {
    final String _sql = "SELECT * FROM alert_logs ORDER BY sent_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<AlertLogEntity>>() {
      @Override
      @NonNull
      public List<AlertLogEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfAlertType = CursorUtil.getColumnIndexOrThrow(_cursor, "alert_type");
          final int _cursorIndexOfMessage = CursorUtil.getColumnIndexOrThrow(_cursor, "message");
          final int _cursorIndexOfRecipientName = CursorUtil.getColumnIndexOrThrow(_cursor, "recipient_name");
          final int _cursorIndexOfRecipientContact = CursorUtil.getColumnIndexOrThrow(_cursor, "recipient_contact");
          final int _cursorIndexOfStatus = CursorUtil.getColumnIndexOrThrow(_cursor, "status");
          final int _cursorIndexOfErrorDetail = CursorUtil.getColumnIndexOrThrow(_cursor, "error_detail");
          final int _cursorIndexOfSentAt = CursorUtil.getColumnIndexOrThrow(_cursor, "sent_at");
          final List<AlertLogEntity> _result = new ArrayList<AlertLogEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final AlertLogEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpAlertType;
            _tmpAlertType = _cursor.getString(_cursorIndexOfAlertType);
            final String _tmpMessage;
            _tmpMessage = _cursor.getString(_cursorIndexOfMessage);
            final String _tmpRecipientName;
            _tmpRecipientName = _cursor.getString(_cursorIndexOfRecipientName);
            final String _tmpRecipientContact;
            _tmpRecipientContact = _cursor.getString(_cursorIndexOfRecipientContact);
            final String _tmpStatus;
            _tmpStatus = _cursor.getString(_cursorIndexOfStatus);
            final String _tmpErrorDetail;
            if (_cursor.isNull(_cursorIndexOfErrorDetail)) {
              _tmpErrorDetail = null;
            } else {
              _tmpErrorDetail = _cursor.getString(_cursorIndexOfErrorDetail);
            }
            final long _tmpSentAt;
            _tmpSentAt = _cursor.getLong(_cursorIndexOfSentAt);
            _item = new AlertLogEntity(_tmpId,_tmpStudentId,_tmpAlertType,_tmpMessage,_tmpRecipientName,_tmpRecipientContact,_tmpStatus,_tmpErrorDetail,_tmpSentAt);
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
