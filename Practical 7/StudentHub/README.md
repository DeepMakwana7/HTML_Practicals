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
│   └── form_helpers.php
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

The registration and contact forms are processed by PHP and append validated submissions to `storage/registrations.json` and `storage/contacts.json`. Registration passwords are saved as password hashes, never as plain text. Run the site through a PHP-enabled web server (such as XAMPP Apache); Live Server does not execute PHP. Ensure the `storage/` directory is writable by the web server. Apache access to this directory is denied by its `.htaccess` file.
