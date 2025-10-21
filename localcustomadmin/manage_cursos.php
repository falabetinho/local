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

echo $OUTPUT->header();

// Build SQL query
$sql = "SELECT c.id, c.fullname, c.shortname, c.category, c.visible, c.timecreated, c.timemodified,
               COUNT(DISTINCT ue.userid) as enrollments
        FROM {course} c
        LEFT JOIN {enrol} e ON e.courseid = c.id
        LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE c.id != :siteid";

$params = ['siteid' => SITEID];

// Apply filters
if (!empty($search)) {
    $sql .= " AND (c.fullname LIKE :search1 OR c.shortname LIKE :search2)";
    $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
}

if ($categoryid > 0) {
    $sql .= " AND c.category = :categoryid";
    $params['categoryid'] = $categoryid;
}

if ($visibility === 'visible') {
    $sql .= " AND c.visible = 1";
} else if ($visibility === 'hidden') {
    $sql .= " AND c.visible = 0";
}

$sql .= " GROUP BY c.id, c.fullname, c.shortname, c.category, c.visible, c.timecreated, c.timemodified
          ORDER BY c.fullname ASC";

// Get courses with pagination
$totalcount = $DB->count_records_sql("SELECT COUNT(DISTINCT c.id) FROM {course} c WHERE c.id != :siteid", ['siteid' => SITEID]);
$courses = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Get all categories for filter
$categories = $DB->get_records('course_categories', null, 'name ASC');

// Get statistics
$totalcourses = $DB->count_records('course') - 1;
$visiblecourses = $DB->count_records('course', ['visible' => 1]) - 1;
$hiddencourses = $totalcourses - $visiblecourses;

// Get total enrollments
$totalenrollments = $DB->count_records_sql(
    "SELECT COUNT(DISTINCT ue.userid) 
     FROM {user_enrolments} ue 
     JOIN {enrol} e ON e.id = ue.enrolid 
     JOIN {course} c ON c.id = e.courseid 
     WHERE c.id != ?", 
    [SITEID]
);

?>

<style>
.manage-courses-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0;
}

.manage-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 3rem 2rem;
    border-radius: 20px;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.manage-header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='3'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    opacity: 0.3;
}

.manage-header-content {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.manage-header-text h1 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.manage-header-text p {
    font-size: 1.1rem;
    opacity: 0.95;
    margin: 0;
}

.manage-header-actions {
    display: flex;
    gap: 1rem;
}

.btn-manage {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    border: none;
    cursor: pointer;
}

.btn-manage-primary {
    background: white;
    color: #667eea;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-manage-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
    color: #667eea;
}

.btn-manage-secondary {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-manage-secondary:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card-manage {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card-manage:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-card-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.stat-card-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.stat-card-icon.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
.stat-card-icon.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.stat-card-icon.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }

.stat-card-content h3 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    color: #2d3748;
}

.stat-card-content p {
    margin: 0;
    font-size: 0.9rem;
    color: #718096;
}

.filters-section {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filters-header h3 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-size: 0.9rem;
    font-weight: 600;
    color: #4a5568;
}

.filter-input {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.filter-input:focus {
    outline: none;
    border-color: #667eea;
}

.courses-table-container {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
}

.courses-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.courses-table thead th {
    background: #f7fafc;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #2d3748;
    border-bottom: 2px solid #e2e8f0;
}

.courses-table tbody tr {
    transition: background 0.2s ease;
}

.courses-table tbody tr:hover {
    background: #f7fafc;
}

.courses-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
}

.course-name {
    font-weight: 600;
    color: #2d3748;
}

.course-shortname {
    color: #718096;
    font-size: 0.9rem;
}

.course-category {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: #edf2f7;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #4a5568;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-status.visible {
    background: #c6f6d5;
    color: #22543d;
}

.badge-status.hidden {
    background: #fed7d7;
    color: #742a2a;
}

.course-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}

.btn-icon.edit {
    background: #edf2f7;
    color: #667eea;
}

.btn-icon.edit:hover {
    background: #667eea;
    color: white;
}

.btn-icon.view {
    background: #edf2f7;
    color: #4299e1;
}

.btn-icon.view:hover {
    background: #4299e1;
    color: white;
}

.btn-icon.delete {
    background: #fed7d7;
    color: #e53e3e;
}

.btn-icon.delete:hover {
    background: #e53e3e;
    color: white;
}

.pagination-container {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination-btn {
    padding: 0.5rem 1rem;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.pagination-btn:hover {
    border-color: #667eea;
    color: #667eea;
}

.pagination-btn.active {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e0;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #718096;
    margin-bottom: 2rem;
}
</style>

<div class="manage-courses-container">
    <!-- Elegant Header -->
    <div class="manage-header">
        <div class="manage-header-content">
            <div class="manage-header-text">
                <h1>
                    <i class="fas fa-graduation-cap"></i>
                    Gerenciar Cursos
                </h1>
                <p>Gerencie todos os cursos da plataforma em um só lugar</p>
            </div>
            <div class="manage-header-actions">
                <a href="<?php echo new moodle_url('/local/localcustomadmin/cursos.php'); ?>" class="btn-manage btn-manage-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Voltar
                </a>
                <a href="<?php echo new moodle_url('/local/localcustomadmin/edit_curso.php'); ?>" class="btn-manage btn-manage-primary">
                    <i class="fas fa-plus"></i>
                    Novo Curso
                </a>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-manage">
            <div class="stat-card-icon primary">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-card-content">
                <h3><?php echo $totalcourses; ?></h3>
                <p>Total de Cursos</p>
            </div>
        </div>
        <div class="stat-card-manage">
            <div class="stat-card-icon success">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-card-content">
                <h3><?php echo $visiblecourses; ?></h3>
                <p>Cursos Visíveis</p>
            </div>
        </div>
        <div class="stat-card-manage">
            <div class="stat-card-icon warning">
                <i class="fas fa-eye-slash"></i>
            </div>
            <div class="stat-card-content">
                <h3><?php echo $hiddencourses; ?></h3>
                <p>Cursos Ocultos</p>
            </div>
        </div>
        <div class="stat-card-manage">
            <div class="stat-card-icon info">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-content">
                <h3><?php echo $totalenrollments; ?></h3>
                <p>Total de Matrículas</p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filters-section">
        <div class="filters-header">
            <h3>
                <i class="fas fa-filter"></i>
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
                    <button type="submit" class="btn-manage btn-manage-primary" style="width: 100%;">
                        <i class="fas fa-search"></i>
                        Filtrar
                    </button>
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
                                <span class="badge-status <?php echo $course->visible ? 'visible' : 'hidden'; ?>">
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
                <a href="<?php echo new moodle_url('/local/localcustomadmin/edit_curso.php'); ?>" class="btn-manage btn-manage-primary">
                    <i class="fas fa-plus"></i>
                    Criar Novo Curso
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
echo $OUTPUT->footer();
