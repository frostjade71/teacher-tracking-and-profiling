<?php
// app/pages/teacher_timetable.php

require_login();
require_role('teacher');

$u = current_user();
$pdo = db();

// Fetch teacher's assigned subjects for the dropdown
$stmt = $pdo->prepare("SELECT subjects_json FROM teacher_profiles WHERE teacher_user_id = ?");
$stmt->execute([$u['id']]);
$profile = $stmt->fetch();
$assignedSubjects = [];
if ($profile && !empty($profile['subjects_json'])) {
    $assignedSubjects = json_decode($profile['subjects_json'], true) ?? [];
}

// Fetch existing timetable
$stmt = $pdo->prepare("SELECT * FROM teacher_timetables WHERE teacher_user_id = ? ORDER BY day, start_time");
$stmt->execute([$u['id']]);
$timetableEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuration
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

// Re-organize entries for easy lookup: $schedule['Monday']['07:00:00']
$schedule = [];
foreach ($timetableEntries as $entry) {
    $timeKey = $entry['start_time']; // Keep the HH:MM:SS format from DB for matching
    $schedule[$entry['day']][$timeKey] = $entry;
}

// 1. First, find all unique time slots for this teacher to build the rows
$stmt = $pdo->prepare("SELECT DISTINCT start_time, end_time FROM teacher_timetables WHERE teacher_user_id = ? ORDER BY start_time ASC");
$stmt->execute([$u['id']]);
$timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If no slots exist (fallback for new users), seed from system defaults
if (empty($timeSlots)) {
    $stmt = $pdo->query("SELECT start_time, end_time FROM system_default_timetable_rows ORDER BY start_time ASC");
    $defaults = $stmt->fetchAll();

    if (empty($defaults)) {
        // Ultimate fallback if even admin hasn't configured defaults
        for ($h = 7; $h < 19; $h++) {
            $defaults[] = ['start_time' => sprintf("%02d:00:00", $h), 'end_time' => sprintf("%02d:00:00", $h + 1)];
        }
    }

    // Seed into database so they become persistent and deletable/editable
    $insert = $pdo->prepare("INSERT INTO teacher_timetables (teacher_user_id, day, start_time, end_time, subject_text, room_text) VALUES (?, ?, ?, ?, '', '')");
    foreach ($days as $day) {
        foreach ($defaults as $row) {
            $insert->execute([$u['id'], $day, $row['start_time'], $row['end_time']]);
        }
    }

    // Now fetch them again to populate $timeSlots and continue normally
    $stmt = $pdo->prepare("SELECT DISTINCT start_time, end_time FROM teacher_timetables WHERE teacher_user_id = ? ORDER BY start_time ASC");
    $stmt->execute([$u['id']]);
    $timeSlots = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Handle Partial Grid Request
if (isset($_GET['partial']) && $_GET['partial'] === 'grid') {
    include __DIR__ . '/../partials/teacher_timetable_grid.php';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Timetable | FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/favicon/favicon-96x96.png') ?>" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="<?= url('assets/favicon/favicon.svg') ?>" />
    <link rel="shortcut icon" href="<?= url('assets/favicon/favicon.ico') ?>" />
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('assets/favicon/apple-touch-icon.png') ?>" />
    <link rel="manifest" href="<?= url('assets/favicon/site.webmanifest') ?>" />
    <link rel="stylesheet" href="<?= url('assets/app.css') ?>">
    <script src="<?= url('assets/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= url('assets/toast.css') ?>">
    <script src="<?= url('assets/toast.js') ?>"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script src="<?= url('assets/map_arrows.js') ?>"></script>

    <style>
        #campusMap { height: 100%; width: 100%; z-index: 1; }
        .leaflet-popup-content-wrapper { border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
        .leaflet-popup-content b { font-size: 1.1em; color: #1e293b; }
        html.dark .leaflet-layer { filter: brightness(0.8) contrast(1.2) grayscale(0.2); }

        .timetable-grid {
            display: grid;
            grid-template-columns: 120px repeat(5, 1fr) 60px;
            grid-auto-rows: minmax(100px, auto);
            gap: 1px;
            background-color: #e2e8f0; /* Border color */
            min-width: 1000px; /* Base width for mobile scrolling */
        }
        
        @media (min-width: 1024px) {
            .timetable-grid {
                min-width: auto; /* Allow it to fit on desktop */
            }
        }
        
        .html.dark .timetable-grid {
            background-color: #334155;
        }

        .grid-header {
            background-color: #2664eb;
            color: white;
            padding: 1rem;
            text-align: center;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .grid-time {
            background-color: #f8fafc;
            padding: 1rem;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-cell {
            background-color: white;
            padding: 0.5rem;
            position: relative;
            transition: background-color 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100px;
        }
        
        .grid-cell:hover {
            background-color: #f1f5f9;
        }

        /* Dark Mode overrides via parent class if needed, or using Tailwind classes directly in PHP loop */
    </style>
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
    <script src="<?= url('assets/loader.js') ?>"></script>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/teacher_sidebar.php'; ?>

        <!-- Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header -->
            <?php include __DIR__ . '/../partials/teacher_mobile_header.php'; ?>

            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span class="text-slate-500">Management</span> 
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Timetable</span>
                </div>
                <!-- Theme Toggle -->
                <div class="flex items-center gap-4">
                    <?php include __DIR__ . '/../partials/theme_toggle.php'; ?>
                </div>
            </div>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-8">
                <div class="max-w-7xl mx-auto">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-white">Weekly Timetable</h1>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Manage your class schedule</p>
                        </div>
                    </div>

                    <!-- Timetable Container -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden border border-gray-200 dark:border-slate-700">
                        <div class="overflow-x-auto" id="timetable-grid-container">
                            <?php include __DIR__ . '/../partials/teacher_timetable_grid.php'; ?>
                        </div>
                    </div>

                    <!-- Add Row Button -->
                    <div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="md:w-1/3"></div> <!-- Spacer -->
                        <div class="flex justify-center flex-1">
                            <button onclick="openAddRowModal()" 
                                    class="flex items-center gap-2 px-6 py-3 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white rounded-xl font-bold shadow-lg transition-all transform hover:scale-105">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                                Add New Time Row
                            </button>
                        </div>
                        <div class="md:w-1/3 flex justify-end">
                            <button onclick="resetToDefault()" 
                                    class="flex items-center gap-2 px-4 py-2 text-slate-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors text-sm font-semibold border border-transparent hover:border-red-200 dark:hover:border-red-900/50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Reset to Default
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Slot Modal -->
    <dialog id="slotModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-[95%] max-w-sm">
        <div class="bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50">
                <h3 class="font-bold text-slate-800 dark:text-white" id="modalTitle">Edit Slot</h3>
                <button onclick="document.getElementById('slotModal').close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="slotForm" onsubmit="saveSlot(event)" class="p-5 space-y-4">
                <input type="hidden" id="slotDay" name="day">
                <input type="hidden" id="slotTime" name="time">
                
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400 mb-2" id="slotInfo"></div>

                <!-- Step 1: Subject -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Subject</label>
                    <select id="slotSubject" name="subject" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2" required>
                        <option value="" disabled selected>Select Subject</option>
                        <?php foreach ($assignedSubjects as $subj): ?>
                            <option value="<?= htmlspecialchars($subj) ?>"><?= htmlspecialchars($subj) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($assignedSubjects)): ?>
                        <p class="text-xs text-red-500 mt-1">No subjects assigned. Go to 'Subjects' page first.</p>
                    <?php endif; ?>
                </div>

                <!-- Step 2: Room -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Room</label>
                    <input type="text" id="slotRoom" name="room" placeholder="e.g. Room 305" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2">
                </div>

                <!-- Step 3: Course/Year -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Course / Year</label>
                    <input type="text" id="slotCourse" name="course" placeholder="e.g. BSIT-4A" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2">
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" id="btnDelete" onclick="deleteSlot()" class="hidden px-4 py-2 border border-red-200 dark:border-red-900/50 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 font-medium transition-colors">
                        Clear
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg shadow-blue-500/30 transition-colors">
                        Done
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Add New Row Modal -->
    <dialog id="addRowModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-[95%] max-w-sm">
        <div class="bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50">
                <h3 class="font-bold text-slate-800 dark:text-white">Add New Time Row</h3>
                <button onclick="document.getElementById('addRowModal').close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form onsubmit="submitAddRow(event)" class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">Start Time</label>
                        <input type="time" name="start_time" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">End Time</label>
                        <input type="time" name="end_time" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2" required>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">This will add empty slot rows for Monday to Friday for this time range.</p>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('addRowModal').close()" class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold shadow-lg transition-colors">
                        Add Row
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Edit Time Modal -->
    <dialog id="editTimeModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-[95%] max-w-sm">
        <div class="bg-white dark:bg-slate-800 overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-slate-700 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50">
                <h3 class="font-bold text-slate-800 dark:text-white">Edit Time Range</h3>
                <button onclick="document.getElementById('editTimeModal').close()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form id="editTimeForm" onsubmit="submitEditTime(event)" class="p-5 space-y-4">
                <input type="hidden" name="old_start_time" id="oldStartTime">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">New Start Time</label>
                        <input type="time" name="new_start_time" id="newStartTime" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">New End Time</label>
                        <input type="time" name="new_end_time" id="newEndTime" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 text-slate-800 dark:text-white focus:ring-blue-500 px-3 py-2" required>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">Changing the time will update all daily slots in this row.</p>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('editTimeModal').close()" class="flex-1 px-4 py-2 border border-gray-200 dark:border-slate-600 text-slate-600 dark:text-slate-300 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 font-medium transition-colors">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold shadow-lg transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Reset Confirmation Modal -->
    <dialog id="resetConfirmModal" class="p-0 rounded-xl shadow-2xl backdrop:bg-black/50 dark:bg-slate-800 w-[95%] max-w-sm">
        <div class="bg-white dark:bg-slate-800 overflow-hidden p-6 text-center">
            <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-2">Reset Timetable?</h3>
            <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 font-medium leading-relaxed">
                Caution: This will delete <span class="text-red-600 dark:text-red-400 font-bold">ALL</span> your current timetable entries and revert back to system defaults. This action cannot be undone.
            </p>
            
            <div class="flex gap-3">
                <button onclick="document.getElementById('resetConfirmModal').close()" class="flex-1 px-4 py-3 border border-gray-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl hover:bg-gray-50 dark:hover:bg-slate-700 font-bold transition-all">
                    Cancel
                </button>
                <button onclick="executeReset()" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold shadow-lg shadow-red-500/25 transition-all">
                    Yes, Reset
                </button>
            </div>
        </div>
    </dialog>

    <script>
        function openSlotModal(day, time, entry) {
            document.getElementById('slotDay').value = day;
            document.getElementById('slotTime').value = time;
            document.getElementById('slotInfo').textContent = `${day} @ ${formatTime(time)}`;
            
            const subjectSelect = document.getElementById('slotSubject');
            const roomInput = document.getElementById('slotRoom');
            const courseInput = document.getElementById('slotCourse');
            const btnDelete = document.getElementById('btnDelete');

            if (entry) {
                // Formatting for existing entry
                document.getElementById('modalTitle').textContent = 'Edit Class';
                subjectSelect.value = entry.subject_text;
                roomInput.value = entry.room_text;
                courseInput.value = entry.course_text || "";
                btnDelete.classList.remove('hidden');
            } else {
                // New entry
                document.getElementById('modalTitle').textContent = 'Add Class';
                subjectSelect.value = "";
                roomInput.value = "";
                courseInput.value = "";
                btnDelete.classList.add('hidden');
            }
            
            document.getElementById('slotModal').showModal();
        }

        async function saveSlot(e) {
            e.preventDefault();
            
            const formData = new FormData(document.getElementById('slotForm'));
            formData.append('action', 'save');
            
            showToast('Saving...', 'info');
            
            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Saved!', 'success');
                    document.getElementById('slotModal').close();
                    
                    // Update UI dynamically
                    const day = document.getElementById('slotDay').value;
                    const time = document.getElementById('slotTime').value;
                    updateCell(day, time, data.entry);
                } else {
                    showToast(data.message || 'Error', 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function deleteSlot() {

            
            const day = document.getElementById('slotDay').value;
            const time = document.getElementById('slotTime').value;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('day', day);
            formData.append('time', time);
            
            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Slot cleared!', 'success');
                    document.getElementById('slotModal').close();
                    
                    const day = document.getElementById('slotDay').value;
                    const time = document.getElementById('slotTime').value;
                    updateCell(day, time, null);
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        function formatTime(timeStr) {
            // "07:00" -> "7:00 AM"
            const [h, m] = timeStr.split(':');
            const hour = parseInt(h);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const showHour = hour % 12 || 12;
            return `${showHour}:${m} ${ampm}`;
        }

        function updateCell(day, time, entry) {
            // Use global regex /:/g to replace ALL colons, matching PHP's str_replace
            const cellId = `cell-${day}-${time.replace(/:/g, '-')}`;
            const cell = document.getElementById(cellId);
            if (!cell) return;

            // Update onclick
            const entryJson = entry ? JSON.stringify(entry) : 'null';
            cell.setAttribute('onclick', `openSlotModal('${day}', '${time}', ${entryJson})`);

            // Check if entry exists AND has a subject
            if (entry && entry.subject_text && entry.subject_text.trim() !== '') {
                cell.innerHTML = `
                    <div class="flex flex-col items-center justify-center text-center w-full h-full p-1">
                        <span class="font-bold text-slate-900 dark:text-white text-sm mb-1 line-clamp-2">
                            ${escapeHtml(entry.subject_text)}
                        </span>
                        ${(entry.room_text || entry.course_text) ? `
                        <span class="text-xs text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-full font-medium">
                            ${escapeHtml(entry.room_text)}${entry.room_text && entry.course_text ? ' • ' : ''}${escapeHtml(entry.course_text)}
                        </span>
                        ` : ''}
                    </div>
                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                `;
            } else {
                cell.innerHTML = `
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity transform group-hover:scale-110">
                        <button class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                    <span class="text-xs text-slate-300 dark:text-slate-600 absolute bottom-2 select-none group-hover:hidden">Empty</span>
                `;
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileSidebarOverlay');
            
            // Sidebar is hidden by default on mobile with 'translate-x-full'
            if (sidebar.classList.contains('translate-x-full')) {
                // Open: Remove the class that hides it
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => {
                    overlay.classList.remove('opacity-0');
                }, 10);
            } else {
                // Close: Add the class that hides it
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('opacity-0');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                }, 300);
            }
        }
        async function saveTimeLabel(index, value) {
            const formData = new FormData();
            formData.append('action', 'save_time_label');
            formData.append('index', index);
            formData.append('value', value);
            
            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Time label updated', 'success');
                } else {
                    showToast(data.message || 'Error saving time label', 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function deleteRow(time) {
            // Remove local confirmation as requested
            const formData = new FormData();
            formData.append('action', 'delete_row');
            formData.append('time', time);

            showToast('Deleting row...', 'info');

            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Row deleted!', 'success');
                    // AJAX-friendly removal
                    document.querySelectorAll(`[data-time-row="${time}"]`).forEach(el => el.remove());
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function refreshGrid() {
            try {
                const res = await fetch(window.location.href + '&partial=grid');
                const html = await res.text();
                document.getElementById('timetable-grid-container').innerHTML = html;
            } catch (err) {
                console.error('Failed to refresh grid:', err);
            }
        }

        function openAddRowModal() {
            document.getElementById('addRowModal').showModal();
        }

        function openEditTimeModal(startTime, endTime) {
            document.getElementById('oldStartTime').value = startTime;
            document.getElementById('newStartTime').value = startTime.substring(0, 5);
            document.getElementById('newEndTime').value = endTime.substring(0, 5);
            document.getElementById('editTimeModal').showModal();
        }

        async function submitEditTime(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'edit_row_time');

            showToast('Updating time range...', 'info');
            document.getElementById('editTimeModal').close();

            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Time range updated!', 'success');
                    await refreshGrid();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function submitAddRow(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'add_row');

            showToast('Adding row...', 'info');
            document.getElementById('addRowModal').close();

            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Row added!', 'success');
                    await refreshGrid(); // AJAX-friendly refresh of the grid
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }

        async function resetToDefault() {
            document.getElementById('resetConfirmModal').showModal();
        }

        async function executeReset() {
            document.getElementById('resetConfirmModal').close();
            
            const formData = new FormData();
            formData.append('action', 'reset_to_default');

            showToast('Resetting timetable...', 'info');

            try {
                const res = await fetch('<?= url("?page=teacher_timetable_action") ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    showToast('Reset successful!', 'success');
                    await refreshGrid();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }
    </script>
    <!-- Live Campus Map Modal (Shared) -->
    <?php include __DIR__ . '/../partials/campus_map_modal.php'; ?>

    <!-- Information Modal (Shared) -->
    <?php include __DIR__ . '/../partials/info_modal.php'; ?>
</body>
</html>
