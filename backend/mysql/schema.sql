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

DELETE subjects
FROM subjects
INNER JOIN courses ON courses.id = subjects.course_id
WHERE courses.code NOT IN ('LGC', 'GRH', 'CF', 'RT', 'ISI');

DELETE courses
FROM courses
LEFT JOIN students ON students.course_id = courses.id
WHERE courses.code NOT IN ('LGC', 'GRH', 'CF', 'RT', 'ISI')
  AND students.id IS NULL;

INSERT INTO courses (code, name, department, semester, capacity, status)
VALUES
    ('LGC', 'Logística e Gestão Comercial', 'INSUTEC', 4, 120, 'available'),
    ('GRH', 'Gestão de Recursos Humanos', 'INSUTEC', 4, 120, 'available'),
    ('CF', 'Contabilidade e Finanças', 'INSUTEC', 4, 120, 'available'),
    ('RT', 'Redes e Telecomunicações', 'INSUTEC', 5, 100, 'available'),
    ('ISI', 'Informática e Sistemas de Informação', 'INSUTEC', 5, 100, 'available')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    department = VALUES(department),
    semester = VALUES(semester),
    capacity = VALUES(capacity),
    status = VALUES(status);

INSERT INTO subjects (course_id, code, name, semester, credits, capacity)
SELECT c.id, s.code, s.name, s.semester, s.credits, s.capacity
FROM courses c
INNER JOIN (
    SELECT 'LGC' course_code, 'LGC-CPE-1' code, 'Comunicação Pessoal e Empresarial' name, 1 semester, 6 credits, 60 capacity
    UNION ALL SELECT 'LGC', 'LGC-CG1-1', 'Contabilidade Geral I', 1, 6, 60
    UNION ALL SELECT 'LGC', 'LGC-IOG-1', 'Introdução às Organizações e à Gestão', 1, 6, 60
    UNION ALL SELECT 'LGC', 'LGC-ING2-1', 'Língua Inglesa II', 1, 6, 60
    UNION ALL SELECT 'LGC', 'LGC-MIC-1', 'Métodos de Investigação Científica', 1, 6, 60
    UNION ALL SELECT 'LGC', 'LGC-CA-2', 'Contabilidade Analítica', 2, 6, 55
    UNION ALL SELECT 'LGC', 'LGC-EM-2', 'Estudos de Mercado', 2, 6, 55
    UNION ALL SELECT 'LGC', 'LGC-ING4-2', 'Língua Inglesa IV', 2, 6, 55
    UNION ALL SELECT 'LGC', 'LGC-MAT2-2', 'Matemática II', 2, 6, 55
    UNION ALL SELECT 'LGC', 'LGC-NCV-2', 'Negociação Comercial e Vendas', 2, 6, 55
    UNION ALL SELECT 'LGC', 'LGC-DE-3', 'Direito das Empresas', 3, 6, 50
    UNION ALL SELECT 'LGC', 'LGC-FISC-3', 'Fiscalidade', 3, 6, 50
    UNION ALL SELECT 'LGC', 'LGC-GC-3', 'Gestão de Compras', 3, 6, 50
    UNION ALL SELECT 'LGC', 'LGC-GO-3', 'Gestão de Operações', 3, 6, 50
    UNION ALL SELECT 'LGC', 'LGC-MKT2-3', 'Marketing II', 3, 6, 50
    UNION ALL SELECT 'LGC', 'LGC-ECI-4', 'Economia e Comércio Internacional', 4, 6, 45
    UNION ALL SELECT 'LGC', 'LGC-EPE-4', 'Estratégia e Planeamento da Empresa', 4, 6, 45
    UNION ALL SELECT 'LGC', 'LGC-GPP-4', 'Gestão de Preços e Produtos', 4, 6, 45
    UNION ALL SELECT 'LGC', 'LGC-MI-4', 'Marketing Internacional', 4, 6, 45

    UNION ALL SELECT 'GRH', 'GRH-CPE-1', 'Comunicação Pessoal e Empresarial', 1, 6, 60
    UNION ALL SELECT 'GRH', 'GRH-IGRH-1', 'Introdução à Gestão de Recursos Humanos', 1, 6, 60
    UNION ALL SELECT 'GRH', 'GRH-ING2-1', 'Língua Inglesa II', 1, 6, 60
    UNION ALL SELECT 'GRH', 'GRH-MIC-1', 'Métodos de Investigação Científica', 1, 6, 60
    UNION ALL SELECT 'GRH', 'GRH-PSI-1', 'Psicologia', 1, 6, 60
    UNION ALL SELECT 'GRH', 'GRH-CO-2', 'Comportamento Organizacional', 2, 6, 55
    UNION ALL SELECT 'GRH', 'GRH-ECO-2', 'Economia', 2, 6, 55
    UNION ALL SELECT 'GRH', 'GRH-EST-2', 'Estatística', 2, 6, 55
    UNION ALL SELECT 'GRH', 'GRH-GERH-2', 'Gestão Estratégica de Recursos Humanos', 2, 6, 55
    UNION ALL SELECT 'GRH', 'GRH-ING4-2', 'Língua Inglesa IV', 2, 6, 55
    UNION ALL SELECT 'GRH', 'GRH-FF-3', 'Fundamentos de Finanças', 3, 6, 50
    UNION ALL SELECT 'GRH', 'GRH-GFDRH-3', 'Gestão da Formação e Desenvolvimento de Recursos Humanos', 3, 6, 50
    UNION ALL SELECT 'GRH', 'GRH-HSST-3', 'Higiene, Segurança e Saúde no Trabalho', 3, 6, 50
    UNION ALL SELECT 'GRH', 'GRH-PD-3', 'Procedimentos Disciplinares', 3, 6, 50
    UNION ALL SELECT 'GRH', 'GRH-RSP-3', 'Recrutamento e Selecção de Pessoal', 3, 6, 50
    UNION ALL SELECT 'GRH', 'GRH-ARH-4', 'Auditoria de Recursos Humanos', 4, 6, 45
    UNION ALL SELECT 'GRH', 'GRH-DIO-4', 'Diagnóstico e Intervenção nas Organizações', 4, 6, 45
    UNION ALL SELECT 'GRH', 'GRH-GIRH-4', 'Gestão Internacional de Recursos Humanos', 4, 6, 45
    UNION ALL SELECT 'GRH', 'GRH-PR-4', 'Políticas de Remuneração', 4, 6, 45

    UNION ALL SELECT 'CF', 'CF-CPE-1', 'Comunicação Pessoal e Empresarial', 1, 6, 60
    UNION ALL SELECT 'CF', 'CF-CG1-1', 'Contabilidade Geral I', 1, 6, 60
    UNION ALL SELECT 'CF', 'CF-IOG-1', 'Introdução às Organizações e à Gestão', 1, 6, 60
    UNION ALL SELECT 'CF', 'CF-ING2-1', 'Língua Inglesa II', 1, 6, 60
    UNION ALL SELECT 'CF', 'CF-MAT2-1', 'Matemática II', 1, 6, 60
    UNION ALL SELECT 'CF', 'CF-CA2-2', 'Contabilidade Analítica II', 2, 6, 55
    UNION ALL SELECT 'CF', 'CF-CI-2', 'Contabilidade Informatizada', 2, 6, 55
    UNION ALL SELECT 'CF', 'CF-DE-2', 'Direito das Empresas', 2, 6, 55
    UNION ALL SELECT 'CF', 'CF-EST2-2', 'Estatística II', 2, 6, 55
    UNION ALL SELECT 'CF', 'CF-MICRO2-2', 'Microeconomia II', 2, 6, 55
    UNION ALL SELECT 'CF', 'CF-EPE-3', 'Estratégia e Planeamento da Empresa', 3, 6, 50
    UNION ALL SELECT 'CF', 'CF-FIN2-3', 'Finanças II', 3, 6, 50
    UNION ALL SELECT 'CF', 'CF-FISC-3', 'Fiscalidade', 3, 6, 50
    UNION ALL SELECT 'CF', 'CF-MACRO2-3', 'Macroeconomia II', 3, 6, 50
    UNION ALL SELECT 'CF', 'CF-MKT2-3', 'Marketing II', 3, 6, 50
    UNION ALL SELECT 'CF', 'CF-AEF-4', 'Análise Económica-Financeira', 4, 6, 45
    UNION ALL SELECT 'CF', 'CF-AUD-4', 'Auditoria', 4, 6, 45
    UNION ALL SELECT 'CF', 'CF-ECI-4', 'Economia e Comércio Internacional', 4, 6, 45
    UNION ALL SELECT 'CF', 'CF-SCG-4', 'Sistemas de Controlo de Gestão', 4, 6, 45

    UNION ALL SELECT 'RT', 'RT-AL-1', 'Álgebra Linear', 1, 6, 55
    UNION ALL SELECT 'RT', 'RT-AM2-1', 'Análise Matemática II', 1, 6, 55
    UNION ALL SELECT 'RT', 'RT-FIS1-1', 'Física I', 1, 6, 55
    UNION ALL SELECT 'RT', 'RT-ING2-1', 'Língua Inglesa II', 1, 6, 55
    UNION ALL SELECT 'RT', 'RT-TC1-1', 'Teoria da Computação I', 1, 6, 55
    UNION ALL SELECT 'RT', 'RT-AM4-2', 'Análise Matemática IV', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-CPE-2', 'Comunicação Pessoal e Empresarial', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-FIS3-2', 'Física III', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-ING4-2', 'Língua Inglesa IV', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-SD-2', 'Sistemas Digitais', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-TC2-2', 'Teoria da Computação II', 2, 6, 50
    UNION ALL SELECT 'RT', 'RT-AED-3', 'Algoritmos e Estrutura de Dados', 3, 6, 45
    UNION ALL SELECT 'RT', 'RT-CDI2-3', 'Cálculo Diferencial e Integral II', 3, 6, 45
    UNION ALL SELECT 'RT', 'RT-EO-3', 'Electromagnetismo e Óptica', 3, 6, 45
    UNION ALL SELECT 'RT', 'RT-MC-3', 'Matemática Computacional', 3, 6, 45
    UNION ALL SELECT 'RT', 'RT-SO1-3', 'Sistemas Operativos I', 3, 6, 45
    UNION ALL SELECT 'RT', 'RT-IECOM-4', 'Introdução à Electrónica das Comunicações', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-PADI-4', 'Plataforma para Aplicações Distribuídas Internet', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-PE-4', 'Probabilidade e Estatística', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-RPA-4', 'Rádio Programação e Antenas', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-SS-4', 'Sinais e Sistemas', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-TRC-4', 'Tecnologia de Redes e Comunicações', 4, 6, 40
    UNION ALL SELECT 'RT', 'RT-CPD-5', 'Computação Paralela Distribuída', 5, 6, 35
    UNION ALL SELECT 'RT', 'RT-PPR-5', 'Planeamento e Projecto de Redes', 5, 6, 35
    UNION ALL SELECT 'RT', 'RT-QSI-5', 'Qualidade de Serviços na Internet', 5, 6, 35
    UNION ALL SELECT 'RT', 'RT-SC-5', 'Sistemas de Comunicações', 5, 6, 35

    UNION ALL SELECT 'ISI', 'ISI-AM2-1', 'Análise Matemática II', 1, 6, 55
    UNION ALL SELECT 'ISI', 'ISI-FIS1-1', 'Física I', 1, 6, 55
    UNION ALL SELECT 'ISI', 'ISI-ICP-1', 'Introdução aos Computadores e Programação', 1, 6, 55
    UNION ALL SELECT 'ISI', 'ISI-QO-1', 'Química Orgânica', 1, 6, 55
    UNION ALL SELECT 'ISI', 'ISI-AM4-2', 'Análise Matemática IV', 2, 6, 50
    UNION ALL SELECT 'ISI', 'ISI-CPE-2', 'Comunicação Pessoal e Empresarial', 2, 6, 50
    UNION ALL SELECT 'ISI', 'ISI-FIS3-2', 'Física III', 2, 6, 50
    UNION ALL SELECT 'ISI', 'ISI-ING2-2', 'Língua Inglesa II', 2, 6, 50
    UNION ALL SELECT 'ISI', 'ISI-SD-2', 'Sistemas Digitais', 2, 6, 50
    UNION ALL SELECT 'ISI', 'ISI-AC1-3', 'Arquitectura de Computadores I', 3, 6, 45
    UNION ALL SELECT 'ISI', 'ISI-BD-3', 'Base de Dados', 3, 6, 45
    UNION ALL SELECT 'ISI', 'ISI-ING4-3', 'Língua Inglesa IV', 3, 6, 45
    UNION ALL SELECT 'ISI', 'ISI-MEC1-3', 'Mecânica I', 3, 6, 45
    UNION ALL SELECT 'ISI', 'ISI-PROG2-3', 'Programação II', 3, 6, 45
    UNION ALL SELECT 'ISI', 'ISI-ASI-4', 'Análise de Sistemas de Informação', 4, 6, 40
    UNION ALL SELECT 'ISI', 'ISI-CG-4', 'Computação Gráfica', 4, 6, 40
    UNION ALL SELECT 'ISI', 'ISI-WEB-4', 'Programação IV - Linguagens e Tecnologias WEB', 4, 6, 40
    UNION ALL SELECT 'ISI', 'ISI-RC-4', 'Redes de Computadores', 4, 6, 40
    UNION ALL SELECT 'ISI', 'ISI-SO2-4', 'Sistemas Operativos II', 4, 6, 40
    UNION ALL SELECT 'ISI', 'ISI-AI-5', 'Auditoria Informática', 5, 6, 35
    UNION ALL SELECT 'ISI', 'ISI-DIO-5', 'Diagnóstico e Intervenção nas Organizações', 5, 6, 35
    UNION ALL SELECT 'ISI', 'ISI-QSIS-5', 'Qualidade de Sistemas de Informação', 5, 6, 35
    UNION ALL SELECT 'ISI', 'ISI-SIRS-5', 'Segurança Informática em Redes e Sistemas', 5, 6, 35
) s ON s.course_code = c.code
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    semester = VALUES(semester),
    credits = VALUES(credits),
    capacity = VALUES(capacity);
