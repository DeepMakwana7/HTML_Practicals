<?php
require_once __DIR__ . '/includes/form_helpers.php';

$formStatus = null;
$formMessage = '';
$formErrors = [];
$formValues = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formValues as $field => $default) {
        $formValues[$field] = form_post_value($field) ?? '';
    }

    $name = $formValues['name'];
    $email = $formValues['email'];
    $subject = $formValues['subject'];
    $message = $formValues['message'];

    if (!preg_match("/^[\\p{L}][\\p{L}\\p{M} .'-]{1,79}$/uD", $name)) {
        $formErrors[] = 'Enter a name using 2–80 letters, spaces, apostrophes, periods, or hyphens.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $formErrors[] = 'Enter a valid email address (maximum 254 characters).';
    }
    if (strlen($subject) < 2 || strlen($subject) > 120 || preg_match('/[\x00-\x1F\x7F]/', $subject)) {
        $formErrors[] = 'Enter a subject between 2 and 120 characters.';
    }
    if ($message === '' || strlen($message) > 5000) {
        $formErrors[] = 'Enter a message of no more than 5000 characters.';
    }

    if (!$formErrors) {
        try {
            append_json_record(__DIR__ . '/storage/contacts.json', [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message,
                'created_at' => gmdate('c'),
            ]);
            $formStatus = 'success';
            $formMessage = 'Your message was sent successfully.';
            foreach ($formValues as $field => $default) {
                $formValues[$field] = '';
            }
        } catch (RuntimeException $exception) {
            error_log('StudentHub contact storage error: ' . $exception->getMessage());
            $formStatus = 'error';
            $formErrors[] = 'We could not save your message right now. Please try again later.';
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
    <meta name="description" content="Contact StudentHub student support.">
    <title>Contact | StudentHub</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/site.js" defer></script>
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>
    <header class="site-header"><div class="container header-inner">
        <a href="index.html" class="brand" aria-label="StudentHub home"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav" aria-label="Toggle navigation menu"><span class="nav-toggle-bar" aria-hidden="true"></span></button>
        <nav class="nav-drawer" id="primary-nav" data-open="false" aria-label="Primary navigation">
            <ul class="nav-list"><li><a href="index.html">Home</a></li><li><a href="about.html">About</a></li><li><a href="registration.php">Registration</a></li><li><a href="dashboard.html">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li><li><a href="attendance.html">Attendance</a></li><li><a href="resources.html">Resources</a></li><li><a href="notice.html">Notices</a></li><li><a href="contact.php" aria-current="page">Contact</a></li></ul>
            <div class="nav-actions"><a href="login.html" class="btn btn--ghost btn--sm">Sign In</a><a href="registration.php" class="btn btn--primary btn--sm">Register</a></div>
        </nav>
    </div></header>
    <main id="main" class="site-main">
        <header class="page-head"><div class="container page-head__inner"><div><span class="t-eyebrow">We're here to help</span><h1 class="t-h1">Contact StudentHub</h1><p class="t-lead">Have a question or need help? Reach out through the channels below.</p></div></div></header>
        <section class="section"><div class="container stack stack--lg">
            <div class="grid grid--3">
                <article class="contact-card"><span class="icon-badge" aria-hidden="true">☎</span><div class="contact-card__body"><span class="contact-card__label">Phone</span><a class="contact-card__value" href="tel:+919878360000">+91 98783 6xxxx</a><span class="t-small t-muted">Student support</span></div></article>
                <article class="contact-card"><span class="icon-badge icon-badge--success" aria-hidden="true">✉</span><div class="contact-card__body"><span class="contact-card__label">Email</span><a class="contact-card__value" href="mailto:abc@gmail.com">abc@gmail.com</a><span class="t-small t-muted">General enquiries</span></div></article>
                <article class="contact-card"><span class="icon-badge icon-badge--accent" aria-hidden="true">◎</span><div class="contact-card__body"><span class="contact-card__label">Instagram</span><a class="contact-card__value" href="https://www.instagram.com/studenthubportal_7/">@studenthubportal_7</a><span class="t-small t-muted">Campus updates</span></div></article>
            </div>
            <section class="card">
                <h2 class="t-h2">Send us a message</h2>
                <p class="t-muted">Complete the form and our student support team will get back to you.</p>
                <form action="contact.php" method="post" class="form">
                    <?php if ($formStatus === 'success'): ?>
                        <div class="alert alert--success" role="status"><?= form_escape($formMessage) ?></div>
                    <?php elseif ($formStatus === 'error'): ?>
                        <div class="alert alert--danger" role="alert">
                            <div>
                                <strong>Your message could not be sent:</strong>
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
                            <input class="input" type="text" name="name" autocomplete="name" minlength="2" maxlength="80" value="<?= form_escape($formValues['name']) ?>" required>
                        </label>
                        <label class="field">
                            <span class="field__label">Email address</span>
                            <input class="input" type="email" name="email" autocomplete="email" maxlength="254" value="<?= form_escape($formValues['email']) ?>" required>
                        </label>
                    </div>
                    <label class="field">
                        <span class="field__label">Subject</span>
                        <input class="input" type="text" name="subject" minlength="2" maxlength="120" value="<?= form_escape($formValues['subject']) ?>" required>
                    </label>
                    <label class="field">
                        <span class="field__label">Message</span>
                        <textarea class="textarea" name="message" maxlength="5000" required><?= form_escape($formValues['message']) ?></textarea>
                    </label>
                    <div class="form__actions"><button class="btn btn--primary" type="submit">Send message</button></div>
                </form>
            </section>
            <section class="card"><h2 class="t-h2">Frequently asked questions</h2>
                <details class="faq"><summary>Where can I check assignment deadlines?</summary><div class="faq__body">Visit the <a href="assignments.html">Assignments page</a> for the current list of coursework and due dates.</div></details>
                <details class="faq"><summary>How do I find my class schedule?</summary><div class="faq__body">Your weekly schedule is available on the <a href="timetable.html">Timetable page</a>.</div></details>
                <details class="faq"><summary>Where are campus announcements posted?</summary><div class="faq__body">Check <a href="notice.html">Notices</a> for academic, IT, and campus announcements.</div></details>
            </section>
        </div></section>
    </main>
    <footer class="site-footer"><div class="container footer-grid"><div class="footer-brand"><a href="index.html" class="brand"><span class="brand-mark" aria-hidden="true">SH</span><span class="brand-text"><strong>StudentHub</strong><small>Student Portal</small></span></a><p>One place for your academic life at CSPIT.</p></div><nav class="footer-col" aria-label="Academic links"><h4>Academic</h4><ul><li><a href="assignments.html">Assignments</a></li><li><a href="grades.html">Grades</a></li><li><a href="timetable.html">Timetable</a></li></ul></nav><nav class="footer-col" aria-label="Student links"><h4>StudentHub</h4><ul><li><a href="dashboard.html">Dashboard</a></li><li><a href="events.html">Events</a></li><li><a href="contact.php">Contact</a></li></ul></nav><div class="footer-bottom"><p>&copy; 2026 StudentHub Portal.</p></div></div></footer>
</body>
</html>
