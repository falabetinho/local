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
require_once(__DIR__ . '/classes/category_price_manager.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$id = optional_param('id', 0, PARAM_INT); // Category ID for editing
$parent = optional_param('parent', 0, PARAM_INT); // Parent category ID for new categories
$modal = optional_param('modal', 0, PARAM_INT); // Is this being displayed in a modal?
$tab = optional_param('tab', 'general', PARAM_ALPHA); // Current tab (general or pricing)

// Set up page
if ($modal) {
    $PAGE->set_pagelayout('popup');
} else {
    $PAGE->set_pagelayout('base');
}

$PAGE->set_url(new moodle_url('/local/localcustomadmin/form_categoria.php', 
    ['id' => $id, 'parent' => $parent, 'modal' => $modal, 'tab' => $tab]));
$PAGE->set_context($context);

// Determine if we're editing or creating
$editing = !empty($id);
$category = null;

if ($editing) {
    $category = $DB->get_record('course_categories', ['id' => $id], '*', MUST_EXIST);
    // Debug: verificar se a categoria foi carregada corretamente
    if (debugging()) {
        debugging('Category loaded: ID=' . $category->id . ', Parent=' . $category->parent . ', Name=' . $category->name);
    }
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
            $current_category_id = null;
            $current_category_path = null;
            
            // Get current category info if editing
            if ($editing && isset($customdata['category'])) {
                $current_category_id = $customdata['category']->id;
                $current_category_path = isset($customdata['category']->path) ? $customdata['category']->path : '';
            }
            
            foreach ($categories as $catid => $catname) {
                // Don't allow a category to be its own parent or child
                $exclude = false;
                
                if ($editing && $current_category_id) {
                    // Exclude self
                    if ($catid == $current_category_id) {
                        $exclude = true;
                    }
                    
                    // Exclude children (categories that have current category in their path)
                    if (!$exclude) {
                        $child_category = $DB->get_record('course_categories', ['id' => $catid], 'path');
                        if ($child_category && $child_category->path && 
                            strpos($child_category->path, '/' . $current_category_id . '/') !== false) {
                            $exclude = true;
                        }
                    }
                }
                
                if (!$exclude) {
                    $parent_options[$catid] = format_string($catname);
                }
            }
        }
        
        $mform->addElement('select', 'parent', get_string('categoryparent', 'local_localcustomadmin'), $parent_options);
        $mform->addHelpButton('parent', 'categoryparent', 'local_localcustomadmin');
        
        // Category description
        $mform->addElement('editor', 'description_editor', get_string('categorydescription', 'local_localcustomadmin'), 
            ['rows' => 10], ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $customdata['context']]);
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'categorydescription', 'local_localcustomadmin');
        
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
            $mform->addElement('select', 'theme', get_string('categorytheme', 'local_localcustomadmin'), $theme_options);
            $mform->addHelpButton('theme', 'categorytheme', 'local_localcustomadmin');
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
            $errors['name'] = get_string('categoryduplicate', 'local_localcustomadmin');
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
$formdata = new stdClass();
$formdata->modal = $modal;

if ($category) {
    // Prepare file areas
    $draftitemid = file_get_submitted_draft_itemid('categoryimage');
    file_prepare_draft_area($draftitemid, $context->id, 'coursecat', 'description', $category->id,
        ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 1]);
    
    // Prepare all form data
    $formdata->id = $category->id;
    $formdata->name = $category->name;
    $formdata->parent = isset($category->parent) ? (int)$category->parent : 0; // Ensure parent is set correctly
    $formdata->categoryimage = $draftitemid;
    $formdata->description_editor = [
        'text' => $category->description,
        'format' => $category->descriptionformat
    ];
    
    // Debug output to verify data
    if (debugging()) {
        debugging('Setting form parent to: ' . $category->parent);
        debugging('Form data parent value: ' . $formdata->parent);
    }
    
} else if ($parent) {
    $formdata->parent = $parent;
}

// Debug final formdata before setting
if (debugging()) {
    debugging('Final formdata parent before set_data: ' . (isset($formdata->parent) ? $formdata->parent : 'NOT SET'));
}

// Set all data at once
$form->set_data($formdata);

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
            
            $message = get_string('categoryupdated', 'local_localcustomadmin');
            
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
            
            $message = get_string('categorycreated', 'local_localcustomadmin');
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

