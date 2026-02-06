-- Add current teaching session columns to teacher_profiles
-- Run this migration to add support for tracking current room and subject

ALTER TABLE teacher_profiles 
ADD COLUMN current_room VARCHAR(50) NULL AFTER office_text,
ADD COLUMN current_subject VARCHAR(255) NULL AFTER current_room,
ADD COLUMN session_updated_at DATETIME NULL AFTER current_subject;

-- Add comment for clarity
ALTER TABLE teacher_profiles 
MODIFY COLUMN office_text VARCHAR(255) NULL COMMENT 'Permanent office location',
MODIFY COLUMN current_room VARCHAR(50) NULL COMMENT 'Current teaching room (dynamic)',
MODIFY COLUMN current_subject VARCHAR(255) NULL COMMENT 'Current subject being taught',
MODIFY COLUMN session_updated_at DATETIME NULL COMMENT 'When current session info was last updated';
