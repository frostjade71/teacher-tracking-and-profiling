<?php
// app/pages/admin_analytics.php

require_login();
require_role('admin');

$u = current_user();
$pdo = db();

// --- Data Fetching ---

// 1. New Visitors (Unique Student Logins)
// Today
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT actor_user_id) 
    FROM audit_logs 
    WHERE action = 'LOGIN_SUCCESS' 
    AND timestamp >= CURDATE()
    AND actor_user_id IN (SELECT id FROM users WHERE role = 'student')
");
$stmt->execute();
$newVisitorsToday = $stmt->fetchColumn();

// Yesterday
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT actor_user_id) 
    FROM audit_logs 
    WHERE action = 'LOGIN_SUCCESS' 
    AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    AND timestamp < CURDATE()
    AND actor_user_id IN (SELECT id FROM users WHERE role = 'student')
");
$stmt->execute();
$newVisitorsYesterday = $stmt->fetchColumn();

// Calculate Percentage Change
$visitorChange = 0;
if ($newVisitorsYesterday > 0) {
    $visitorChange = (($newVisitorsToday - $newVisitorsYesterday) / $newVisitorsYesterday) * 100;
} else {
    $visitorChange = $newVisitorsToday > 0 ? 100 : 0;
}


// 2. Recent Traffic (Last 7 Days) - Line Chart
// Metrics: Student Logins, Map Views (All roles)
$dates = [];
$trafficLogins = [];
$trafficMapViews = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('M d', strtotime($date));

    // Logins
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_logs 
        WHERE action = 'LOGIN_SUCCESS' 
        AND DATE(timestamp) = ?
        AND actor_user_id IN (SELECT id FROM users WHERE role = 'student')
    ");
    $stmt->execute([$date]);
    $trafficLogins[] = $stmt->fetchColumn();

    // Map Views
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_logs 
        WHERE action = 'VIEW_MAP' 
        AND DATE(timestamp) = ?
    ");
    $stmt->execute([$date]);
    $trafficMapViews[] = $stmt->fetchColumn();
}


// 3. Weekly Traffic (Same data, aggregated by week - simplified to just show the same daily data as bar for now as per request "same but weekly", assuming weekly breakdown)
// Actually, let's do real weekly aggregation for the last 4 weeks.
$weeks = [];
$weeklyLogins = [];
$weeklyMapViews = [];

for ($i = 3; $i >= 0; $i--) {
    $startOfWeek = date('Y-m-d', strtotime("-$i weeks monday this week")); // Start of week (Monday)
    $endOfWeek = date('Y-m-d', strtotime("-$i weeks sunday this week")); // End of week (Sunday)
    
    $weeks[] = 'Week ' . date('W', strtotime($startOfWeek));
    
    // Logins
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_logs 
        WHERE action = 'LOGIN_SUCCESS' 
        AND DATE(timestamp) BETWEEN ? AND ?
        AND actor_user_id IN (SELECT id FROM users WHERE role = 'student')
    ");
    $stmt->execute([$startOfWeek, $endOfWeek]);
    $weeklyLogins[] = $stmt->fetchColumn();

    // Map Views
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM audit_logs 
        WHERE action = 'VIEW_MAP' 
        AND DATE(timestamp) BETWEEN ? AND ?
    ");
    $stmt->execute([$startOfWeek, $endOfWeek]);
    $weeklyMapViews[] = $stmt->fetchColumn();
}


