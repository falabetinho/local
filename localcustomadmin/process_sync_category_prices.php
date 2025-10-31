<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$action = required_param('action', PARAM_ALPHA);
$categoryid = required_param('categoryid', PARAM_INT);

// Corrigir para aceitar apenas 'sync_category' e evitar erro de ação inválida
if ($action !== 'sync_category') {
    print_error('invalidaction', 'local_localcustomadmin');
    exit;
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

    // Buscar preços da categoria (exemplo: tabela local_customadmin_category_prices)
    $prices = $DB->get_records('local_customadmin_category_prices', ['categoryid' => $categoryid]);
    if (!$prices) {
        throw new Exception(get_string('no_category_prices', 'local_localcustomadmin'));
    }

    // Buscar todas as categorias filhas (recursivo)
    $allcategoryids = [$categoryid];
    $queue = [$categoryid];
    while ($queue) {
        $current = array_pop($queue);
        $children = $DB->get_records('course_categories', ['parent' => $current]);
        foreach ($children as $child) {
            $allcategoryids[] = $child->id;
            $queue[] = $child->id;
        }
    }

    // Buscar todos os cursos dessas categorias
    list($in_sql, $params) = $DB->get_in_or_equal($allcategoryids, SQL_PARAMS_NAMED);
    $courses = $DB->get_records_select('course', "category $in_sql", $params);

    $updated = 0;
    foreach ($courses as $course) {
        foreach ($prices as $price) {
            // Buscar enrol customstatus para o curso e preço (usando customint1 para vincular ao id do preço)
            $enrol = $DB->get_record('enrol', [
                'courseid' => $course->id,
                'enrol' => 'customstatus',
                'customint1' => $price->id // customint1 usado para vincular ao id do preço
            ]);
            if (!$enrol) {
                // Criar enrol vinculado ao preço
                $enrol = new stdClass();
                $enrol->enrol = 'customstatus';
                $enrol->courseid = $course->id;
                $enrol->cost = $price->price;
                $enrol->currency = $price->currency;
                $enrol->customint1 = $price->id; // vincula ao preço
                $enrol->customint2 = $price->ispromotional;
                $enrol->customint3 = $price->isenrollmentfee;
                $enrol->customint4 = $price->installments;
                $enrol->timecreated = time();
                $enrol->timemodified = time();
                $DB->insert_record('enrol', $enrol);
            } else {
                // Atualizar enrol existente
                $enrol->cost = $price->price;
                $enrol->currency = $price->currency;
                $enrol->customint2 = $price->ispromotional;
                $enrol->customint3 = $price->isenrollmentfee;
                $enrol->customint4 = $price->installments;
                $enrol->timemodified = time();
                $DB->update_record('enrol', $enrol);
            }
            $updated++;
        }
    }
    return $updated;
}
