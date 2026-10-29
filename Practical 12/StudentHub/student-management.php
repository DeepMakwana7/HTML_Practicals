<?php
require_once __DIR__ . '/includes/form_helpers.php';
require_once __DIR__ . '/includes/auth.php';
try {
    require_once __DIR__ . '/includes/db.php';
} catch (RuntimeException $exception) {
    http_response_code(503);
    error_log('StudentHub student management unavailable: ' . $exception->getMessage());
    echo '<!DOCTYPE html><html lang="en"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Student Management | StudentHub</title><body><main><h1>Student management is unavailable</h1><p>The database connection failed. Please try again later.</p></main></body></html>';
    exit;
}

auth_require_user($conn, ['admin']);

$courses = [
    'computer-engineering' => 'Computer Engineering',
    'information-technology' => 'Information Technology',
    'electronics' => 'Electronics',
    'other' => 'Other',
];
$genders = [
    'female' => 'Female',
    'male' => 'Male',
    'other' => 'Other',
    'prefer-not-to-say' => 'Prefer not to say',
];
$formValues = [
    'full_name' => '',
    'username' => '',
    'email' => '',
    'mobile' => '',
    'course' => '',
    'year_of_study' => '',
    'gender' => '',
];
$formErrors = [];
$formStatus = null;
$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = form_post_value('action') ?? '';
    $postedId = form_post_value('id');
    $editId = $action === 'edit' && $postedId !== null && ctype_digit($postedId)
        ? (int) $postedId
        : 0;

    foreach ($formValues as $field => $default) {
        $formValues[$field] = form_post_value($field) ?? '';
    }
    $formValues['email'] = strtolower($formValues['email']);

    if (!auth_valid_csrf_token(form_post_value('csrf_token'))) {
        $formErrors[] = 'Your form session expired. Refresh the page and try again.';
    } elseif (!in_array($action, ['add', 'edit', 'delete'], true)) {
        $formErrors[] = 'The requested action is not valid.';
    } elseif ($action === 'delete') {
        if ($postedId === null || !ctype_digit($postedId) || (int) $postedId < 1) {
            $formErrors[] = 'Select a valid student record to delete.';
        } else {
            try {
                $delete = $conn->prepare("DELETE FROM students WHERE id = ? AND role = 'student'");
                $studentId = (int) $postedId;
                $delete->bind_param('i', $studentId);
                $delete->execute();
                if ($delete->affected_rows === 1) {
                    $_SESSION['student_management_flash'] = ['success', 'Student record deleted successfully.'];
                } else {
                    $_SESSION['student_management_flash'] = ['error', 'No student record was deleted. It may no longer exist.'];
                }
                $delete->close();
                header('Location: student-management.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $exception) {
                error_log('StudentHub student deletion failed: ' . $exception->getMessage());
                $formErrors[] = 'The student record could not be deleted. Please try again.';
            }
        }
    } else {
        $name = $formValues['full_name'];
        $username = $formValues['username'];
        $email = $formValues['email'];
        $mobile = $formValues['mobile'];
        $course = $formValues['course'];
        $year = $formValues['year_of_study'];
        $gender = $formValues['gender'];

        if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,59}$/D", $name)) {
            $formErrors[] = 'Enter a name using 2–60 letters, spaces, apostrophes, periods, or hyphens.';
        }
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/D', $username)) {
            $formErrors[] = 'Choose a username using 3–30 letters, numbers, or underscores.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $formErrors[] = 'Enter a valid email address (maximum 254 characters).';
        }
        if (!preg_match('/^[6-9][0-9]{9}$/D', $mobile)) {
            $formErrors[] = 'Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
        }
        if (!isset($courses[$course])) {
            $formErrors[] = 'Select a valid course.';
        }
        if (!in_array($year, ['1', '2', '3', '4'], true)) {
            $formErrors[] = 'Select a valid year of study.';
        }
        if (!isset($genders[$gender])) {
            $formErrors[] = 'Select a valid gender option.';
        }

        $password = form_post_raw_value('password');
        if ($action === 'add' && ($password === null || strlen($password) > 128 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s])\S{8,}$/D', $password))) {
            $formErrors[] = 'Use 8–128 characters, including uppercase, lowercase, a number, and a symbol; do not use spaces.';
        }
        if ($action === 'edit' && $editId < 1) {
            $formErrors[] = 'Select a valid student record to edit.';
        }

        if (!$formErrors) {
            try {
                if ($action === 'add') {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $insert = $conn->prepare(
                        'INSERT INTO students (full_name, username, email, mobile, course, year_of_study, gender, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $yearOfStudy = (int) $year;
                    $insert->bind_param(
                        'sssssiss',
                        $name,
                        $username,
                        $email,
                        $mobile,
                        $course,
                        $yearOfStudy,
                        $gender,
                        $passwordHash
                    );
                    $insert->execute();
                    $insert->close();
                    $_SESSION['student_management_flash'] = ['success', 'Student record added successfully.'];
                } else {
                    $update = $conn->prepare(
                        "UPDATE students SET full_name = ?, username = ?, email = ?, mobile = ?, course = ?, year_of_study = ?, gender = ? WHERE id = ? AND role = 'student'"
                    );
                    $yearOfStudy = (int) $year;
                    $update->bind_param(
                        'sssssisi',
                        $name,
                        $username,
                        $email,
                        $mobile,
                        $course,
                        $yearOfStudy,
                        $gender,
                        $editId
                    );
                    $update->execute();
                    if ($update->affected_rows > 0) {
                        $_SESSION['student_management_flash'] = ['success', 'Student record updated successfully.'];
                    } else {
                        $exists = $conn->prepare("SELECT id FROM students WHERE id = ? AND role = 'student'");
                        $exists->bind_param('i', $editId);
                        $exists->execute();
                        $exists->store_result();
                        $_SESSION['student_management_flash'] = $exists->num_rows === 1
                            ? ['success', 'Student record saved. No details were changed.']
                            : ['error', 'No student record was updated. It may no longer exist.'];
                        $exists->close();
                    }
                    $update->close();
                }
                header('Location: student-management.php', true, 303);
                exit;
            } catch (mysqli_sql_exception $exception) {
                if ($exception->getCode() === 1062) {
                    $formErrors[] = 'That username or email address is already assigned to an account.';
                } else {
                    error_log('StudentHub student save failed: ' . $exception->getMessage());
                    $formErrors[] = 'The student record could not be saved. Please try again.';
                }
            }
        }
    }

    if ($formErrors) {
        $formStatus = 'error';
    }
}

