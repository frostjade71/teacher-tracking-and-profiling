<?php
// app/pages/login.php

// Redirect if already logged in
if (current_user()) {
    $u = current_user();
    if ($u['role'] === 'student') header("Location: /?page=student_dashboard");
    elseif ($u['role'] === 'teacher') header("Location: /?page=teacher_dashboard");
    elseif ($u['role'] === 'admin') header("Location: /?page=admin_dashboard");
    exit;
}

$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FacultyLink - Smart Faculty Tracking</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .landing-page {
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .landing-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 6rem;
            max-width: 1000px;
            margin: auto;
            align-items: center;
        }

        /* Footer Section */
        .professional-footer {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            padding: 4rem 2rem;
            position: relative;
            z-index: 10;
            width: 100%;
        }

        .footer-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 4rem;
            padding: 0 2rem;
        }

        .footer-col h3 {
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
        }

        .footer-col p {
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-links li {
            margin-bottom: 0.75rem;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.875rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #60a5fa;
        }

        .footer-bottom {
            max-width: 1200px;
            margin: 3rem auto 0;
            padding: 2rem 2rem 0;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
        }

        @media (max-width: 900px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2.5rem;
                text-align: center;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* Hero Text */
        .hero-text {
            color: white;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .hero-title span {
            background: linear-gradient(135deg, #60a5fa, #22d3ee);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-description {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.6);
            line-height: 1.6;
            max-width: 400px;
        }

        /* Login Card */
        .login-card {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 1.25rem;
            padding: 2.5rem;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .login-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
            min-height: 110px; /* Preserve space for logo and title */
        }

        .login-header img {
            width: 48px;
            height: 48px;
            margin-bottom: 1rem;
            aspect-ratio: 1/1;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Footer */
        .landing-footer {
            position: absolute;
            bottom: 2rem;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            letter-spacing: 0.025em;
        }

        .login-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .login-header p {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 500;
            color: #475569;
            margin-bottom: 0.375rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.15s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            background: #334155;
        }

        .login-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .demo-info {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #f1f5f9;
            text-align: center;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .demo-info code {
            background: #f1f5f9;
            padding: 0.125rem 0.375rem;
            border-radius: 0.25rem;
            font-family: monospace;
            color: #64748b;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .landing-content {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .hero-title {
                font-size: 2.25rem;
            }

            .hero-description {
                margin: 0 auto;
            }

            .login-card {
                max-width: 400px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body class="font-sans bg-slate-900">
    
    <!-- Loader -->
    <div class="loader-container">
        <div class="loader">
            <div class="loader-square"></div>
            <div class="loader-square"></div>
            <div class="loader-square"></div>
        </div>
    </div>
    <script src="/assets/loader.js"></script>

    <!-- Aurora Background -->
    <div class="absolute inset-0 z-0 aurora-bg pointer-events-none"></div>

    <main class="landing-page relative z-10">
        <div class="landing-content">
            <!-- Hero -->
            <div class="hero-text opacity-0 animate-[fadeIn_0.8s_ease-out_forwards]">
                <h1 class="hero-title">Track & Connect with <span>FacultyLink</span></h1>
                <p class="hero-description">
                    Real-time faculty tracking and availability management for modern educational institutions.
                </p>
            </div>

            <!-- Login -->
            <div class="login-card">
                <div class="login-header">
                    <img src="/assets/favicon/favicon.svg" width="48" height="48" alt="Logo">
                    <h2>Sign In</h2>
                    <p>Access your dashboard</p>
                </div>

                <?php if ($error): ?>
                    <div class="login-error">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/?page=login_post" method="POST" class="login-form">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="name@school.edu">
                    </div>
                    
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="••••••••">
                    </div>
                    
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
            </div>
        </div>
    </main>

    <footer class="professional-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3><img src="/assets/favicon/favicon.svg" alt="FacultyLink" style="width: 20px; height: 20px; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">FacultyLink</h3>
                <p>Revolutionizing faculty tracking and profiling through real-time technology and smart data management.</p>
            </div>
            <div class="footer-col">
                <h3>Portals</h3>
                <ul class="footer-links">
                    <li><a href="#">Admin Dashboard</a></li>
                    <li><a href="#">Faculty Portal</a></li>
                    <li><a href="#">Student Access</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h3>Support</h3>
                <ul class="footer-links">
                    <li><a href="#">Help Center</a></li>
                    <li><a href="#">Contact Us</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> FacultyLink. All rights reserved.</span>
            <span class="opacity-50">Designed for modern educational excellence.</span>
        </div>
    </footer>
</body>
</html>
