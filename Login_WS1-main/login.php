<?php
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        redirectWithMessage('login.php', 'error', 'Please enter your username and password.');
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        redirectWithMessage('login.php', 'error', 'Invalid username or password.');
    }

    $role = normalizeRole($user['role'] ?? 'student');

    $_SESSION['user'] = [
        'id' => $user['id'],
        'fullname' => $user['fullname'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $role,
        'grades' => decodeGrades($user['grades'] ?? '[]'),
    ];

    header('Location: dashboard.php');
    exit;
}

$accounts = getVisibleAccounts();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LoginPage</title>
    <link rel="stylesheet" href="style_login.css">
</head>

<body>
    <div class="loginpage">
        <div class="login-form-box">
            <h1>LOGIN</h1>
            <form id="loginForm" action="login.php" method="post">
                <div class="username">
                    <label>Username</label><br>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>

                <div class="password">
                    <label>Password</label><br>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <button class="button" type="submit">Login</button>

                <div class="footer">
                    <p>Don't have an account? <a href="register.html">Sign up</a></p>
                </div>
            </form>
        </div>

        <div class="demo-box">
            <h2>Registered Accounts</h2>
            <?php if (empty($accounts)): ?>
                <p>No accounts have been created yet.</p>
            <?php else: ?>
                <?php foreach ($accounts as $account): ?>
                    <div class="demo-row">
                        <span>Role</span>
                        <strong><?php echo htmlspecialchars(ucfirst($account['role'] ?? 'student')); ?></strong>
                        <span>Username</span>
                        <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                        <span>Password</span>
                        <strong><?php echo htmlspecialchars($account['display_password']); ?></strong>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="script.js"></script>
</body>

</html>
