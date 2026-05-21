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
import com.bnhs.edutrack.securityaudit.SecurityAuditReportEntity;
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
public final class SecurityAuditReportDao_Impl implements SecurityAuditReportDao {
  private final RoomDatabase __db;

  private final EntityInsertionAdapter<SecurityAuditReportEntity> __insertionAdapterOfSecurityAuditReportEntity;

  private final SharedSQLiteStatement __preparedStmtOfDeleteAll;

  public SecurityAuditReportDao_Impl(@NonNull final RoomDatabase __db) {
    this.__db = __db;
    this.__insertionAdapterOfSecurityAuditReportEntity = new EntityInsertionAdapter<SecurityAuditReportEntity>(__db) {
      @Override
      @NonNull
      protected String createQuery() {
        return "INSERT OR ABORT INTO `security_audit_reports` (`id`,`period_label`,`risk_level`,`summary_enc`,`failed_login_count`,`incident_count`,`successful_login_count`,`generated_at`) VALUES (nullif(?, 0),?,?,?,?,?,?,?)";
      }

      @Override
      protected void bind(@NonNull final SupportSQLiteStatement statement,
          @NonNull final SecurityAuditReportEntity entity) {
        statement.bindLong(1, entity.getId());
        statement.bindString(2, entity.getPeriodLabel());
        statement.bindString(3, entity.getRiskLevel());
        statement.bindString(4, entity.getSummaryEnc());
        statement.bindLong(5, entity.getFailedLoginCount());
        statement.bindLong(6, entity.getIncidentCount());
        statement.bindLong(7, entity.getSuccessfulLoginCount());
        statement.bindLong(8, entity.getGeneratedAt());
      }
    };
    this.__preparedStmtOfDeleteAll = new SharedSQLiteStatement(__db) {
      @Override
      @NonNull
      public String createQuery() {
        final String _query = "DELETE FROM security_audit_reports";
        return _query;
      }
    };
  }

