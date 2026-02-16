CREATE TABLE school_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    grade_level TINYINT UNSIGNED NOT NULL,
    track VARCHAR(255) NULL,
    strand VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    lrn VARCHAR(255) NULL UNIQUE,
    first_name VARCHAR(255) NOT NULL,
    middle_name VARCHAR(255) NULL,
    last_name VARCHAR(255) NOT NULL,
    suffix VARCHAR(255) NULL,
    sex VARCHAR(20) NULL,
    date_of_birth DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE teachers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT teachers_user_id_foreign FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE subject_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    school_year_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_subject_assignment (section_id, subject_id, school_year_id),
    CONSTRAINT subject_assignments_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE SET NULL,
    CONSTRAINT subject_assignments_section_id_foreign FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE CASCADE,
    CONSTRAINT subject_assignments_subject_id_foreign FOREIGN KEY (subject_id) REFERENCES subjects (id) ON DELETE CASCADE,
    CONSTRAINT subject_assignments_school_year_id_foreign FOREIGN KEY (school_year_id) REFERENCES school_years (id) ON DELETE CASCADE
);

CREATE TABLE enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    school_year_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_student_year (student_id, school_year_id),
    CONSTRAINT enrollments_student_id_foreign FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    CONSTRAINT enrollments_section_id_foreign FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE CASCADE,
    CONSTRAINT enrollments_school_year_id_foreign FOREIGN KEY (school_year_id) REFERENCES school_years (id) ON DELETE CASCADE
);

CREATE TABLE grade_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id BIGINT UNSIGNED NOT NULL,
    subject_assignment_id BIGINT UNSIGNED NOT NULL,
    quarter TINYINT UNSIGNED NOT NULL,
    quiz DECIMAL(5,2) NULL,
    assignment DECIMAL(5,2) NULL,
    exam DECIMAL(5,2) NULL,
    quarter_grade DECIMAL(5,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_grade_quarter (enrollment_id, subject_assignment_id, quarter),
    CONSTRAINT grade_entries_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE,
    CONSTRAINT grade_entries_subject_assignment_id_foreign FOREIGN KEY (subject_assignment_id) REFERENCES subject_assignments (id) ON DELETE CASCADE
);

CREATE TABLE subject_final_grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id BIGINT UNSIGNED NOT NULL,
    subject_assignment_id BIGINT UNSIGNED NOT NULL,
    q1 DECIMAL(5,2) NULL,
    q2 DECIMAL(5,2) NULL,
    q3 DECIMAL(5,2) NULL,
    q4 DECIMAL(5,2) NULL,
    final_grade DECIMAL(5,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_subject_final_grade (enrollment_id, subject_assignment_id),
    CONSTRAINT subject_final_grades_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE,
    CONSTRAINT subject_final_grades_subject_assignment_id_foreign FOREIGN KEY (subject_assignment_id) REFERENCES subject_assignments (id) ON DELETE CASCADE
);

CREATE TABLE report_cards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id BIGINT UNSIGNED NOT NULL UNIQUE,
    general_average DECIMAL(5,2) NULL,
    remarks VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT report_cards_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES enrollments (id) ON DELETE CASCADE
);

CREATE TABLE report_card_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_card_id BIGINT UNSIGNED NOT NULL,
    subject_assignment_id BIGINT UNSIGNED NOT NULL,
    q1 DECIMAL(5,2) NULL,
    q2 DECIMAL(5,2) NULL,
    q3 DECIMAL(5,2) NULL,
    q4 DECIMAL(5,2) NULL,
    final_grade DECIMAL(5,2) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY uniq_report_card_item (report_card_id, subject_assignment_id),
    CONSTRAINT report_card_items_report_card_id_foreign FOREIGN KEY (report_card_id) REFERENCES report_cards (id) ON DELETE CASCADE,
    CONSTRAINT report_card_items_subject_assignment_id_foreign FOREIGN KEY (subject_assignment_id) REFERENCES subject_assignments (id) ON DELETE CASCADE
);

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

CREATE TABLE user_roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY user_roles_user_id_role_id_unique (user_id, role_id),
    CONSTRAINT user_roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT user_roles_role_id_foreign FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE guardians (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    phone VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT guardians_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE guardian_students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guardian_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    relationship VARCHAR(50) NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    receive_sms TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY guardian_students_guardian_id_student_id_unique (guardian_id, student_id),
    CONSTRAINT guardian_students_guardian_id_foreign FOREIGN KEY (guardian_id) REFERENCES guardians(id) ON DELETE CASCADE,
    CONSTRAINT guardian_students_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_year_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT courses_school_year_id_foreign FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE,
    CONSTRAINT courses_section_id_foreign FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT courses_subject_id_foreign FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    CONSTRAINT courses_teacher_id_foreign FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE course_materials (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NULL,
    file_path VARCHAR(255) NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT course_materials_course_id_foreign FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    instructions TEXT NULL,
    points DECIMAL(8,2) NOT NULL DEFAULT 100.00,
    due_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT assignments_course_id_foreign FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

CREATE TABLE assignment_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assignment_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    content TEXT NULL,
    file_path VARCHAR(255) NULL,
    submitted_at TIMESTAMP NULL,
    score DECIMAL(8,2) NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY assignment_submissions_assignment_id_student_id_unique (assignment_id, student_id),
    CONSTRAINT assignment_submissions_assignment_id_foreign FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    CONSTRAINT assignment_submissions_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

CREATE TABLE attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id BIGINT UNSIGNED NOT NULL,
    course_id BIGINT UNSIGNED NULL,
    attendance_date DATE NOT NULL,
    school_week_start DATE NOT NULL,
    status ENUM('present','late','absent','excused') NOT NULL DEFAULT 'present',
    remarks TEXT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY attendance_records_enrollment_id_attendance_date_unique (enrollment_id, attendance_date),
    KEY attendance_records_week_idx (enrollment_id, school_week_start, status),
    CONSTRAINT attendance_records_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    CONSTRAINT attendance_records_course_id_foreign FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    CONSTRAINT attendance_records_recorded_by_foreign FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    student_id BIGINT UNSIGNED NULL,
    type VARCHAR(255) NOT NULL,
    channel VARCHAR(255) NOT NULL DEFAULT 'in_app',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    meta JSON NULL,
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT notifications_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

CREATE TABLE sms_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    guardian_id BIGINT UNSIGNED NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    enrollment_id BIGINT UNSIGNED NULL,
    week_start DATE NOT NULL,
    absences_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    phone_number VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_key VARCHAR(255) NOT NULL UNIQUE,
    provider VARCHAR(255) NOT NULL DEFAULT 'twilio',
    provider_message_id VARCHAR(255) NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'queued',
    error_message TEXT NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT sms_logs_guardian_id_foreign FOREIGN KEY (guardian_id) REFERENCES guardians(id) ON DELETE SET NULL,
    CONSTRAINT sms_logs_student_id_foreign FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT sms_logs_enrollment_id_foreign FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE SET NULL
);

CREATE TABLE api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL DEFAULT 'mobile',
    token_hash VARCHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT api_tokens_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE sync_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_id VARCHAR(255) NOT NULL,
    batch_uuid CHAR(36) NOT NULL UNIQUE,
    payload JSON NOT NULL,
    status VARCHAR(255) NOT NULL DEFAULT 'processed',
    error_message TEXT NULL,
    synced_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT sync_batches_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
