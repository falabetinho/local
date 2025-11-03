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
 * Edit course page with pricing integration.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/classes/course_manager.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$id = optional_param('id', 0, PARAM_INT); // Course ID for editing
$tab = optional_param('tab', 'general', PARAM_ALPHA); // Current tab (general or pricing)

// Set up page
$PAGE->set_pagelayout('base');
$PAGE->set_url(new moodle_url('/local/localcustomadmin/edit_curso.php', 
    ['id' => $id, 'tab' => $tab]));
$PAGE->set_context($context);

// Determine if we're editing or creating
$editing = !empty($id);
$course = null;

if ($editing) {
    $course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editcourse', 'local_localcustomadmin'));
} else {
    $PAGE->set_title(get_string('addcourse', 'local_localcustomadmin'));
}

// Add navigation breadcrumb
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/cursos.php'));
if ($editing) {
    $PAGE->navbar->add(get_string('editcourse', 'local_localcustomadmin'));
} else {
    $PAGE->navbar->add(get_string('addcourse', 'local_localcustomadmin'));
}

/**
 * Course form class
 */
class course_form extends moodleform {
    
    public function definition() {
        global $DB;
        
        $mform = $this->_form;
        $customdata = $this->_customdata;
        
        $editing = !empty($customdata['course']);
        $course = $editing ? $customdata['course'] : null;
        
        // Hidden fields
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        
        // Course fullname
        $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 254), 'maxlength', 254, 'client');
        
        // Course shortname
        $mform->addElement('text', 'shortname', get_string('shortname'), 'maxlength="100" size="50"');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        
        // Course category
        $categories = $DB->get_records_menu('course_categories', null, 'sortorder', 'id, name');
        $categoryoptions = [];
        foreach ($categories as $catid => $catname) {
            $categoryoptions[$catid] = format_string($catname);
        }
        $mform->addElement('select', 'category', get_string('category'), $categoryoptions);
        $mform->setType('category', PARAM_INT);
        $mform->addRule('category', get_string('required'), 'required', null, 'client');
        
        // Course description
        $mform->addElement('editor', 'summary_editor', get_string('summary'), 
            ['rows' => 10], ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $customdata['context']]);
        $mform->setType('summary_editor', PARAM_RAW);
        
        // Course format
        $formats = get_plugin_list('format');
        $formatselectoptions = [];
        foreach ($formats as $format => $path) {
            $formatname = get_string('pluginname', 'format_' . $format);
            $formatselectoptions[$format] = $formatname;
        }
        $mform->addElement('select', 'format', get_string('format'), $formatselectoptions);
        $mform->setDefault('format', 'topics');
        
        // Visibility
        $mform->addElement('advcheckbox', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);
        
        // Start date
        $mform->addElement('date_selector', 'startdate', get_string('startdate'), array('optional' => true));
        $mform->setDefault('startdate', time());
        
        // Action buttons
        $this->add_action_buttons(true, get_string('savechanges'));
    }
    
    /**
     * Validation
     */
    public function validation($data, $files) {
        global $DB;
        
        $errors = parent::validation($data, $files);
        
        // Validate shortname uniqueness (except for current course)
        $shortname = trim($data['shortname']);
        if ($data['id']) {
            $existing = $DB->get_records_select('course', "shortname = ? AND id != ?", array($shortname, $data['id']));
        } else {
            $existing = $DB->get_records_select('course', "shortname = ?", array($shortname));
        }
        
        if (!empty($existing)) {
            $errors['shortname'] = get_string('shortnametaken', 'error');
        }
        
        return $errors;
    }
}

// Prepare form data
$customdata = array(
    'course' => $course,
    'context' => $context
);

// Create form
$form = new course_form(null, $customdata);

