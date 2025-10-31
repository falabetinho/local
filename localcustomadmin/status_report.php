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
 * Status Report - Integration with Custom Status plugin
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/api/customstatus_integration.php');

use local_localcustomadmin\api\customstatus_integration;

$categoryid = optional_param('categoryid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/status_report.php', ['categoryid' => $categoryid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title('Relatório de Status de Alunos');
$PAGE->set_heading('Relatório de Status de Alunos');

// Add navigation breadcrumb
$PAGE->navbar->add('LocalCustomAdmin', new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add('Relatório de Status');

// Handle actions
if ($action === 'mark_overdue' && $categoryid && confirm_sesskey()) {
    $count = customstatus_integration::mark_category_overdue($categoryid);
    \core\notification::success("$count aluno(s) marcado(s) como inadimplente(s)");
    redirect($PAGE->url);
}

echo $OUTPUT->header();

// Back button
echo '<div class="back-button-container">';
$back_url = new moodle_url('/local/localcustomadmin/index.php');
echo '<a href="' . $back_url . '" class="btn-back">';
echo '<i class="fas fa-arrow-left"></i> ';
echo get_string('back', 'local_localcustomadmin');
echo '</a>';
echo '</div>';

// Check if Custom Status is available
if (!customstatus_integration::is_available()) {
    echo $OUTPUT->notification(
        'O plugin Custom Status não está instalado ou ativado. Por favor, instale o plugin enrol_customstatus.',
        'error'
    );
    echo $OUTPUT->footer();
    exit;
}

// Category selector
$categories = $DB->get_records_menu('course_categories', null, 'name ASC', 'id, name');

echo '<div class="card mb-3">';
echo '<div class="card-body">';
echo '<form method="get" action="' . $PAGE->url->out(false) . '" class="form-inline">';
echo '<label class="mr-2">Selecionar Categoria:</label>';
echo '<select name="categoryid" class="custom-select mr-2" onchange="this.form.submit()">';
echo '<option value="0"' . ($categoryid == 0 ? ' selected' : '') . '>📊 Todas as Categorias (Consolidado)</option>';
foreach ($categories as $id => $name) {
    $selected = ($id == $categoryid) ? 'selected' : '';
    echo "<option value=\"$id\" $selected>" . format_string($name) . "</option>";
}
echo '</select>';
echo '<noscript><button type="submit" class="btn btn-primary">Filtrar</button></noscript>';
echo '</form>';
echo '</div>';
echo '</div>';

// Get report data
if ($categoryid) {
    // Report for specific category
    $report = customstatus_integration::get_category_report($categoryid);
} else {
    // Consolidated report for all categories
    $report = [
        'category' => null,
        'price' => 0,
        'statistics' => [
            'total' => 0,
            'paid' => 0,
            'payment_due' => 0,
            'blocked' => 0,
            'other' => 0
        ],
        'revenue' => [
            'expected' => 0,
            'received' => 0,
            'pending' => 0
        ],
        'users' => [
            'paid' => [],
            'payment_due' => [],
            'blocked' => []
        ]
    ];
    
    // Sum statistics from all categories with prices
    $categories_with_prices = $DB->get_records_sql("
        SELECT DISTINCT categoryid 
        FROM {local_customadmin_category_prices}
        WHERE price > 0
    ");
    
    foreach ($categories_with_prices as $cat) {
        $cat_report = customstatus_integration::get_category_report($cat->categoryid);
        
        // Sum statistics
        $report['statistics']['total'] += $cat_report['statistics']['total'];
        $report['statistics']['paid'] += $cat_report['statistics']['paid'];
        $report['statistics']['payment_due'] += $cat_report['statistics']['payment_due'];
        $report['statistics']['blocked'] += $cat_report['statistics']['blocked'];
        $report['statistics']['other'] += $cat_report['statistics']['other'];
        
        // Sum revenue
        $report['revenue']['expected'] += $cat_report['revenue']['expected'];
        $report['revenue']['received'] += $cat_report['revenue']['received'];
        $report['revenue']['pending'] += $cat_report['revenue']['pending'];
        
        // Merge users (avoid duplicates by using userid as key)
        foreach ($cat_report['users']['paid'] as $user) {
            if (!isset($report['users']['paid'][$user->id])) {
                $report['users']['paid'][$user->id] = $user;
            }
        }
        foreach ($cat_report['users']['payment_due'] as $user) {
            if (!isset($report['users']['payment_due'][$user->id])) {
                $report['users']['payment_due'][$user->id] = $user;
            }
        }
        foreach ($cat_report['users']['blocked'] as $user) {
            if (!isset($report['users']['blocked'][$user->id])) {
                $report['users']['blocked'][$user->id] = $user;
            }
        }
    }
}

// Show title with category info
if ($categoryid) {
    $category = $DB->get_record('course_categories', ['id' => $categoryid]);
    echo '<div class="alert alert-info mb-3">';
    echo '<h5 class="mb-0"><i class="fa fa-folder-open"></i> Categoria: <strong>' . format_string($category->name) . '</strong></h5>';
    echo '</div>';
} else {
    echo '<div class="alert alert-primary mb-3">';
    echo '<h5 class="mb-0"><i class="fa fa-globe"></i> <strong>Visão Consolidada</strong> - Todas as categorias com preços definidos</h5>';
    echo '</div>';
}

// Statistics Cards
echo '<div class="row mb-4">';

// Total Enrolled
echo '<div class="col-md-3">';
echo '<div class="card border-primary">';
echo '<div class="card-body text-center">';
echo '<h3 class="display-4 text-primary">' . $report['statistics']['total'] . '</h3>';
echo '<p class="text-muted mb-0">Total de Alunos</p>';
echo '</div></div></div>';

// Paid
$paidPercentage = $report['statistics']['total'] > 0 
    ? round(($report['statistics']['paid'] / $report['statistics']['total']) * 100, 1)
    : 0;
echo '<div class="col-md-3">';
echo '<div class="card border-success">';
echo '<div class="card-body text-center">';
echo '<h3 class="display-4 text-success">' . $report['statistics']['paid'] . '</h3>';
echo '<p class="text-muted mb-0">Quitados</p>';
echo '<small class="badge badge-success">' . $paidPercentage . '%</small>';
echo '</div></div></div>';

// Payment Due
$duePercentage = $report['statistics']['total'] > 0 
    ? round(($report['statistics']['payment_due'] / $report['statistics']['total']) * 100, 1)
    : 0;
echo '<div class="col-md-3">';
echo '<div class="card border-warning">';
echo '<div class="card-body text-center">';
echo '<h3 class="display-4 text-warning">' . $report['statistics']['payment_due'] . '</h3>';
echo '<p class="text-muted mb-0">Pagamento Pendente</p>';
echo '<small class="badge badge-warning">' . $duePercentage . '%</small>';
echo '</div></div></div>';

// Blocked
echo '<div class="col-md-3">';
echo '<div class="card border-danger">';
echo '<div class="card-body text-center">';
echo '<h3 class="display-4 text-danger">' . $report['statistics']['blocked'] . '</h3>';
echo '<p class="text-muted mb-0">Bloqueados</p>';
echo '</div></div></div>';

echo '</div>';

// Revenue Cards
echo '<div class="row mb-4">';

// Expected Revenue
echo '<div class="col-md-4">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Receita Esperada</h5>';
echo '<h3 class="text-info">R$ ' . number_format($report['revenue']['expected'], 2, ',', '.') . '</h3>';
if ($categoryid) {
    echo '<small class="text-muted">' . $report['statistics']['total'] . ' alunos × R$ ' . 
         number_format($report['price'], 2, ',', '.') . '</small>';
} else {
    echo '<small class="text-muted">Total de todas as categorias</small>';
}
echo '</div></div></div>';

// Received Revenue
echo '<div class="col-md-4">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Receita Recebida</h5>';
echo '<h3 class="text-success">R$ ' . number_format($report['revenue']['received'], 2, ',', '.') . '</h3>';
echo '<small class="text-muted">' . $report['statistics']['paid'] . ' alunos pagos</small>';
echo '</div></div></div>';

// Pending Revenue
echo '<div class="col-md-4">';
echo '<div class="card">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Receita Pendente</h5>';
echo '<h3 class="text-warning">R$ ' . number_format($report['revenue']['pending'], 2, ',', '.') . '</h3>';
echo '<small class="text-muted">' . ($report['statistics']['payment_due'] + $report['statistics']['blocked']) . 
     ' alunos pendentes</small>';
echo '</div></div></div>';

echo '</div>';

// Actions (only for specific category)
if ($categoryid) {
    echo '<div class="card mb-3">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">Ações Rápidas</h5>';
    echo '<div class="btn-group">';
    
    $markurl = new moodle_url('/local/localcustomadmin/status_report.php', [
        'categoryid' => $categoryid,
        'action' => 'mark_overdue',
        'sesskey' => sesskey()
    ]);
    echo '<a href="' . $markurl . '" class="btn btn-warning" onclick="return confirm(\'Tem certeza que deseja marcar todos os não pagos como inadimplentes?\');">';
    echo '<i class="fa fa-exclamation-triangle"></i> Marcar Inadimplentes</a>';
    echo '</div>';
    echo '</div></div>';
}

// Tabs for user lists
echo '<ul class="nav nav-tabs mb-3" role="tablist">';
echo '<li class="nav-item">';
echo '<a class="nav-link active" data-toggle="tab" href="#paid" role="tab">Quitados (' . 
     count($report['users']['paid']) . ')</a>';
echo '</li>';
echo '<li class="nav-item">';
echo '<a class="nav-link" data-toggle="tab" href="#payment_due" role="tab">Pagamento Pendente (' . 
     count($report['users']['payment_due']) . ')</a>';
echo '</li>';
echo '<li class="nav-item">';
echo '<a class="nav-link" data-toggle="tab" href="#blocked" role="tab">Bloqueados (' . 
     count($report['users']['blocked']) . ')</a>';
echo '</li>';
echo '</ul>';

echo '<div class="tab-content">';

// Paid tab
echo '<div class="tab-pane fade show active" id="paid" role="tabpanel">';
if (empty($report['users']['paid'])) {
    echo '<div class="alert alert-info">Nenhum aluno com status Quitado.</div>';
} else {
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Nome</th><th>Email</th><th>Status</th><th>Atualizado</th></tr></thead>';
    echo '<tbody>';
    foreach ($report['users']['paid'] as $user) {
        echo '<tr>';
        echo '<td>' . fullname($user) . '</td>';
        echo '<td>' . $user->email . '</td>';
        echo '<td><span class="badge" style="background-color: ' . $user->statuscolor . ';">' . 
             format_string($user->statusname) . '</span></td>';
        echo '<td>' . userdate($user->timemodified, '%d/%m/%Y %H:%M') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

// Payment Due tab
echo '<div class="tab-pane fade" id="payment_due" role="tabpanel">';
if (empty($report['users']['payment_due'])) {
    echo '<div class="alert alert-info">Nenhum aluno com pagamento pendente.</div>';
} else {
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Nome</th><th>Email</th><th>Status</th><th>Atualizado</th><th>Ações</th></tr></thead>';
    echo '<tbody>';
    foreach ($report['users']['payment_due'] as $user) {
        echo '<tr>';
        echo '<td>' . fullname($user) . '</td>';
        echo '<td>' . $user->email . '</td>';
        echo '<td><span class="badge" style="background-color: ' . $user->statuscolor . ';">' . 
             format_string($user->statusname) . '</span></td>';
        echo '<td>' . userdate($user->timemodified, '%d/%m/%Y %H:%M') . '</td>';
        echo '<td>';
        echo '<a href="#" class="btn btn-sm btn-primary" onclick="alert(\'Enviar lembrete para ' . 
             fullname($user) . '\'); return false;">Enviar Lembrete</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

// Blocked tab
echo '<div class="tab-pane fade" id="blocked" role="tabpanel">';
if (empty($report['users']['blocked'])) {
    echo '<div class="alert alert-info">Nenhum aluno bloqueado.</div>';
} else {
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Nome</th><th>Email</th><th>Status</th><th>Atualizado</th><th>Ações</th></tr></thead>';
    echo '<tbody>';
    foreach ($report['users']['blocked'] as $user) {
        echo '<tr>';
        echo '<td>' . fullname($user) . '</td>';
        echo '<td>' . $user->email . '</td>';
        echo '<td><span class="badge" style="background-color: ' . $user->statuscolor . ';">' . 
             format_string($user->statusname) . '</span></td>';
        echo '<td>' . userdate($user->timemodified, '%d/%m/%Y %H:%M') . '</td>';
        echo '<td>';
        echo '<a href="#" class="btn btn-sm btn-danger" onclick="alert(\'Contatar ' . 
             fullname($user) . '\'); return false;">Contatar</a>';
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
echo '</div>';

echo '</div>'; // end tab-content

echo $OUTPUT->footer();