if (isset($_SESSION['student_management_flash'])) {
    [$flashType, $flashMessage] = $_SESSION['student_management_flash'];
    $formStatus = $flashType;
    $formErrors = $flashType === 'error' ? [$flashMessage] : [];
    $_SESSION['student_management_flash'] = null;
}

$editingStudent = null;
if ($editId > 0 && !($_SERVER['REQUEST_METHOD'] === 'POST' && $formErrors)) {
    try {
        $selectStudent = $conn->prepare(
            "SELECT full_name, username, email, mobile, course, year_of_study, gender FROM students WHERE id = ? AND role = 'student'"
        );
        $selectStudent->bind_param('i', $editId);
        $selectStudent->execute();
        $selectStudent->bind_result(
            $editName,
            $editUsername,
            $editEmail,
            $editMobile,
            $editCourse,
            $editYear,
            $editGender
        );
        if ($selectStudent->fetch()) {
            $editingStudent = [
                'full_name' => $editName,
                'username' => $editUsername,
                'email' => $editEmail,
                'mobile' => $editMobile,
                'course' => $editCourse,
                'year_of_study' => (string) $editYear,
                'gender' => $editGender,
            ];
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $formValues = $editingStudent;
            }
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $formStatus = 'error';
            $formErrors[] = 'The selected student record could not be found.';
        }
        $selectStudent->close();
    } catch (mysqli_sql_exception $exception) {
        error_log('StudentHub student lookup failed: ' . $exception->getMessage());
        $formStatus = 'error';
        $formErrors[] = 'Student details could not be loaded. Please try again.';
    }
}

$search = form_post_value('search');
$search = $search ?? (isset($_GET['search']) && is_string($_GET['search']) ? trim($_GET['search']) : '');
$isEditing = $editId > 0 || (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && form_post_value('action') === 'edit'
);
$filterCourse = isset($_GET['course']) && is_string($_GET['course']) ? $_GET['course'] : '';
$filterYear = isset($_GET['year']) && is_string($_GET['year']) ? $_GET['year'] : '';
if (!isset($courses[$filterCourse])) {
    $filterCourse = '';
}
if (!in_array($filterYear, ['', '1', '2', '3', '4'], true)) {
    $filterYear = '';
}
$students = [];