// Add back button
if (!$modal) {
    $back_url = new moodle_url('/local/localcustomadmin/categorias.php');
    echo '<div class="mb-3">';
    echo '<a href="' . $back_url . '" class="btn btn-secondary">';
    echo '<i class="fas fa-arrow-left me-2"></i>' . get_string('back') . ' ' . get_string('categories', 'local_localcustomadmin');
    echo '</a>';
    echo '</div>';
}

if ($editing) {
    echo '<h3><i class="fas fa-edit me-2"></i>' . get_string('edit_category', 'local_localcustomadmin') . ': ' . format_string($category->name) . '</h3>';
} else {
    echo '<h3><i class="fas fa-plus-circle me-2"></i>' . get_string('add_category', 'local_localcustomadmin') . '</h3>';
}

// Tab Navigation
echo '<ul class="nav nav-tabs mb-3" role="tablist">';

echo '<li class="nav-item" role="presentation">';
echo '<button class="nav-link ' . ($tab !== 'pricing' ? 'active' : '') . '" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-content" type="button" role="tab" aria-controls="general-tab-content" ' . ($tab !== 'pricing' ? 'aria-selected="true"' : 'aria-selected="false"') . '>';
echo '<i class="fas fa-info-circle me-2"></i>' . get_string('general') . '</button>';
echo '</li>';

echo '<li class="nav-item" role="presentation">';
$pricing_disabled = !$editing ? 'disabled' : '';
$pricing_class = !$editing ? 'text-muted' : '';
echo '<button class="nav-link ' . ($tab === 'pricing' ? 'active' : '') . ' ' . $pricing_class . '" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing-tab-content" type="button" role="tab" aria-controls="pricing-tab-content" ' . $pricing_disabled . ' ' . ($tab === 'pricing' ? 'aria-selected="true"' : 'aria-selected="false"') . '>';
echo '<i class="fas fa-tag me-2"></i>' . get_string('pricing', 'local_localcustomadmin') . '</button>';
echo '</li>';

echo '</ul>';

// Tab Content
echo '<div class="tab-content">';

// General Tab
echo '<div class="tab-pane fade ' . ($tab !== 'pricing' ? 'show active' : '') . '" id="general-tab-content" role="tabpanel" aria-labelledby="general-tab">';
$form->display();
echo '</div>';

// Pricing Tab
echo '<div class="tab-pane fade ' . ($tab === 'pricing' ? 'show active' : '') . '" id="pricing-tab-content" role="tabpanel" aria-labelledby="pricing-tab">';

if ($editing) {
    // Pricing content will be loaded here
    echo render_pricing_tab($category->id);
} else {
    echo '<div class="alert alert-info">';
    echo '<i class="fas fa-info-circle me-2"></i>' . get_string('create_category_first', 'local_localcustomadmin');
    echo '</div>';
}

echo '</div>';

echo '</div>'; // End tab-content

echo '</div>';

if ($modal) {
    echo '</div>';
    // Add JavaScript to initialize file managers and other Moodle components
    echo '<script>
    // Initialize file managers after modal content is loaded
    $(document).ready(function() {
        // Re-initialize any file managers
        if (window.M && window.M.util && window.M.util.init_filemanager) {
            $(".filemanager").each(function() {
                var options = $(this).data("options");
                if (options) {
                    try {
                        window.M.util.init_filemanager(options);
                    } catch (e) {
                        console.log("File manager init error:", e);
                    }
                }
            });
        }
        
        // Re-initialize form elements
        if (window.M && window.M.core && window.M.core.init_skiplink) {
            window.M.core.init_skiplink();
        }
        
        // Initialize any HTML editors
        if (window.M && window.M.editor_atto) {
            $("textarea[data-fieldtype=editor]").each(function() {
                var id = $(this).attr("id");
                if (id && !$(this).data("editor-initialized")) {
                    try {
                        $(this).data("editor-initialized", true);
                    } catch (e) {
                        console.log("Editor init error:", e);
                    }
                }
            });
        }
        
        // Trigger Moodle form initialization
        if (window.M && window.M.util && window.M.util.js_complete) {
            setTimeout(function() {
                window.M.util.js_complete();
            }, 100);
        }
    });
    </script>';
}

echo $OUTPUT->footer();

