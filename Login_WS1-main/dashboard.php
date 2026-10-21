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
        <aside class="topbar">
            <h1>DASHBOARD</h1>
            <p class="welcome-message">You are logged in as <strong><?php echo htmlspecialchars($user['username']); ?></strong>.</p>
            <nav aria-label="Dashboard sections">
                <ul role="tablist">
                    <li><button type="button" role="tab" data-view-target="home" aria-controls="home" aria-selected="true">Home</button></li>
                    <li><button type="button" role="tab" data-view-target="profile" aria-controls="profile" aria-selected="false">Profile</button></li>
                    <li><button type="button" role="tab" data-view-target="about" aria-controls="about" aria-selected="false">About</button></li>
                </ul>
            </nav>
            <a class="logout-link" href="logout.php">Logout</a>
        </aside>

        <main class="content-area">
            <section class="profile view-panel" id="profile" role="tabpanel" hidden>
                <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
                <p><strong>Fullname:</strong> <?php echo htmlspecialchars($user['fullname']); ?></p>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            </section>

            <section class="home view-panel" id="home" role="tabpanel">
                <h1>Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($user['username']); ?>.</p>
            </section>

            <section class="about view-panel" id="about" role="tabpanel" hidden>
                <h1>About</h1>
                <p>This is for school purposes only.</p>
            </section>
        </main>
    </div>

    <footer>
        <p>&copy; Francisco, E. All Rights Reserved</p>
    </footer>
    <script src="script.js"></script>
</body>
</html>
