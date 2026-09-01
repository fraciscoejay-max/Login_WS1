<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('login.html', 'error', 'Invalid request method.');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    redirectWithMessage('login.html', 'error', 'Please enter your username and password.');
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    redirectWithMessage('login.html', 'error', 'Invalid username or password.');
}

$_SESSION['user'] = [
    'id' => $user['id'],
    'fullname' => $user['fullname'],
    'username' => $user['username'],
    'email' => $user['email'],
];

header('Location: dashboard.php');
exit;
