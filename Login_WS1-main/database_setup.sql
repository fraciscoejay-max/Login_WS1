ALTER TABLE users
  ADD COLUMN IF NOT EXISTS display_password VARCHAR(255) NULL AFTER password,
  ADD COLUMN IF NOT EXISTS role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student' AFTER email,
  ADD COLUMN IF NOT EXISTS grades JSON NULL DEFAULT '[]' AFTER role;

UPDATE users
SET role = 'student'
WHERE role IS NULL OR role = '';

INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at)
SELECT 'System Administrator', 'admin', '$2y$10$5N9fQ2fR3I2hHszlS6aQnO4YlLJv7yStlW3KfUNbV8iU5RLwQwD4O', 'admin123', 'admin@school.local', 'admin', '[]', NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'admin');

-- Example:
-- Teacher accounts created through the admin panel will use role = 'teacher'
-- Student accounts created through the teacher panel will use role = 'student'
