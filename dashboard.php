<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    redirectWithMessage('login.html', 'error', 'Please log in to access the dashboard.');
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style_dshbrd.css">
</head>
<body>
    <div class="dshbrd">
        <div class="topbar">
            <h1>DASHBOARD</h1>
            <ul>
                <li>Profile</li>
                <li>Home</li>
                <li>About</li>
            </ul>
        </div>

        <div class="profile">
            <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
            <p><strong>Fullname:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <div class="about">
            <h1>About</h1>
            <p>This is for school purposes only.</p>
            <p><a href="logout.php">Logout</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; Francisco, E. All Rights Reserved</p>
    </footer>
</body>
</html>
