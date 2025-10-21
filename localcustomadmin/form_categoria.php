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

// Add elegant form styles
echo '<style>
.elegant-form-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.elegant-form-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem 2rem;
    border-radius: 20px;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.elegant-form-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.1\'%3E%3Ccircle cx=\'30\' cy=\'30\' r=\'3\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.elegant-form-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.elegant-form-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.elegant-form-subtitle {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
}

.elegant-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.elegant-back-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    color: white;
}

.elegant-form-content {
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    padding: 2rem;
}

.elegant-form-inner {
    padding: 0;
}
</style>';

if ($modal) {
    echo '<div class="modal-body p-0">';
    echo '<div class="elegant-form-container">';
} else {
    echo '<div class="elegant-form-container">';
    
    // Elegant Header
    echo '<div class="elegant-form-header">';
    echo '<div class="elegant-form-header-content">';
    
    // Title section
    echo '<div class="elegant-form-header-text">';
    if ($editing) {
        echo '<h1 class="elegant-form-title">';
        echo '<i class="fas fa-edit"></i>';
        echo 'Editar Categoria';
        echo '</h1>';
        echo '<p class="elegant-form-subtitle">Atualize as informações da categoria: ' . format_string($category->name) . '</p>';
    } else {
        echo '<h1 class="elegant-form-title">';
        echo '<i class="fas fa-plus-circle"></i>';
        echo 'Nova Categoria';
        echo '</h1>';
        echo '<p class="elegant-form-subtitle">Crie uma nova categoria para organizar seus cursos</p>';
    }
    echo '</div>';
    
    // Back button
    echo '<div class="elegant-form-header-actions">';
    $back_url = new moodle_url('/local/localcustomadmin/categorias.php');
    echo '<a href="' . $back_url . '" class="elegant-back-btn">';
    echo '<i class="fas fa-arrow-left"></i>';
    echo '<span>Voltar</span>';
    echo '</a>';
    echo '</div>';
    
    echo '</div>'; // End elegant-form-header-content
    echo '</div>'; // End elegant-form-header
}

echo '<div class="elegant-form-content">';
echo '<div class="elegant-form-inner">';

// Tab Navigation with elegant design
echo '<div class="elegant-tabs-container mb-4">';
echo '<ul class="elegant-tabs" role="tablist">';

echo '<li class="elegant-tab-item" role="presentation">';
echo '<a class="elegant-tab-link ' . ($tab !== 'pricing' ? 'active' : '') . '" id="general-tab" data-bs-toggle="tab" data-bs-target="#general-tab-content" role="tab" aria-controls="general-tab-content" ' . ($tab !== 'pricing' ? 'aria-selected="true"' : 'aria-selected="false"') . '>';
echo '<div class="tab-icon"><i class="fas fa-info-circle"></i></div>';
echo '<div class="tab-content-wrapper">';
echo '<div class="tab-title">Informações Gerais</div>';
echo '<div class="tab-subtitle">Nome, descrição e configurações básicas</div>';
echo '</div>';
echo '</a>';
echo '</li>';

echo '<li class="elegant-tab-item" role="presentation">';
$pricing_disabled = !$editing ? 'disabled' : '';
$pricing_class = !$editing ? 'disabled' : '';
echo '<a class="elegant-tab-link ' . ($tab === 'pricing' ? 'active' : '') . ' ' . $pricing_class . '" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing-tab-content" role="tab" aria-controls="pricing-tab-content" ' . $pricing_disabled . ' ' . ($tab === 'pricing' ? 'aria-selected="true"' : 'aria-selected="false"') . '>';
echo '<div class="tab-icon"><i class="fas fa-tag"></i></div>';
echo '<div class="tab-content-wrapper">';
echo '<div class="tab-title">Gestão de Preços</div>';
echo '<div class="tab-subtitle">Configure preços e promoções</div>';
echo '</div>';
echo '</a>';
echo '</li>';

echo '</ul>';
echo '</div>';

// Tab Content with elegant styling
echo '<div class="elegant-tab-content">';

