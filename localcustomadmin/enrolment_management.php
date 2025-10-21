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
 * Enrolment Management main page
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/lib.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get custom display name
$displayname = local_localcustomadmin_get_display_name();

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/enrolment_management.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('enrolment_management', 'local_localcustomadmin'));

// Add navigation breadcrumb
$PAGE->navbar->add($displayname, new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('enrolment_management', 'local_localcustomadmin'));

echo $OUTPUT->header();

// Back button
echo '<div class="back-button-container">';
$back_url = new moodle_url('/local/localcustomadmin/index.php');
echo '<a href="' . $back_url . '" class="btn-back">';
echo '<i class="fas fa-arrow-left"></i> ';
echo get_string('back', 'local_localcustomadmin');
echo '</a>';
echo '</div>';

// Hero Section
echo '<div class="elegant-hero-section mb-4">';
echo '<div class="hero-content">';
echo '<h1 class="hero-title">';
echo '<i class="fas fa-user-graduate"></i> ';
echo get_string('enrolment_management', 'local_localcustomadmin');
echo '</h1>';
echo '<p class="hero-subtitle">' . get_string('enrolment_management_desc', 'local_localcustomadmin') . '</p>';
echo '</div>';
echo '</div>';

// Cards Container
echo '<div class="elegant-actions-grid">';

// Status Report Card
require_once($CFG->dirroot . '/local/localcustomadmin/classes/api/customstatus_integration.php');
if (\local_localcustomadmin\api\customstatus_integration::is_available()) {
    echo '<div class="action-card">';
    echo '<div class="action-card-icon icon-primary">';
    echo '<i class="fas fa-chart-bar"></i>';
    echo '</div>';
    echo '<div class="action-card-content">';
    echo '<h4 class="action-card-title">' . get_string('statusreport', 'local_localcustomadmin') . '</h4>';
    echo '<p class="action-card-description">' . get_string('statusreport_desc', 'local_localcustomadmin') . '</p>';
    echo '</div>';
    echo '<a href="' . new moodle_url('/local/localcustomadmin/status_report.php') . '" class="action-card-link">';
    echo '<i class="fas fa-arrow-right"></i>';
    echo '</a>';
    echo '</div>';
}

// Category Prices Card
echo '<div class="action-card">';
echo '<div class="action-card-icon icon-success">';
echo '<i class="fas fa-tags"></i>';
echo '</div>';
echo '<div class="action-card-content">';
echo '<h4 class="action-card-title">' . get_string('categoryprices', 'local_localcustomadmin') . '</h4>';
echo '<p class="action-card-description">' . get_string('categoryprices_management_desc', 'local_localcustomadmin') . '</p>';
echo '</div>';
echo '<a href="' . new moodle_url('/local/localcustomadmin/categorias.php') . '" class="action-card-link">';
echo '<i class="fas fa-arrow-right"></i>';
echo '</a>';
echo '</div>';

// Enrolment Methods Card (placeholder for future functionality)
echo '<div class="action-card">';
echo '<div class="action-card-icon icon-info">';
echo '<i class="fas fa-key"></i>';
echo '</div>';
echo '<div class="action-card-content">';
echo '<h4 class="action-card-title">' . get_string('enrolment_methods', 'local_localcustomadmin') . '</h4>';
echo '<p class="action-card-description">' . get_string('enrolment_methods_desc', 'local_localcustomadmin') . '</p>';
echo '</div>';
echo '<a href="#" class="action-card-link" onclick="alert(\'Em desenvolvimento\'); return false;">';
echo '<i class="fas fa-arrow-right"></i>';
echo '</a>';
echo '</div>';

echo '</div>'; // End elegant-actions-grid

