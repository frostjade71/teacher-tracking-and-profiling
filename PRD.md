# PRD Title: AI‑Powered Teacher Tracking & Profile System (Vanilla PHP + MySQL, Dockerized)

This is the final, implementation-ready PRD for a Dockerized vanilla PHP/MySQL web system with Admin/Teacher/Student roles, where students see **status only** and admins can view teacher GPS locations. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
It follows your flow (login → role authentication → dashboards, logs, RBAC, data security, error handling) and uses browser geolocation + Leaflet maps (teacher self-check + admin monitoring). [leafletjs](https://leafletjs.com/examples/quick-start/)

## 1) Product definition
The system provides three dashboards (Admin, Teacher, Student) after login and role authentication. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
Students can search teachers and view teacher status and timestamps, while admins can monitor teacher locations on a map and manage teacher profiles/reports/logs. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)

**Goals (v1)**
- Students can find a teacher and see the latest status quickly (status-only rule). [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- Teachers can submit GPS pings via browser geolocation and update status. [developer.mozilla](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition)
- Admins can always view teacher locations and export reports, with audit logs recorded. [huntress](https://www.huntress.com/cybersecurity-101/topic/what-is-an-audit-log)

## 2) Requirements (FR)
RBAC must be enforced server-side for every request, not just by hiding UI controls. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
Session IDs must be regenerated when privileges are elevated (after login) before setting authentication data in `$_SESSION`. [php](https://www.php.net/manual/en/features.session.security.management.php)

**Roles & permissions**
- Student: search teacher, view teacher profile (public fields), view teacher **status only** (no location data ever returned). [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- Teacher: update own status, submit own GPS location ping using `navigator.geolocation.getCurrentPosition()`. [developer.mozilla](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition)
- Admin: manage teacher profiles, monitor teacher status + **location** on a Leaflet map, generate reports, and view logs (admin can always view location). [leafletjs](https://leafletjs.com/examples/quick-start/)

**Functional requirements**
- FR-01 Login: authenticate via email + password using `password_verify()` against stored hashes. [php](https://www.php.net/manual/en/function.password-verify.php)
- FR-02 Password storage: store passwords with `password_hash()` (never plaintext). [php](https://www.php.net/manual/en/function.password-hash.php)
- FR-03 Teacher status: teacher can set `AVAILABLE | IN_CLASS | BUSY | OFFLINE | OFF_CAMPUS` with timestamp.  [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- FR-04 Teacher location: teacher can submit `lat/lng/accuracy` captured from browser geolocation. [developer.mozilla](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition)
- FR-05 Student privacy: student pages/endpoints must not expose lat/lng in HTML, JSON, or logs accessible to students (status-only). [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- FR-06 Admin monitoring: admin can see latest location per teacher plotted on Leaflet. [leafletjs](https://leafletjs.com/examples/quick-start/)
- FR-07 Audit logs: record privileged actions and access (admin monitor views, exports, profile edits) in audit logs. [huntress](https://www.huntress.com/cybersecurity-101/topic/what-is-an-audit-log)

## 3) Data model (MySQL)
Database writes/reads must use PDO prepared statements (`PDO::prepare`) to separate SQL structure from user input. [php](https://www.php.net/manual/en/pdo.prepared-statements.php)
Location pings and status events are stored historically, while “current” values are derived by selecting the latest event for each teacher. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)

**DDL (db/init.sql)**  
```sql
CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('admin','teacher','student') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE teacher_profiles (
  teacher_user_id BIGINT UNSIGNED PRIMARY KEY,
  employee_no VARCHAR(50) NULL UNIQUE,
  department VARCHAR(120) NULL,
  subjects_json JSON NULL,
  office_text VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tp_user FOREIGN KEY (teacher_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE teacher_locations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_user_id BIGINT UNSIGNED NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  accuracy_m INT NULL,
  source ENUM('geolocation') NOT NULL DEFAULT 'geolocation',
  captured_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_loc_teacher_time (teacher_user_id, captured_at),
  CONSTRAINT fk_loc_user FOREIGN KEY (teacher_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE teacher_status_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  teacher_user_id BIGINT UNSIGNED NOT NULL,
  status ENUM('AVAILABLE','IN_CLASS','BUSY','OFFLINE','OFF_CAMPUS') NOT NULL,
  note VARCHAR(255) NULL,
  set_by_user_id BIGINT UNSIGNED NOT NULL,
  set_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status_teacher_time (teacher_user_id, set_at),
  CONSTRAINT fk_status_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id),
  CONSTRAINT fk_status_actor FOREIGN KEY (set_by_user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
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
  CONSTRAINT fk_audit_actor FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB;
```

## 4) Implementation blueprint (vanilla PHP)
This implementation uses routing style **1A**: `public/index.php?page=...` and server-rendered PHP views with a small admin-only JSON endpoint for map markers. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
Leaflet is loaded via CDN (**2A**) for the admin map and optional teacher self-check map. [leafletjs](https://leafletjs.com/examples/quick-start/)

**Folder structure**
```txt
/public
  index.php
  assets/app.css
/app
  config.php
  db.php
  auth.php
  rbac.php
  audit.php
  pages/
    login.php
    student_dashboard.php
    student_teacher.php
    teacher_dashboard.php
    admin_dashboard.php
    admin_monitor.php
  actions/
    login_post.php
    logout_post.php
    teacher_status_post.php
    teacher_location_post.php
/storage
  logs/app.log
/db
  init.sql
```

**public/index.php (router)**
```php
<?php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/rbac.php';
require __DIR__ . '/../app/audit.php';

$page = $_GET['page'] ?? 'login';

$routes = [
  'login' => __DIR__ . '/../app/pages/login.php',
  'student_dashboard' => __DIR__ . '/../app/pages/student_dashboard.php',
  'student_teacher' => __DIR__ . '/../app/pages/student_teacher.php',
  'teacher_dashboard' => __DIR__ . '/../app/pages/teacher_dashboard.php',
  'admin_dashboard' => __DIR__ . '/../app/pages/admin_dashboard.php',
  'admin_monitor' => __DIR__ . '/../app/pages/admin_monitor.php',

  // POST actions
  'login_post' => __DIR__ . '/../app/actions/login_post.php',
  'logout_post' => __DIR__ . '/../app/actions/logout_post.php',
  'teacher_status_post' => __DIR__ . '/../app/actions/teacher_status_post.php',
  'teacher_location_post' => __DIR__ . '/../app/actions/teacher_location_post.php',

  // admin-only JSON for map markers
  'admin_locations_json' => __DIR__ . '/../app/pages/admin_locations_json.php',
];

if (!isset($routes[$page])) {
  http_response_code(404);
  echo "404 Not Found";
  exit;
}

require $routes[$page];
```

**app/auth.php (sessions + password verify)**
```php
<?php
function auth_start(): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
}

function current_user(): ?array {
  auth_start();
  return $_SESSION['user'] ?? null;
}

function require_login(): array {
  $u = current_user();
  if (!$u) { header("Location: /?page=login"); exit; }
  return $u;
}
```

**app/db.php (PDO connection + prepare)**
```php
<?php
function db(): PDO {
  static $pdo = null;
  if ($pdo) return $pdo;

  $dsn = "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4";
  $pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASS'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
  return $pdo;
}
```

**Teacher GPS capture (in teacher_dashboard.php)**
```html
<button id="btnLoc">Update my location</button>
<script>
document.getElementById('btnLoc').addEventListener('click', () => {
  navigator.geolocation.getCurrentPosition(async (pos) => {
    const payload = {
      lat: pos.coords.latitude,
      lng: pos.coords.longitude,
      accuracy_m: pos.coords.accuracy
    };
    const res = await fetch('/?page=teacher_location_post', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload)
    });
    alert(await res.text());
  }, (err) => alert(err.message), { enableHighAccuracy: true });
});
</script>
```

**Student status-only guarantee**
- Do not create any student endpoint that queries `teacher_locations`, and do not include admin JSON endpoints in student pages. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- Keep a separate query function for student teacher view that reads only teacher profile + latest status.

## 5) Docker (compose) + runbook + tests
Docker Compose runs Nginx + PHP-FPM + MySQL, which is a common deployment pattern for PHP apps. [atlantic](https://www.atlantic.net/vps-hosting/how-to-deploy-a-php-application-with-nginx-and-mysql-using-docker-and-docker-compose/)
Runbook: `docker compose up --build`, then open `http://localhost:8080/?page=login` and login with seeded accounts. [atlantic](https://www.atlantic.net/vps-hosting/how-to-deploy-a-php-application-with-nginx-and-mysql-using-docker-and-docker-compose/)

**docker-compose.yml**
```yaml
services:
  nginx:
    image: nginx:1.25-alpine
    ports: ["8080:80"]
    volumes:
      - ./public:/var/www/public:ro
      - ./app:/var/www/app:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on: [php]

  php:
    build: ./docker/php
    volumes:
      - ./public:/var/www/public
      - ./app:/var/www/app
      - ./storage:/var/www/storage
    environment:
      DB_HOST: mysql
      DB_NAME: ttrack
      DB_USER: ttrack_user
      DB_PASS: ttrack_pass
    depends_on: [mysql]

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: ttrack
      MYSQL_USER: ttrack_user
      MYSQL_PASSWORD: ttrack_pass
      MYSQL_ROOT_PASSWORD: rootpass
    volumes:
      - dbdata:/var/lib/mysql
      - ./db/init.sql:/docker-entrypoint-initdb.d/01-init.sql:ro

volumes:
  dbdata:
```

**Security must-haves**
- Call `session_regenerate_id()` after successful login before setting session auth data. [php](https://www.php.net/manual/en/features.session.security.management.php)
- Use `password_hash()` and `password_verify()` for password storage and login checks. [php](https://www.php.net/manual/en/function.password-verify.php)
- Use PDO prepared statements for every SQL query that includes user input. [php](https://www.php.net/manual/en/pdo.prepare.php)

**Acceptance test cases (must pass)**
- TC-01 Student can view a teacher’s latest status + timestamp, and no lat/lng appears in the response body or page source. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- TC-02 Teacher submits status update and it appears on student teacher view after refresh. [ppl-ai-file-upload.s3.amazonaws](https://ppl-ai-file-upload.s3.amazonaws.com/web/direct-files/attachments/images/118345168/39cecf60-6ef0-4b7d-abb1-1d04e11189ba/5a1e4d3a-cb5b-430b-9b88-6b6e5ff2747f.jpg)
- TC-03 Teacher submits GPS ping via browser geolocation and the row appears in `teacher_locations`. [developer.mozilla](https://developer.mozilla.org/en-US/docs/Web/API/Geolocation/getCurrentPosition)
- TC-04 Admin opens monitor map and sees markers (latest location per teacher) rendered with Leaflet. [leafletjs](https://leafletjs.com/examples/quick-start/)
- TC-05 Admin monitor access and report exports create `audit_logs` entries to support traceability. [huntress](https://www.huntress.com/cybersecurity-101/topic/what-is-an-audit-log)