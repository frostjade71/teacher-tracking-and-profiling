<?php
// app/partials/teacher_timetable_grid.php
?>
<div class="timetable-grid bg-slate-200 dark:bg-slate-700 gap-[1px]">
    <!-- Header Row -->
    <div class="grid-header">Time</div>
    <?php foreach ($days as $day): ?>
        <div class="grid-header"><?= $day ?></div>
    <?php endforeach; ?>
    <div class="grid-header"></div>

    <!-- Time Slots -->
<?php 
        $timeLabels = json_decode($profile['time_labels_json'] ?? '[]', true) ?? [];
        $isReadonly = isset($readonly) && $readonly;
    ?>
    <?php foreach ($timeSlots as $index => $slot): 
        $startTime = $slot['start_time'];
        $endTime = $slot['end_time'];
    ?>
        <!-- Time Column -->
        <div class="grid-time bg-white dark:bg-slate-800 text-slate-500 dark:text-slate-400 p-2 relative group/time" data-time-row="<?= $startTime ?>">
            <div class="flex flex-col items-center justify-center w-full h-full text-xs font-bold">
                <span><?= date('h:i A', strtotime($startTime)) ?></span>
                <span class="text-[10px] opacity-50">-</span>
                <span><?= date('h:i A', strtotime($endTime)) ?></span>
                
                <?php if (!$isReadonly): ?>
                <button onclick="openEditTimeModal('<?= $startTime ?>', '<?= $endTime ?>')" 
                        class="absolute inset-0 bg-blue-600/10 opacity-0 group-hover/time:opacity-100 flex items-center justify-center transition-opacity rounded-lg">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Day Columns for this Time -->
        <?php foreach ($days as $day): ?>
            <?php 
                $entry = $schedule[$day][$startTime] ?? null;
                // Dense implementation: Entry always exists, but check if subject/room is empty
                $hasEntry = !empty($entry['subject_text']);
            ?>
            <div id="cell-<?= $day ?>-<?= str_replace(':', '-', $startTime) ?>" 
                    data-time-row="<?= $startTime ?>"
                    class="grid-cell group relative bg-white dark:bg-slate-800 <?= $isReadonly ? '' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors cursor-pointer' ?>"
                    <?= $isReadonly ? '' : 'onclick="openSlotModal(\'' . $day . '\', \'' . $startTime . '\', ' . htmlspecialchars(json_encode($entry)) . ')"' ?>>
                
                <?php if ($hasEntry): ?>
                    <div class="flex flex-col items-center justify-center text-center w-full h-full p-1">
                        <span class="font-bold text-slate-900 dark:text-white text-sm mb-1 line-clamp-2">
                            <?= htmlspecialchars($entry['subject_text'] ?? '') ?>
                        </span>
                        <?php if (!empty($entry['room_text'] ?? '') || !empty($entry['course_text'] ?? '')): ?>
                        <span class="text-xs text-slate-600 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 px-2.5 py-1 rounded-full font-medium">
                            <?= htmlspecialchars($entry['room_text'] ?? '') ?><?= (!empty($entry['room_text'] ?? '') && !empty($entry['course_text'] ?? '')) ? ' • ' : '' ?><?= htmlspecialchars($entry['course_text'] ?? '') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!$isReadonly): ?>
                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!$isReadonly): ?>
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity transform group-hover:scale-110">
                        <button class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                    <span class="text-xs text-slate-300 dark:text-slate-600 absolute bottom-2 select-none group-hover:hidden">Empty</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Delete Row Column -->
        <?php if (!$isReadonly): ?>
        <div class="bg-white dark:bg-slate-800 flex items-center justify-center border-l border-gray-100 dark:border-slate-700" data-time-row="<?= $startTime ?>">
            <button onclick="deleteRow('<?= $startTime ?>')" 
                    class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors"
                    title="Delete this entire hour row">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </button>
        </div>
        <?php else: ?>
        <div class="bg-white dark:bg-slate-800 border-l border-gray-100 dark:border-slate-700"></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