  @Override
  public Object insert(final SecurityAuditReportEntity report,
      final Continuation<? super Long> $completion) {
    return CoroutinesRoom.execute(__db, true, new Callable<Long>() {
      @Override
      @NonNull
      public Long call() throws Exception {
        __db.beginTransaction();
        try {
          final Long _result = __insertionAdapterOfSecurityAuditReportEntity.insertAndReturnId(report);
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
  public Object latest(final Continuation<? super SecurityAuditReportEntity> $completion) {
    final String _sql = "SELECT * FROM security_audit_reports ORDER BY generated_at DESC LIMIT 1";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<SecurityAuditReportEntity>() {
      @Override
      @Nullable
      public SecurityAuditReportEntity call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfPeriodLabel = CursorUtil.getColumnIndexOrThrow(_cursor, "period_label");
          final int _cursorIndexOfRiskLevel = CursorUtil.getColumnIndexOrThrow(_cursor, "risk_level");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfFailedLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "failed_login_count");
          final int _cursorIndexOfIncidentCount = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_count");
          final int _cursorIndexOfSuccessfulLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "successful_login_count");
          final int _cursorIndexOfGeneratedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "generated_at");
          final SecurityAuditReportEntity _result;
          if (_cursor.moveToFirst()) {
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpPeriodLabel;
            _tmpPeriodLabel = _cursor.getString(_cursorIndexOfPeriodLabel);
            final String _tmpRiskLevel;
            _tmpRiskLevel = _cursor.getString(_cursorIndexOfRiskLevel);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final int _tmpFailedLoginCount;
            _tmpFailedLoginCount = _cursor.getInt(_cursorIndexOfFailedLoginCount);
            final int _tmpIncidentCount;
            _tmpIncidentCount = _cursor.getInt(_cursorIndexOfIncidentCount);
            final int _tmpSuccessfulLoginCount;
            _tmpSuccessfulLoginCount = _cursor.getInt(_cursorIndexOfSuccessfulLoginCount);
            final long _tmpGeneratedAt;
            _tmpGeneratedAt = _cursor.getLong(_cursorIndexOfGeneratedAt);
            _result = new SecurityAuditReportEntity(_tmpId,_tmpPeriodLabel,_tmpRiskLevel,_tmpSummaryEnc,_tmpFailedLoginCount,_tmpIncidentCount,_tmpSuccessfulLoginCount,_tmpGeneratedAt);
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
  public Object recent(final int limit,
      final Continuation<? super List<SecurityAuditReportEntity>> $completion) {
    final String _sql = "SELECT * FROM security_audit_reports ORDER BY generated_at DESC LIMIT ?";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 1);
    int _argIndex = 1;
    _statement.bindLong(_argIndex, limit);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityAuditReportEntity>>() {
      @Override
      @NonNull
      public List<SecurityAuditReportEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfPeriodLabel = CursorUtil.getColumnIndexOrThrow(_cursor, "period_label");
          final int _cursorIndexOfRiskLevel = CursorUtil.getColumnIndexOrThrow(_cursor, "risk_level");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfFailedLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "failed_login_count");
          final int _cursorIndexOfIncidentCount = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_count");
          final int _cursorIndexOfSuccessfulLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "successful_login_count");
          final int _cursorIndexOfGeneratedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "generated_at");
          final List<SecurityAuditReportEntity> _result = new ArrayList<SecurityAuditReportEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityAuditReportEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpPeriodLabel;
            _tmpPeriodLabel = _cursor.getString(_cursorIndexOfPeriodLabel);
            final String _tmpRiskLevel;
            _tmpRiskLevel = _cursor.getString(_cursorIndexOfRiskLevel);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final int _tmpFailedLoginCount;
            _tmpFailedLoginCount = _cursor.getInt(_cursorIndexOfFailedLoginCount);
            final int _tmpIncidentCount;
            _tmpIncidentCount = _cursor.getInt(_cursorIndexOfIncidentCount);
            final int _tmpSuccessfulLoginCount;
            _tmpSuccessfulLoginCount = _cursor.getInt(_cursorIndexOfSuccessfulLoginCount);
            final long _tmpGeneratedAt;
            _tmpGeneratedAt = _cursor.getLong(_cursorIndexOfGeneratedAt);
            _item = new SecurityAuditReportEntity(_tmpId,_tmpPeriodLabel,_tmpRiskLevel,_tmpSummaryEnc,_tmpFailedLoginCount,_tmpIncidentCount,_tmpSuccessfulLoginCount,_tmpGeneratedAt);
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
  public Object getAll(final Continuation<? super List<SecurityAuditReportEntity>> $completion) {
    final String _sql = "SELECT * FROM security_audit_reports";
    final RoomSQLiteQuery _statement = RoomSQLiteQuery.acquire(_sql, 0);
    final CancellationSignal _cancellationSignal = DBUtil.createCancellationSignal();
    return CoroutinesRoom.execute(__db, false, _cancellationSignal, new Callable<List<SecurityAuditReportEntity>>() {
      @Override
      @NonNull
      public List<SecurityAuditReportEntity> call() throws Exception {
        final Cursor _cursor = DBUtil.query(__db, _statement, false, null);
        try {
          final int _cursorIndexOfId = CursorUtil.getColumnIndexOrThrow(_cursor, "id");
          final int _cursorIndexOfPeriodLabel = CursorUtil.getColumnIndexOrThrow(_cursor, "period_label");
          final int _cursorIndexOfRiskLevel = CursorUtil.getColumnIndexOrThrow(_cursor, "risk_level");
          final int _cursorIndexOfSummaryEnc = CursorUtil.getColumnIndexOrThrow(_cursor, "summary_enc");
          final int _cursorIndexOfFailedLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "failed_login_count");
          final int _cursorIndexOfIncidentCount = CursorUtil.getColumnIndexOrThrow(_cursor, "incident_count");
          final int _cursorIndexOfSuccessfulLoginCount = CursorUtil.getColumnIndexOrThrow(_cursor, "successful_login_count");
          final int _cursorIndexOfGeneratedAt = CursorUtil.getColumnIndexOrThrow(_cursor, "generated_at");
          final List<SecurityAuditReportEntity> _result = new ArrayList<SecurityAuditReportEntity>(_cursor.getCount());
          while (_cursor.moveToNext()) {
            final SecurityAuditReportEntity _item;
            final long _tmpId;
            _tmpId = _cursor.getLong(_cursorIndexOfId);
            final String _tmpPeriodLabel;
            _tmpPeriodLabel = _cursor.getString(_cursorIndexOfPeriodLabel);
            final String _tmpRiskLevel;
            _tmpRiskLevel = _cursor.getString(_cursorIndexOfRiskLevel);
            final String _tmpSummaryEnc;
            _tmpSummaryEnc = _cursor.getString(_cursorIndexOfSummaryEnc);
            final int _tmpFailedLoginCount;
            _tmpFailedLoginCount = _cursor.getInt(_cursorIndexOfFailedLoginCount);
            final int _tmpIncidentCount;
            _tmpIncidentCount = _cursor.getInt(_cursorIndexOfIncidentCount);
            final int _tmpSuccessfulLoginCount;
            _tmpSuccessfulLoginCount = _cursor.getInt(_cursorIndexOfSuccessfulLoginCount);
            final long _tmpGeneratedAt;
            _tmpGeneratedAt = _cursor.getLong(_cursorIndexOfGeneratedAt);
            _item = new SecurityAuditReportEntity(_tmpId,_tmpPeriodLabel,_tmpRiskLevel,_tmpSummaryEnc,_tmpFailedLoginCount,_tmpIncidentCount,_tmpSuccessfulLoginCount,_tmpGeneratedAt);
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
