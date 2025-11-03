<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Public course showcase page
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/course_showcase.php');

// Page setup
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/localcustomadmin/showcase_courses.php'));
$PAGE->set_pagelayout('frontpage');
$PAGE->set_title(get_string('course_showcase', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('course_showcase', 'local_localcustomadmin'));

// Get filter parameters
$categoryid = optional_param('category', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$sort = optional_param('sort', 'name', PARAM_ALPHA); // name, price, popular

// Include CSS
$PAGE->requires->css(new moodle_url('/local/localcustomadmin/styles.css'));

echo $OUTPUT->header();

// Get course showcase instance
$showcase = new \local_localcustomadmin\course_showcase();

// Get courses with prices
$courses = $showcase->get_courses_with_prices($categoryid, $search, $sort);

// Get categories for filter
$categories = $showcase->get_categories();

// Get statistics
$stats = $showcase->get_statistics();

// Prepare template context
$templatecontext = [
    'page_title' => get_string('course_showcase', 'local_localcustomadmin'),
    'page_subtitle' => get_string('course_showcase_desc', 'local_localcustomadmin'),
    'courses' => $courses,
    'categories' => $categories,
    'stats' => $stats,
    'filters' => [
        'category' => $categoryid,
        'search' => $search,
        'sort' => $sort
    ],
    'has_courses' => !empty($courses),
    'current_url' => $PAGE->url->out()
];

// Render template
echo $OUTPUT->render_from_template('local_localcustomadmin/showcase_courses', $templatecontext);

echo $OUTPUT->footer();