// CustomStatus Plugin Quick Actions Footer
if (\local_localcustomadmin\api\customstatus_integration::is_available()) {
    echo '<div class="customstatus-footer">';
    echo '<div class="footer-header">';
    echo '<i class="fas fa-plug"></i> ';
    echo '<h5>' . get_string('customstatus_operations', 'local_localcustomadmin') . '</h5>';
    echo '</div>';
    echo '<div class="footer-links-grid">';
    
    // Matricula
    echo '<a href="' . new moodle_url('/enrol/customstatus/matricula.php') . '" class="footer-link">';
    echo '<i class="fas fa-user-plus"></i> ';
    echo get_string('customstatus_matricula', 'local_localcustomadmin');
    echo '</a>';
    
    // Manage Statuses
    echo '<a href="' . new moodle_url('/enrol/customstatus/manage_statuses.php') . '" class="footer-link">';
    echo '<i class="fas fa-cogs"></i> ';
    echo get_string('customstatus_manage', 'local_localcustomadmin');
    echo '</a>';
    
    // Assign Status
    echo '<a href="' . new moodle_url('/enrol/customstatus/assign_status.php') . '" class="footer-link">';
    echo '<i class="fas fa-user-tag"></i> ';
    echo get_string('customstatus_assign', 'local_localcustomadmin');
    echo '</a>';
    
    // Edit Status
    echo '<a href="' . new moodle_url('/enrol/customstatus/edit_status.php') . '" class="footer-link">';
    echo '<i class="fas fa-edit"></i> ';
    echo get_string('customstatus_edit', 'local_localcustomadmin');
    echo '</a>';
    
    // Report
    echo '<a href="' . new moodle_url('/enrol/customstatus/report.php') . '" class="footer-link">';
    echo '<i class="fas fa-chart-line"></i> ';
    echo get_string('customstatus_report', 'local_localcustomadmin');
    echo '</a>';
    
    // Blocked Users
    echo '<a href="' . new moodle_url('/enrol/customstatus/blocked.php') . '" class="footer-link">';
    echo '<i class="fas fa-ban"></i> ';
    echo get_string('customstatus_blocked', 'local_localcustomadmin');
    echo '</a>';
    
    echo '</div>';
    echo '</div>';
}

// Add custom styles
echo '<style>
/* CustomStatus Footer Section */
.customstatus-footer {
    margin-top: 3rem;
    padding: 2rem;
    background: #f8f9fa;
    border-radius: 16px;
    border: 2px dashed #d0d0d0;
}

.footer-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    color: #2b53a0;
}

.footer-header i {
    font-size: 1.5rem;
}

.footer-header h5 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
}

.footer-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.footer-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.25rem;
    background: white;
    border-radius: 10px;
    border: 1px solid #e0e0e0;
    color: #2c3e50;
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    font-weight: 500;
}

.footer-link i {
    color: #2b53a0;
    font-size: 1.1rem;
}

.footer-link:hover {
    background: #2b53a0;
    color: white;
    border-color: #2b53a0;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(43, 83, 160, 0.2);
}

.footer-link:hover i {
    color: white;
    transform: scale(1.1);
}

@media (max-width: 768px) {
    .footer-links-grid {
        grid-template-columns: 1fr;
    }
    
    .customstatus-footer {
        padding: 1.5rem;
        margin-top: 2rem;
    }
}

.elegant-hero-section {
    background: linear-gradient(135deg, #2b53a0 0%, #4a90e2 100%);
    padding: 3rem 2rem;
    border-radius: 20px;
    color: white;
    position: relative;
    overflow: hidden;
}

.elegant-hero-section::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.1\'%3E%3Ccircle cx=\'30\' cy=\'30\' r=\'3\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 1;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.hero-subtitle {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
}

/* Action Cards Grid */
.elegant-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.action-card {
    background: white;
    border-radius: 16px;
    padding: 1.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    border: 1px solid #e0e0e0;
    cursor: pointer;
}

.action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
    border-color: #2b53a0;
}

.action-card-icon {
    width: 70px;
    height: 70px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    flex-shrink: 0;
    transition: all 0.3s ease;
}

.action-card:hover .action-card-icon {
    transform: scale(1.1) rotate(5deg);
}

.action-card-icon.icon-primary {
    background: linear-gradient(135deg, #2b53a0 0%, #4a90e2 100%);
}

.action-card-icon.icon-success {
    background: linear-gradient(135deg, #28a745 0%, #34ce57 100%);
}

.action-card-icon.icon-info {
    background: linear-gradient(135deg, #17a2b8 0%, #20c9e3 100%);
}

.action-card-content {
    flex: 1;
}

.action-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 0.5rem 0;
}

.action-card-description {
    color: #6c757d;
    margin: 0;
    font-size: 0.95rem;
    line-height: 1.5;
}

.action-card-link {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2b53a0;
    transition: all 0.3s ease;
    flex-shrink: 0;
    text-decoration: none;
}

.action-card:hover .action-card-link {
    background: #2b53a0;
    color: white;
    transform: translateX(4px);
}

@media (max-width: 768px) {
    .elegant-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .action-card {
        padding: 1.25rem;
    }
    
    .action-card-icon {
        width: 60px;
        height: 60px;
        font-size: 1.75rem;
    }
}
</style>';

echo $OUTPUT->footer();
