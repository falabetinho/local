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
 * Complete courses management page for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();

require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$visibility = optional_param('visibility', 'all', PARAM_ALPHA);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/manage_cursos.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_courses', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('manage_courses', 'local_localcustomadmin'));

// Add navigation breadcrumb
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), '/local/localcustomadmin/index.php');
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), '/local/localcustomadmin/cursos.php');
$PAGE->navbar->add(get_string('manage_courses', 'local_localcustomadmin'));

// Inclua o CSS centralizado antes do header
$PAGE->requires->css(new moodle_url('/local/localcustomadmin/styles.css'));

// Estatísticas
$totalcourses = $DB->count_records_select('course', 'id != :siteid', ['siteid' => SITEID]);
$visiblecourses = $DB->count_records_select('course', 'id != :siteid AND visible = 1', ['siteid' => SITEID]);
$hiddencourses = $totalcourses - $visiblecourses;
$totalenrollments = (int)$DB->get_field_sql(
    "SELECT COUNT(ue.id)
       FROM {user_enrolments} ue
       JOIN {enrol} e ON e.id = ue.enrolid
       JOIN {course} c ON c.id = e.courseid
      WHERE c.id != :siteid",
    ['siteid' => SITEID]
);

// Filtros
$where = ['c.id != :siteid']; $params = ['siteid' => SITEID];
if (!empty($search)) {
    $where[] = '(c.fullname LIKE :s OR c.shortname LIKE :s)';
    $params['s'] = '%' . $DB->sql_like_escape($search) . '%';
}
if (!empty($categoryid)) {
    $where[] = 'c.category = :categoryid';
    $params['categoryid'] = $categoryid;
}
if ($visibility === 'visible') {
    $where[] = 'c.visible = 1';
} else if ($visibility === 'hidden') {
    $where[] = 'c.visible = 0';
}
$whereclause = implode(' AND ', $where);

// Contagem total p/ paginação
$countsql = "SELECT COUNT(1) FROM {course} c WHERE {$whereclause}";
$totalcount = (int)$DB->count_records_sql($countsql, $params);

// Cursos (com matrículas)
$sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.visible,
               (SELECT COUNT(ue.id)
                  FROM {enrol} e
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE e.courseid = c.id) AS enrollments
          FROM {course} c
         WHERE {$whereclause}
      ORDER BY c.fullname ASC";
$courserecords = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Categorias para filtro e map
$categories = $DB->get_records('course_categories', null, 'name ASC');
$categoriesmap = [];
foreach ($categories as $cat) {
    $categoriesmap[$cat->id] = format_string($cat->name);
}

// Monta contexto de cursos
$coursesctx = [];
foreach ($courserecords as $c) {
    $courseurl = new moodle_url('/course/view.php', ['id' => $c->id]);
    $editurl = new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $c->id]);
    $coursesctx[] = [
        'id' => $c->id,
        'fullname' => format_string($c->fullname),
        'shortname' => format_string($c->shortname),
        'categoryname' => $categoriesmap[$c->category] ?? 'N/A',
        'enrollments' => (int)$c->enrollments,
        'visible' => (int)$c->visible,
        'status_class' => $c->visible ? 'success' : 'secondary',
        'status_text' => $c->visible ? get_string('visible') : get_string('hidden'),
        'courseurl' => $courseurl->out(),
        'editurl' => $editurl->out(),
    ];
}

// Contexto do template
$templatecontext = [
    'page_title' => get_string('manage_courses', 'local_localcustomadmin'),
    'page_description' => get_string('manage_courses_desc', 'local_localcustomadmin'),
    'statistics' => [
        ['icon' => 'fa-graduation-cap', 'value' => $totalcourses,    'label' => get_string('total_courses', 'local_localcustomadmin'), 'bgclass' => 'bg-primary'],
        ['icon' => 'fa-eye',            'value' => $visiblecourses,   'label' => get_string('visible_courses', 'local_localcustomadmin'), 'bgclass' => 'bg-success'],
        ['icon' => 'fa-eye-slash',      'value' => $hiddencourses,    'label' => get_string('hidden_courses', 'local_localcustomadmin'),  'bgclass' => 'bg-warning'],
        ['icon' => 'fa-users',          'value' => $totalenrollments, 'label' => get_string('enrolments', 'enrol'),                        'bgclass' => 'bg-info'],
    ],
    'filters' => [
        'search' => $search,
        'categoryid' => $categoryid,
        'visibility' => $visibility,
    ],
    'categories' => array_values(array_map(function($cat) use ($categoryid) {
        return ['id' => $cat->id, 'name' => format_string($cat->name), 'selected' => ((int)$categoryid === (int)$cat->id)];
    }, $categories)),
    'courses' => $coursesctx,
    'has_courses' => !empty($coursesctx),
    'add_course_url' => (new moodle_url('/local/localcustomadmin/edit_curso.php'))->out(),
    'back_to_index_url' => (new moodle_url('/local/localcustomadmin/cursos.php'))->out(),
];

// Paginação
if ($totalcount > $perpage) {
    $base = new moodle_url('/local/localcustomadmin/manage_cursos.php', [
        'search' => $search,
        'categoryid' => $categoryid,
        'visibility' => $visibility,
        'perpage' => $perpage
    ]);
    $templatecontext['pagination'] = [
        'has_pagination' => true,
        'current_page' => $page + 1,
        'total_pages' => ceil($totalcount / $perpage),
        'prev_url' => $page > 0 ? $base->out(false, ['page' => $page - 1]) : null,
        'next_url' => (($page + 1) * $perpage < $totalcount) ? $base->out(false, ['page' => $page + 1]) : null,
    ];
}

// Render
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_localcustomadmin/manage_cursos', $templatecontext);
echo $OUTPUT->footer();