// General Tab
echo '<div class="elegant-tab-pane ' . ($tab !== 'pricing' ? 'active' : '') . '" id="general-tab-content" role="tabpanel" aria-labelledby="general-tab">';
echo '<div class="elegant-form-section">';
echo '<div class="form-section-header">';
echo '<h4><i class="fas fa-cog me-2"></i>Configurações da Categoria</h4>';
echo '<p class="form-section-subtitle">Configure as informações básicas desta categoria</p>';
echo '</div>';
$form->display();
echo '</div>';
echo '</div>';

// Pricing Tab
echo '<div class="elegant-tab-pane ' . ($tab === 'pricing' ? 'active' : '') . '" id="pricing-tab-content" role="tabpanel" aria-labelledby="pricing-tab">';

if ($editing) {
    echo '<div class="elegant-form-section">';
    echo '<div class="form-section-header">';
    echo '<h4><i class="fas fa-chart-line me-2"></i>Gestão de Preços</h4>';
    echo '<p class="form-section-subtitle">Configure preços, promoções e condições de pagamento</p>';
    echo '</div>';
    echo render_pricing_tab($category->id);
    echo '</div>';
} else {
    echo '<div class="elegant-info-card">';
    echo '<div class="info-card-icon">';
    echo '<i class="fas fa-info-circle"></i>';
    echo '</div>';
    echo '<div class="info-card-content">';
    echo '<h5>Primeiro, crie a categoria</h5>';
    echo '<p>A gestão de preços estará disponível após salvar as informações gerais da categoria.</p>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '</div>'; // End elegant-tab-content

echo '</div>'; // End elegant-form-inner
echo '</div>'; // End elegant-form-content

// Add JavaScript for enhanced interactions
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Initialize elegant form interactions
    initElegantForm();
});

function initElegantForm() {
    // Enhanced tab functionality
    initElegantTabs();
    
    // Form field enhancements
    enhanceFormFields();
    
    // Add loading states for form submission
    enhanceFormSubmission();
}

function initElegantTabs() {
    const tabLinks = document.querySelectorAll(".elegant-tab-link");
    const tabPanes = document.querySelectorAll(".elegant-tab-pane");
    
    tabLinks.forEach(link => {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            
            if (this.classList.contains("disabled")) {
                return;
            }
            
            // Remove active class from all tabs
            tabLinks.forEach(tab => tab.classList.remove("active"));
            tabPanes.forEach(pane => pane.classList.remove("active"));
            
            // Add active class to clicked tab
            this.classList.add("active");
            
            // Show corresponding pane
            const targetId = this.getAttribute("data-bs-target");
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add("active");
            }
            
            // Add ripple effect
            addRippleEffect(this, e);
        });
    });
}

function enhanceFormFields() {
    // Add focus effects to form fields
    const formFields = document.querySelectorAll("input[type=\"text\"], select, textarea");
    
    formFields.forEach(field => {
        field.addEventListener("focus", function() {
            this.closest(".fitem").classList.add("focused");
        });
        
        field.addEventListener("blur", function() {
            this.closest(".fitem").classList.remove("focused");
        });
        
        // Add validation visual feedback
        field.addEventListener("input", function() {
            if (this.checkValidity()) {
                this.classList.remove("error");
                this.classList.add("valid");
            } else {
                this.classList.remove("valid");
                if (this.value.length > 0) {
                    this.classList.add("error");
                }
            }
        });
    });
}

function enhanceFormSubmission() {
    const forms = document.querySelectorAll("form");
    
    forms.forEach(form => {
        form.addEventListener("submit", function() {
            const submitBtn = this.querySelector("input[type=\"submit\"]");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.value = "Salvando...";
                submitBtn.style.background = "#9ca3af";
                
                // Add loading spinner
                const spinner = document.createElement("i");
                spinner.className = "fas fa-spinner fa-spin";
                spinner.style.marginRight = "8px";
                submitBtn.parentNode.insertBefore(spinner, submitBtn);
            }
        });
    });
}

function addRippleEffect(element, event) {
    const ripple = document.createElement("span");
    const rect = element.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.className = "elegant-ripple";
    ripple.style.cssText = `
        position: absolute;
        border-radius: 50%;
        background: rgba(99, 102, 241, 0.2);
        transform: scale(0);
        animation: ripple-animation 0.6s cubic-bezier(0.4, 0.0, 0.2, 1);
        pointer-events: none;
        width: ${size}px;
        height: ${size}px;
        left: ${x}px;
        top: ${y}px;
    `;
    
    element.appendChild(ripple);
    
    setTimeout(() => {
        ripple.remove();
    }, 600);
}

