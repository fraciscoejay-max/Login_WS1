<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    redirectWithMessage('login.html', 'error', 'Please log in to access the teacher panel.');
}

if (!in_array(normalizeRole($_SESSION['user']['role'] ?? 'student'), ['teacher', 'super_admin'], true)) {
    redirectWithMessage('dashboard.php', 'error', 'You do not have permission to access the teacher panel.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $fullName = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $username === '' || $email === '' || $password === '') {
            redirectWithMessage('teacher.php', 'error', 'Student details are incomplete.');
        }

        if (strlen($password) < 6) {
            redirectWithMessage('teacher.php', 'error', 'Student password must be at least 6 characters long.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('teacher.php', 'error', 'Please enter a valid email address for the student.');
        }

        $existing = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $existing->execute([':username' => $username, ':email' => $email]);
        if ($existing->fetch()) {
            redirectWithMessage('teacher.php', 'error', 'A student with that username or email already exists.');
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $grades = json_encode(generateRandomGrades());
        $nowExpression = dbNowExpression();
        $stmt = $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, ' . $nowExpression . ')');
        $stmt->execute([
            ':fullname' => $fullName,
            ':username' => $username,
            ':password' => $hashed,
            ':display_password' => $password,
            ':email' => $email,
            ':role' => 'student',
            ':grades' => $grades,
        ]);

        redirectWithMessage('teacher.php', 'success', 'Student account created successfully.');
    }

    if ($action === 'edit') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $fullName = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($studentId <= 0 || $fullName === '' || $username === '' || $email === '') {
            redirectWithMessage('teacher.php', 'error', 'Student information is incomplete.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirectWithMessage('teacher.php', 'error', 'Please enter a valid email address for the student.');
        }

        $check = $pdo->prepare('SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :student_id LIMIT 1');
        $check->execute([':username' => $username, ':email' => $email, ':student_id' => $studentId]);
        if ($check->fetch()) {
            redirectWithMessage('teacher.php', 'error', 'Another student already uses that username or email.');
        }

        $sql = 'UPDATE users SET fullname = :fullname, username = :username, email = :email';
        $params = [':fullname' => $fullName, ':username' => $username, ':email' => $email, ':student_id' => $studentId];

        if ($password !== '') {
            if (strlen($password) < 6) {
                redirectWithMessage('teacher.php', 'error', 'Student password must be at least 6 characters long.');
            }
            $sql .= ', password = :password, display_password = :display_password';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
            $params[':display_password'] = $password;
        }

        $sql .= ' WHERE id = :student_id AND role = :role';
        $params[':role'] = 'student';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        redirectWithMessage('teacher.php', 'success', 'Student details updated successfully.');
    }

    if ($action === 'delete') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            redirectWithMessage('teacher.php', 'error', 'Please choose a valid student to delete.');
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :student_id AND role = :role');
        $stmt->execute([':student_id' => $studentId, ':role' => 'student']);

        redirectWithMessage('teacher.php', 'success', 'Student deleted successfully.');
    }
}

$students = $pdo->query("SELECT id, fullname, username, email, created_at FROM users WHERE role = 'student' ORDER BY fullname ASC")->fetchAll();
$editingStudent = null;
if (isset($_GET['edit_id'])) {
    $editingStudentId = (int) $_GET['edit_id'];
    if ($editingStudentId > 0) {
        $studentStmt = $pdo->prepare('SELECT id, fullname, username, email FROM users WHERE id = :student_id AND role = :role LIMIT 1');
        $studentStmt->execute([':student_id' => $editingStudentId, ':role' => 'student']);
        $editingStudent = $studentStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="style_dshbrd.css">
</head>
<body>
    <div class="dshbrd">
        <aside class="topbar">
            <h1>STUDENT MANAGEMENT</h1>
            <p class="welcome-message">Teacher: <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></p>
            <a href="dashboard.php">Back to Dashboard</a>
            <a class="logout-link" href="logout.php">Logout</a>
        </aside>

        <main class="content-area">
            <div class="student-management-panel">
                <?php if (isset($_GET['status']) && isset($_GET['message'])): ?>
                    <div class="message-box">
                        <p><?php echo htmlspecialchars($_GET['message']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="student-management-layout">
                    <section class="student-form-panel">
                        <h2><?php echo $editingStudent ? 'Edit Student' : 'Add Student'; ?></h2>
                        <form action="teacher.php" method="post">
                            <?php if ($editingStudent): ?>
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="student_id" value="<?php echo (int) $editingStudent['id']; ?>">
                            <?php else: ?>
                                <input type="hidden" name="action" value="add">
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="fullname">Full name</label>
                                <input id="fullname" type="text" name="fullname" value="<?php echo htmlspecialchars($editingStudent['fullname'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="username">Username</label>
                                <input id="username" type="text" name="username" value="<?php echo htmlspecialchars($editingStudent['username'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" value="<?php echo htmlspecialchars($editingStudent['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="password"><?php echo $editingStudent ? 'New Password (optional)' : 'Password'; ?></label>
                                <input id="password" type="password" name="password" <?php echo $editingStudent ? '' : 'required'; ?>>
                            </div>

                            <div class="form-actions">
                                <button type="submit"><?php echo $editingStudent ? 'Update Student' : 'Add Student'; ?></button>
                                <?php if ($editingStudent): ?>
                                    <a href="teacher.php" class="secondary-link">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </section>

                    <section class="student-list-panel">
                        <h2>Student List</h2>
                        <?php if (empty($students)): ?>
                            <p class="empty-state">No students found.</p>
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
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars($student['username']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td class="actions-cell">
                                                <a href="teacher.php?edit_id=<?php echo (int) $student['id']; ?>" class="table-action-link">Edit</a>
                                                <form action="teacher.php" method="post" class="inline-delete-form">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?php echo (int) $student['id']; ?>">
                                                    <button type="submit" class="delete-button">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </section>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
