<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/sync_courses.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('sync_courses', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('sync_courses', 'local_localcustomadmin'));

// Get mappings for courses and categories
$courses = $DB->get_records_sql("SELECT * FROM {course} WHERE id > ? ORDER BY fullname ASC", [SITEID]);
$course_mappings = $DB->get_records('local_customadmin_wp_mapping', ['moodle_type' => 'course']);
$category_mappings_raw = $DB->get_records('local_customadmin_wp_mapping', ['moodle_type' => 'category']);

// Create associative array for category mappings (otimizado)
$category_mappings = [];
foreach ($category_mappings_raw as $mapping) {
    $category_mappings[$mapping->moodle_id] = $mapping;
}

$coursedata = [];
foreach ($courses as $course) {
    $mapping = false;
    foreach ($course_mappings as $map) {
        if ($map->moodle_id == $course->id) {
            $mapping = $map;
            break;
        }
    }
    
    // Check if category is synced
    $category_synced = isset($category_mappings[$course->category]) && $category_mappings[$course->category]->sync_status == 'synced';
    
    $coursedata[] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'shortname' => format_string($course->shortname),
        'category' => $course->category,
        'category_synced' => $category_synced,
        'mapping_id' => $mapping ? $mapping->id : null,
        'wordpress_id' => $mapping ? $mapping->wordpress_id : null,
        'sync_status' => $mapping ? $mapping->sync_status : 'not_synced',
        'last_synced' => $mapping ? userdate($mapping->last_synced) : '-',
        'actions' => [
            'sync' => (!$mapping && $category_synced) ? true : false,
            'update' => ($mapping && $category_synced) ? true : false,
            'delete' => ($mapping && $category_synced) ? true : false
        ]
    ];
}

// Template context
$templatecontext = [
    'courses' => $coursedata,
    'back_to_index_url' => (new moodle_url('/local/localcustomadmin/wordpress_integration.php'))->out()
];

$PAGE->requires->css(new moodle_url('/local/localcustomadmin/styles.css'));
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_localcustomadmin/sync_courses', $templatecontext);
echo $OUTPUT->footer();
