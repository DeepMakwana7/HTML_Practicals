<?php
function navigation_items_for_role(string $role): array
{
    if ($role === 'admin') {
        return [
            ['label' => 'Home', 'href' => 'index.html'],
            ['label' => 'Admin dashboard', 'href' => 'admin-dashboard.php'],
            ['label' => 'Student management', 'href' => 'student-management.php'],
            ['label' => 'Event management', 'href' => 'event-management.php'],
        ];
    }

    if ($role === 'student') {
        return [
            ['label' => 'Home', 'href' => 'index.html'],
            ['label' => 'About', 'href' => 'about.html'],
            ['label' => 'Registration', 'href' => 'registration.php'],
            ['label' => 'Dashboard', 'href' => 'dashboard.php'],
            ['label' => 'Events', 'href' => 'events.html'],
            ['label' => 'Assignments', 'href' => 'assignments.html'],
            ['label' => 'Grades', 'href' => 'grades.html'],
            ['label' => 'Timetable', 'href' => 'timetable.html'],
            ['label' => 'Attendance', 'href' => 'attendance.html'],
            ['label' => 'Resources', 'href' => 'resources.html'],
            ['label' => 'Notices', 'href' => 'notice.html'],
            ['label' => 'Contact', 'href' => 'contact.php'],
        ];
    }

    return [];
}
