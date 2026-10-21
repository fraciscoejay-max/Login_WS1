<?php
session_start();

$dbHost = 'sql207.infinityfree.com';
$dbName = 'if0_42804269_db_Earl';
$dbUser = 'if0_42804269';
$dbPass = 'EarlJan123';

$pdo = null;

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        $stmt = $pdo->query("PRAGMA table_info('{$table}')");
        foreach ($stmt as $row) {
            if (($row['name'] ?? '') === $column) {
                return true;
            }
        }
        return false;
    }

    $stmt = $pdo->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '{$table}' AND column_name = '{$column}' LIMIT 1");
    return (bool) $stmt->fetchColumn();
}

function ensureUserColumns(PDO $pdo): void
{
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
        return;
    }

    if (!columnExists($pdo, 'users', 'display_password')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN display_password VARCHAR(255) NULL AFTER password");
    }
    if (!columnExists($pdo, 'users', 'role')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student' AFTER email");
    }
    if (!columnExists($pdo, 'users', 'grades')) {
        $pdo->exec("ALTER TABLE users ADD COLUMN grades JSON NULL DEFAULT ('[]') AFTER role");
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . $dbHost . ';dbname=' . $dbName . ';charset=utf8mb4',
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    ensureUserColumns($pdo);

    $adminUser = $pdo->prepare('SELECT id, display_password, role FROM users WHERE username = :username LIMIT 1');
    $adminUser->execute([':username' => 'admin']);
    $adminRow = $adminUser->fetch();

    if (!$adminRow) {
        $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, NOW())')->execute([
            ':fullname' => 'System Administrator',
            ':username' => 'admin',
            ':password' => password_hash('admin123', PASSWORD_DEFAULT),
            ':display_password' => 'admin123',
            ':email' => 'admin@school.local',
            ':role' => 'admin',
            ':grades' => json_encode([]),
        ]);
    } elseif (($adminRow['display_password'] ?? '') !== 'admin123' || normalizeRole($adminRow['role'] ?? 'student') !== 'admin') {
        $pdo->prepare('UPDATE users SET display_password = :display_password, role = :role, password = :password WHERE username = :username')->execute([
            ':display_password' => 'admin123',
            ':role' => 'admin',
            ':password' => password_hash('admin123', PASSWORD_DEFAULT),
            ':username' => 'admin',
        ]);
    }
} catch (PDOException $mysqlError) {
    try {
        $sqlitePath = __DIR__ . '/app.sqlite';
        $pdo = new PDO(
            'sqlite:' . $sqlitePath,
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        ensureUserColumns($pdo);

        $adminUser = $pdo->prepare('SELECT id, display_password, role FROM users WHERE username = :username LIMIT 1');
        $adminUser->execute([':username' => 'admin']);
        $adminRow = $adminUser->fetch();

        if (!$adminRow) {
            $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, datetime("now"))')->execute([
                ':fullname' => 'System Administrator',
                ':username' => 'admin',
                ':password' => password_hash('admin123', PASSWORD_DEFAULT),
                ':display_password' => 'admin123',
                ':email' => 'admin@school.local',
                ':role' => 'admin',
                ':grades' => json_encode([]),
            ]);
        } elseif (($adminRow['display_password'] ?? '') !== 'admin123' || normalizeRole($adminRow['role'] ?? 'student') !== 'admin') {
            $pdo->prepare('UPDATE users SET display_password = :display_password, role = :role, password = :password WHERE username = :username')->execute([
                ':display_password' => 'admin123',
                ':role' => 'admin',
                ':password' => password_hash('admin123', PASSWORD_DEFAULT),
                ':username' => 'admin',
            ]);
        }
    } catch (PDOException $sqliteError) {
        error_log('MySQL connection failed: ' . $mysqlError->getMessage());
        error_log('SQLite fallback failed: ' . $sqliteError->getMessage());
        die('Database connection failed. Please check your database configuration.');
    }
}

function redirectWithMessage($page, $status, $message)
{
    header('Location: ' . $page . '?status=' . $status . '&message=' . urlencode($message));
    exit;
}

function getVisibleAccounts()
{
    global $pdo;

    if (!columnExists($pdo, 'users', 'display_password')) {
        return [];
    }

    $stmt = $pdo->prepare('SELECT username, display_password, role FROM users WHERE display_password IS NOT NULL AND display_password != "" ORDER BY created_at DESC');
    $stmt->execute();

    return $stmt->fetchAll();
}

function getAllowedRoles()
{
    return [
        'admin' => 'Admin',
        'teacher' => 'Teacher',
        'student' => 'Student',
    ];
}

function dbNowExpression(): string
{
    global $pdo;

    return $pdo && $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'datetime("now")' : 'NOW()';
}

function normalizeRole($role)
{
    $role = strtolower((string) $role);
    return in_array($role, ['admin', 'teacher', 'student'], true) ? $role : 'student';
}

function decodeGrades($gradesValue)
{
    if (empty($gradesValue)) {
        return [];
    }

    $decoded = json_decode((string) $gradesValue, true);
    return is_array($decoded) ? $decoded : [];
}

function generateRandomGrades()
{
    $subjects = ['Mathematics', 'Science', 'English', 'History', 'Computer Science'];
    $grades = [];

    foreach ($subjects as $subject) {
        $grades[] = [
            'subject' => $subject,
            'grade' => random_int(75, 100),
        ];
    }

    return $grades;
}
