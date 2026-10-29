USE studenthub;

ALTER TABLE students
    ADD COLUMN username VARCHAR(30) NULL AFTER full_name;

UPDATE students
SET username = CONCAT('student_', id)
WHERE username IS NULL OR username = '';

ALTER TABLE students
    MODIFY username VARCHAR(30) NOT NULL,
    ADD UNIQUE KEY uq_students_username (username);
