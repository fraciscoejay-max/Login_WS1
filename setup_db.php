<?php
require __DIR__ . '/config.php';

$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'sqlite') {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fullname TEXT NOT NULL,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            display_password TEXT NULL,
            email TEXT NOT NULL UNIQUE,
            role TEXT NOT NULL DEFAULT "student",
            grades TEXT DEFAULT "[]",
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );
} else {
    function ensureColumnExists(PDO $pdo, string $columnName, string $definition): void
    {
        $result = $pdo->query("SHOW COLUMNS FROM users LIKE '{$columnName}'");
        if ($result->fetch()) {
            return;
        }

        $pdo->exec('ALTER TABLE users ' . $definition);
    }

    ensureColumnExists($pdo, 'display_password', "ADD COLUMN display_password VARCHAR(255) NULL AFTER password");
    ensureColumnExists($pdo, 'role', "ADD COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'student' AFTER email");
    $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch();
    if ($roleColumn && strpos($roleColumn['Type'], 'super_admin') === false) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'teacher', 'student') NOT NULL DEFAULT 'student' AFTER email");
    }
    ensureColumnExists($pdo, 'grades', "ADD COLUMN grades JSON NULL DEFAULT '[]' AFTER role");
}

$pdo->exec("UPDATE users SET role = 'student' WHERE role IS NULL OR role = ''");

$superAdminUser = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
$superAdminUser->execute([':username' => 'superadmin']);
if (!$superAdminUser->fetch()) {
    $insertSql = $driver === 'sqlite'
        ? 'INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, datetime("now"))'
        : 'INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, NOW())';

    $pdo->prepare($insertSql)->execute([
        ':fullname' => 'Super Administrator',
        ':username' => 'superadmin',
        ':password' => password_hash('superadmin123', PASSWORD_DEFAULT),
        ':display_password' => 'superadmin123',
        ':email' => 'superadmin@school.local',
        ':role' => 'super_admin',
        ':grades' => json_encode([]),
    ]);
}

$adminUser = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
$adminUser->execute([':username' => 'admin']);
if (!$adminUser->fetch()) {
    $insertSql = $driver === 'sqlite'
        ? 'INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, datetime("now"))'
        : 'INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, NOW())';

    $pdo->prepare($insertSql)->execute([
        ':fullname' => 'System Administrator',
        ':username' => 'admin',
        ':password' => password_hash('admin123', PASSWORD_DEFAULT),
        ':display_password' => 'admin123',
        ':email' => 'admin@school.local',
        ':role' => 'admin',
        ':grades' => json_encode([]),
    ]);
}

echo "Database setup completed successfully.\n";
echo "Default super admin login: username=superadmin, password=superadmin123\n";
echo "Default admin login: username=admin, password=admin123\n";
