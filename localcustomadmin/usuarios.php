<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Users management page for Local Custom Admin plugin
 *
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get filter parameters
$email_filter = optional_param('email', '', PARAM_TEXT);
$username_filter = optional_param('username', '', PARAM_TEXT);
$course_filter = optional_param('course', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/usuarios.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('users_management', 'local_localcustomadmin'));

// Breadcrumb navigation
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('users', 'local_localcustomadmin'));

echo $OUTPUT->header();

// Build WHERE conditions based on filters
$where_conditions = [];
$params = [];

// Base condition - exclude guest and admin users typically
$where_conditions[] = "u.id > 2";

if (!empty($email_filter)) {
    $where_conditions[] = "u.email LIKE :email";
    $params['email'] = '%' . $email_filter . '%';
}

if (!empty($username_filter)) {
    $where_conditions[] = "u.username LIKE :username";
    $params['username'] = '%' . $username_filter . '%';
}

if (!empty($course_filter)) {
    $where_conditions[] = "EXISTS (
        SELECT 1 FROM {user_enrolments} ue 
        JOIN {enrol} e ON e.id = ue.enrolid 
        WHERE e.courseid = :courseid AND ue.userid = u.id
    )";
    $params['courseid'] = $course_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT u.id) 
              FROM {user} u 
              $where_clause";
$total_users = $DB->count_records_sql($count_sql, $params);

// Get users with course enrollment info
$sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.username, 
               u.lastlogin, u.timecreated, u.confirmed, u.suspended,
               (SELECT COUNT(*) FROM {user_enrolments} ue 
                JOIN {enrol} e ON e.id = ue.enrolid 
                WHERE ue.userid = u.id) as enrolled_courses
        FROM {user} u 
        $where_clause
        ORDER BY u.lastname, u.firstname";

$users = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get all courses for the filter dropdown
$courses = $DB->get_records_menu('course', ['visible' => 1], 'fullname', 'id, fullname');

// Prepare template context
$templatecontext = [
    'page_title' => get_string('users_management', 'local_localcustomadmin'),
    'page_description' => get_string('users_management_desc', 'local_localcustomadmin'),
    'users' => [],
    'courses' => [],
    'filters' => [
        'email' => $email_filter,
        'username' => $username_filter,
        'course' => $course_filter
    ],
    'pagination' => [
        'page' => $page,
        'perpage' => $perpage,
        'total' => $total_users,
        'has_pagination' => $total_users > $perpage
    ],
    'has_users' => !empty($users),
    'total_users' => $total_users,
    'reset_filters_url' => (new moodle_url('/local/localcustomadmin/usuarios.php'))->out(),
];

// Populate users data
foreach ($users as $user) {
    $profile_url = new moodle_url('/user/profile.php', ['id' => $user->id]);
    $edit_url = new moodle_url('/user/editadvanced.php', ['id' => $user->id]);
    
    $templatecontext['users'][] = [
        'id' => $user->id,
        'fullname' => fullname($user),
        'firstname' => format_string($user->firstname),
        'lastname' => format_string($user->lastname),
        'email' => $user->email,
        'username' => $user->username,
        'lastlogin' => $user->lastlogin ? userdate($user->lastlogin) : get_string('never'),
        'lastlogin_timestamp' => $user->lastlogin,
        'timecreated' => userdate($user->timecreated),
        'enrolled_courses' => $user->enrolled_courses,
        'confirmed' => $user->confirmed,
        'suspended' => $user->suspended,
        'profile_url' => $profile_url->out(),
        'edit_url' => $edit_url->out(),
        'status_class' => $user->suspended ? 'text-danger' : ($user->confirmed ? 'text-success' : 'text-warning'),
        'status_text' => $user->suspended ? get_string('suspended', 'core') : 
                        ($user->confirmed ? get_string('confirmed', 'core') : get_string('unconfirmed', 'core'))
    ];
}

// Populate courses for filter
foreach ($courses as $courseid => $coursename) {
    $templatecontext['courses'][] = [
        'id' => $courseid,
        'name' => format_string($coursename),
        'selected' => ($courseid == $course_filter)
    ];
}

// Pagination URLs
if ($templatecontext['pagination']['has_pagination']) {
    $base_url = new moodle_url('/local/localcustomadmin/usuarios.php', [
        'email' => $email_filter,
        'username' => $username_filter, 
        'course' => $course_filter,
        'perpage' => $perpage
    ]);
    
    if ($page > 0) {
        $templatecontext['pagination']['prev_url'] = $base_url->out(false, ['page' => $page - 1]);
    }
    
    if (($page + 1) * $perpage < $total_users) {
        $templatecontext['pagination']['next_url'] = $base_url->out(false, ['page' => $page + 1]);
    }
    
    $templatecontext['pagination']['current_page'] = $page + 1;
    $templatecontext['pagination']['total_pages'] = ceil($total_users / $perpage);
}

// Back to index URL
$templatecontext['back_to_index_url'] = (new moodle_url('/local/localcustomadmin/index.php'))->out();

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/usuarios', $templatecontext);

echo $OUTPUT->footer();