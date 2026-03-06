# System Architecture

> **AI‑Powered Teacher Tracking & Profile System** — Vanilla PHP 8 · MySQL 8 · Docker

---

## 1 · High‑Level Overview

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#4F46E5', 'primaryTextColor': '#fff', 'primaryBorderColor': '#3730A3', 'lineColor': '#6366F1', 'secondaryColor': '#F59E0B', 'tertiaryColor': '#10B981', 'background': '#F8FAFC', 'mainBkg': '#EEF2FF', 'nodeBorder': '#4338CA', 'clusterBkg': '#F1F5F9', 'clusterBorder': '#CBD5E1', 'titleColor': '#1E293B', 'edgeLabelBackground': '#fff', 'fontSize': '14px' }, 'flowchart': { 'rankSpacing': 55, 'nodeSpacing': 35, 'curve': 'basis' }}}%%

graph TD
    %% ── Users ──
    STU["👨‍🎓 Student"]
    TCH["👩‍🏫 Teacher"]
    ADM["🔑 Admin"]

    STU & TCH & ADM --> BROWSER["🌐 Browser<br/>HTML · Tailwind CSS · JS<br/>Leaflet.js · Geolocation API"]

    BROWSER -->|"HTTP :8080"| NGINX["🔀 Nginx 1.25‑alpine"]
    NGINX -->|"FastCGI :9000"| PHP_FPM["⚙️ PHP 8‑FPM"]
    PHP_FPM --> ROUTER["📡 public/index.php<br/>Route Dispatcher"]
    ROUTER --> APP["📦 Application Layer<br/>(app/)"]
    APP --> DB[("💾 MySQL 8.0<br/>Database: ttrack")]
    APP -->|"AI Queries"| PERPLEXITY["🧠 Perplexity API"]
    BROWSER -.->|"Map Tiles"| OSM["🗺️ OpenStreetMap"]
    PMA["🛠️ phpMyAdmin :8081"] -.-> DB

    classDef userNode fill:#818CF8,stroke:#4338CA,stroke-width:2px,color:#fff,font-weight:bold
    classDef browserNode fill:#60A5FA,stroke:#2563EB,stroke-width:2px,color:#fff
    classDef serverNode fill:#06B6D4,stroke:#0E7490,stroke-width:2px,color:#fff
    classDef appNode fill:#8B5CF6,stroke:#6D28D9,stroke-width:2px,color:#fff
    classDef dbNode fill:#FBBF24,stroke:#D97706,stroke-width:2px,color:#1E293B
    classDef externalNode fill:#34D399,stroke:#059669,stroke-width:2px,color:#fff
    classDef toolNode fill:#94A3B8,stroke:#64748B,stroke-width:2px,color:#fff

    class STU,TCH,ADM userNode
    class BROWSER browserNode
    class NGINX,PHP_FPM serverNode
    class ROUTER,APP appNode
    class DB dbNode
    class PERPLEXITY,OSM externalNode
    class PMA toolNode
```

---

## 2 · Infrastructure & Docker

The system runs as **four Docker containers** on a single bridge network (`ttrack-network`).

| Service | Image | Port | Role |
|---|---|---|---|
| **nginx** | `nginx:1.25-alpine` | `:8080 → :80` | Reverse proxy, serves static files, forwards PHP to FastCGI |
| **php** | Custom build (`docker/php/`) | `:9000` (internal) | PHP‑FPM application runtime |
| **mysql** | `mysql:8.0` | internal only | Persistent data store; seeded from `db/init.sql` |
| **phpmyadmin** | `phpmyadmin/phpmyadmin` | `:8081 → :80` | Database admin GUI (dev only) |

**Volume mounts:** `public/` and `app/` are mounted into both nginx (read‑only) and php containers. A named volume `dbdata` persists MySQL data. Environment variables (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `PERPLEXITY_API_KEY`) are injected from `.env`.

```
docker-compose.yml
├── nginx        → default.conf (docker/nginx/)
├── php          → Dockerfile   (docker/php/)
├── mysql        → init.sql     (db/)
└── phpmyadmin
```

---

## 3 · Application Architecture

### 3.1 · Request Lifecycle

```
Browser  ──HTTP──▸  Nginx  ──FastCGI──▸  PHP-FPM
                                            │
                                    public/index.php
                                       ├── loads .env
                                       ├── requires core modules
                                       ├── reads ?page= param
                                       ├── matches route whitelist
                                       └── requires page/action file
