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

$insert = $pdo->prepare('INSERT INTO users (fullname, username, password, email, created_at) VALUES (:fullname, :username, :password, :email, NOW())');
$insert->execute([
    ':fullname' => $fullName,
    ':username' => $username,
    ':password' => $hashedPassword,
    ':email' => $email,
]);

$_SESSION['user'] = [
    'id' => $pdo->lastInsertId(),
    'fullname' => $fullName,
    'username' => $username,
    'email' => $email,
];

header('Location: dashboard.php');
exit;
