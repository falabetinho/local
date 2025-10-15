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

/**
 * Build hierarchical category structure for dropdown
 */
function build_category_hierarchy($categories) {
    $hierarchy = [];
    $indexed = [];
    
    // First, index all categories by ID
    foreach ($categories as $category) {
        $indexed[$category->id] = (object)[
            'id' => $category->id,
            'name' => $category->name,
            'parent' => $category->parent,
            'depth' => $category->depth,
            'courses_count' => $category->courses_count,
            'subcategories_count' => $category->subcategories_count,
            'children' => []
        ];
    }
    
    // Build hierarchy
    foreach ($indexed as $category) {
        if ($category->parent == 0) {
            $hierarchy[] = $category;
        } else {
            if (isset($indexed[$category->parent])) {
                $indexed[$category->parent]->children[] = $category;
            }
        }
    }
    
    return $hierarchy;
}

/**
 * Flatten hierarchy for dropdown display
 */
function flatten_hierarchy($hierarchy, $level = 0) {
    $flat = [];
    foreach ($hierarchy as $category) {
        $category->display_name = str_repeat('└ ', $level) . $category->name;
        $category->level = $level;
        $flat[] = $category;
        
        if (!empty($category->children)) {
            $flat = array_merge($flat, flatten_hierarchy($category->children, $level + 1));
        }
    }
    return $flat;
}

// Build hierarchical structure
$category_hierarchy = build_category_hierarchy($categories);
$flat_categories = flatten_hierarchy($category_hierarchy);

// Prepare template context
$templatecontext = [
    'page_description' => get_string('categories_management_desc', 'local_localcustomadmin'),
    'add_category_url' => (new moodle_url('/local/localcustomadmin/form_categoria.php'))->out(),
    'add_category_text' => get_string('add_category', 'local_localcustomadmin'),
    'back_to_courses_url' => (new moodle_url('/local/localcustomadmin/cursos.php'))->out(),
    'back_to_courses_text' => 'Voltar para Cursos',
    'categories' => [],
    'flat_categories' => [],
    'has_categories' => !empty($categories)
];

// Use flat categories for both table and accordion
$category_count = count($flat_categories);
foreach ($flat_categories as $index => $category) {
    $edit_url = new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $category->id]);
    
    // Get original category data for parent info
    $original_category = $categories[$category->id];
    
    $templatecontext['categories'][] = [
        'id' => $category->id,
        'name' => format_string($category->name),
        'display_name' => $category->display_name,
        'courses_count' => $category->courses_count,
        'subcategories_count' => $category->subcategories_count,
        'depth' => $category->level + 1, // Adjust for template compatibility
        'edit_url' => $edit_url->out(),
        'parent_id' => $original_category->parent,
        'is_root' => $category->level == 0,
        'level' => $category->level,
        'is_last' => ($index === $category_count - 1)
    ];
}

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/categorias', $templatecontext);

echo $OUTPUT->footer();