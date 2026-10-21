<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    redirectWithMessage('login.html', 'error', 'Please log in to access the dashboard.');
}

$user = $_SESSION['user'];
$role = normalizeRole($user['role'] ?? 'student');
$grades = decodeGrades($user['grades'] ?? '[]');

if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_action'])) {
    $action = $_POST['student_action'];

    if ($action === 'add') {
        $fullName = trim($_POST['fullname'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $username === '' || $email === '' || $password === '') {
            $teacherNotice = 'Student details are incomplete.';
        } elseif (strlen($password) < 6) {
            $teacherNotice = 'Student password must be at least 6 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $teacherNotice = 'Please enter a valid email address for the student.';
        } else {
            $existing = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
            $existing->execute([':username' => $username, ':email' => $email]);
            if ($existing->fetch()) {
                $teacherNotice = 'A student with that username or email already exists.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO users (fullname, username, password, display_password, email, role, grades, created_at) VALUES (:fullname, :username, :password, :display_password, :email, :role, :grades, ' . dbNowExpression() . ')');
                $stmt->execute([
                    ':fullname' => $fullName,
                    ':username' => $username,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':display_password' => $password,
                    ':email' => $email,
                    ':role' => 'student',
                    ':grades' => json_encode(generateRandomGrades()),
                ]);
                $teacherNotice = 'Student account created successfully.';
            }
        }
    }

    if ($action === 'delete') {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId > 0) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :student_id AND role = :role');
            $stmt->execute([':student_id' => $studentId, ':role' => 'student']);
            $teacherNotice = 'Student deleted successfully.';
        }
    }
}

if ($role === 'teacher') {
    $students = $pdo->query("SELECT id, fullname, username, email FROM users WHERE role = 'student' ORDER BY fullname ASC")->fetchAll();
    $editingStudent = null;
    if (isset($_GET['edit_id'])) {
        $editingStudentId = (int) $_GET['edit_id'];
        if ($editingStudentId > 0) {
            $studentStmt = $pdo->prepare('SELECT id, fullname, username, email FROM users WHERE id = :student_id AND role = :role LIMIT 1');
            $studentStmt->execute([':student_id' => $editingStudentId, ':role' => 'student']);
            $editingStudent = $studentStmt->fetch();
        }
    }
}
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
            <p class="welcome-message">You are logged in as <strong><?php echo htmlspecialchars($user['username']); ?></strong> (<strong><?php echo htmlspecialchars(ucfirst($role)); ?></strong>).</p>
            <nav aria-label="Dashboard sections">
                <ul role="tablist">
                    <li><button type="button" role="tab" data-view-target="home" aria-controls="home" aria-selected="true">Home</button></li>
                    <li><button type="button" role="tab" data-view-target="profile" aria-controls="profile" aria-selected="false">Profile</button></li>
                    <?php if ($role === 'admin'): ?>
                        <li><button type="button" role="tab" data-view-target="admin-panel" aria-controls="admin-panel" aria-selected="false">Teacher Management</button></li>
                    <?php elseif ($role === 'teacher'): ?>
                        <li><button type="button" role="tab" data-view-target="teacher-panel" aria-controls="teacher-panel" aria-selected="false">Student Management</button></li>
                    <?php else: ?>
                        <li><button type="button" role="tab" data-view-target="grades-panel" aria-controls="grades-panel" aria-selected="false">My Grades</button></li>
                    <?php endif; ?>
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
                <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($role)); ?></p>
            </section>

            <section class="home view-panel" id="home" role="tabpanel">
                <h1>Dashboard</h1>
                <p>Welcome, <?php echo htmlspecialchars($user['username']); ?>.</p>
                <?php if ($role === 'admin'): ?>
                    <p><a href="admin.php">Go to teacher management</a></p>
                <?php elseif ($role === 'teacher'): ?>
                    <p><a href="teacher.php">Go to student management</a></p>
                <?php else: ?>
                    <p>Your grades are available under the My Grades tab.</p>
                <?php endif; ?>
            </section>

            <?php if ($role === 'admin'): ?>
                <section class="admin-panel view-panel" id="admin-panel" role="tabpanel" hidden>
                    <h1>Teacher Management</h1>
                    <p>Use the teacher management page to add, edit, or remove teachers.</p>
                    <p><a href="admin.php">Open teacher management</a></p>
                </section>
            <?php elseif ($role === 'teacher'): ?>
                <section class="teacher-panel view-panel" id="teacher-panel" role="tabpanel" hidden>
                    <div class="student-management-panel">
                        <h1>Student Management</h1>
                        <p>Use the student management page to add, edit, or remove students.</p>

                        <?php if (!empty($teacherNotice)): ?>
                            <div class="message-box">
                                <p><?php echo htmlspecialchars($teacherNotice); ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="student-management-layout">
                            <section class="student-form-panel">
                                <h2><?php echo $editingStudent ? 'Edit Student' : 'Add Student'; ?></h2>
                                <form action="dashboard.php" method="post">
                                    <?php if ($editingStudent): ?>
                                        <input type="hidden" name="student_action" value="edit">
                                        <input type="hidden" name="student_id" value="<?php echo (int) $editingStudent['id']; ?>">
                                    <?php else: ?>
                                        <input type="hidden" name="student_action" value="add">
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
                                            <a href="dashboard.php" class="secondary-link">Cancel</a>
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
                                                        <a href="dashboard.php?edit_id=<?php echo (int) $student['id']; ?>" class="table-action-link">Edit</a>
                                                        <form action="dashboard.php" method="post" class="inline-delete-form">
                                                            <input type="hidden" name="student_action" value="delete">
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
                </section>
            <?php else: ?>
                <section class="grades-panel view-panel" id="grades-panel" role="tabpanel" hidden>
                    <h1>My Grades</h1>
                    <?php if ($grades === []): ?>
                        <p>No grades have been assigned yet.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['subject'] ?? 'Subject'); ?></td>
                                        <td><?php echo htmlspecialchars((string) ($entry['grade'] ?? 0)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

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
