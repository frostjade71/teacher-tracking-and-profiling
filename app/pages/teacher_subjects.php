<?php
// app/pages/teacher_subjects.php

require_login();
require_role('teacher');

$u = current_user();
$pdo = db();

// Fetch teacher profile for current subjects
$stmt = $pdo->prepare("SELECT subjects_json FROM teacher_profiles WHERE teacher_user_id = ?");
$stmt->execute([$u['id']]);
$profile = $stmt->fetch();

// Fetch all subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY name ASC");
$allSubjects = $stmt->fetchAll();

// Filter subjects into Current and Available based on the profiles
$subjectNames = [];
if ($profile && !empty($profile['subjects_json'])) {
    $subjectNames = json_decode($profile['subjects_json'], true) ?? [];
}

$currentSubjects = [];
$availableSubjects = [];

foreach ($allSubjects as $subj) {
    if (in_array($subj['name'], $subjectNames)) {
        // Hydrate current subjects with full object data (including code)
        $currentSubjects[] = $subj;
    } else {
        $availableSubjects[] = $subj;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects | FacultyLink</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/favicon/favicon-96x96.png" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="/assets/favicon/favicon.svg" />
    <link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
    <link rel="manifest" href="/assets/favicon/site.webmanifest" />
    <link rel="stylesheet" href="/assets/app.css">
    <script src="/assets/theme.js"></script>
    <link rel="stylesheet" href="/assets/toast.css">
    <script src="/assets/toast.js"></script>
    <style>
        .draggable-item {
            cursor: grab;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            touch-action: none;
        }
        .draggable-item:active {
            cursor: grabbing;
        }
        .drop-zone {
            min-height: 200px;
        }
        .dragging {
            opacity: 0.5;
            background-color: #e2e8f0; /* slate-200 */
        }
        html.dark .dragging {
            background-color: #334155; /* slate-700 */
        }
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
    <script src="/assets/loader.js"></script>

    <!-- Interact.js -->
    <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 dark:bg-slate-950 text-white flex-shrink-0 hidden md:flex flex-col border-r border-slate-800">
            <div class="h-16 flex items-center px-4 border-b border-slate-800 gap-2">
                <img src="/assets/favicon/web-app-manifest-512x512.png" class="w-7 h-7 rounded-lg" alt="Logo" style="width: 28px; height: 28px;">
                <span class="text-base font-bold tracking-tight" style="white-space: nowrap;">FacultyLink <span class="text-blue-500">Staff</span></span>
            </div>
            
            <nav class="flex-1 px-3 py-6 space-y-1">
                <div class="px-3 mb-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Main
                </div>
                <a href="/?page=teacher_dashboard" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    Dashboard
                </a>
                
                <button onclick="window.location.href='/?page=teacher_dashboard&openMap=1'" class="flex items-center px-3 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg group transition-colors w-full">
                    <svg class="w-5 h-5 mr-3 text-slate-400 group-hover:text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 01-.553-.894L15 4m0 13V4m0 0L9 7"></path></svg>
                    Live Campus Map
                </button>

                <div class="px-3 text-xs font-semibold text-slate-500 uppercase tracking-wider" style="margin-top: 40px;">
                    Management
                </div>

                <a href="/?page=teacher_subjects" class="flex items-center px-3 py-2.5 text-sm font-medium bg-blue-600 rounded-lg text-white group">
                    <svg class="w-5 h-5 mr-3 text-blue-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    Subjects
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800">
                <a href="/?page=profile" class="px-3 mb-4 flex items-center gap-3 hover:bg-slate-800 rounded-lg py-2 transition-colors group">
                     <div class="h-8 w-8 rounded-full bg-slate-700 flex items-center justify-center font-bold text-xs text-slate-300 group-hover:bg-slate-600 group-hover:text-white transition-colors">
                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                    </div>
                    <div class="overflow-hidden">
                         <div class="text-sm font-medium text-white truncate group-hover:text-blue-400 transition-colors"><?= htmlspecialchars($u['name']) ?></div>
                         <div class="text-xs text-slate-400 truncate">Teacher</div>
                    </div>
                </a>

                <a href="/?page=logout_post" class="flex items-center px-3 py-2 text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Sign Out
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto" style="touch-action: none;">
             <!-- Header for Mobile -->
             <header class="bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 flex items-center justify-between px-6 md:hidden sticky top-0 z-20">
                <span class="font-bold text-slate-800 dark:text-white">FacultyLink</span>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Mobile -->
                    <button onclick="window.toggleTheme()" class="p-2 text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition-colors">
                         <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                         <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                    <a href="/?page=logout_post" class="text-sm font-medium text-gray-500 dark:text-slate-400">Sign Out</a>
                </div>
            </header>

            <!-- Desktop Header -->
            <div class="hidden md:flex bg-white dark:bg-slate-800 border-b border-gray-200 dark:border-slate-700 h-16 items-center justify-between px-8 sticky top-0 z-10 transition-colors duration-200">
                 <div class="text-sm text-slate-700 dark:text-slate-300 font-semibold">
                    <span class="text-slate-500">Management</span> 
                    <span class="mx-2 text-slate-400">/</span>
                    <span class="text-slate-900 dark:text-white">Subjects</span>
                </div>
                <div class="flex items-center gap-4">
                     <!-- Theme Toggle Desktop -->
                    <button onclick="window.toggleTheme()" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-700 text-gray-500 dark:text-slate-400 transition-colors">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </button>
                </div>
            </div>

            <div class="max-w-7xl mx-auto p-6 md:p-12">
                <div class="flex items-center justify-between mb-8">
                     <div>
                        <h1 class="text-3xl font-bold text-slate-900 dark:text-white">Manage Subjects</h1>
                        <p class="text-slate-500 dark:text-slate-400 mt-2">Drag and drop subjects to manage your teaching load.</p>
                    </div>
                    <button onclick="saveSubjects()" class="flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg transition-colors shadow-lg shadow-blue-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                        Save Changes
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Current Subjects Column -->
                    <div class="flex flex-col bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden h-full">
                        <div class="p-4 border-b border-green-600 dark:border-green-500 bg-green-500 dark:bg-green-600">
                            <h2 class="font-bold text-white flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Current Subjects
                            </h2>
                        </div>
                        <div id="currentSubjectsList" class="drop-zone p-4 flex-1 space-y-3 bg-slate-50 dark:bg-slate-800/50 transition-colors">
                            <?php foreach ($currentSubjects as $subj): ?>
                                <div id="<?= md5($subj['name']) ?>" data-subject-name="<?= htmlspecialchars($subj['name']) ?>" class="draggable-item bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 p-3 rounded-lg shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group touch-none select-none">
                                    <span class="font-medium text-slate-700 dark:text-slate-200 subject-name flex items-center gap-4">
                                        <?php if (!empty($subj['code'])): ?>
                                            <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($subj['code']) ?></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($subj['name']) ?>
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"></path></svg>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- All Subjects Column -->
                    <div class="flex flex-col bg-white dark:bg-slate-800 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden h-full">
                        <div class="p-4 border-b border-yellow-500 dark:border-yellow-600 bg-yellow-400 dark:bg-yellow-600">
                             <h2 class="font-bold text-yellow-900 dark:text-yellow-50 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                Available Subjects
                             </h2>
                        </div>
                        <div class="p-4 border-b border-gray-100 dark:border-slate-700 bg-white dark:bg-slate-800">
                            <input type="text" placeholder="Search available subjects..." onkeyup="filterSubjects(this)" class="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-700 p-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div id="allSubjectsList" class="drop-zone p-4 flex-1 space-y-3 bg-slate-50 dark:bg-slate-800/50 transition-colors overflow-y-auto max-h-[600px]">
                            <?php foreach ($availableSubjects as $subj): ?>
                                <div id="<?= md5($subj['name']) ?>" data-subject-name="<?= htmlspecialchars($subj['name']) ?>" class="draggable-item bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 p-3 rounded-lg shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group touch-none select-none">
                                    <span class="font-medium text-slate-700 dark:text-slate-200 subject-name flex items-center gap-4">
                                        <?php if (!empty($subj['code'])): ?>
                                            <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300"><?= htmlspecialchars($subj['code']) ?></span>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($subj['name']) ?>
                                    </span>
                                    <svg class="w-4 h-4 text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8h16M4 16h16"></path></svg>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>

    <script>
        // Initialize Interact.js
        // Reduce the drag start threshold (default is often higher)
        // Set to 0 for immediate drag start
        interact.pointerMoveTolerance(1);

        interact('.draggable-item').draggable({
            inertia: false, // Disable inertia for more immediate 1:1 feel
            modifiers: [
                interact.modifiers.restrictRect({
                    restriction: 'body', // Restrict to body now since we move it there
                    endOnly: true
                })
            ],
            autoScroll: true,
            listeners: {
                start: function(event) {
                    var target = event.target;
                    var rect = target.getBoundingClientRect();

                    // Store original context to restore if cancelled
                    target._originalParent = target.parentNode;
                    target._originalSibling = target.nextElementSibling;
                    target._dropped = false;

                    // Set fixed position to lift out of overflow containers
                    // We must set explicit dimensions because 'fixed' takes it out of flow
                    target.style.width = rect.width + 'px';
                    target.style.height = rect.height + 'px';
                    target.style.left = rect.left + 'px';
                    target.style.top = rect.top + 'px';
                    target.style.position = 'fixed';
                    target.style.zIndex = '9999';

                    // Move to body to avoid clipping by overflow:hidden containers
                    document.body.appendChild(target);

                    // Reset transformation data for the new valid context
                    // interact.js tracks relative movement, so we reset to 0
                    target.setAttribute('data-x', 0);
                    target.setAttribute('data-y', 0);
                    // Disable transitions during drag to prevent lag
                    target.style.transition = 'none';
                    target.style.transform = 'none';
                },
                move: dragMoveListener,
                end: dragEndListener
            }
        });

        // Setup Drop Zones
        interact('.drop-zone').dropzone({
            accept: '.draggable-item',
            overlap: 0.50, // Require 50% overlap for drop

            ondropactivate: function (event) {
                event.target.classList.add('border-blue-300');
                event.target.classList.add('dark:border-blue-500');
            },
            ondragenter: function (event) {
                var draggableElement = event.relatedTarget;
                var dropzoneElement = event.target;

                dropzoneElement.classList.add('bg-blue-100');
                dropzoneElement.classList.add('dark:bg-blue-900/40');
                draggableElement.classList.add('can-drop');
            },
            ondragleave: function (event) {
                event.target.classList.remove('bg-blue-100');
                event.target.classList.remove('dark:bg-blue-900/40');
                event.relatedTarget.classList.remove('can-drop');
            },
            ondrop: function (event) {
                var draggableElement = event.relatedTarget;
                var dropzoneElement = event.target;
                
                // Mark as successfully dropped
                draggableElement._dropped = true;

                // Move the element to the new zone
                dropzoneElement.appendChild(draggableElement);
                
                // Cleanup fixed positioning styles so it flows in the list again
                draggableElement.style.position = '';
                draggableElement.style.width = '';
                draggableElement.style.height = '';
                draggableElement.style.left = '';
                draggableElement.style.top = '';
                draggableElement.style.zIndex = '';
                // Restore transitions if needed (though class restoration handles it)
                draggableElement.style.transition = '';
                
                // Reset styles
                dropzoneElement.classList.remove('bg-blue-100');
                dropzoneElement.classList.remove('dark:bg-blue-900/40');
                
                // Reset transform
                draggableElement.style.transform = 'none';
                draggableElement.setAttribute('data-x', 0);
                draggableElement.setAttribute('data-y', 0);
            },
            ondropdeactivate: function (event) {
                event.target.classList.remove('border-blue-300');
                event.target.classList.remove('dark:border-blue-500');
            }
        });

        function dragMoveListener(event) {
            var target = event.target;
            // keep the dragged position in the data-x/data-y attributes
            var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
            var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

            // translate the element
            target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

            // update the posiion attributes
            target.setAttribute('data-x', x);
            target.setAttribute('data-y', y);
        }
        
        function dragEndListener(event) {
            var target = event.target;
            
            // If not dropped, restore to original position
            if (!target._dropped && target._originalParent) {
                // Clear fixed styles
                target.style.position = '';
                target.style.width = '';
                target.style.height = '';
                target.style.left = '';
                target.style.top = '';
                target.style.zIndex = '';
                target.style.transform = 'none';
                target.setAttribute('data-x', 0);
                target.setAttribute('data-y', 0);

                // Insert back to original location
                target._originalParent.insertBefore(target, target._originalSibling);
                // Restore transition
                target.style.transition = '';
            }
            
            // Allow clicking again if needed (cleanup)
            delete target._originalParent;
            delete target._originalSibling;
            delete target._dropped;
        }

        // Search Filter
        function filterSubjects(input) {
            const filter = input.value.toLowerCase();
            const list = document.getElementById('allSubjectsList');
            const items = list.getElementsByClassName('draggable-item');

            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                const text = item.querySelector('.subject-name').textContent || item.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    item.style.display = "";
                } else {
                    item.style.display = "none";
                }
            }
        }

        async function saveSubjects() {
            showToast('Saving changes...', 'info');
            
            // Collect subjects from "Current Subjects" list
            const currentList = document.getElementById('currentSubjectsList');
            const items = currentList.getElementsByClassName('draggable-item');
            const subjects = [];
            
            for (let i = 0; i < items.length; i++) {
                // Use data attribute for robust name retrieval
                const name = items[i].getAttribute('data-subject-name');
                if (name) {
                    subjects.push(name.trim());
                }
            }

            try {
                const formData = new FormData();
                formData.append('subjects', JSON.stringify(subjects));

                const res = await fetch('/?page=teacher_subjects_update', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    showToast('Subjects updated successfully!', 'success');
                } else {
                    showToast(data.message || 'Failed to update subjects', 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            }
        }
    </script>
</body>
</html>
