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
 * Form for creating and editing courses with two tabs: General and Pricing.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Course form class with two tabs
 */
class local_localcustomadmin_course_form extends moodleform {
    
    /**
     * Define the form structure
     *
     * @return void
     */
    public function definition() {
        global $DB, $CFG, $PAGE;
        
        $mform = $this->_form;
        $customdata = $this->_customdata;
        $courseid = $customdata['courseid'] ?? null;
        $course = $customdata['course'] ?? null;
        $categories = $customdata['categories'] ?? [];

        // Tabs header
        $mform->addElement('html', '<div class="local-customadmin-course-tabs">');
        $mform->addElement('html', '<ul class="nav nav-tabs" role="tablist" id="courseTabs">');
        $mform->addElement('html', '<li class="nav-item" role="presentation">');
        $mform->addElement('html', '<a class="nav-link active" id="general-tab" data-toggle="tab" data-target="#general-content" role="tab" aria-controls="general-content" aria-selected="true">' . get_string('general') . '</a>');
        $mform->addElement('html', '</li>');
        $mform->addElement('html', '<li class="nav-item" role="presentation">');
        $mform->addElement('html', '<a class="nav-link" id="pricing-tab" data-toggle="tab" data-target="#pricing-content" role="tab" aria-controls="pricing-content" aria-selected="false">' . get_string('pricing', 'local_localcustomadmin') . '</a>');
        $mform->addElement('html', '</li>');
        $mform->addElement('html', '</ul>');
        $mform->addElement('html', '<div class="tab-content" id="courseTabContent">');

        // TAB 1: GENERAL
        $mform->addElement('html', '<div class="tab-pane fade show active" id="general-content" role="tabpanel" aria-labelledby="general-tab">');

        // Hidden course ID
        $mform->addElement('hidden', 'id', $courseid ?? 0);
        $mform->setType('id', PARAM_INT);

        // Course fullname
        $mform->addElement('text', 'fullname', get_string('fullname'), array('size' => '50'));
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 254), 'maxlength', 254, 'client');

        // Course shortname
        $mform->addElement('text', 'shortname', get_string('shortname'), array('size' => '50'));
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
        $mform->addRule('shortname', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        // Course category
        $categoryoptions = [];
        foreach ($categories as $cat) {
            $categoryoptions[$cat->id] = $cat->name;
        }
        $mform->addElement('select', 'category', get_string('category'), $categoryoptions);
        $mform->setType('category', PARAM_INT);
        $mform->addRule('category', get_string('required'), 'required', null, 'client');

        // Course description
        $mform->addElement('editor', 'summary_editor', get_string('summary'), null, array('rows' => 10, 'cols' => 50));
        $mform->setType('summary_editor', PARAM_RAW);

        // Course format
        // Get available course formats
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
        $mform->addElement('date_time_selector', 'startdate', get_string('startdate'), array('optional' => true));

        $mform->addElement('html', '</div>'); // End general tab

        // TAB 2: PRICING
        $mform->addElement('html', '<div class="tab-pane fade" id="pricing-content" role="tabpanel" aria-labelledby="pricing-tab">');

        $mform->addElement('html', '<p class="text-muted">' . get_string('course_enrolments_info', 'local_localcustomadmin') . '</p>');

        // Course enrolments table
        if ($courseid && $course) {
            $mform->addElement('html', $this->get_enrolments_html($courseid));
        } else {
            $mform->addElement('html', '<p class="alert alert-info">' . get_string('save_course_first', 'local_localcustomadmin') . '</p>');
        }

        $mform->addElement('html', '</div>'); // End pricing tab

        $mform->addElement('html', '</div>'); // End tab content

        // Action buttons
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('save'));
        $buttonarray[] = &$mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    /**
     * Generate HTML for course enrolments table
     *
     * @param int $courseid Course ID
     * @return string HTML content
     */
    private function get_enrolments_html($courseid) {
        global $DB;

        $html = '<div class="course-enrolments-section">';
        $html .= '<h4>' . get_string('enrolled_methods', 'local_localcustomadmin') . '</h4>';

        // Get all enrolment methods for this course
        $enrolments = enrol_get_instances($courseid, true);

        if (empty($enrolments)) {
            $html .= '<p class="alert alert-warning">' . get_string('no_enrolment_methods', 'local_localcustomadmin') . '</p>';
            $html .= '</div>';
            return $html;
        }

        // Debug: mostrar quantos enrolments foram encontrados
        if (debugging()) {
            $html .= '<!-- DEBUG: Found ' . count($enrolments) . ' enrolment instances -->';
        }

        $html .= '<table class="table table-striped table-hover">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>' . get_string('enrolment_method', 'local_localcustomadmin') . '</th>';
        $html .= '<th>' . get_string('status') . '</th>';
        $html .= '<th>' . get_string('price', 'local_localcustomadmin') . '</th>';
        $html .= '<th>' . get_string('actions') . '</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        $rowcount = 0;
        foreach ($enrolments as $enrolment) {
            try {
                $enrolmethod = enrol_get_plugin($enrolment->enrol);
                if (!$enrolmethod) {
                    continue;
                }
                
                $methodname = $enrolmethod->get_instance_name($enrolment);

                // Get price from enrol record
                $price = isset($enrolment->cost) && $enrolment->cost > 0 ? $enrolment->cost : '-';

                $statusclass = $enrolment->status == ENROL_INSTANCE_ENABLED ? 'badge bg-success' : 'badge bg-danger';
                $statustext = $enrolment->status == ENROL_INSTANCE_ENABLED ? get_string('active') : get_string('inactive');

                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($methodname) . '</td>';
                $html .= '<td><span class="' . $statusclass . '">' . $statustext . '</span></td>';
                $html .= '<td>' . htmlspecialchars($price) . '</td>';
                $html .= '<td>';
                $html .= '<a href="#" class="btn btn-sm btn-primary" data-enrolid="' . $enrolment->id . '" onclick="return false;">' . get_string('edit') . '</a>';
                $html .= '</td>';
                $html .= '</tr>';
                $rowcount++;
            } catch (Exception $e) {
                // Log error and continue
                if (debugging()) {
                    $html .= '<!-- ERROR processing enrolment ' . $enrolment->id . ': ' . $e->getMessage() . ' -->';
                }
                continue;
            }
        }

        $html .= '</tbody>';
        $html .= '</table>';
        
        // Debug: mostrar quantas linhas foram renderizadas
        if (debugging()) {
            $html .= '<!-- DEBUG: Rendered ' . $rowcount . ' rows -->';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get enrolment price from custom field or custom table
     *
     * @param int $enrolid Enrolment instance ID
     * @return string|null
     */
    private function get_enrolment_price($enrolid) {
        global $DB;

        // Try to get price from enrol_fee table
        $fee = $DB->get_record('enrol', array('id' => $enrolid));
        if ($fee && isset($fee->cost)) {
            return $fee->cost;
        }

        return null;
    }

    /**
     * Validation of the form
     *
     * @param array $data Data array
     * @param array $files Files array
     * @return array Errors array
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
