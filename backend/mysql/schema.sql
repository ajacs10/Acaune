CREATE DATABASE IF NOT EXISTS AcaUni CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE AcaUni;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    department VARCHAR(120) NOT NULL,
    semester INT UNSIGNED NOT NULL,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('available','almost_full','closed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    semester INT UNSIGNED NOT NULL,
    credits TINYINT UNSIGNED NOT NULL DEFAULT 0,
    capacity INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subjects_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    full_name VARCHAR(150) NOT NULL,
    academic_number VARCHAR(40) NOT NULL UNIQUE,
    email VARCHAR(190) NOT NULL UNIQUE,
    academic_status ENUM('active','pending','suspended','graduated') DEFAULT 'active',
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    semester VARCHAR(20) NOT NULL,
    status ENUM('active','pending','closed') DEFAULT 'pending',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE enrollment_subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    enrollment_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_es_enrollment FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_es_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uniq_enrollment_subject (enrollment_id, subject_id)
) ENGINE=InnoDB;

CREATE TABLE grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    score DECIMAL(5,2) NOT NULL DEFAULT 0,
    status ENUM('approved','failed','pending') DEFAULT 'pending',
    published_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_grades_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload LONGTEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO roles (name)
VALUES ('Administrador'), ('Professor'), ('Estudante')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (role_id, name, email, password, status)
SELECT id,
       'Ana Juliana Avelino Da Costa Sobrinho',
       'ajacs@gmail.com',
       '$2y$10$FgKnkdsOLMjZ/Ql9rMAoNOlttrqUyUM99adr7UzN2Cg1TsJRpUnA.',
       'active'
FROM roles
WHERE name = 'Administrador'
ON DUPLICATE KEY UPDATE
    role_id = VALUES(role_id),
    name = VALUES(name),
    password = VALUES(password),
    status = VALUES(status);

INSERT INTO courses (code, name, department, semester, capacity, status)
VALUES
    ('EI', 'Engenharia Informática', 'Engenharias', 1, 120, 'available'),
    ('MED', 'Medicina', 'Ciências da Saúde', 1, 80, 'almost_full'),
    ('DIR', 'Direito', 'Ciências Sociais', 1, 100, 'available')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department = VALUES(department),
    semester = VALUES(semester),
    capacity = VALUES(capacity),
    status = VALUES(status);

INSERT INTO subjects (course_id, code, name, semester, credits, capacity)
SELECT id, 'PROG-II', 'Programação II', 1, 6, 60 FROM courses WHERE code = 'EI'
ON DUPLICATE KEY UPDATE name = VALUES(name), credits = VALUES(credits), capacity = VALUES(capacity);

INSERT INTO subjects (course_id, code, name, semester, credits, capacity)
SELECT id, 'BD-I', 'Base de Dados I', 1, 6, 60 FROM courses WHERE code = 'EI'
ON DUPLICATE KEY UPDATE name = VALUES(name), credits = VALUES(credits), capacity = VALUES(capacity);
