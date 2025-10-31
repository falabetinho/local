<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/sync_prices.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('sync_prices', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('sync_prices', 'local_localcustomadmin'));

// Get synced courses
$course_mappings = $DB->get_records('local_customadmin_wp_mapping', ['moodle_type' => 'course', 'sync_status' => 'synced']);

$pricedata = [];
foreach ($course_mappings as $course_map) {
    $course = $DB->get_record('course', ['id' => $course_map->moodle_id]);
    if (!$course) continue;
    
    // Get customstatus enrols for this course
    $enrols = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'customstatus']);
    
    foreach ($enrols as $enrol) {
        $pricedata[] = [
            'id' => $enrol->id,
            'course_name' => format_string($course->fullname),
            'cost' => $enrol->cost ?: 0,
            'currency' => 'BRL', // Assuming
            'installments' => $enrol->customint4 ?: 1,
            'ispromotional' => $enrol->customint2 ? true : false,
            'isenrollmentfee' => $enrol->customint3 ? true : false,
            'sync_status' => 'not_synced', // Placeholder
            'last_synced' => '-',
            'actions' => [
                'sync' => true,
                'update' => false,
                'delete' => false
            ]
        ];
    }
}

// Template context
$templatecontext = [
    'prices' => $pricedata,
    'back_to_index_url' => (new moodle_url('/local/localcustomadmin/wordpress_integration.php'))->out()
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_localcustomadmin/sync_prices', $templatecontext);
echo $OUTPUT->footer();