```

`public/index.php` is the **single entry point**. The `.htaccess` rewrites all requests to it. Routes are a whitelist array mapping `?page=` values to PHP files under `app/pages/` or `app/actions/`.

### 3.2 · Directory Structure

```
project-root/
│
├── public/                  # Web‑accessible document root
│   ├── index.php            # 📡 Single entry point & route dispatcher
│   ├── .htaccess            # URL rewrite rules
│   └── assets/
│       ├── app.css          # Global stylesheet (Tailwind compiled)
│       ├── toast.css        # Toast notification styles
│       ├── theme.js         # Dark/light mode toggle logic
│       ├── mobile.js        # Mobile responsive helpers
│       ├── loader.js        # Page loading animation
│       ├── map_arrows.js    # Map directional arrows for Leaflet
│       ├── toast.js         # Toast notification system
│       └── favicon/         # Favicon assets
│
├── app/                     # Application logic (NOT web‑accessible)
│   ├── config.php           # App constants & configuration
│   ├── db.php               # PDO database connection factory
│   ├── auth.php             # Session management & authentication
│   ├── rbac.php             # Role‑based access control
│   ├── audit.php            # Audit logging to database
│   ├── settings.php         # Dynamic settings loader
│   ├── setup_radar_table.php
│   │
│   ├── helpers/
│   │   ├── env_loader.php       # .env file parser
│   │   └── perplexity_helper.php # Perplexity AI API wrapper
│   │
│   ├── partials/            # Reusable UI fragments (included by pages)
│   │   ├── student_sidebar.php
│   │   ├── teacher_sidebar.php
│   │   ├── admin_sidebar.php
│   │   ├── student_mobile_header.php
│   │   ├── teacher_mobile_header.php
│   │   ├── admin_mobile_header.php
│   │   ├── chatbot_widget.php
│   │   ├── campus_map_modal.php
│   │   ├── info_modal.php
│   │   ├── teacher_timetable_grid.php
│   │   └── theme_toggle.php
│   │
│   ├── pages/               # Page controllers (render HTML)
│   │   └── (26 files — see §4)
│   │
│   └── actions/             # POST handlers & mutations
│       └── (27 files — see §5)
│
├── db/
│   └── init.sql             # Database schema + seed data
│
├── docker/
│   ├── nginx/default.conf   # Nginx server config
│   └── php/Dockerfile       # PHP‑FPM image build
│
├── docker-compose.yml
├── .env / .env.example
├── PRD.md                   # Product Requirements Document
└── README.md
```

### 3.3 · Core Module Dependency Flow

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#8B5CF6', 'primaryTextColor': '#fff', 'lineColor': '#6366F1', 'fontSize': '13px' }, 'flowchart': { 'rankSpacing': 40, 'nodeSpacing': 25, 'curve': 'basis' }}}%%

graph LR
    ENV["env_loader.php"] --> CONFIG["config.php"]
    CONFIG --> DB["db.php<br/>(PDO)"]
    CONFIG --> AUTH["auth.php<br/>(Sessions)"]
    CONFIG --> RBAC["rbac.php<br/>(Access Control)"]
    CONFIG --> AUDIT["audit.php<br/>(Logging)"]
    CONFIG --> SETTINGS["settings.php"]
    DB --> AUDIT
    AUTH --> RBAC

    classDef core fill:#8B5CF6,stroke:#6D28D9,stroke-width:2px,color:#fff
    class ENV,CONFIG,DB,AUTH,RBAC,AUDIT,SETTINGS core
```

| Module | File | Responsibility |
|---|---|---|
| **Env Loader** | `helpers/env_loader.php` | Parses `.env` file into environment variables |
| **Config** | `config.php` | App‑wide constants, error reporting, timezone |
| **Database** | `db.php` | Singleton PDO connection with prepared statement helper |
| **Auth** | `auth.php` | `session_start()`, `current_user()`, `require_login()` |
| **RBAC** | `rbac.php` | `require_role()` — enforces `admin`, `teacher`, `student` |
| **Audit** | `audit.php` | Writes action logs to `audit_logs` table |
| **Settings** | `settings.php` | Dynamic key‑value settings from database |
| **Perplexity** | `helpers/perplexity_helper.php` | Wraps Perplexity AI API calls for the chatbot |

---

