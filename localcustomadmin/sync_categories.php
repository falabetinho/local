<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/sync_categories.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('sync_categories', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('sync_categories', 'local_localcustomadmin'));

// Configs
$baseurl = get_config('local_localcustomadmin', 'wordpress_baseurl');
$username = get_config('local_localcustomadmin', 'wordpress_username');
$password = get_config('local_localcustomadmin', 'wordpress_apppassword');

// Get mappings for categories
$categories = $DB->get_records('course_categories', null, 'sortorder ASC');
$mappings = $DB->get_records('local_customadmin_wp_mapping', ['moodle_type' => 'category']);

$categorydata = [];
foreach ($categories as $cat) {
    $mapping = false;
    foreach ($mappings as $map) {
        if ($map->moodle_id == $cat->id) {
            $mapping = $map;
            break;
        }
    }
    $categorydata[] = [
        'id' => $cat->id,
        'name' => format_string($cat->name),
        'description' => format_text($cat->description, $cat->descriptionformat),
        'parent' => $cat->parent,
        'mapping_id' => $mapping ? $mapping->id : null,
        'wordpress_id' => $mapping ? $mapping->wordpress_id : null,
        'sync_status' => $mapping ? $mapping->sync_status : 'not_synced',
        'last_synced' => $mapping ? userdate($mapping->last_synced) : '-',
        'actions' => [
            'sync' => !$mapping ? true : false,
            'update' => $mapping ? true : false,
            'delete' => $mapping ? true : false
        ]
    ];
}

// Template context
$templatecontext = [
    'categories' => $categorydata,
    'baseurl' => $baseurl,
    'back_to_index_url' => (new moodle_url('/local/localcustomadmin/wordpress_integration.php'))->out()
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_localcustomadmin/sync_categories', $templatecontext);
echo $OUTPUT->footer();