/**
 * Render pricing tab content
 * 
 * @param int $category_id
 * @return string HTML content
 */
function render_pricing_tab($category_id) {
    $html = '';
    
    // Pricing Section Header
    $html .= '<div class="pricing-section">';
    $html .= '<div class="d-flex justify-content-between align-items-center mb-3">';
    $html .= '<h4><i class="fas fa-list me-2"></i>' . get_string('category_prices', 'local_localcustomadmin') . '</h4>';
    $html .= '<button type="button" class="btn btn-primary btn-sm" id="btn-add-price" data-bs-toggle="modal" data-bs-target="#priceModal">';
    $html .= '<i class="fas fa-plus me-2"></i>' . get_string('add_price', 'local_localcustomadmin') . '</button>';
    $html .= '</div>';
    
    // Pricing List Table
    $html .= '<div class="table-responsive">';
    $html .= '<table class="table table-striped table-hover" id="prices-table">';
    $html .= '<thead class="table-light">';
    $html .= '<tr>';
    $html .= '<th>' . get_string('price', 'local_localcustomadmin') . '</th>';
    $html .= '<th>' . get_string('validity_start', 'local_localcustomadmin') . '</th>';
    $html .= '<th>' . get_string('validity_end', 'local_localcustomadmin') . '</th>';
    $html .= '<th>' . get_string('status', 'local_localcustomadmin') . '</th>';
    $html .= '<th>' . get_string('actions', 'local_localcustomadmin') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="prices-tbody">';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Modal para adicionar/editar preço
    $html .= get_price_modal_html($category_id);
    
    return $html;
}

/**
 * Get HTML for price management modal
 * 
 * @param int $category_id
 * @return string HTML
 */
function get_price_modal_html($category_id) {
    global $PAGE;
    
    $html = '';
    
    $html .= '<div class="modal fade" id="priceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">';
    $html .= '<div class="modal-dialog">';
    $html .= '<div class="modal-content">';
    
    $html .= '<div class="modal-header">';
    $html .= '<h5 class="modal-title" id="priceModalLabel">' . get_string('add_price', 'local_localcustomadmin') . '</h5>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
    $html .= '</div>';
    
    $html .= '<div class="modal-body">';
    $html .= '<form id="price-form">';
    
    $html .= '<input type="hidden" id="category_id" value="' . $category_id . '">';
    $html .= '<input type="hidden" id="price_id" value="">';
    
    $html .= '<div class="mb-3">';
    $html .= '<label for="price-value" class="form-label">' . get_string('price', 'local_localcustomadmin') . ' *</label>';
    $html .= '<input type="number" class="form-control" id="price-value" name="price" step="0.01" min="0" required>';
    $html .= '</div>';
    
    $html .= '<div class="mb-3">';
    $html .= '<label for="validity-start" class="form-label">' . get_string('validity_start', 'local_localcustomadmin') . ' *</label>';
    $html .= '<input type="datetime-local" class="form-control" id="validity-start" name="validity_start" required>';
    $html .= '</div>';
    
    $html .= '<div class="mb-3">';
    $html .= '<label for="validity-end" class="form-label">' . get_string('validity_end', 'local_localcustomadmin') . '</label>';
    $html .= '<input type="datetime-local" class="form-control" id="validity-end" name="validity_end">';
    $html .= '</div>';
    
    $html .= '<div class="mb-3">';
    $html .= '<label for="price-status" class="form-label">' . get_string('status', 'local_localcustomadmin') . '</label>';
    $html .= '<select class="form-select" id="price-status" name="status">';
    $html .= '<option value="1">' . get_string('active', 'local_localcustomadmin') . '</option>';
    $html .= '<option value="0">' . get_string('inactive', 'local_localcustomadmin') . '</option>';
    $html .= '</select>';
    $html .= '</div>';
    
    $html .= '</form>';
    $html .= '</div>';
    
    $html .= '<div class="modal-footer">';
    $html .= '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . get_string('cancel', 'local_localcustomadmin') . '</button>';
    $html .= '<button type="button" class="btn btn-primary" id="btn-save-price">' . get_string('save', 'local_localcustomadmin') . '</button>';
    $html .= '</div>';
    
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Load AMD module for price management
    $PAGE->requires->js_call_amd('local_localcustomadmin/price_manager', 'init', [$category_id]);
    
    return $html;
}