## 4 · Pages by Role

### 👨‍🎓 Student Pages

| Route (`?page=`) | File | Description |
|---|---|---|
| `student_dashboard` | `student_dashboard.php` | Overview with teacher statuses |
| `student_teacher` | `student_teacher.php` | Detailed teacher profile & location view |

### 👩‍🏫 Teacher Pages

| Route (`?page=`) | File | Description |
|---|---|---|
| `teacher_dashboard` | `teacher_dashboard.php` | Status controls, location sharing, notes |
| `teacher_subjects` | `teacher_subjects.php` | Manage assigned subjects |
| `teacher_timetable` | `teacher_timetable.php` | Weekly schedule management |
| `profile` | `profile.php` | Teacher profile editor |

### 🔑 Admin Pages

| Route (`?page=`) | File | Description |
|---|---|---|
| `admin_dashboard` | `admin_dashboard.php` | System overview & statistics |
| `admin_monitor` | `admin_monitor.php` | Real‑time teacher monitoring |
| `admin_teachers` | `admin_teachers.php` | Teacher CRUD management |
| `admin_teachers_view` | `admin_teachers_view.php` | Detailed teacher view |
| `admin_teacher_profile` | `admin_teacher_profile.php` | Admin view of teacher profile |
| `admin_students` | `admin_students.php` | Student CRUD management |
| `admin_admins` | `admin_admins.php` | Admin user management |
| `admin_users` | `admin_users.php` | Combined user management |
| `admin_subjects` | `admin_subjects.php` | Subject CRUD management |
| `admin_timetable` | `admin_timetable.php` | System‑wide timetable |
| `admin_analytics` | `admin_analytics.php` | Reports & data visualization |
| `admin_audit` | `admin_audit.php` | Audit log viewer |
| `admin_campus_radar` | `admin_campus_radar.php` | Campus geofence configuration |

### 🔌 JSON API Endpoints

| Route (`?page=`) | File | Returns |
|---|---|---|
| `admin_locations_json` | `admin_locations_json.php` | All teacher GPS data (admin) |
| `public_locations_json` | `public_locations_json.php` | Filtered teacher locations (student) |
| `campus_radar_json` | `campus_radar_json.php` | Campus geofence polygon |
| `chatbot_api` | `chatbot_api.php` | AI chatbot responses via Perplexity |
| `teacher_subjects_api` | `teacher_subjects_api.php` | Teacher subject list (JSON) |

---

## 5 · Actions (POST Handlers)

All actions process form submissions or AJAX calls, perform database mutations, and redirect back.

### Authentication

| Action | File |
|---|---|
| `login_post` | `login_post.php` |
| `logout_post` | `logout_post.php` |

### Teacher Actions

| Action | File | What it does |
|---|---|---|
| `teacher_status_post` | `teacher_status_post.php` | Set availability status |
| `teacher_location_post` | `teacher_location_post.php` | Submit GPS coordinates |
| `teacher_note_post` | `teacher_note_post.php` | Add/update status note |
| `teacher_session_update` | `teacher_session_update.php` | Update active session |
| `teacher_subjects_update` | `teacher_subjects_update.php` | Save subject assignments |
| `teacher_timetable_action` | `teacher_timetable_action.php` | CRUD timetable entries |

### Admin — Teacher Management

| Action | File |
|---|---|
| `admin_teacher_create` | `admin_teacher_create.php` |
| `admin_teacher_update` | `admin_teacher_update.php` |
| `admin_teacher_delete` | `admin_teacher_delete.php` |

### Admin — Student Management

| Action | File |
|---|---|
| `admin_student_create` | `admin_student_create.php` |
| `admin_student_update` | `admin_student_update.php` |
| `admin_student_delete` | `admin_student_delete.php` |

### Admin — Admin Management

| Action | File |
|---|---|
| `admin_admin_create` | `admin_admin_create.php` |
| `admin_admin_update` | `admin_admin_update.php` |
| `admin_admin_delete` | `admin_admin_delete.php` |

### Admin — Subject Management

| Action | File |
|---|---|
| `admin_subject_create` | `admin_subject_create.php` |
| `admin_subject_update` | `admin_subject_update.php` |
| `admin_subject_delete` | `admin_subject_delete.php` |

### Admin — System

