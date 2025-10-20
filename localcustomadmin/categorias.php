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
 * Categories management with Fluent Design System tree structure
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
$PAGE->set_title('Gerenciamento de Categorias - Fluent Design');

// Include JavaScript for tree functionality
$PAGE->requires->js_call_amd('local_localcustomadmin/categories_accordion', 'init');

// Breadcrumb navigation
$PAGE->navbar->add('Cursos', new moodle_url('/local/localcustomadmin/cursos.php'));
$PAGE->navbar->add('Categorias');

echo $OUTPUT->header();

/**
 * Build category tree structure recursively
 */
function build_category_tree($categories, $parent = 0, $level = 0) {
    $tree = [];
    foreach ($categories as $category) {
        if ($category->parent == $parent) {
            // Get statistics for this category
            $stats = get_category_statistics($category->id);
            
            $category_data = [
                'id' => $category->id,
                'name' => format_string($category->name),
                'description' => format_text($category->description, FORMAT_HTML, ['noclean' => true]),
                'parent' => $category->parent,
                'level' => $level,
                'direct_courses' => $stats['direct_courses'],
                'total_courses' => $stats['total_courses'],
                'subcategories_count' => $stats['subcategories_count'],
                'edit_url' => (new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $category->id]))->out(),
                'is_root' => ($level === 0),
                'children' => []
            ];
            
            // Recursively get children
            $children = build_category_tree($categories, $category->id, $level + 1);
            if (!empty($children)) {
                $category_data['children'] = $children;
                $category_data['has_children'] = true;
            } else {
                $category_data['has_children'] = false;
            }
            
            $tree[] = $category_data;
        }
    }
    return $tree;
}

/**
 * Get comprehensive statistics for a category
 */
function get_category_statistics($categoryid) {
    global $DB;
    
    // Direct courses in this category
    $direct_courses = $DB->count_records('course', ['category' => $categoryid]);
    
    // Direct subcategories
    $subcategories_count = $DB->count_records('course_categories', ['parent' => $categoryid]);
    
    // Total courses including all subcategories (recursive)
    $category = $DB->get_record('course_categories', ['id' => $categoryid], 'path');
    $total_courses = 0;
    
    if ($category && $category->path) {
        $sql = "SELECT COUNT(c.id) 
                FROM {course} c 
                JOIN {course_categories} cc ON c.category = cc.id 
                WHERE cc.path LIKE :path";
        $total_courses = $DB->count_records_sql($sql, ['path' => $category->path . '%']);
    } else {
        $total_courses = $direct_courses;
    }
    
    return [
        'direct_courses' => $direct_courses,
        'total_courses' => $total_courses,
        'subcategories_count' => $subcategories_count
    ];
}

// Get all course categories
$sql = "SELECT cc.id, cc.name, cc.description, cc.parent, cc.sortorder, cc.coursecount, cc.depth, cc.path
        FROM {course_categories} cc
        ORDER BY cc.sortorder ASC";

$all_categories = $DB->get_records_sql($sql);

// Build the hierarchical tree
$category_tree = build_category_tree($all_categories);

// Calculate summary statistics
$total_categories = count($all_categories);
$root_categories = count($category_tree);
$total_courses = $DB->count_records('course', ['category' => 0], 'id != 1'); // Exclude site course

// Prepare template context with Fluent Design principles
$templatecontext = [
    'page_title' => 'Gerenciamento de Categorias',
    'page_description' => 'Organize e gerencie as categorias de cursos em uma estrutura hierárquica intuitiva',
    'add_category_url' => (new moodle_url('/local/localcustomadmin/form_categoria.php'))->out(),
    'back_to_courses_url' => (new moodle_url('/local/localcustomadmin/cursos.php'))->out(),
    'categories' => $category_tree,
    'has_categories' => !empty($category_tree),
    'stats' => [
        'total_categories' => $total_categories,
        'root_categories' => $root_categories,
        'total_courses' => $total_courses
    ]
];

// Render the template
echo $OUTPUT->render_from_template('local_localcustomadmin/categorias', $templatecontext);

echo $OUTPUT->footer();