try {
    $searchPattern = '%' . $search . '%';
    $courseFilter = $filterCourse;
    $yearFilter = (int) $filterYear;
    $list = $conn->prepare(
        "SELECT id, full_name, username, email, mobile, course, year_of_study, gender
         FROM students
         WHERE role = 'student'
           AND (? = '' OR course = ?)
           AND (? = 0 OR year_of_study = ?)
           AND (? = '%%' OR full_name LIKE ? OR username LIKE ? OR email LIKE ? OR mobile LIKE ?)
         ORDER BY full_name, id"
    );
    $list->bind_param(
        'ssiisssss',
        $courseFilter,
        $courseFilter,
        $yearFilter,
        $yearFilter,
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern,
        $searchPattern
    );
    $list->execute();
    $list->bind_result($studentId, $studentName, $studentUsername, $studentEmail, $studentMobile, $studentCourse, $studentYear, $studentGender);
    while ($list->fetch()) {
        $students[] = [
            'id' => $studentId,
            'full_name' => $studentName,
            'username' => $studentUsername,
            'email' => $studentEmail,
            'mobile' => $studentMobile,
            'course' => $studentCourse,
            'year_of_study' => $studentYear,
            'gender' => $studentGender,
        ];
    }
    $list->close();
} catch (mysqli_sql_exception $exception) {
    error_log('StudentHub student search failed: ' . $exception->getMessage());
    $formStatus = 'error';
    $formErrors[] = 'Student records could not be loaded. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage StudentHub student records.">
    <title>Student Management | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Management</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <div class="nav-actions">
                <a class="btn btn--ghost btn--sm" href="admin-dashboard.php">Admin dashboard</a>
                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <button class="btn btn--ghost btn--sm" type="submit">Sign out</button>
                </form>
            </div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Administrator</span><h1 class="t-h1">Student management</h1><p class="t-lead">Add, view, edit, delete, search, and filter student records.</p></div></div></header>
        <section class="section section--tight"><div class="container">
            <?php if ($formStatus === 'success'): ?>
                <div class="alert alert--success" role="status"><?= form_escape((string) $flashMessage) ?></div>
            <?php elseif ($formStatus === 'error' && $formErrors): ?>
                <div class="alert alert--danger" role="alert"><div><strong>Unable to complete the request:</strong><ul><?php foreach ($formErrors as $error): ?><li><?= form_escape($error) ?></li><?php endforeach; ?></ul></div></div>
            <?php endif; ?>

            <section class="card" aria-labelledby="student-form-title">
                <h2 class="t-h2" id="student-form-title"><?= $isEditing ? 'Edit student' : 'Add a student' ?></h2>
                <form method="post" action="student-management.php<?= $editId > 0 ? '?edit=' . (int) $editId : '' ?>" class="form">
                    <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                    <input type="hidden" name="action" value="<?= $isEditing ? 'edit' : 'add' ?>">
                    <?php if ($isEditing): ?><input type="hidden" name="id" value="<?= form_escape((string) ($postedId ?? $editId)) ?>"><?php endif; ?>
                    <div class="grid grid--2">
                        <label class="field"><span class="field__label">Full name</span><input class="input" type="text" name="full_name" maxlength="60" autocomplete="name" required value="<?= form_escape($formValues['full_name']) ?>"></label>
                        <label class="field"><span class="field__label">Username</span><input class="input" type="text" name="username" minlength="3" maxlength="30" autocomplete="username" required value="<?= form_escape($formValues['username']) ?>"></label>
                        <label class="field"><span class="field__label">Email</span><input class="input" type="email" name="email" maxlength="254" autocomplete="email" required value="<?= form_escape($formValues['email']) ?>"></label>
                        <label class="field"><span class="field__label">Mobile</span><input class="input" type="tel" name="mobile" inputmode="numeric" pattern="[6-9][0-9]{9}" maxlength="10" autocomplete="tel" required value="<?= form_escape($formValues['mobile']) ?>"></label>
                        <label class="field"><span class="field__label">Course</span><select class="select" name="course" required><option value="">Select a course</option><?php foreach ($courses as $value => $label): ?><option value="<?= form_escape($value) ?>"<?= $formValues['course'] === $value ? ' selected' : '' ?>><?= form_escape($label) ?></option><?php endforeach; ?></select></label>
                        <label class="field"><span class="field__label">Year of study</span><select class="select" name="year_of_study" required><option value="">Select a year</option><?php for ($year = 1; $year <= 4; $year++): ?><option value="<?= $year ?>"<?= $formValues['year_of_study'] === (string) $year ? ' selected' : '' ?>>Year <?= $year ?></option><?php endfor; ?></select></label>
                        <label class="field"><span class="field__label">Gender</span><select class="select" name="gender" required><option value="">Select an option</option><?php foreach ($genders as $value => $label): ?><option value="<?= form_escape($value) ?>"<?= $formValues['gender'] === $value ? ' selected' : '' ?>><?= form_escape($label) ?></option><?php endforeach; ?></select></label>
                        <?php if (!$isEditing): ?>
                            <label class="field"><span class="field__label">Initial password</span><input class="input" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s])\S{8,128}" title="Use 8–128 characters with uppercase, lowercase, a number, and a symbol; no spaces." required><span class="field__hint">Use 8–128 characters with uppercase, lowercase, a number, and a symbol.</span></label>
                        <?php endif; ?>
                    </div>
                    <div class="form__actions">
                        <button class="btn btn--primary" type="submit"><?= $isEditing ? 'Save changes' : 'Add student' ?></button>
                        <?php if ($isEditing): ?><a class="btn btn--ghost" href="student-management.php">Cancel edit</a><?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="section--tight" aria-labelledby="student-list-title">
                <div class="section-head"><div class="section-head__text"><h2 class="t-h2" id="student-list-title">Student records</h2><p><?= count($students) ?> matching student<?= count($students) === 1 ? '' : 's' ?>.</p></div></div>
                <form method="get" action="student-management.php" class="form">
                    <div class="grid grid--wide">
                        <label class="field"><span class="field__label">Search name, username, email, or mobile</span><input class="input" type="search" name="search" value="<?= form_escape($search) ?>" placeholder="Search students"></label>
                        <label class="field"><span class="field__label">Course</span><select class="select" name="course"><option value="">All courses</option><?php foreach ($courses as $value => $label): ?><option value="<?= form_escape($value) ?>"<?= $filterCourse === $value ? ' selected' : '' ?>><?= form_escape($label) ?></option><?php endforeach; ?></select></label>
                        <label class="field"><span class="field__label">Year</span><select class="select" name="year"><option value="">All years</option><?php for ($year = 1; $year <= 4; $year++): ?><option value="<?= $year ?>"<?= $filterYear === (string) $year ? ' selected' : '' ?>>Year <?= $year ?></option><?php endfor; ?></select></label>
                    </div>
                    <div class="form__actions"><button class="btn btn--primary" type="submit">Search and filter</button><a class="btn btn--ghost" href="student-management.php">Clear filters</a></div>
                </form>
                <div class="table-wrap">
                    <table class="table">
                        <caption class="t-small t-muted">Student accounts and academic details</caption>
                        <thead><tr><th scope="col">Name</th><th scope="col">Username</th><th scope="col">Email</th><th scope="col">Mobile</th><th scope="col">Course</th><th scope="col">Year</th><th scope="col">Gender</th><th scope="col">Actions</th></tr></thead>
                        <tbody>
                            <?php if (!$students): ?>
                                <tr><td colspan="8">No student records match your search and filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <th scope="row"><?= form_escape((string) $student['full_name']) ?></th>
                                        <td><?= form_escape((string) $student['username']) ?></td>
                                        <td><?= form_escape((string) $student['email']) ?></td>
                                        <td><?= form_escape((string) $student['mobile']) ?></td>
                                        <td><?= form_escape($courses[(string) $student['course']] ?? (string) $student['course']) ?></td>
                                        <td><?= (int) $student['year_of_study'] ?></td>
                                        <td><?= form_escape($genders[(string) $student['gender']] ?? (string) $student['gender']) ?></td>
                                        <td><div class="btn-group">
                                            <a class="btn btn--soft btn--sm" href="student-management.php?edit=<?= (int) $student['id'] ?>">Edit</a>
                                            <form method="post" action="student-management.php" onsubmit="return confirm('Delete this student record? Related event registrations will also be deleted.');">
                                                <input type="hidden" name="csrf_token" value="<?= form_escape(auth_csrf_token()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                                <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
                                            </form>
                                        </div></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div></section>
    </main>
</body>
</html>
