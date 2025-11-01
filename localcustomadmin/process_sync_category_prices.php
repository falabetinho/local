<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$action = optional_param('action', 'sync_category', PARAM_ALPHANUMEXT);
$categoryid = required_param('categoryid', PARAM_INT);

// Validação padrão e redirect em caso de ação inválida
if ($action !== 'sync_category') {
    redirect(
        new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $categoryid, 'tab' => 'pricing']),
        get_string('invalidaction', 'local_localcustomadmin'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

try {
    $count = sync_category_prices_to_courses($categoryid);
    $message = get_string('category_prices_synced', 'local_localcustomadmin', $count);
    redirect(new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $categoryid, 'tab' => 'pricing']), $message, null, \core\output\notification::NOTIFY_SUCCESS);
} catch (Exception $e) {
    redirect(new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => $categoryid, 'tab' => 'pricing']), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}

/**
 * Sincroniza os preços da categoria para todos os cursos da categoria e subcategorias.
 * @param int $categoryid
 * @return int Número de cursos atualizados
 */
function sync_category_prices_to_courses($categoryid) {
    global $DB;

    $prices = $DB->get_records('local_customadmin_category_prices', ['categoryid' => $categoryid]);
    if (!$prices) {
        throw new Exception(get_string('no_category_prices', 'local_localcustomadmin'));
    }

    // Buscar todas as subcategorias (filhas diretas e indiretas) da categoria pai usando core_course_category::get_children()
    require_once($GLOBALS['CFG']->dirroot . '/course/classes/category.php');
    $allcategoryids = [];
    $queue = [$categoryid];
    $visited = [];
    while ($queue) {
        $current = array_pop($queue);
        if (in_array($current, $visited)) continue;
        $visited[] = $current;
        $allcategoryids[] = $current;
        $cat = \core_course_category::get($current, IGNORE_MISSING);
        if ($cat) {
            $children = $cat->get_children();
            foreach ($children as $childcat) {
                // Adiciona o id da subcategoria diretamente ao array de categorias
                if (!in_array($childcat->id, $visited) && !in_array($childcat->id, $queue)) {
                    $queue[] = $childcat->id;
                }
            }
        }
    }

    // Buscar todos os cursos dessas categorias usando função core_course_category::get_courses()
    $courses = [];
    foreach ($allcategoryids as $catid) {
        $cat = \core_course_category::get($catid, IGNORE_MISSING);
        if ($cat) {
            $catcourses = $cat->get_courses();
            foreach ($catcourses as $course) {
                if ($course->id > 1) {
                    $courses[$course->id] = $course;
                }
            }
        }
    }

    $updated = 0;
    foreach ($courses as $course) {
        foreach ($prices as $price) {
            $enroldata = [
                'enrol' => 'customstatus',
                'courseid' => $course->id,
                'name' => $price->name,
                'cost' => isset($price->price) ? $price->price : 0,
                'currency' => isset($price->currency) ? $price->currency : 'BRL',
                'customint1' => $price->id,
                'customint2' => isset($price->ispromotional) ? $price->ispromotional : 0,
                'customint3' => isset($price->isenrollmentfee) ? $price->isenrollmentfee : 0,
                'customint4' => isset($price->installments) ? $price->installments : 1,
                'timecreated' => time(),
                'enrolstartdate' => $price->startdate ? strtotime($price->startdate) : 0,
                'enrolenddate' => $price->enddate ? strtotime($price->enddate) : 0,
                'timemodified' => time()
            ];

            $enrol = $DB->get_record('enrol', [
                'courseid' => $course->id,
                'enrol' => 'customstatus',
                'customint1' => $price->id
            ]);

            try {                
                if (!$enrol) {
                    $DB->insert_record('enrol', (object)$enroldata);
                } else {
                    $enrol->cost = $enroldata['cost'];
                    $enrol->name = $enroldata['name'];
                    $enrol->currency = $enroldata['currency'];
                    $enrol->customint2 = $enroldata['customint2'];
                    $enrol->customint3 = $enroldata['customint3'];
                    $enrol->customint4 = $enroldata['customint4'];
                    $enrol->timemodified = $enroldata['timemodified'];
                    $DB->update_record('enrol', $enrol);
                }
                $updated++;
            } catch (Exception $e) {
                debugging('Erro ao inserir/atualizar enrol para cursoid=' . $course->id . ' e priceid=' . $price->id . ': ' . $e->getMessage());
            }
        }
    }
    debugging('Total de cursos atualizados/criados: ' . $updated);
    return $updated;
}
