-- Database Initialization

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin','teacher','student') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_profiles (
  teacher_user_id BIGINT UNSIGNED PRIMARY KEY,
  employee_no VARCHAR(50) NULL UNIQUE,
  department VARCHAR(120) NULL,
  subjects_json JSON NULL,
  office_text VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tp_user FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_user_id BIGINT UNSIGNED NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  accuracy_m INT NULL,
  source ENUM('geolocation') NOT NULL DEFAULT 'geolocation',
  captured_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_loc_teacher_time (teacher_user_id, captured_at),
  CONSTRAINT fk_loc_user FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS teacher_status_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('AVAILABLE','IN_CLASS','BUSY','OFFLINE','OFF_CAMPUS') NOT NULL,
  note VARCHAR(255) NULL,
  set_by_user_id BIGINT UNSIGNED NOT NULL,
  set_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_teacher_time (teacher_user_id, set_at),
  CONSTRAINT fk_status_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_status_actor FOREIGN KEY (set_by_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actor_user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(60) NULL,
  entity_id BIGINT UNSIGNED NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  metadata_json JSON NULL,
  INDEX idx_audit_time (timestamp),
  INDEX idx_audit_actor_time (actor_user_id, timestamp),
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SEED DATA
-- Passwords are 'password123' hashed with PASSWORD_DEFAULT (BCRYPT)
-- For demonstration, we'll use a known hash. 
-- In a real app, generate these dynamically or use a setup script.
-- Hash for 'password123': $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

INSERT INTO users (role, name, email, password_hash) VALUES
('admin', 'Admin User', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('teacher', 'John Doe', 'john.doe@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('teacher', 'Jane Smith', 'jane.smith@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student', 'Student One', 'student1@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO teacher_profiles (teacher_user_id, employee_no, department, subjects_json, office_text) VALUES
((SELECT id FROM users WHERE email='john.doe@school.edu'), 'T001', 'Science', '["Physics", "Math"]', 'Room 101'),
((SELECT id FROM users WHERE email='jane.smith@school.edu'), 'T002', 'Arts', '["History", "English"]', 'Room 202');

-- Initial Status
INSERT INTO teacher_status_events (teacher_user_id, status, set_by_user_id, set_at) VALUES
((SELECT id FROM users WHERE email='john.doe@school.edu'), 'AVAILABLE', (SELECT id FROM users WHERE email='john.doe@school.edu'), NOW()),
((SELECT id FROM users WHERE email='jane.smith@school.edu'), 'OFFLINE', (SELECT id FROM users WHERE email='jane.smith@school.edu'), NOW());
