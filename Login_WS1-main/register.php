<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('register.html', 'error', 'Invalid request method.');
}

$fullName = trim($_POST['fullname'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';
$role = 'student';

if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirmPassword === '') {
    redirectWithMessage('register.html', 'error', 'Please complete all fields.');
}

if (strlen($password) < 6) {
    redirectWithMessage('register.html', 'error', 'Password must be at least 6 characters long.');
}

if ($password !== $confirmPassword) {
    redirectWithMessage('register.html', 'error', 'Passwords do not match.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithMessage('register.html', 'error', 'Please enter a valid email address.');
}

$stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
$stmt->execute([
    ':username' => $username,
    ':email' => $email,
]);

if ($stmt->fetch()) {
    redirectWithMessage('register.html', 'error', 'Username or email already exists.');
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$displayPassword = $password;
$grades = json_encode(generateRandomGrades());
$nowExpression = dbNowExpression();

$insert = $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, ' . $nowExpression . ')');
$insert->execute([
    ':fullname' => $fullName,
    ':username' => $username,
    ':password' => $hashedPassword,
    ':display_password' => $displayPassword,
    ':email' => $email,
    ':role' => $role,
    ':grades' => $grades,
]);

$_SESSION['user'] = [
    'id' => $pdo->lastInsertId(),
    'fullname' => $fullName,
    'username' => $username,
    'email' => $email,
    'role' => $role,
    'grades' => decodeGrades($grades),
];

header('Location: dashboard.php');
exit;