// 4. Status Distribution
$stmt = $pdo->prepare("
    SELECT t.latest_status, COUNT(*) as count
    FROM (
        SELECT 
            (SELECT status FROM teacher_status_events WHERE teacher_user_id = u.id ORDER BY set_at DESC LIMIT 1) as latest_status
        FROM users u
        WHERE u.role = 'teacher' AND u.is_active = 1
    ) t
    WHERE t.latest_status IS NOT NULL
    GROUP BY t.latest_status
");
$stmt->execute();
$statusDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Ensure all statuses are present for chart consistency
$allStatuses = ['AVAILABLE', 'IN_CLASS', 'BUSY', 'OFF_CAMPUS', 'OFFLINE'];
foreach ($allStatuses as $s) {
    if (!isset($statusDistribution[$s])) {
        $statusDistribution[$s] = 0;
    }
}


// 5. Top Teacher Status Updates (Ranking)
$stmt = $pdo->prepare("
    SELECT u.name, COUNT(*) as update_count
    FROM teacher_status_events tse
    JOIN users u ON tse.teacher_user_id = u.id
    GROUP BY tse.teacher_user_id
    ORDER BY update_count DESC
    LIMIT 5
");
$stmt->execute();
$topTeachers = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics | Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 dark:bg-slate-900 min-h-screen font-sans text-slate-800 dark:text-slate-200 transition-colors duration-200">
    
    <!-- Loader -->
    <div class="loader-container">
        <div class="loader">
            <div class="loader-square"></div>
            <div class="loader-square"></div>
            <div class="loader-square"></div>
        </div>
    </div>
    <script src="/assets/loader.js"></script>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/admin_sidebar.php'; ?>

        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header for Mobile -->
            <?php include __DIR__ . '/../partials/admin_mobile_header.php'; ?>


            <!-- Top Bar Desktop -->
            <header class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                <div class="flex items-center text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span>Overview</span>
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Analytics</span>
                </div>
                <!-- Theme Toggle -->
                <!-- Theme Toggle -->
                <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
            </header>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto">

            <div class="p-4 md:p-8 max-w-7xl mx-auto space-y-6">
                
                <div class="relative text-left mb-6 md:mb-10 pt-6">
                    <!-- Decorative background glow -->
                    <div class="absolute top-1/2 left-0 -translate-y-1/2 w-[150px] md:w-[400px] h-[150px] md:h-[200px] bg-blue-500/20 dark:bg-blue-500/10 rounded-full blur-[40px] md:blur-[60px] -z-10 pointer-events-none"></div>
                    
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-bold uppercase tracking-wider mb-2 md:mb-4 border border-blue-100 dark:border-blue-800 shadow-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span>
                        Analytics Overview
                    </div>
                    
                    <h1 class="text-2xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-2 md:mb-3 tracking-tight">
                        Platform <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-400 dark:to-indigo-400">Activity & Stats</span>
                    </h1>
                </div>

                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- New Visitors -->
                    <div class="group bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 relative overflow-hidden transition-all duration-300 shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <svg class="w-24 h-24 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        
                        <h3 class="font-bold text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider mb-2">New Visitors (Today)</h3>
                        <div class="text-5xl font-black text-slate-900 dark:text-white mb-3 tracking-tighter"><?= number_format($newVisitorsToday) ?></div>
                        
                        <div class="flex items-center gap-1.5 w-fit px-2 py-1 rounded-md text-sm font-medium <?= $visitorChange >= 0 ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20' ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $visitorChange >= 0 ? 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6' : 'M13 17h8m0 0V9m0 8l-8-8-4 4-6-6' ?>"></path>
                            </svg>
                            <span><?= number_format(abs($visitorChange), 1) ?>%</span>
                            <span class="opacity-70 ml-1 font-normal">vs yesterday</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Recent Traffic (Line Chart) -->
                    <div class="col-span-1 lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                </span>
                                Recent Traffic
                            </h3>
                            <span class="text-xs font-medium text-slate-500 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded-md">Last 7 Days</span>
                        </div>
                        <div class="relative h-72 w-full">
                            <canvas id="recentTrafficChart"></canvas>
                        </div>
                    </div>

                    <!-- Status Distribution (Doughnut) -->
                    <div class="col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 transition-all duration-300">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                             <span class="p-2 rounded-lg bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                            </span>
                            Current Status
                        </h3>
                        <div class="relative h-56 w-full flex justify-center">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <!-- Custom Legend/Stats below if needed -->
                        <div class="mt-6 grid grid-cols-2 gap-3 text-xs font-medium">
                             <div class="flex items-center gap-2 p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-900/30"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span><span class="text-slate-700 dark:text-slate-300">Available: <?= $statusDistribution['AVAILABLE'] ?></span></div>
                             <div class="flex items-center gap-2 p-2 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span><span class="text-slate-700 dark:text-slate-300">In Class: <?= $statusDistribution['IN_CLASS'] ?></span></div>
                             <div class="flex items-center gap-2 p-2 rounded-lg bg-rose-50 dark:bg-rose-900/10 border border-rose-100 dark:border-rose-900/30"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span><span class="text-slate-700 dark:text-slate-300">Busy: <?= $statusDistribution['BUSY'] ?></span></div>
                             <div class="flex items-center gap-2 p-2 rounded-lg bg-purple-50 dark:bg-purple-900/10 border border-purple-100 dark:border-purple-900/30"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span><span class="text-slate-700 dark:text-slate-300">Off Campus: <?= $statusDistribution['OFF_CAMPUS'] ?></span></div>
                             <div class="col-span-2 flex items-center gap-2 p-2 rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700"><span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span><span class="text-slate-700 dark:text-slate-300">Offline: <?= $statusDistribution['OFFLINE'] ?></span></div>
                        </div>
                    </div>

                     <!-- Weekly Traffic (Bar Chart) -->
                     <div class="col-span-1 lg:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                </span>
                                Weekly Overview
                            </h3>
                            <span class="text-xs font-medium text-slate-500 px-2 py-1 bg-slate-100 dark:bg-slate-700 rounded-md">Last 4 Weeks</span>
                        </div>
                        <div class="relative h-72 w-full">
                            <canvas id="weeklyTrafficChart"></canvas>
                        </div>
                    </div>

                    <!-- Top Teachers -->
                    <div class="col-span-1 bg-white dark:bg-slate-800 rounded-2xl border border-gray-200 dark:border-slate-700 p-6 shadow-sm hover:shadow-lg hover:border-blue-400 dark:hover:border-blue-500 hover:-translate-y-1 transition-all duration-300">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-6 flex items-center gap-2">
                            <span class="p-2 rounded-lg bg-orange-50 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </span>
                            Top Active Teachers
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($topTeachers as $index => $t): ?>
                            <div class="flex items-center justify-between group cursor-default">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm shadow-sm transition-transform group-hover:scale-110 <?= [
                                        'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300', // #1 Gold
                                        'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',    // #2 Silver
                                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'     // #3 Silver-ish
                                    ][$index] ?? 'bg-slate-50 text-slate-400 dark:bg-slate-900 dark:text-slate-600' ?>">
                                        <?= $index + 1 ?>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800 dark:text-white transition-colors"><?= htmlspecialchars($t['name']) ?></div>
                                        <div class="text-xs text-slate-500 dark:text-slate-400"><?= $t['update_count'] ?> status updates</div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($topTeachers)): ?>
                                <div class="flex flex-col items-center justify-center py-8 text-slate-400">
                                    <svg class="w-10 h-10 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4"></path></svg>
                                    <p class="text-sm">No activity recorded yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- Chart Config -->
    <script>
        // Colors & Theme Utils
        const isDark = document.documentElement.classList.contains('dark');
        const gridColor = isDark ? '#334155' : '#e2e8f0';
        const textColor = isDark ? '#cbd5e1' : '#64748b';
        
        // Store chart instances
        let recentTrafficChart, statusChart, weeklyTrafficChart;

        function initCharts() {
            // 1. Recent Traffic Line Chart
            const ctxRecent = document.getElementById('recentTrafficChart').getContext('2d');
            recentTrafficChart = new Chart(ctxRecent, {
                type: 'line',
                data: {
                    labels: <?= json_encode($dates) ?>,
                    datasets: [
                        {
                            label: 'Student Logins',
                            data: <?= json_encode($trafficLogins) ?>,
                            borderColor: '#3b82f6', // blue-500
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Map Views',
                            data: <?= json_encode($trafficMapViews) ?>,
                            borderColor: '#8b5cf6', // purple-500
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });

            // 2. Status Distribution Doughnut
            const ctxStatus = document.getElementById('statusChart').getContext('2d');
            statusChart = new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'In Class', 'Busy', 'Off Campus', 'Offline'],
                    datasets: [{
                        data: [
                            <?= $statusDistribution['AVAILABLE'] ?>,
                            <?= $statusDistribution['IN_CLASS'] ?>,
                            <?= $statusDistribution['BUSY'] ?>,
                            <?= $statusDistribution['OFF_CAMPUS'] ?>,
                            <?= $statusDistribution['OFFLINE'] ?>
                        ],
                        backgroundColor: [
                            '#10b981', // emerald-500
                            '#f59e0b', // amber-500
                            '#ef4444', // rose-500
                            '#a855f7', // purple-500
                            '#64748b'  // slate-500
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#1e293b' : '#ffffff',
                            titleColor: isDark ? '#f8fafc' : '#0f172a',
                            bodyColor: isDark ? '#f8fafc' : '#0f172a',
                            borderColor: isDark ? '#334155' : '#e2e8f0',
                            borderWidth: 1
                        }
                    },
                    cutout: '70%'
                }
            });

            // 3. Weekly Traffic Bar Chart
            const ctxWeekly = document.getElementById('weeklyTrafficChart').getContext('2d');
            weeklyTrafficChart = new Chart(ctxWeekly, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($weeks) ?>,
                    datasets: [
                        {
                            label: 'Student Logins',
                            data: <?= json_encode($weeklyLogins) ?>,
                            backgroundColor: '#3b82f6',
                            borderRadius: 6
                        },
                        {
                            label: 'Map Views',
                            data: <?= json_encode($weeklyMapViews) ?>,
                            backgroundColor: '#8b5cf6',
                            borderRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { color: textColor }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: gridColor },
                            ticks: { color: textColor }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: textColor }
                        }
                    }
                }
            });
        }

        initCharts();

        // Theme Change Observer
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    const isDarkNow = document.documentElement.classList.contains('dark');
                    updateChartTheme(isDarkNow);
                }
            });
        });

        observer.observe(document.documentElement, { attributes: true });

        function updateChartTheme(dark) {
            const newGridColor = dark ? '#334155' : '#e2e8f0';
            const newTextColor = dark ? '#cbd5e1' : '#64748b';

            // Helper to update cartesian charts (Line/Bar)
            const updateCartesian = (chart) => {
                if (!chart) return;
                
                // Update Scales
                if (chart.options.scales.x) {
                    chart.options.scales.x.ticks.color = newTextColor;
                    chart.options.scales.x.grid.color = false; // Keep x grid hidden
                }
                if (chart.options.scales.y) {
                    chart.options.scales.y.ticks.color = newTextColor;
                    chart.options.scales.y.grid.color = newGridColor;
                }

                // Update Legend
                if (chart.options.plugins.legend) {
                    chart.options.plugins.legend.labels.color = newTextColor;
                }
                
                chart.update();
            }

            updateCartesian(recentTrafficChart);
            updateCartesian(weeklyTrafficChart);

            // Update Doughnut (Tooltip only mainly)
            if (statusChart) {
                if (statusChart.options.plugins.tooltip) {
                    statusChart.options.plugins.tooltip.backgroundColor = dark ? '#1e293b' : '#ffffff';
                    statusChart.options.plugins.tooltip.titleColor = dark ? '#f8fafc' : '#0f172a';
                    statusChart.options.plugins.tooltip.bodyColor = dark ? '#f8fafc' : '#0f172a';
                    statusChart.options.plugins.tooltip.borderColor = dark ? '#334155' : '#e2e8f0';
                }
                statusChart.update();
            }
        }

    </script>
        </main>
        </div>
    </div>
</body>
</html>
