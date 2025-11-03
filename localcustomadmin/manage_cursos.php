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
 * Complete courses management page for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$visibility = optional_param('visibility', 'all', PARAM_ALPHA);

// Set up the page
$PAGE->set_url(new moodle_url('/local/localcustomadmin/manage_cursos.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('manage_courses', 'local_localcustomadmin'));
$PAGE->set_heading(get_string('manage_courses', 'local_localcustomadmin'));

// Add navigation breadcrumb
$PAGE->navbar->add(get_string('localcustomadmin', 'local_localcustomadmin'), '/local/localcustomadmin/index.php');
$PAGE->navbar->add(get_string('courses', 'local_localcustomadmin'), '/local/localcustomadmin/cursos.php');
$PAGE->navbar->add(get_string('manage_courses', 'local_localcustomadmin'));

// Inclua o CSS centralizado antes do header
$PAGE->requires->css(new moodle_url('/local/localcustomadmin/styles.css'));

echo $OUTPUT->header();

?>
<!-- Back button - First element -->
<div class="back-button-container">
    <a href="<?php echo new moodle_url('/local/localcustomadmin/cursos.php'); ?>" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        Voltar
    </a>
</div>

<div class="elegant-courses-container">
    <!-- Elegant Header -->
    <div class="hero-header">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    <i class="fas fa-graduation-cap hero-icon"></i>
                    Gerenciar Cursos
                </h1>
                <p class="hero-subtitle">Gerencie todos os cursos da plataforma em um só lugar</p>
            </div>
            <div class="hero-actions">
                <a href="<?php echo new moodle_url('/local/localcustomadmin/edit_curso.php'); ?>" class="btn-elegant btn-primary">
                    <i class="fas fa-plus"></i>
                    Novo Curso
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="elegant-stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $totalcourses; ?></span>
                <span class="stat-label">Total de Cursos</span>
            </div>
        </div>
        <div class="elegant-stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $visiblecourses; ?></span>
                <span class="stat-label">Cursos Visíveis</span>
            </div>
        </div>
        <div class="elegant-stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $hiddencourses; ?></span>
                <span class="stat-label">Cursos Ocultos</span>
            </div>
        </div>
        <div class="elegant-stat-card">
            <div class="stat-icon bg-info">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <span class="stat-value"><?php echo $totalenrollments; ?></span>
                <span class="stat-label">Total de Matrículas</span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-header">
            <h3>
                <i class="fas fa-filter mr-2"></i>
                Filtros
            </h3>
        </div>
        <form method="get" action="">
            <div class="filters-grid">
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="search" class="filter-input" placeholder="Nome ou código do curso..." value="<?php echo s($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Categoria</label>
                    <select name="categoryid" class="filter-input">
                        <option value="0">Todas as categorias</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat->id; ?>" <?php echo ($categoryid == $cat->id) ? 'selected' : ''; ?>>
                                <?php echo format_string($cat->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Visibilidade</label>
                    <select name="visibility" class="filter-input">
                        <option value="all" <?php echo ($visibility === 'all') ? 'selected' : ''; ?>>Todos</option>
                        <option value="visible" <?php echo ($visibility === 'visible') ? 'selected' : ''; ?>>Visíveis</option>
                        <option value="hidden" <?php echo ($visibility === 'hidden') ? 'selected' : ''; ?>>Ocultos</option>
                    </select>
                </div>
                <div class="filter-group" style="align-self: end;">
                    <div class="btn-group" role="group" aria-label="Ações de filtro" style="width: 100%;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filtrar
                        </button>
                        <a href="<?php echo new moodle_url('/local/localcustomadmin/manage_cursos.php'); ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-undo"></i>
                            Resetar Filtros
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Courses Table -->
    <div class="courses-table-container">
        <?php if (!empty($courses)): ?>
            <table class="courses-table">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Categoria</th>
                        <th>Matrículas</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): 
                        $category = $DB->get_record('course_categories', ['id' => $course->category]);
                        $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
                        $editurl = new moodle_url('/local/localcustomadmin/edit_curso.php', ['id' => $course->id]);
                    ?>
                        <tr>
                            <td>
                                <div class="course-name"><?php echo format_string($course->fullname); ?></div>
                                <div class="course-shortname"><?php echo format_string($course->shortname); ?></div>
                            </td>
                            <td>
                                <span class="course-category">
                                    <?php echo $category ? format_string($category->name) : 'N/A'; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $course->enrollments; ?></strong> alunos
                            </td>
                            <td>
                                <span class="badge-status <?php echo $course->visible ? 'status-success' : 'status-danger'; ?>">
                                    <i class="fas fa-circle"></i>
                                    <?php echo $course->visible ? 'Visível' : 'Oculto'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="course-actions">
                                    <a href="<?php echo $editurl; ?>" class="btn-icon edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo $courseurl; ?>" class="btn-icon view" title="Visualizar">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($totalcount > $perpage): ?>
                <div class="pagination-container">
                    <?php
                    $totalpages = ceil($totalcount / $perpage);
                    for ($i = 0; $i < $totalpages; $i++):
                        $pageurl = new moodle_url('/local/localcustomadmin/manage_cursos.php', [
                            'page' => $i,
                            'perpage' => $perpage,
                            'search' => $search,
                            'categoryid' => $categoryid,
                            'visibility' => $visibility
                        ]);
                    ?>
                        <a href="<?php echo $pageurl; ?>" class="pagination-btn <?php echo ($page === $i) ? 'active' : ''; ?>">
                            <?php echo $i + 1; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>Nenhum curso encontrado</h3>
                <p>Tente ajustar seus filtros ou criar um novo curso</p>
                <a href="<?php echo new moodle_url('/local/localcustomadmin/edit_curso.php'); ?>" class="btn-elegant btn-primary btn-large">
                    <i class="fas fa-plus"></i>
                    Criar Novo Curso
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="elegant-footer">
        <div class="footer-info">
            <span class="footer-text">Gerenciando cursos com elegância</span>
        </div>
    </div>
</div>

<?php
echo $OUTPUT->footer();
