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
 * Manage course enrollment prices imported from category prices
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/localcustomadmin/classes/enrolment_price_manager.php');

use local_localcustomadmin\enrolment_price_manager;

$courseid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

require_login();

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_capability('local/localcustomadmin:manage', $context);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/manage_enrol_prices.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_enrol_prices', 'local_localcustomadmin', format_string($course->fullname)));
$PAGE->set_heading(get_string('manage_enrol_prices', 'local_localcustomadmin', format_string($course->fullname)));

// Add navigation breadcrumb
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/index.php'));
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), new moodle_url('/local/localcustomadmin/cursos.php'));
$PAGE->navbar->add(format_string($course->fullname), new moodle_url('/course/view.php', ['id' => $courseid]));
$PAGE->navbar->add(get_string('manage_enrol_prices', 'local_localcustomadmin'));

// Handle actions
if ($action === 'import' && confirm_sesskey()) {
    $priceids = optional_param_array('priceids', [], PARAM_INT);
    
    if (!empty($priceids)) {
        try {
            $result = enrolment_price_manager::import_category_prices_to_course($courseid, $priceids);
            
            if ($result['success']) {
                $message = count($result['created']) . ' preço(s) importado(s) com sucesso!';
                \core\notification::success($message);
            } else {
                \core\notification::error('Erro ao importar alguns preços: ' . implode(', ', $result['errors']));
            }
        } catch (Exception $e) {
            \core\notification::error('Erro: ' . $e->getMessage());
        }
    }
    redirect($PAGE->url);
}

if ($action === 'unlink' && confirm_sesskey()) {
    $enrolid = required_param('enrolid', PARAM_INT);
    
    try {
        if (enrolment_price_manager::unlink_enrolment_from_price($enrolid)) {
            \core\notification::success('Vínculo removido com sucesso!');
        } else {
            \core\notification::error('Erro ao remover vínculo.');
        }
    } catch (Exception $e) {
        \core\notification::error('Erro: ' . $e->getMessage());
    }
    redirect($PAGE->url);
}

echo $OUTPUT->header();

// Get current imported prices
$imported_enrols = enrolment_price_manager::get_course_price_enrolments($courseid);

// Get available prices for import
$available_prices = enrolment_price_manager::get_available_prices_for_course($courseid);

?>

<div class="manage-enrol-prices-container">
    
    <!-- Back button -->
    <div class="back-button-container">
        <a href="<?php echo new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $courseid]); ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Voltar para Edição do Curso
        </a>
    </div>

    <!-- Page Header -->
    <div class="page-header-elegant">
        <h1>
            <i class="fas fa-tags"></i>
            Gerenciar Preços de Matrícula
        </h1>
        <p class="lead">Curso: <strong><?php echo format_string($course->fullname); ?></strong></p>
    </div>

    <!-- Current Imported Prices -->
    <div class="section-card mb-4">
        <div class="section-header">
            <h2><i class="fas fa-list-ul"></i> Preços Importados</h2>
        </div>
        <div class="section-body">
            <?php if (empty($imported_enrols)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    Nenhum preço foi importado ainda. Use o formulário abaixo para importar preços da categoria.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nome do Preço</th>
                                <th>Valor</th>
                                <th>Parcelamento</th>
                                <th>Promocional</th>
                                <th>Status</th>
                                <th>Vigência</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($imported_enrols as $enrol): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo s($enrol->name); ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $enrol->id; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-success">
                                            R$ <?php echo number_format($enrol->cost, 2, ',', '.'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $enrol->customint4 > 0 ? $enrol->customint4 . 'x' : '-'; ?>
                                    </td>
                                    <td>
                                        <?php if ($enrol->customint2): ?>
                                            <span class="badge badge-warning">
                                                <i class="fas fa-percentage"></i> Sim
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Não</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($enrol->status == ENROL_INSTANCE_ENABLED): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php 
                                            echo userdate($enrol->enrolstartdate, '%d/%m/%Y');
                                            echo ' - ';
                                            echo $enrol->enrolenddate ? userdate($enrol->enrolenddate, '%d/%m/%Y') : 'Indefinido';
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <a href="<?php echo new moodle_url($PAGE->url, ['action' => 'unlink', 'enrolid' => $enrol->id, 'sesskey' => sesskey()]); ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Deseja realmente desvincular este preço?');">
                                            <i class="fas fa-unlink"></i> Desvincular
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Import Prices Form -->
    <div class="section-card">
        <div class="section-header">
            <h2><i class="fas fa-download"></i> Importar Preços da Categoria</h2>
        </div>
        <div class="section-body">
            <?php if (empty($available_prices)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Não há preços disponíveis na categoria deste curso.
                    <a href="<?php echo new moodle_url('/local/localcustomadmin/categorias.php'); ?>">
                        Gerenciar Categorias
                    </a>
                </div>
            <?php else: ?>
                <form method="post" action="<?php echo $PAGE->url; ?>">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <input type="hidden" name="action" value="import">
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" id="selectall">
                                    </th>
                                    <th>Nome do Preço</th>
                                    <th>Valor</th>
                                    <th>Parcelamento</th>
                                    <th>Tipo</th>
                                    <th>Vigência</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_prices as $price): ?>
                                    <?php 
                                    $already_imported = enrolment_price_manager::is_price_imported($courseid, $price->id);
                                    ?>
                                    <tr class="<?php echo $already_imported ? 'table-secondary' : ''; ?>">
                                        <td>
                                            <?php if (!$already_imported): ?>
                                                <input type="checkbox" name="priceids[]" value="<?php echo $price->id; ?>" class="price-checkbox">
                                            <?php else: ?>
                                                <span class="text-success" title="Já importado">
                                                    <i class="fas fa-check-circle"></i>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo s($price->name); ?></strong>
                                            <?php if ($already_imported): ?>
                                                <br><small class="text-success">Já importado</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong>R$ <?php echo number_format($price->price, 2, ',', '.'); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo $price->installments > 0 ? $price->installments . 'x' : '-'; ?>
                                        </td>
                                        <td>
                                            <?php if ($price->ispromotional): ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-percentage"></i> Promocional
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($price->isenrollmentfee): ?>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-graduation-cap"></i> Taxa de Matrícula
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php 
                                                echo userdate($price->startdate, '%d/%m/%Y');
                                                echo '<br>';
                                                echo $price->enddate ? userdate($price->enddate, '%d/%m/%Y') : 'Indefinido';
                                                ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($price->status): ?>
                                                <span class="badge badge-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-download"></i>
                            Importar Preços Selecionados
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.manage-enrol-prices-container {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header-elegant {
    background: linear-gradient(135deg, #2b53a0 0%, #4a90e2 100%);
    color: white;
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
}

.page-header-elegant h1 {
    color: white;
    margin: 0 0 0.5rem 0;
}

.page-header-elegant .lead {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
}

.section-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.section-header {
    background: #f8f9fa;
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.section-header h2 {
    margin: 0;
    font-size: 1.25rem;
    color: #2b53a0;
}

.section-body {
    padding: 1.5rem;
}

.table-secondary {
    opacity: 0.6;
}
</style>

<script>
// Select all checkboxes
document.getElementById('selectall').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('.price-checkbox');
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = this.checked;
    }, this);
});
</script>

<?php

echo $OUTPUT->footer();
