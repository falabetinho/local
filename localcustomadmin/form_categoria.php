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
 * Category form page for Local Custom Admin plugin
 *
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$id = optional_param('id', 0, PARAM_INT); // Category ID for editing
$parent = optional_param('parent', 0, PARAM_INT); // Parent category ID for new categories
$modal = optional_param('modal', 0, PARAM_INT); // Is this being displayed in a modal?

// Set up page
if ($modal) {
    $PAGE->set_pagelayout('popup');
} else {
    $PAGE->set_pagelayout('base');
}

$PAGE->set_url(new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $id, 'parent' => $parent, 'modal' => $modal]));
$PAGE->set_context($context);

// Determine if we're editing or creating
$editing = !empty($id);
$category = null;

if ($editing) {
    $category = $DB->get_record('course_categories', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('edit_category', 'local_localcustomadmin'));
} else {
    $PAGE->set_title(get_string('add_category', 'local_localcustomadmin'));
}

/**
 * Category form class
 */
class category_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $customdata = $this->_customdata;
        
        $editing = !empty($customdata['category']);
        $category = $editing ? $customdata['category'] : null;
        
        // Hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        $mform->addElement('hidden', 'modal');
        $mform->setType('modal', PARAM_INT);
        
        // Category name
        $mform->addElement('text', 'name', get_string('categoryname', 'core'), 'maxlength="254" size="50"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 254), 'maxlength', 254, 'client');
        
        // Parent category
        $categories = $DB->get_records_menu('course_categories', null, 'sortorder', 'id, name');
        $parent_options = [0 => get_string('top')];
        
        if ($categories) {
            foreach ($categories as $catid => $catname) {
                // Don't allow a category to be its own parent or child
                if (!$editing || ($catid != $category->id && strpos($category->path, '/' . $catid . '/') === false)) {
                    $parent_options[$catid] = format_string($catname);
                }
            }
        }
        
        $mform->addElement('select', 'parent', get_string('categoryparent', 'core'), $parent_options);
        $mform->addHelpButton('parent', 'categoryparent', 'core');
        
        // Category description
        $mform->addElement('editor', 'description_editor', get_string('categorydescription', 'core'), 
            ['rows' => 10], ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $customdata['context']]);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'categorydescription', 'core');
        
        // Category image
        $mform->addElement('filemanager', 'categoryimage', get_string('categoryimage', 'core'), null,
            [
                'subdirs' => 0,
                'maxbytes' => 0,
                'maxfiles' => 1,
                'accepted_types' => 'web_image',
                'context' => $customdata['context']
            ]
        );
        $mform->addHelpButton('categoryimage', 'categoryimage', 'core');
        
        // Category theme (optional)
        if (!empty($CFG->allowcategorythemes)) {
            $themes = get_list_of_themes();
            $theme_options = ['' => get_string('forceno')];
            foreach ($themes as $key => $theme) {
                if (empty($theme->hidefromselector)) {
                    $theme_options[$key] = get_string('pluginname', 'theme_' . $key);
                }
            }
            $mform->addElement('select', 'theme', get_string('categorytheme', 'core'), $theme_options);
            $mform->addHelpButton('theme', 'categorytheme', 'core');
        }
        
        // Action buttons
        $this->add_action_buttons(true, $editing ? get_string('savechanges') : get_string('createcategory', 'core'));
    }
    
    public function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        // Check for duplicate category names within the same parent
        $sql = "SELECT id FROM {course_categories} WHERE name = :name AND parent = :parent";
        $params = ['name' => $data['name'], 'parent' => $data['parent']];
        
        // If editing, exclude the current category from the check
        if (!empty($data['id'])) {
            $sql .= " AND id != :id";
            $params['id'] = $data['id'];
        }
        
        if ($DB->record_exists_sql($sql, $params)) {
            $errors['name'] = get_string('categoryduplicate', 'core');
        }
        
        return $errors;
    }
}

// Create form instance
$form = new category_form(null, [
    'category' => $category,
    'context' => $context
]);

// Set default data
if ($category) {
    // Prepare file areas
    $draftitemid = file_get_submitted_draft_itemid('categoryimage');
    file_prepare_draft_area($draftitemid, $context->id, 'coursecat', 'description', $category->id,
        ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]);
    
    $category->categoryimage = $draftitemid;
    
    // Prepare description editor
    $category->description_editor = [
        'text' => $category->description,
        'format' => $category->descriptionformat
    ];
    
    $form->set_data($category);
} else if ($parent) {
    $form->set_data(['parent' => $parent]);
}

