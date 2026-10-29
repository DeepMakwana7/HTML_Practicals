USE studenthub;

ALTER TABLE students
    ADD COLUMN role ENUM('student', 'admin') NOT NULL DEFAULT 'student';

CREATE TABLE IF NOT EXISTS remember_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id BIGINT UNSIGNED NOT NULL,
    selector CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_remember_tokens_selector (selector),
    KEY idx_remember_tokens_student_id (student_id),
    KEY idx_remember_tokens_expires_at (expires_at),
    CONSTRAINT fk_remember_tokens_student
        FOREIGN KEY (student_id) REFERENCES students (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