| Action | File | What it does |
|---|---|---|
| `admin_timetable_action` | `admin_timetable_action.php` | Manage system timetable |
| `admin_settings_save` | `admin_settings_save.php` | Persist settings changes |
| `admin_save_radar` | `admin_save_radar.php` | Save campus geofence polygon |
| `admin_reset_locations` | `admin_reset_locations.php` | Purge all teacher location data |

### Utilities & Migrations

| Action | File |
|---|---|
| `auto_offline_helper` | `auto_offline_helper.php` |
| `log_map_view_post` | `log_map_view_post.php` |
| `migrate_timetables` | `migrate_timetables.php` |
| `migrate_time_labels` | `migrate_time_labels.php` |

---

## 6 · Database Schema

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#FBBF24', 'primaryTextColor': '#1E293B', 'lineColor': '#D97706', 'fontSize': '12px' }}}%%

erDiagram
    users {
        BIGINT id PK
        ENUM role "admin | teacher | student"
        VARCHAR name
        VARCHAR email UK
        VARCHAR password_hash
        DATETIME created_at
    }

    teacher_profiles {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR employee_no
        VARCHAR department
        JSON subjects_json
        VARCHAR office
    }

    teacher_locations {
        BIGINT id PK
        BIGINT teacher_user_id FK
        DECIMAL lat
        DECIMAL lng
        FLOAT accuracy_m
        DATETIME captured_at
    }

    teacher_status_events {
        BIGINT id PK
        BIGINT teacher_user_id FK
        ENUM status "AVAILABLE | IN_CLASS | BUSY | OFFLINE | OFF_CAMPUS"
        VARCHAR note
        BIGINT set_by FK
        DATETIME set_at
    }

    audit_logs {
        BIGINT id PK
        VARCHAR action
        VARCHAR entity_type
        BIGINT actor_user_id FK
        VARCHAR ip
        JSON metadata_json
        DATETIME timestamp
    }

    users ||--o| teacher_profiles : "has profile"
    users ||--o{ teacher_locations : "submits"
    users ||--o{ teacher_status_events : "triggers"
    users ||--o{ audit_logs : "generates"
```

### Key Relationships

- **users → teacher_profiles**: 1:1 for teachers; stores employee info, department, office
- **users → teacher_locations**: 1:many; GPS pings with accuracy & timestamp
- **users → teacher_status_events**: 1:many; chronological status changes
- **users → audit_logs**: 1:many; every system action is logged with IP & metadata

---

## 7 · External Integrations

| Service | Usage | Called From |
|---|---|---|
| **Perplexity AI API** | Powers the student AI chatbot for teacher/schedule queries | `chatbot_api.php` via `perplexity_helper.php` |
| **OpenStreetMap / Leaflet.js** | Map tile rendering for campus & teacher location views | Browser‑side (client JS) |
| **Browser Geolocation API** | Captures teacher lat/lng coordinates | Browser‑side → `teacher_location_post.php` |

---

## 8 · Security Model

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'fontSize': '13px' }}}%%

flowchart LR
    A["Request"] --> B{"Authenticated?"}
    B -->|No| C["Redirect → Login"]
    B -->|Yes| D{"Role Check<br/>(rbac.php)"}
    D -->|Unauthorized| E["403 Forbidden"]
    D -->|Authorized| F["Load Page"]
    F --> G["Audit Log"]

    style A fill:#60A5FA,stroke:#2563EB,color:#fff
    style C fill:#EF4444,stroke:#B91C1C,color:#fff
    style E fill:#EF4444,stroke:#B91C1C,color:#fff
    style F fill:#4ADE80,stroke:#16A34A,color:#fff
    style G fill:#FBBF24,stroke:#D97706,color:#1E293B
```

| Layer | Mechanism |
|---|---|
| **Authentication** | PHP sessions with ID regeneration on login |
| **Authorization** | RBAC via `require_role()` — three roles: `admin`, `teacher`, `student` |
| **Input Validation** | PDO prepared statements for all SQL queries |
| **Audit Trail** | Every mutation logged to `audit_logs` with actor, IP, and metadata |
| **Route Protection** | Whitelist‑only routing — unknown `?page=` values return 404 |

---

## 9 · Frontend Architecture

The frontend is **server‑rendered PHP** enhanced with client‑side JavaScript for interactivity.

| Technology | Purpose |
|---|---|
| **Tailwind CSS** | Utility‑first styling (compiled to `app.css`) |
| **Leaflet.js** | Interactive campus maps with teacher location markers |
| **Geolocation API** | GPS coordinate capture from the browser |
| **Toast System** | `toast.js` + `toast.css` for user notifications |
| **Theme Toggle** | `theme.js` — dark/light mode persistence |
| **Mobile Support** | `mobile.js` + per‑role mobile headers for responsive navigation |

### Shared Partials

Reusable UI components included across pages:

| Partial | Used By |
|---|---|
| `student_sidebar.php` | Student pages |
| `teacher_sidebar.php` | Teacher pages |
| `admin_sidebar.php` | Admin pages |
| `*_mobile_header.php` | Mobile responsive headers (per role) |
| `chatbot_widget.php` | Student pages (AI assistant) |
| `campus_map_modal.php` | Pages with map interaction |
| `info_modal.php` | Contextual info dialogs |
| `teacher_timetable_grid.php` | Timetable views |
| `theme_toggle.php` | All pages |

---

## 10 · Detailed Component Diagram

```mermaid
%%{init: {'theme': 'base', 'themeVariables': { 'primaryColor': '#4F46E5', 'primaryTextColor': '#fff', 'primaryBorderColor': '#3730A3', 'lineColor': '#6366F1', 'secondaryColor': '#F59E0B', 'tertiaryColor': '#10B981', 'background': '#F8FAFC', 'mainBkg': '#EEF2FF', 'nodeBorder': '#4338CA', 'clusterBkg': '#F1F5F9', 'clusterBorder': '#CBD5E1', 'titleColor': '#1E293B', 'edgeLabelBackground': '#fff', 'fontSize': '12px' }, 'flowchart': { 'rankSpacing': 50, 'nodeSpacing': 25, 'curve': 'basis' }}}%%

graph TD
    %% ── Users ──
    STU["👨‍🎓 Student"] --- BROWSER
    TCH["👩‍🏫 Teacher"] --- BROWSER
    ADM["🔑 Admin"] --- BROWSER

    BROWSER["🌐 Browser<br/>HTML · Tailwind CSS · JS<br/>Leaflet.js · Geolocation API"]

    BROWSER -->|"HTTP :8080"| NGINX["🔀 Nginx 1.25‑alpine<br/>default.conf"]
    NGINX -->|"FastCGI"| INDEX["📡 public/index.php<br/>.htaccess URL rewrite"]

    %% ── Core Modules ──
    INDEX --> CONFIG["config.php"]
    INDEX --> DB_FILE["db.php<br/>PDO Connection"]
    INDEX --> AUTH["auth.php<br/>Session Mgmt"]
    INDEX --> RBAC["rbac.php<br/>Access Control"]
    INDEX --> AUDIT_FILE["audit.php<br/>Logging"]
    INDEX --> SETTINGS["settings.php"]

    %% ── Partials ──
    AUTH --> PARTIALS_GROUP

    subgraph PARTIALS_GROUP["🧩 Shared Partials"]
        direction LR
        P1["student_sidebar"]
        P2["teacher_sidebar"]
        P3["admin_sidebar"]
        P4["chatbot_widget"]
        P5["campus_map_modal"]
        P6["info_modal"]
        P7["theme_toggle"]
        P8["mobile_headers"]
        P9["timetable_grid"]
    end

    %% ── Role Pages ──
    RBAC --> S_PAGES
    RBAC --> T_PAGES
    RBAC --> A_PAGES

    subgraph S_PAGES["👨‍🎓 Student Pages"]
        direction LR
        S1["student_dashboard"]
        S2["student_teacher"]
    end

    subgraph T_PAGES["👩‍🏫 Teacher Pages"]
        direction LR
        T1["teacher_dashboard"]
        T2["teacher_subjects"]
        T3["teacher_timetable"]
        T4["profile"]
    end

    subgraph A_PAGES["🔑 Admin Pages"]
        direction LR
        A1["admin_dashboard"]
        A2["admin_monitor"]
        A3["admin_teachers"]
        A4["admin_students"]
        A5["admin_subjects"]
        A6["admin_analytics"]
        A7["admin_audit"]
        A8["admin_timetable"]
        A9["admin_campus_radar"]
        A10["admin_admins"]
        A11["admin_users"]
    end

    %% ── Actions ──
    S_PAGES --> S_ACTIONS
    T_PAGES --> T_ACTIONS
    A_PAGES --> A_ACTIONS

    subgraph S_ACTIONS["👨‍🎓 Student Actions"]
        direction LR
        SA1["login_post"]
        SA2["logout_post"]
    end

    subgraph T_ACTIONS["👩‍🏫 Teacher Actions"]
        direction LR
        TA1["teacher_status_post"]
        TA2["teacher_location_post"]
        TA3["teacher_note_post"]
        TA4["teacher_session_update"]
        TA5["teacher_subjects_update"]
        TA6["teacher_timetable_action"]
    end

    subgraph A_ACTIONS["🔑 Admin Actions"]
        direction LR
        AA1["teacher CRUD"]
        AA2["student CRUD"]
        AA3["admin CRUD"]
        AA4["subject CRUD"]
        AA5["timetable_action"]
        AA6["settings_save"]
        AA7["save_radar"]
        AA8["reset_locations"]
    end

    %% ── API ──
    A_PAGES --> API_GROUP
    T_PAGES --> API_GROUP
    S_PAGES --> API_GROUP

    subgraph API_GROUP["🔌 JSON API Endpoints"]
        direction LR
        API1["admin_locations_json"]
        API2["public_locations_json"]
        API3["campus_radar_json"]
        API4["chatbot_api"]
        API5["teacher_subjects_api"]
    end

    %% ── Database ──
    S_ACTIONS --> DB[("💾 MySQL 8.0<br/>Database: ttrack")]
    T_ACTIONS --> DB
    A_ACTIONS --> DB
    API_GROUP --> DB
    DB_FILE --> DB
    AUDIT_FILE -->|"Log entries"| DB

    %% ── DB Tables ──
    DB --- TBL1[("users")]
    DB --- TBL2[("teacher_profiles")]
    DB --- TBL3[("teacher_locations")]
    DB --- TBL4[("teacher_status_events")]
    DB --- TBL5[("audit_logs")]

    %% ── External ──
    API4 -->|"AI Queries"| PERPLEXITY["🧠 Perplexity API"]
    BROWSER -.->|"Map Tiles"| OSM["🗺️ OpenStreetMap"]
    PMA["🛠️ phpMyAdmin :8081"] -.-> DB

    %% ── Styling ──
    classDef userNode fill:#818CF8,stroke:#4338CA,stroke-width:2px,color:#fff,font-weight:bold
    classDef browserNode fill:#60A5FA,stroke:#2563EB,stroke-width:2px,color:#fff
    classDef nginxNode fill:#06B6D4,stroke:#0E7490,stroke-width:2px,color:#fff
    classDef coreNode fill:#8B5CF6,stroke:#6D28D9,stroke-width:2px,color:#fff
    classDef partialNode fill:#C4B5FD,stroke:#7C3AED,stroke-width:1px,color:#1E293B
    classDef studentNode fill:#38BDF8,stroke:#0284C7,stroke-width:2px,color:#fff
    classDef teacherNode fill:#4ADE80,stroke:#16A34A,stroke-width:2px,color:#fff
    classDef adminNode fill:#FB923C,stroke:#EA580C,stroke-width:2px,color:#fff
    classDef apiNode fill:#2DD4BF,stroke:#0D9488,stroke-width:2px,color:#fff
    classDef dbNode fill:#FBBF24,stroke:#D97706,stroke-width:2px,color:#1E293B
    classDef externalNode fill:#34D399,stroke:#059669,stroke-width:2px,color:#fff
    classDef toolNode fill:#94A3B8,stroke:#64748B,stroke-width:2px,color:#fff

    class STU,TCH,ADM userNode
    class BROWSER browserNode
    class NGINX nginxNode
    class INDEX,CONFIG,DB_FILE,AUTH,RBAC,AUDIT_FILE,SETTINGS coreNode
    class P1,P2,P3,P4,P5,P6,P7,P8,P9 partialNode
    class S1,S2,SA1,SA2 studentNode
    class T1,T2,T3,T4,TA1,TA2,TA3,TA4,TA5,TA6 teacherNode
    class A1,A2,A3,A4,A5,A6,A7,A8,A9,A10,A11,AA1,AA2,AA3,AA4,AA5,AA6,AA7,AA8 adminNode
    class API1,API2,API3,API4,API5 apiNode
    class DB,TBL1,TBL2,TBL3,TBL4,TBL5 dbNode
    class PERPLEXITY,OSM externalNode
    class PMA toolNode
```