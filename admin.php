<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    redirectWithMessage('login.html', 'error', 'Please log in to access the admin panel.');
}

if (!in_array(normalizeRole($_SESSION['user']['role'] ?? 'student'), ['admin', 'super_admin'], true)) {
    redirectWithMessage('dashboard.php', 'error', 'You do not have permission to access the admin panel.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $fullName = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $username === '' || $email === '' || $password === '') {
            redirectWithMessage('admin.php', 'error', 'Teacher details are incomplete.');
        }

        if (strlen($password) < 6) {
            redirectWithMessage('admin.php', 'error', 'Teacher password must be at least 6 characters long.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('admin.php', 'error', 'Please enter a valid email address for the teacher.');
        }

        $existing = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $existing->execute([':username' => $username, ':email' => $email]);
        if ($existing->fetch()) {
            redirectWithMessage('admin.php', 'error', 'A teacher with that username or email already exists.');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $nowExpression = dbNowExpression();
        $stmt = $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, ' . $nowExpression . ')');
        $stmt->execute([
            ':fullname' => $fullName,
            ':username' => $username,
            ':password' => $hashed,
            ':display_password' => $password,
            ':email' => $email,
            ':role' => 'teacher',
            ':grades' => json_encode([]),
        ]);

        redirectWithMessage('admin.php', 'success', 'Teacher account created successfully.');
    }

    if ($action === 'edit') {
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $fullName = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($teacherId <= 0 || $fullName === '' || $username === '' || $email === '') {
            redirectWithMessage('admin.php', 'error', 'Teacher information is incomplete.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('admin.php', 'error', 'Please enter a valid email address for the teacher.');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :teacher_id LIMIT 1');
        $check->execute([':username' => $username, ':email' => $email, ':teacher_id' => $teacherId]);
        if ($check->fetch()) {
            redirectWithMessage('admin.php', 'error', 'Another teacher already uses that username or email.');
        }

        $sql = 'UPDATE users SET fullname = :fullname, username = :username, email = :email';
        $params = [':fullname' => $fullName, ':username' => $username, ':email' => $email, ':teacher_id' => $teacherId];

        if ($password !== '') {
            if (strlen($password) < 6) {
                redirectWithMessage('admin.php', 'error', 'Teacher password must be at least 6 characters long.');
            }
            $sql .= ', password = :password, display_password = :display_password';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            $params[':display_password'] = $password;
        }

        $sql .= ' WHERE id = :teacher_id AND role = :role';
        $params[':role'] = 'teacher';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        redirectWithMessage('admin.php', 'success', 'Teacher details updated successfully.');
    }

    if ($action === 'delete') {
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        if ($teacherId <= 0) {
            redirectWithMessage('admin.php', 'error', 'Please choose a valid teacher to delete.');
        }

        if ((int) $_SESSION['user']['id'] === $teacherId) {
            redirectWithMessage('admin.php', 'error', 'You cannot delete your own admin account.');
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :teacher_id AND role = :role');
        $stmt->execute([':teacher_id' => $teacherId, ':role' => 'teacher']);

        redirectWithMessage('admin.php', 'success', 'Teacher deleted successfully.');
    }
}

$teachers = $pdo->query("SELECT id, fullname, username, email, created_at FROM users WHERE role = 'teacher' ORDER BY fullname ASC")->fetchAll();
$editingTeacher = null;
if (isset($_GET['edit_id'])) {
    $editingTeacherId = (int) $_GET['edit_id'];
    if ($editingTeacherId > 0) {
        $teacherStmt = $pdo->prepare('SELECT id, fullname, username, email FROM users WHERE id = :teacher_id AND role = :role LIMIT 1');
        $teacherStmt->execute([':teacher_id' => $editingTeacherId, ':role' => 'teacher']);
        $editingTeacher = $teacherStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Management</title>
    <link rel="stylesheet" href="style_dshbrd.css">
</head>
<body>
    <div class="dshbrd">
        <aside class="topbar">
            <h1>TEACHER MANAGEMENT</h1>
            <p class="welcome-message">Admin: <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></p>
            <a href="dashboard.php">Back to Dashboard</a>
            <a class="logout-link" href="logout.php">Logout</a>
        </aside>

        <main class="content-area">
            <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
                <div class="message-box">
                    <p><?php echo htmlspecialchars($_GET['message']); ?></p>
                </div>
            <?php endif; ?>

            <section>
                <h2><?php echo $editingTeacher ? 'Edit Teacher' : 'Add Teacher'; ?></h2>
                <form action="admin.php" method="post">
                    <?php if ($editingTeacher): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="teacher_id" value="<?php echo (int) $editingTeacher['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>

                    <div>
                        <label for="fullname">Full name</label><br>
                        <input id="fullname" type="text" name="fullname" value="<?php echo htmlspecialchars($editingTeacher['fullname'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label for="username">Username</label><br>
                        <input id="username" type="text" name="username" value="<?php echo htmlspecialchars($editingTeacher['username'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label for="email">Email</label><br>
                        <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($editingTeacher['email'] ?? ''); ?>" required>
                    </div>

                    <div>
                        <label for="password"><?php echo $editingTeacher ? 'New Password (optional)' : 'Password'; ?></label><br>
                        <input id="password" type="password" name="password" <?php echo $editingTeacher ? '' : 'required'; ?>>
                    </div>

                    <button type="submit"><?php echo $editingTeacher ? 'Update Teacher' : 'Add Teacher'; ?></button>
                    <?php if ($editingTeacher): ?>
                        <a href="admin.php">Cancel</a>
                    <?php endif; ?>
                </form>
            </section>

            <section>
                <h2>Teacher List</h2>
                <?php if (empty($teachers)): ?>
                    <p>No teachers found.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Full name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($teacher['fullname']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td>
                                        <a href="admin.php?edit_id=<?php echo (int) $teacher['id']; ?>">Edit</a>
                                        <form action="admin.php" method="post" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="teacher_id" value="<?php echo (int) $teacher['id']; ?>">
                                            <button type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
