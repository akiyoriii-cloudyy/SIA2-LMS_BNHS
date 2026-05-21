package com.bnhs.edutrack.securityaudit

object IncidentType {
    const val BRUTE_FORCE = "BRUTE_FORCE"
    const val EXCESSIVE_LOGIN_FAILURES = "EXCESSIVE_LOGIN_FAILURES"
    const val SUSPICIOUS_PASSWORD_RESET = "SUSPICIOUS_PASSWORD_RESET"
    const val ACCESS_DENIED = "ACCESS_DENIED"
    const val UNAUTHORIZED_RESTORE = "UNAUTHORIZED_RESTORE"
    const val SECURITY_BREACH = "SECURITY_BREACH"
}

object Severity {
    const val LOW = "LOW"
    const val MEDIUM = "MEDIUM"
    const val HIGH = "HIGH"
    const val CRITICAL = "CRITICAL"
}

object RiskLevel {
    const val GREEN = "GREEN"
    const val YELLOW = "YELLOW"
    const val RED = "RED"
}

object ReportPeriod {
    const val LAST_24H = "LAST_24H"
    const val LAST_7D = "LAST_7D"
}