// Handle form submission
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/localcustomadmin/cursos.php'));
} else if ($formdata = $form->get_data()) {
    // Process form data
    if ($editing) {
        // Update existing course
        $oldcategory = $course->category;
        $formdata->id = $id;
        
        // Use native Moodle function to update course
        update_course($formdata);

        // Check if category changed - if so, recreate enrollments with new category pricing
        if ($oldcategory !== $formdata->category) {
            try {
                \local_localcustomadmin\course_manager::handle_category_change(
                    $id,
                    $formdata->category,
                    $oldcategory
                );
            } catch (Exception $e) {
                debugging('Error handling category change: ' . $e->getMessage());
            }
        }

        // Permanece na página atual após salvar
        redirect(
            new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $id, 'tab' => $tab]),
            get_string('courseupdated', 'local_localcustomadmin'),
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Create new course
        $formdata->summaryformat = FORMAT_HTML;
        
        // Use native Moodle function to create course
        $course = create_course($formdata);
        $courseid = $course->id;

        // Permanece na página de edição do novo curso
        redirect(
            new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $courseid, 'tab' => $tab]),
            get_string('coursecreated', 'local_localcustomadmin'),
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

// Set form data if editing
if ($editing && $course) {
    $form->set_data($course);
}

/**
 * Render the pricing tab content
 */
