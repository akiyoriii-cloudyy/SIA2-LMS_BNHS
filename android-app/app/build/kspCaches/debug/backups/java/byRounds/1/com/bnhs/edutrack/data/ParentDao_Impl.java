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
public final class ParentDao_Impl implements ParentDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<ParentEntity> __insertionAdapterOfParentEntity;

  private final EntityDeletionOrUpdateAdapter<ParentEntity> __updateAdapterOfParentEntity;

  private final SharedSQLiteStatement __preparedStmtOfUpdateContactInfo;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public ParentDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfParentEntity = new EntityInsertionAdapter<ParentEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR REPLACE INTO `parents` (`id`,`student_id`,`name`,`contact`,`email`,`relationship`,`is_primary`,`created_at`,`updated_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ParentEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindLong(2, entity.getStudentId());
        statement.bindString(3, entity.getName());
        statement.bindString(4, entity.getContact());
        statement.bindString(5, entity.getEmail());
        statement.bindString(6, entity.getRelationship());
        final int _tmp = entity.isPrimary() ? 1 : 0;
        statement.bindLong(7, _tmp);
        statement.bindLong(8, entity.getCreatedAt());
        statement.bindLong(9, entity.getUpdatedAt());
      }
    };
    this.__updateAdapterOfParentEntity = new EntityDeletionOrUpdateAdapter<ParentEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "UPDATE OR ABORT `parents` SET `id` = ?,`student_id` = ?,`name` = ?,`contact` = ?,`email` = ?,`relationship` = ?,`is_primary` = ?,`created_at` = ?,`updated_at` = ? WHERE `id` = ?";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final ParentEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindLong(2, entity.getStudentId());
        statement.bindString(3, entity.getName());
        statement.bindString(4, entity.getContact());
        statement.bindString(5, entity.getEmail());
        statement.bindString(6, entity.getRelationship());
        final int _tmp = entity.isPrimary() ? 1 : 0;
        statement.bindLong(7, _tmp);
        statement.bindLong(8, entity.getCreatedAt());
        statement.bindLong(9, entity.getUpdatedAt());
        statement.bindLong(10, entity.getId());
      }
    };
    this.__preparedStmtOfUpdateContactInfo = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "UPDATE parents SET name = ?, contact = ?, updated_at = ? WHERE id = ?";
        return _query;
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM parents";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final ParentEntity parent, final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfParentEntity.insertAndReturnId(parent);
          __db.setTransactionSuccessful();
          return _result;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object update(final ParentEntity parent, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        __db.beginTransaction();
        try {
          __updateAdapterOfParentEntity.handle(parent);
          __db.setTransactionSuccessful();
          return Unit.INSTANCE;
        } finally {
          __db.endTransaction();
        }
      }
    }, $completion);
  }

  @Override
  public Object updateContactInfo(final long id, final String name, final String contact,
      final long updatedAt, final Continuation<? super Unit> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Unit>() {
      @Override
      @NonNull
      public Unit call() throws Exception {
        final SupportSQLiteStatement _stmt = __preparedStmtOfUpdateContactInfo.acquire();
        int _argIndex = 1;
        _stmt.bindString(_argIndex, name);
        _argIndex = 2;
        _stmt.bindString(_argIndex, contact);
        _argIndex = 3;
        _stmt.bindLong(_argIndex, updatedAt);
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
          __preparedStmtOfUpdateContactInfo.release(_stmt);
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
  public Object getAll(final Continuation<? super List<ParentEntity>> $completion) {
    final String _sql = "SELECT * FROM parents";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<ParentEntity>>() {
      @Override
      @NonNull
      public List<ParentEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfName = CursorUtil.getColumnIndexOrThrow(_cursor, "name");
          final int _cursorIndexOfContact = CursorUtil.getColumnIndexOrThrow(_cursor, "contact");
          final int _cursorIndexOfEmail = CursorUtil.getColumnIndexOrThrow(_cursor, "email");
          final int _cursorIndexOfRelationship = CursorUtil.getColumnIndexOrThrow(_cursor, "relationship");
          final int _cursorIndexOfIsPrimary = CursorUtil.getColumnIndexOrThrow(_cursor, "is_primary");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final int _cursorIndexOfUpdatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "updated_at");
          final List<ParentEntity> _result = new ArrayList<ParentEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final ParentEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpName;
            _tmpName = _cursor.getString(_cursorIndexOfName);
            final String _tmpContact;
            _tmpContact = _cursor.getString(_cursorIndexOfContact);
            final String _tmpEmail;
            _tmpEmail = _cursor.getString(_cursorIndexOfEmail);
            final String _tmpRelationship;
            _tmpRelationship = _cursor.getString(_cursorIndexOfRelationship);
            final boolean _tmpIsPrimary;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfIsPrimary);
            _tmpIsPrimary = _tmp != 0;
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            final long _tmpUpdatedAt;
            _tmpUpdatedAt = _cursor.getLong(_cursorIndexOfUpdatedAt);
            _item = new ParentEntity(_tmpId,_tmpStudentId,_tmpName,_tmpContact,_tmpEmail,_tmpRelationship,_tmpIsPrimary,_tmpCreatedAt,_tmpUpdatedAt);
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
  public Object primaryForStudent(final long studentId,
      final Continuation<? super ParentEntity> $completion) {
    final String _sql = "SELECT * FROM parents WHERE student_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, studentId);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<ParentEntity>() {
      @Override
      @Nullable
      public ParentEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfStudentId = CursorUtil.getColumnIndexOrThrow(_cursor, "student_id");
          final int _cursorIndexOfName = CursorUtil.getColumnIndexOrThrow(_cursor, "name");
          final int _cursorIndexOfContact = CursorUtil.getColumnIndexOrThrow(_cursor, "contact");
          final int _cursorIndexOfEmail = CursorUtil.getColumnIndexOrThrow(_cursor, "email");
          final int _cursorIndexOfRelationship = CursorUtil.getColumnIndexOrThrow(_cursor, "relationship");
          final int _cursorIndexOfIsPrimary = CursorUtil.getColumnIndexOrThrow(_cursor, "is_primary");
          final int _cursorIndexOfCreatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "created_at");
          final int _cursorIndexOfUpdatedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "updated_at");
          final ParentEntity _result;
          if (_cursor.moveToFirst()) {
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final long _tmpStudentId;
            _tmpStudentId = _cursor.getLong(_cursorIndexOfStudentId);
            final String _tmpName;
            _tmpName = _cursor.getString(_cursorIndexOfName);
            final String _tmpContact;
            _tmpContact = _cursor.getString(_cursorIndexOfContact);
            final String _tmpEmail;
            _tmpEmail = _cursor.getString(_cursorIndexOfEmail);
            final String _tmpRelationship;
            _tmpRelationship = _cursor.getString(_cursorIndexOfRelationship);
            final boolean _tmpIsPrimary;
            final int _tmp;
            _tmp = _cursor.getInt(_cursorIndexOfIsPrimary);
            _tmpIsPrimary = _tmp != 0;
            final long _tmpCreatedAt;
            _tmpCreatedAt = _cursor.getLong(_cursorIndexOfCreatedAt);
            final long _tmpUpdatedAt;
            _tmpUpdatedAt = _cursor.getLong(_cursorIndexOfUpdatedAt);
            _result = new ParentEntity(_tmpId,_tmpStudentId,_tmpName,_tmpContact,_tmpEmail,_tmpRelationship,_tmpIsPrimary,_tmpCreatedAt,_tmpUpdatedAt);
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

  @NonNull
  public static List<Class<?>> getRequiredConverters() {
    return Collections.emptyList();
  }
}
