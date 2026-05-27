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
public final class BackupMetaDao_Impl implements BackupMetaDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<BackupMetaEntity> __insertionAdapterOfBackupMetaEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteById;

  public BackupMetaDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfBackupMetaEntity = new EntityInsertionAdapter<BackupMetaEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `backup_history` (`id`,`file_name`,`file_path`,`record_count`,`checksum_sha256`,`created_at`) VALUES (nullif(?, 0),?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final BackupMetaEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getFileName());
        statement.bindString(3, entity.getFilePath());
        statement.bindLong(4, entity.getRecordCount());
        statement.bindString(5, entity.getChecksumSha256());
        statement.bindLong(6, entity.getCreatedAt());
      }
    };
    this.__preparedStmtOfDeleteById = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM backup_history WHERE id = ?";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final BackupMetaEntity meta, final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfBackupMetaEntity.insertAndReturnId(meta);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object deleteById(final long id, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfDeleteById.acquire();
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
          __preparedStmtOfDeleteById.release(_stmt);
        }
      }
    }, $completion);
  }

  @Override
  public Object getAll(final Continuation<? super List<BackupMetaEntity>> $completion) {
    final String _sql = "SELECT * FROM backup_history ORDER BY created_at DESC";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<BackupMetaEntity>>() {
      @Override
      @NonNull
      public List<BackupMetaEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfFileName = CursorUtil.getColumnIndexOrThrow(_cursor, "file_name");
          final int _cursorIndexOfFilePath = CursorUtil.getColumnIndexOrThrow(_cursor, "file_path");
          final int _cursorIndexOfRecordCount = CursorUtil.getColumnIndexOrThrow(_cursor, "record_count");
          final int _cursorIndexOfChecksumSha256 = CursorUtil.getColumnIndexOrThrow(_cursor, "checksum_sha256");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final List<BackupMetaEntity> _result = new ArrayList<BackupMetaEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final BackupMetaEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpFileName;
            _tmpFileName = _cursor.getString(_cursorIndexOfFileName);
            final String _tmpFilePath;
            _tmpFilePath = _cursor.getString(_cursorIndexOfFilePath);
            final int _tmpRecordCount;
            _tmpRecordCount = _cursor.getInt(_cursorIndexOfRecordCount);
            final String _tmpChecksumSha256;
            _tmpChecksumSha256 = _cursor.getString(_cursorIndexOfChecksumSha256);
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            _item = new BackupMetaEntity(_tmpId,_tmpFileName,_tmpFilePath,_tmpRecordCount,_tmpChecksumSha256,_tmpCreatedAt);
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