// Add CSS for ripple animation
const style = document.createElement("style");
style.textContent = `
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .fitem.focused {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
    }
    
    .fitem input.valid,
    .fitem select.valid,
    .fitem textarea.valid {
        border-color: #10b981 !important;
    }
    
    .fitem input.error,
    .fitem select.error,
    .fitem textarea.error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
`;
document.head.appendChild(style);
</script>';

echo '</div>'; // End elegant-form-container

if ($modal) {
    echo '</div>'; // End modal-body
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
    
    // Elegant Pricing Section
    $html .= '<div class="elegant-pricing-section">';
    
    // Pricing Header with gradient
    $html .= '<div class="pricing-header">';
    $html .= '<div class="pricing-header-content">';
    $html .= '<div class="pricing-title">';
    $html .= '<i class="fas fa-tags mr-2"></i>';
    $html .= '<h3>' . get_string('category_prices', 'local_localcustomadmin') . '</h3>';
    $html .= '</div>';
    $html .= '<button type="button" class="btn-elegant btn-primary" id="btn-add-price" data-bs-toggle="modal" data-bs-target="#priceModal">';
    $html .= '<span class="btn-icon"><i class="fas fa-plus"></i></span>';
    $html .= '<span class="btn-text">' . get_string('add_price', 'local_localcustomadmin') . '</span>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Elegant Table Container
    $html .= '<div class="elegant-table-wrapper">';
    $html .= '<div class="elegant-table-container">';
    $html .= '<table class="elegant-prices-table" id="prices-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th><i class="fas fa-tag mr-2"></i>' . get_string('price_name', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-dollar-sign mr-2"></i>' . get_string('price', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-calendar-plus mr-2"></i>' . get_string('validity_start', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-calendar-times mr-2"></i>' . get_string('validity_end', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-percentage mr-2"></i>' . get_string('promotional', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-graduation-cap mr-2"></i>' . get_string('enrollment_fee', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-credit-card mr-2"></i>' . get_string('installments', 'local_localcustomadmin') . '</th>';
    $html .= '<th><i class="fas fa-toggle-on mr-2"></i>' . get_string('status', 'local_localcustomadmin') . '</th>';
    $html .= '<th class="actions-col"><i class="fas fa-cog mr-2"></i>' . get_string('actions', 'local_localcustomadmin') . '</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody id="prices-tbody">';
    $html .= '<tr><td colspan="9" class="loading-row"><i class="fas fa-spinner fa-spin mr-2"></i>Loading prices...</td></tr>';
    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
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
    $html .= '<div class="modal-dialog modal-lg">';
    $html .= '<div class="modal-content">';
    
    $html .= '<div class="modal-header">';
    $html .= '<h5 class="modal-title" id="priceModalLabel">' . get_string('add_price', 'local_localcustomadmin') . '</h5>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">';
    $html .= '<i class="fa fa-times" aria-hidden="true"></i>';
    $html .= '</button>';
    $html .= '</div>';
    
    $html .= '<div class="modal-body">';
    
    // Error/Success alert container
    $html .= '<div id="price-alert" class="alert alert-dismissible fade" role="alert" style="display: none;">';
    $html .= '<span id="price-alert-message"></span>';
    $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    $html .= '</div>';
    
    $html .= '<form id="price-form">';
    
    $html .= '<input type="hidden" id="category_id" value="' . $category_id . '">';
    $html .= '<input type="hidden" id="price_id" value="">';
    
    // Price Name
    $html .= '<div class="mb-3">';
    $html .= '<label for="price-name" class="form-label">' . get_string('price_name', 'local_localcustomadmin') . ' *</label>';
    $html .= '<input type="text" class="form-control" id="price-name" name="name" maxlength="255" required>';
    $html .= '<small class="form-text text-muted">Ex: Regular Price, Summer Promotion, etc.</small>';
    $html .= '</div>';
    
    // Price Value
    $html .= '<div class="mb-3">';
    $html .= '<label for="price-value" class="form-label">' . get_string('price', 'local_localcustomadmin') . ' (R$) *</label>';
    $html .= '<input type="number" class="form-control" id="price-value" name="price" step="0.01" min="0" required>';
    $html .= '</div>';
    
    // Start Date
    $html .= '<div class="mb-3">';
    $html .= '<label for="validity-start" class="form-label">' . get_string('validity_start', 'local_localcustomadmin') . ' *</label>';
    $html .= '<input type="datetime-local" class="form-control" id="validity-start" name="startdate" required>';
    $html .= '</div>';
    
    // End Date
    $html .= '<div class="mb-3">';
    $html .= '<label for="validity-end" class="form-label">' . get_string('validity_end', 'local_localcustomadmin') . '</label>';
    $html .= '<input type="datetime-local" class="form-control" id="validity-end" name="enddate">';
    $html .= '<small class="form-text text-muted">Leave empty for indefinite duration</small>';
    $html .= '</div>';
    
    // Row 1: Promotional, Enrollment Fee
    $html .= '<div class="row">';
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="form-check">';
    $html .= '<input type="checkbox" class="form-check-input" id="is-promotional" name="ispromotional" value="1">';
    $html .= '<label class="form-check-label" for="is-promotional">' . get_string('promotional', 'local_localcustomadmin') . '</label>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-6 mb-3">';
    $html .= '<div class="form-check">';
    $html .= '<input type="checkbox" class="form-check-input" id="is-enrollment-fee" name="isenrollmentfee" value="1">';
    $html .= '<label class="form-check-label" for="is-enrollment-fee">' . get_string('enrollment_fee', 'local_localcustomadmin') . '</label>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Row 2: Scheduled Task, Installments, Status
    $html .= '<div class="row">';
    $html .= '<div class="col-md-4 mb-3">';
    $html .= '<div class="form-check">';
    $html .= '<input type="checkbox" class="form-check-input" id="scheduled-task" name="scheduledtask" value="1">';
    $html .= '<label class="form-check-label" for="scheduled-task">' . get_string('scheduled_task', 'local_localcustomadmin') . '</label>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="col-md-4 mb-3">';
    $html .= '<label for="installments" class="form-label">' . get_string('installments', 'local_localcustomadmin') . '</label>';
    $html .= '<input type="number" class="form-control" id="installments" name="installments" min="0" value="0">';
    $html .= '</div>';
    
    $html .= '<div class="col-md-4 mb-3">';
    $html .= '<label for="price-status" class="form-label">' . get_string('status', 'local_localcustomadmin') . '</label>';
    $html .= '<select class="form-select" id="price-status" name="status">';
    $html .= '<option value="1">' . get_string('active', 'local_localcustomadmin') . '</option>';
    $html .= '<option value="0">' . get_string('inactive', 'local_localcustomadmin') . '</option>';
    $html .= '</select>';
    $html .= '</div>';
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
    
    // Add inline script to fix z-index and ensure modal is in correct position
    $html .= '<script>';
    $html .= 'document.addEventListener("DOMContentLoaded", function() {';
    $html .= '    var priceModal = document.getElementById("priceModal");';
    $html .= '    if (priceModal && !priceModal.dataset.moved) {';
    $html .= '        document.body.appendChild(priceModal);';
    $html .= '        priceModal.dataset.moved = "true";';
    $html .= '        priceModal.style.zIndex = "1060";';
    $html .= '    }';
    $html .= '    priceModal.addEventListener("show.bs.modal", function() {';
    $html .= '        var backdrop = document.querySelector(".modal-backdrop");';
    $html .= '        if (backdrop) {';
    $html .= '            backdrop.style.zIndex = "1050";';
    $html .= '        }';
    $html .= '        setTimeout(function() {';
    $html .= '            priceModal.style.zIndex = "1060";';
    $html .= '            var dialog = priceModal.querySelector(".modal-dialog");';
    $html .= '            if (dialog) dialog.style.zIndex = "1061";';
    $html .= '            backdrop = document.querySelector(".modal-backdrop");';
    $html .= '            if (backdrop) backdrop.style.zIndex = "1050";';
    $html .= '        }, 50);';
    $html .= '    });';
    $html .= '});';
    $html .= '</script>';
    
    // Load AMD module for price management
    $PAGE->requires->js_call_amd('local_localcustomadmin/price_manager', 'init', [$category_id]);
    
    return $html;
}