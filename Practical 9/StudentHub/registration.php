<?php
require_once __DIR__ . '/includes/form_helpers.php';

$formStatus = null;
$formErrors = [];
$formValues = [
    'name' => '',
    'username' => '',
    'email' => '',
    'mobile' => '',
    'course' => '',
    'year' => '',
    'gender' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formValues as $field => $default) {
        $formValues[$field] = form_post_value($field) ?? '';
    }

    $name = $formValues['name'];
    $username = $formValues['username'];
    $email = $formValues['email'];
    $mobile = $formValues['mobile'];
    $course = $formValues['course'];
    $year = $formValues['year'];
    $gender = $formValues['gender'];
    $password = form_post_raw_value('password');
    $passwordConfirmation = form_post_raw_value('password-confirmation');

    if (!preg_match("/^[A-Za-z][A-Za-z .'-]{1,59}$/D", $name)) {
        $formErrors[] = 'Enter a name using 2–60 letters, spaces, apostrophes, periods, or hyphens.';
    }
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/D', $username)) {
        $formErrors[] = 'Choose a username using 3–30 letters, numbers, or underscores.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $formErrors[] = 'Enter a valid email address (maximum 254 characters).';
    }
    $email = strtolower($email);
    if (!preg_match('/^[6-9][0-9]{9}$/D', $mobile)) {
        $formErrors[] = 'Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.';
    }
    if (!in_array($course, ['computer-engineering', 'information-technology', 'electronics', 'other'], true)) {
        $formErrors[] = 'Select a valid course.';
    }
    if (!in_array($year, ['1', '2', '3', '4'], true)) {
        $formErrors[] = 'Select a valid year of study.';
    }
    if (!in_array($gender, ['female', 'male', 'other', 'prefer-not-to-say'], true)) {
        $formErrors[] = 'Select a valid gender option.';
    }
    if ($password === null || strlen($password) > 128 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s])\S{8,}$/D', $password)) {
        $formErrors[] = 'Use 8–128 characters, including uppercase, lowercase, a number, and a symbol; do not use spaces.';
    }
    if ($passwordConfirmation === null || $password !== $passwordConfirmation) {
        $formErrors[] = 'Passwords do not match.';
    }
    if (form_post_value('terms') !== 'on') {
        $formErrors[] = 'You must agree to the student portal guidelines.';
    }

    if (!$formErrors) {
        try {
            require __DIR__ . '/includes/db.php';

            $duplicateCheck = $conn->prepare(
                'SELECT EXISTS(SELECT 1 FROM students WHERE email = ?) AS email_exists, EXISTS(SELECT 1 FROM students WHERE username = ?) AS username_exists'
            );
            $duplicateCheck->bind_param('ss', $email, $username);
            $duplicateCheck->execute();
            $duplicateCheck->bind_result($emailExists, $usernameExists);
            $duplicateCheck->fetch();
            $duplicateCheck->close();

            if ($emailExists || $usernameExists) {
                if ($emailExists) {
                    $formErrors[] = 'That email address is already registered.';
                }
                if ($usernameExists) {
                    $formErrors[] = 'That username is already taken.';
                }
                $formStatus = 'error';
            } else {
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
                $formStatus = 'success';
                foreach ($formValues as $field => $default) {
                    $formValues[$field] = '';
                }
            }
            $conn->close();
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                $formStatus = 'error';
                $formErrors[] = 'That email address or username is already registered.';
            } else {
                error_log('StudentHub registration database error: ' . $exception->getMessage());
                $formStatus = 'error';
                $formErrors[] = 'We could not save your registration right now. Please try again later.';
            }
        } catch (RuntimeException $exception) {
            error_log('StudentHub registration database error: ' . $exception->getMessage());
            $formStatus = 'error';
            $formErrors[] = 'We could not save your registration right now. Please try again later.';
        }

    } else {
        $formStatus = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your StudentHub student portal account.">
    <title>Registration | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <ul class="nav-list"><li><a href="index.html">Home</a></li><li><a href="about.html">About</a></li><li><a href="registration.php" aria-current="page">Registration</a></li><li><a href="dashboard.html">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li><li><a href="attendance.html">Attendance</a></li><li><a href="resources.html">Resources</a></li><li><a href="notice.html">Notices</a></li><li><a href="contact.php">Contact</a></li></ul>
            <div class="nav-actions"><a href="login.html" class="btn btn--ghost btn--sm">Sign In</a><a href="registration.php" class="btn btn--primary btn--sm">Register</a></div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">Join StudentHub</span><h1 class="t-h1">Create your student account</h1><p class="t-lead">Register to bring your academic tools and updates together.</p></div></div></header>
        <section class="section"><div class="container auth">
            <div class="auth__card" style="max-width: 680px;">
                <div><h2 class="t-h2">Student registration</h2><p class="t-muted">Enter your details to get started.</p></div>
                <form action="registration.php" method="post" class="form" data-registration-form>
                    <?php if ($formStatus === 'success'): ?>
                        <div class="alert alert--success" role="status">Your account was created successfully.</div>
                    <?php elseif ($formStatus === 'error'): ?>
                        <div class="alert alert--danger" role="alert">
                            <div>
                                <strong>Registration could not be completed:</strong>
                                <ul>
                                    <?php foreach ($formErrors as $error): ?>
                                        <li><?= form_escape($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="grid grid--2">
                        <label class="field">
                            <span class="field__label">Full name</span>
                            <input class="input" type="text" name="name" autocomplete="name" minlength="2" maxlength="60" pattern="[A-Za-z][A-Za-z .'-]{1,59}" title="Use 2–60 letters, spaces, apostrophes, periods, or hyphens." value="<?= form_escape($formValues['name']) ?>" required>
                        </label>
                        <label class="field">
                            <span class="field__label">Username</span>
                            <input class="input" type="text" name="username" autocomplete="username" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" title="Use 3–30 letters, numbers, or underscores." value="<?= form_escape($formValues['username']) ?>" required>
                        </label>
                        <label class="field">
                            <span class="field__label">Email address</span>
                            <input class="input" type="email" name="email" autocomplete="email" maxlength="254" value="<?= form_escape($formValues['email']) ?>" required>
                        </label>
                        <label class="field">
                            <span class="field__label">Mobile number</span>
                            <input class="input" type="tel" name="mobile" autocomplete="tel" inputmode="numeric" maxlength="10" pattern="[6-9][0-9]{9}" title="Enter a 10-digit mobile number starting with 6, 7, 8, or 9." placeholder="10-digit mobile number" value="<?= form_escape($formValues['mobile']) ?>" required>
                        </label>
                        <label class="field">
                            <span class="field__label">Course</span>
                            <select class="select" name="course" required>
                                <option value="">Select your course</option>
                                <option value="computer-engineering"<?= $formValues['course'] === 'computer-engineering' ? ' selected' : '' ?>>Computer Engineering</option>
                                <option value="information-technology"<?= $formValues['course'] === 'information-technology' ? ' selected' : '' ?>>Information Technology</option>
                                <option value="electronics"<?= $formValues['course'] === 'electronics' ? ' selected' : '' ?>>Electronics</option>
                                <option value="other"<?= $formValues['course'] === 'other' ? ' selected' : '' ?>>Other</option>
                            </select>
                        </label>
                        <label class="field">
                            <span class="field__label">Year of study</span>
                            <select class="select" name="year" required>
                                <option value="">Select your year</option>
                                <option value="1"<?= $formValues['year'] === '1' ? ' selected' : '' ?>>First year</option>
                                <option value="2"<?= $formValues['year'] === '2' ? ' selected' : '' ?>>Second year</option>
                                <option value="3"<?= $formValues['year'] === '3' ? ' selected' : '' ?>>Third year</option>
                                <option value="4"<?= $formValues['year'] === '4' ? ' selected' : '' ?>>Fourth year</option>
                            </select>
                        </label>
                        <label class="field">
                            <span class="field__label">Password</span>
                            <input class="input" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="128" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s])\S{8,128}" title="Use 8–128 characters, including uppercase, lowercase, a number, and a symbol; do not use spaces." required>
                            <span class="field__hint">8–128 characters, with uppercase, lowercase, a number, and a symbol.</span>
                        </label>
                        <label class="field">
                            <span class="field__label">Confirm password</span>
                            <input class="input" type="password" name="password-confirmation" autocomplete="new-password" maxlength="128" data-password-confirmation required>
                        </label>
                    </div>
                    <fieldset class="field registration-gender">
                        <legend class="field__label">Gender</legend>
                        <div class="choice-grid">
                            <label class="choice"><input type="radio" name="gender" value="female"<?= $formValues['gender'] === 'female' ? ' checked' : '' ?> required><span class="choice__title">Female</span></label>
                            <label class="choice"><input type="radio" name="gender" value="male"<?= $formValues['gender'] === 'male' ? ' checked' : '' ?> required><span class="choice__title">Male</span></label>
                            <label class="choice"><input type="radio" name="gender" value="other"<?= $formValues['gender'] === 'other' ? ' checked' : '' ?> required><span class="choice__title">Other</span></label>
                            <label class="choice"><input type="radio" name="gender" value="prefer-not-to-say"<?= $formValues['gender'] === 'prefer-not-to-say' ? ' checked' : '' ?> required><span class="choice__title">Prefer not to say</span></label>
                        </div>
                    </fieldset>
                    <label class="check"><input type="checkbox" name="terms" required><span>I agree to use StudentHub in accordance with the student portal guidelines.</span></label>
                    <div class="form__actions"><button class="btn btn--primary btn--lg" type="submit">Create account</button><span class="t-small t-muted">Already registered? <a href="login.html">Sign in</a></span></div>
                </form>
            </div>
        </div></section>
    </main>
    <footer class="site-footer"><div class="container footer-grid"><div class="footer-brand"><a href="index.html" class="brand"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a><p>One place for your academic life at CSPIT.</p></div><nav class="footer-col" aria-label="Academic links"><h4>Academic</h4><ul><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li></ul></nav><nav class="footer-col" aria-label="Student links"><h4>StudentHub</h4><ul><li><a href="dashboard.html">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="contact.php">Contact</a></li></ul></nav><div class="footer-bottom"><p>&copy; 2026 StudentHub Portal.</p></div></div></footer>
</body>
</html>
