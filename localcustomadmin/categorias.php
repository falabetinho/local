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
 * Categories management page for Local Custom Admin plugin
 *
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/categorias.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('categories_management', 'local_localcustomadmin'));

// Breadcrumb navigation
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/cursos.php'));
$PAGE->navbar->add(get_string('categories', 'local_localcustomadmin'));

echo $OUTPUT->header();

// Get all course categories with counts
$sql = "SELECT cc.id, cc.name, cc.description, cc.parent, cc.coursecount, cc.depth, cc.path,
               (SELECT COUNT(*) FROM {course_categories} sub WHERE sub.parent = cc.id) as subcategories_count,
               (SELECT COUNT(*) FROM {course} c WHERE c.category = cc.id) as courses_count
        FROM {course_categories} cc
        ORDER BY cc.sortorder ASC";

$categories = $DB->get_records_sql($sql);

// Prepare template context
$templatecontext = [
    'page_description' => get_string('categories_management_desc', 'local_localcustomadmin'),
    'add_category_url' => (new moodle_url('/course/editcategory.php'))->out(),
    'add_category_text' => get_string('add_category', 'local_localcustomadmin'),
    'back_to_courses_url' => (new moodle_url('/local/localcustomadmin/cursos.php'))->out(),
    'back_to_courses_text' => 'Voltar para Cursos',
    'categories' => [],
    'has_categories' => !empty($categories)
];

// Populate categories data
foreach ($categories as $category) {
    $edit_url = new moodle_url('/course/editcategory.php', ['id' => $category->id]);
    
    // Build category path for better visualization
    $category_path = '';
    if ($category->depth > 1) {
        $path_parts = explode('/', trim($category->path, '/'));
        $parent_names = [];
        foreach ($path_parts as $path_id) {
            if ($path_id != $category->id && isset($categories[$path_id])) {
                $parent_names[] = $categories[$path_id]->name;
            }
        }
        if (!empty($parent_names)) {
            $category_path = implode(' > ', $parent_names) . ' > ';
        }
    }
    
    $templatecontext['categories'][] = [
        'id' => $category->id,
        'name' => format_string($category->name),
        'description' => format_text($category->description, FORMAT_HTML),
        'full_path' => $category_path . format_string($category->name),
        'courses_count' => $category->courses_count,
        'subcategories_count' => $category->subcategories_count,
        'depth' => $category->depth,
        'edit_url' => $edit_url->out(),
        'parent_id' => $category->parent,
        'is_root' => $category->depth == 1
    ];
}

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/categorias', $templatecontext);

echo $OUTPUT->footer();