$form->set_data(['modal' => $modal]);

// Handle form submission
if ($form->is_cancelled()) {
    if ($modal) {
        echo '<script>window.parent.location.reload();</script>';
        die();
    } else {
        redirect(new moodle_url('/local/localcustomadmin/categorias.php'));
    }
} else if ($data = $form->get_data()) {
    
    try {
        if ($editing) {
            // Update existing category
            $category = new stdClass();
            $category->id = $data->id;
            $category->name = $data->name;
            $category->parent = $data->parent;
            $category->description = $data->description_editor['text'];
            $category->descriptionformat = $data->description_editor['format'];
            
            if (isset($data->theme)) {
                $category->theme = $data->theme;
            }
            
            $category->timemodified = time();
            
            $DB->update_record('course_categories', $category);
            
            // Handle file uploads
            if (isset($data->categoryimage)) {
                file_save_draft_area_files($data->categoryimage, $context->id, 'coursecat', 'description', 
                    $category->id, ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]);
            }
            
            // Clear cache
            cache_helper::purge_by_event('changesincoursecat');
            
            $message = get_string('categoryupdated', 'core');
            
        } else {
            // Create new category
            $category = new stdClass();
            $category->name = $data->name;
            $category->parent = $data->parent;
            $category->description = $data->description_editor['text'];
            $category->descriptionformat = $data->description_editor['format'];
            
            if (isset($data->theme)) {
                $category->theme = $data->theme;
            }
            
            $category->timecreated = time();
            $category->timemodified = time();
            $category->sortorder = 0; // Will be set properly by fix_course_sortorder()
            
            // Get the correct path and depth
            if ($category->parent) {
                $parent_category = $DB->get_record('course_categories', ['id' => $category->parent], 'path, depth', MUST_EXIST);
                $category->path = $parent_category->path;
                $category->depth = $parent_category->depth + 1;
            } else {
                $category->path = '';
                $category->depth = 1;
            }
            
            $category->id = $DB->insert_record('course_categories', $category);
            
            // Fix the path now that we have the ID
            $category->path = $category->path . '/' . $category->id;
            $DB->update_record('course_categories', $category);
            
            // Handle file uploads
            if (isset($data->categoryimage)) {
                file_save_draft_area_files($data->categoryimage, $context->id, 'coursecat', 'description', 
                    $category->id, ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]);
            }
            
            // Fix sort order
            fix_course_sortorder();
            
            // Clear cache
            cache_helper::purge_by_event('changesincoursecat');
            
            $message = get_string('categorycreated', 'core');
        }
        
        if ($modal) {
            // If modal, return success message
            echo '<div class="alert alert-success text-center">';
            echo '<i class="fas fa-check-circle me-2"></i>' . $message;
            echo '</div>';
            echo '<div class="text-center mt-3">';
            echo '<button type="button" class="btn btn-primary" onclick="window.parent.location.reload();">';
            echo '<i class="fas fa-sync-alt me-2"></i>Refresh Page';
            echo '</button>';
            echo '</div>';
            die();
        } else {
            redirect(new moodle_url('/local/localcustomadmin/categorias.php'), $message);
        }
        
    } catch (Exception $e) {
        $error_message = 'Erro ao salvar categoria: ' . $e->getMessage();
        
        if ($modal) {
            echo '<div class="alert alert-danger text-center">';
            echo '<i class="fas fa-exclamation-triangle me-2"></i>' . $error_message;
            echo '</div>';
            echo '<div class="text-center mt-3">';
            echo '<button type="button" class="btn btn-secondary" onclick="window.location.reload();">';
            echo '<i class="fas fa-redo me-2"></i>Try Again';
            echo '</button>';
            echo '</div>';
        } else {
            redirect(new moodle_url('/local/localcustomadmin/categorias.php'), $error_message, null, \core\output\notification::NOTIFY_ERROR);
        }
    }
}

// Output the page
echo $OUTPUT->header();

if ($modal) {
    echo '<div class="modal-body p-3">';
}

echo '<div class="category-form-container">';

if ($editing) {
    echo '<h3><i class="fas fa-edit me-2"></i>' . get_string('edit_category', 'local_localcustomadmin') . ': ' . format_string($category->name) . '</h3>';
} else {
    echo '<h3><i class="fas fa-plus-circle me-2"></i>' . get_string('add_category', 'local_localcustomadmin') . '</h3>';
}

$form->display();

echo '</div>';

if ($modal) {
    echo '</div>';
}

echo $OUTPUT->footer();