function render_pricing_tab($courseid) {
    global $DB;

    $html = '';
    $html .= '<div class="alert alert-info">';
    $html .= '<i class="fas fa-info-circle mr-2"></i>';
    $html .= get_string('course_enrolments_info', 'local_localcustomadmin');
    $html .= '</div>';

    // Recupera todos os enrolments, inclusive customstatus, diretamente da tabela
    $enrolments = $DB->get_records('enrol', ['courseid' => $courseid]);

    $totalenrolments = count($enrolments);
    $activeenrolments = 0;
    $totalrevenue = 0;

    // Count total students
    $totalstudents = 0;
    $enrolids = [];
    foreach ($enrolments as $enrol) {
        $enrolids[] = $enrol->id;
    }

    if (!empty($enrolids)) {
        list($insql, $params) = $DB->get_in_or_equal($enrolids);
        $totalstudents = $DB->count_records_select('user_enrolments', "enrolid $insql", $params);
    }

    foreach ($enrolments as $enrol) {
        if ($enrol->status == ENROL_INSTANCE_ENABLED) {
            $activeenrolments++;
            if (isset($enrol->cost) && $enrol->cost > 0) {
                $totalrevenue += $enrol->cost;
            }
        }
    }
    
    $html .= '<div class="row mb-4">';
    
    // Card 1: Total Methods
    $html .= '<div class="col-md-3">';
    $html .= '<div class="elegant-stat-card">';
    $html .= '<div class="stat-icon bg-primary"><i class="fas fa-key"></i></div>';
    $html .= '<div class="stat-content">';
    $html .= '<div class="stat-value">' . $totalenrolments . '</div>';
    $html .= '<div class="stat-label">Métodos de Matrícula</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Card 2: Active Methods
    $html .= '<div class="col-md-3">';
    $html .= '<div class="elegant-stat-card">';
    $html .= '<div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>';
    $html .= '<div class="stat-content">';
    $html .= '<div class="stat-value">' . $activeenrolments . '</div>';
    $html .= '<div class="stat-label">Métodos Ativos</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Card 3: Total Students
    $html .= '<div class="col-md-3">';
    $html .= '<div class="elegant-stat-card">';
    $html .= '<div class="stat-icon bg-info"><i class="fas fa-users"></i></div>';
    $html .= '<div class="stat-content">';
    $html .= '<div class="stat-value">' . $totalstudents . '</div>';
    $html .= '<div class="stat-label">Alunos Matriculados</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Card 4: Expected Revenue
    $html .= '<div class="col-md-3">';
    $html .= '<div class="elegant-stat-card">';
    $html .= '<div class="stat-icon bg-warning"><i class="fas fa-dollar-sign"></i></div>';
    $html .= '<div class="stat-content">';
    $html .= '<div class="stat-value">R$ ' . number_format($totalrevenue, 2, ',', '.') . '</div>';
    $html .= '<div class="stat-label">Receita Esperada</div>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    // Link to manage enrollment prices
    $html .= '<div class="mb-3">';
    $html .= '<a href="' . new moodle_url('/local/localcustomadmin/manage_enrol_prices.php', ['id' => $courseid]) . '" class="btn btn-primary">';
    $html .= '<i class="fas fa-dollar-sign"></i> ' . get_string('manage_enrol_prices', 'local_localcustomadmin');
    $html .= '</a>';
    $html .= '</div>';
    
    if (empty($enrolments)) {
        $html .= '<div class="elegant-empty-state">';
        $html .= '<div class="empty-icon"><i class="fas fa-user-slash"></i></div>';
        $html .= '<h4 class="empty-title">Nenhum método de matrícula</h4>';
        $html .= '<p class="empty-description">Use o botão acima para importar preços da categoria e criar métodos de matrícula</p>';
        $html .= '</div>';
        return $html;
    }

    $html .= '<div class="elegant-table-wrapper">';
    $html .= '<table class="elegant-enrolments-table">';
    $html .= '<thead>';
    $html .= '<tr>';
    $html .= '<th><i class="fas fa-key mr-2"></i>Método de Matrícula</th>';
    $html .= '<th><i class="fas fa-toggle-on mr-2"></i>Status</th>';
    $html .= '<th><i class="fas fa-dollar-sign mr-2"></i>Preço</th>';
    $html .= '<th><i class="fas fa-users mr-2"></i>Alunos</th>';
    $html .= '<th><i class="fas fa-calendar mr-2"></i>Vigência</th>';
    $html .= '<th class="actions-col"><i class="fas fa-cog mr-2"></i>Ações</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';

    foreach ($enrolments as $enrolment) {
        // Exibe todos os métodos, inclusive customstatus
        $methodname = $enrolment->enrol === 'customstatus'
            ? 'Custom Status'
            : (function_exists('enrol_get_plugin') && enrol_get_plugin($enrolment->enrol)
                ? enrol_get_plugin($enrolment->enrol)->get_instance_name($enrolment)
                : ucfirst($enrolment->enrol));

        $price = '-';
        if (isset($enrolment->cost) && $enrolment->cost > 0) {
            $price = 'R$ ' . number_format($enrolment->cost, 2, ',', '.');
            if (isset($enrolment->customint4) && $enrolment->customint4 > 1) {
                $price .= ' <small class="text-muted">(' . $enrolment->customint4 . 'x)</small>';
            }
        }

        $isactive = $enrolment->status == ENROL_INSTANCE_ENABLED;
        $statusclass = $isactive ? 'status-active' : 'status-inactive';
        $statustext = $isactive ? get_string('active') : get_string('inactive');
        $statusicon = $isactive ? 'fa-check-circle' : 'fa-times-circle';

        $enrolledcount = $DB->count_records('user_enrolments', ['enrolid' => $enrolment->id]);

        $validity = '';
        if (!empty($enrolment->enrolstartdate) && $enrolment->enrolstartdate > 0) {
            $validity = userdate($enrolment->enrolstartdate, '%d/%m/%Y');
            if (!empty($enrolment->enrolenddate) && $enrolment->enrolenddate > 0) {
                $validity .= ' - ' . userdate($enrolment->enrolenddate, '%d/%m/%Y');
            } else {
                $validity .= ' - ∞';
            }
        } else {
            $validity = '<span class="text-muted">Sem limite</span>';
        }

        $html .= '<tr class="enrolment-row">';
        $html .= '<td class="method-cell"><div class="method-name"><i class="fas fa-key method-icon mr-2"></i><strong>' . htmlspecialchars($methodname) . '</strong></div></td>';
        $html .= '<td class="status-cell"><span class="status-badge ' . $statusclass . '"><i class="fas ' . $statusicon . ' mr-2"></i>' . $statustext . '</span></td>';
        $html .= '<td class="price-cell">' . ($price !== '-' ? '<span class="price-value">' . $price . '</span>' : '<span class="text-muted">-</span>') . '</td>';
        $html .= '<td class="students-cell"><span class="badge badge-info">' . $enrolledcount . ' aluno(s)</span></td>';
        $html .= '<td class="validity-cell"><small>' . $validity . '</small></td>';
        $html .= '<td class="actions-cell">';
        $html .= '<a href="' . new moodle_url('/user/index.php', ['id' => $courseid]) . '" class="btn-action btn-action-users" title="Ver Alunos"><i class="fas fa-users"></i></a>';
        $editurl = new moodle_url('/enrol/editinstance.php', ['courseid' => $courseid, 'id' => $enrolment->id, 'type' => $enrolment->enrol]);
        $html .= '<a href="' . $editurl . '" class="btn-action btn-action-edit" title="Editar"><i class="fas fa-edit"></i></a>';
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody>';
    $html .= '</table>';
    $html .= '</div>';
    
    return $html;
}

// Inclua o CSS centralizado antes do header
$PAGE->requires->css(new moodle_url('/local/localcustomadmin/styles.css'));

// Output page
echo $OUTPUT->header();

// Back button - First element
echo '<div class="back-button-container">';
$back_url = new moodle_url('/local/localcustomadmin/cursos.php');
echo '<a href="' . $back_url . '" class="btn-back">';
echo '<i class="fas fa-arrow-left"></i>';
echo 'Voltar';
echo '</a>';
echo '</div>';

echo '<div class="elegant-form-container">';

// Elegant Form Header
echo '<div class="elegant-form-header">';
echo '<div class="elegant-form-header-content">';

// Title section
echo '<div class="elegant-form-header-text">';
if ($courseid) {
    echo '<h1 class="elegant-form-title">';
    echo '<i class="fas fa-edit"></i>';
    echo 'Editar Curso';
    echo '</h1>';
    echo '<p class="elegant-form-subtitle">Atualize as informações do curso: ' . format_string($course->fullname) . '</p>';
} else {
    echo '<h1 class="elegant-form-title">';
    echo '<i class="fas fa-plus-circle"></i>';
    echo 'Novo Curso';
    echo '</h1>';
    echo '<p class="elegant-form-subtitle">Crie um novo curso no sistema</p>';
}
echo '</div>';

echo '</div>'; // End elegant-form-header-content
echo '</div>'; // End elegant-form-header

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
echo '<div class="tab-icon"><i class="fas fa-dollar-sign"></i></div>';
echo '<div class="tab-content-wrapper">';
echo '<div class="tab-title">Métodos de Matrícula</div>';
echo '<div class="tab-subtitle">Configure preços e inscrições</div>';
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
echo '<h4><i class="fas fa-graduation-cap me-2"></i>Configurações do Curso</h4>';
echo '<p class="form-section-subtitle">Configure as informações básicas deste curso</p>';
echo '</div>';
$form->display();
echo '</div>';
echo '</div>';

// Pricing Tab
echo '<div class="elegant-tab-pane ' . ($tab === 'pricing' ? 'active' : '') . '" id="pricing-tab-content" role="tabpanel" aria-labelledby="pricing-tab">';

if ($editing) {
    echo '<div class="elegant-form-section">';
    echo '<div class="form-section-header">';
    echo '<h4><i class="fas fa-dollar-sign me-2"></i>Gestão de Matrículas</h4>';
    echo '<p class="form-section-subtitle">Visualize e gerencie os métodos de inscrição do curso</p>';
    echo '</div>';
    
    echo render_pricing_tab($id);
    echo '</div>';
} else {
    echo '<div class="elegant-info-card">';
    echo '<div class="info-card-icon">';
    echo '<i class="fas fa-info-circle"></i>';
    echo '</div>';
    echo '<div class="info-card-content">';
    echo '<h5>Primeiro, crie o curso</h5>';
    echo '<p>Os métodos de matrícula estarão disponíveis após salvar as informações gerais do curso.</p>';
    echo '</div>';
    echo '</div>';
}

echo '</div>';

echo '</div>'; // End elegant-tab-content
echo '</div>'; // End elegant-form-inner
echo '</div>'; // End elegant-form-content
echo '</div>'; // End elegant-form-container

// Add JavaScript for tab functionality
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    // Get all tab links
    const tabLinks = document.querySelectorAll(".elegant-tab-link");
    const tabPanes = document.querySelectorAll(".elegant-tab-pane");
    
    tabLinks.forEach(function(tabLink) {
        tabLink.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Check if tab is disabled
            if (this.classList.contains("disabled")) {
                return false;
            }
            
            // Get target tab
            const targetId = this.getAttribute("data-bs-target");
            
            if (!targetId) {
                return;
            }
            
            // Remove active class from all tabs and panes
            tabLinks.forEach(function(link) {
                link.classList.remove("active");
                link.setAttribute("aria-selected", "false");
            });
            
            tabPanes.forEach(function(pane) {
                pane.classList.remove("active");
            });
            
            // Add active class to clicked tab
            this.classList.add("active");
            this.setAttribute("aria-selected", "true");
            
            // Show target pane
            const targetPane = document.querySelector(targetId);
            if (targetPane) {
                targetPane.classList.add("active");
            }
        });
    });
});
</script>';

echo $OUTPUT->footer();
