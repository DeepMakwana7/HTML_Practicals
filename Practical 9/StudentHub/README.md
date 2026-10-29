StudentHub - Student Portal Web Application

StudentHub is a lightweight HTML, CSS, and JavaScript student portal designed to present academic information such as notices, course resources, grades, attendance, timetables, and assignments in a simple and intuitive interface. Its shared CSS system uses mobile-first responsive layouts built with CSS Grid and Flexbox.

Project Structure : 
StudentHub/
│
├── index.html
├── about.html
├── registration.php
├── login.html
├── dashboard.html
├── events.html
├── assignments.html
├── attendance.html
├── grades.html
├── timetable.html
├── notice.html
├── resources.html
├── contact.php
├── includes/
│   ├── db.php
│   └── form_helpers.php
├── database.sql
├── storage/
│   └── .htaccess
├── css/
│   └── styles.css
├── js/
│   └── site.js
└── README.md

Features : 

Authentication: Simple user login screen.

Central Dashboard: Easy navigation hub linking all student services.

Responsive Layout: Mobile-first navigation, cards, forms, and tables that adapt to phones, tablets, and desktop screens.

View grades and marksheets.

Track attendance records.

View class routines and timetables.

Manage and track assignments.

Stay updated with official announcements.

Contact academic support and administration.

Study Material: Access lecture notes, lab manuals, and useful software links.

Interactive UI: The home page includes a dismissible campus notice, a quick-tour modal, a content slider, and collapsible FAQs. Use the navigation menu to switch between light and dark themes; the theme preference is saved in the browser.

Events and notices are loaded from the JSON files in `data/` with the Fetch API. Both lists support text search, category filtering, sorting, and pagination. Since browsers restrict Fetch from `file://` pages, run the project through a local web server (for example, VS Code Live Server) to use these pages.

The Dashboard profile and academic summary are also loaded from `data/student.json` with the Fetch API.

The registration form validates submitted details in the browser and on the server, checks for duplicate usernames and email addresses, and inserts student accounts into MySQL using prepared MySQLi statements. Passwords are stored as `password_hash()` hashes, never as plain text. Contact form submissions continue to be saved in `storage/contacts.json`. Run the site through a PHP-enabled web server (such as XAMPP Apache); Live Server does not execute PHP.

## MySQL database

The relational schema in `database.sql` defines `students`, `events`, and `registrations`. Each registration connects one student to one event; a student can register for multiple events, and an event can have multiple student registrations. The schema prevents duplicate student/event registrations and cascades dependent registrations when a student or event is deleted.

Import `database.sql` using phpMyAdmin or the MySQL command line. The PHP connection is available in `includes/db.php` as `$conn` (a `mysqli` connection). It defaults to a local XAMPP MySQL setup (`localhost`, user `root`, blank password, database `studenthub`). Override these settings with the `STUDENTHUB_DB_HOST`, `STUDENTHUB_DB_USER`, `STUDENTHUB_DB_PASSWORD`, `STUDENTHUB_DB_NAME`, and `STUDENTHUB_DB_PORT` environment variables when needed. For an existing `students` table created from an older version of the schema, run `database-migrate-registration.sql` once to add usernames and preserve existing accounts using generated usernames.
