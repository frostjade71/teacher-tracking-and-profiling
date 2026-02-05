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
    <title>Sign In - FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body class="min-h-screen w-full relative overflow-hidden flex items-center justify-center font-sans bg-slate-900">
    
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
    
    <!-- Content -->
    <div class="relative z-10 w-full max-w-md bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-white/20 overflow-hidden mx-4">
        
        <div class="p-8 md:p-10">
            <div class="text-center mb-10">
                <div class="inline-flex items-center justify-center mb-4">
                     <img src="/assets/favicon/favicon.svg" class="w-16 h-16" alt="Logo">
                </div>
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">FacultyLink</h1>
                <p class="text-gray-500 mt-2 text-sm">Please sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-100 text-red-600 px-4 py-3 rounded-lg text-sm flex items-center">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="/?page=login_post" method="POST" class="space-y-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Address</label>
                    <input type="email" name="email" required 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm"
                        placeholder="name@school.edu">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required 
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all text-sm"
                        placeholder="••••••••">
                </div>
                
                <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-semibold py-3 rounded-lg transition-colors shadow-lg shadow-slate-900/20 focus:ring-4 focus:ring-slate-200">
                    Sign In
                </button>
            </form>
        </div>
        
        <div class="bg-gray-50/50 px-8 py-6 border-t border-gray-100 text-center">
             <div class="text-xs text-gray-400">
                <span class="block mb-2 font-medium text-gray-500">Demo Accounts</span>
                <span class="inline-block mx-2">admin@school.edu</span>&bull;
                <span class="inline-block mx-2">john.doe@school.edu</span>&bull;
                <span class="inline-block mx-2">student1@school.edu</span>
             </div>
             <div class="mt-2 text-xs text-gray-400">Password for all: <span class="font-mono">password123</span></div>
        </div>

    </div>
</body>
</html>
