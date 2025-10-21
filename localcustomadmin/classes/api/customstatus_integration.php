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
 * Custom Status Integration API
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin\api;

defined('MOODLE_INTERNAL') || die();

/**
 * API para integração com o plugin enrol_customstatus.
 */
class customstatus_integration {
    
    /**
     * Verificar se o plugin Custom Status está disponível.
     *
     * @return bool True se disponível
     */
    public static function is_available() {
        return class_exists('enrol_customstatus\api\integration_api');
    }
    
    /**
     * Obter preço de uma categoria (para Custom Status).
     *
     * @param int $categoryid ID da categoria
     * @return float|null Preço ou null se não definido
     */
    public static function get_category_price($categoryid) {
        global $DB;
        
        $record = $DB->get_record('local_customadmin_category_prices', 
            ['categoryid' => $categoryid], 
            'price',
            IGNORE_MULTIPLE
        );
        
        return $record ? (float)$record->price : null;
    }
    
    /**
     * Verificar se usuário pagou categoria.
     * (Placeholder - implementar conforme sistema de pagamento)
     *
     * @param int $userid ID do usuário
     * @param int $categoryid ID da categoria
     * @return bool True se pagou
     */
    public static function has_user_paid($userid, $categoryid) {
        global $DB;
        
        // TODO: Implementar verificação real de pagamento
        // Por enquanto, verifica se usuário tem status 'paid' no Custom Status
        
        if (!self::is_available()) {
            return false;
        }
        
        // Obter status do usuário nos cursos da categoria
        $sql = "SELECT DISTINCT csu.id
                FROM {enrol_customstatus_user} csu
                JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                JOIN {enrol} e ON e.id = csu.enrolid
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                AND csu.userid = :userid
                AND css.shortname = 'paid'";
        
        return $DB->record_exists_sql($sql, [
            'categoryid' => $categoryid,
            'userid' => $userid
        ]);
    }
    
    /**
     * Registrar pagamento e atualizar status do aluno.
     *
     * @param int $userid ID do usuário
     * @param int $categoryid ID da categoria
     * @param float $amount Valor pago
     * @return bool Sucesso
     */
    public static function register_payment($userid, $categoryid, $amount) {
        global $DB;
        
        // TODO: Criar tabela local_customadmin_payments se necessário
        // Por enquanto, apenas atualiza status no Custom Status
        
        if (!self::is_available()) {
            return false;
        }
        
        // Atualizar status via API do Custom Status
        try {
            \enrol_customstatus\api\integration_api::handle_payment_completed($userid, $categoryid);
            return true;
        } catch (\Exception $e) {
            debugging('Error registering payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
    
    /**
     * Obter estatísticas de status para uma categoria.
     *
     * @param int $categoryid ID da categoria
     * @return array Estatísticas
     */
    public static function get_category_statistics($categoryid) {
        global $DB;
        
        if (!self::is_available()) {
            return [
                'total' => 0,
                'paid' => 0,
                'payment_due' => 0,
                'blocked' => 0,
                'other' => 0
            ];
        }
        
        $stats = [
            'total' => 0,
            'paid' => 0,
            'payment_due' => 0,
            'blocked' => 0,
            'other' => 0
        ];
        
        // Total de alunos matriculados na categoria
        $sql = "SELECT COUNT(DISTINCT ue.userid) as total
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                AND e.enrol = 'customstatus'
                AND ue.status = 0";
        
        $result = $DB->get_record_sql($sql, ['categoryid' => $categoryid]);
        $stats['total'] = $result ? (int)$result->total : 0;
        
        // Status específicos
        $sql = "SELECT css.shortname, COUNT(DISTINCT csu.userid) as count
                FROM {enrol_customstatus_user} csu
                JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                JOIN {enrol} e ON e.id = csu.enrolid
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                GROUP BY css.shortname";
        
        $records = $DB->get_records_sql($sql, ['categoryid' => $categoryid]);
        
        foreach ($records as $record) {
            if (isset($stats[$record->shortname])) {
                $stats[$record->shortname] = (int)$record->count;
            } else {
                $stats['other'] += (int)$record->count;
            }
        }
        
        return $stats;
    }
    
    /**
     * Obter lista de usuários com status específico em uma categoria.
     *
     * @param int $categoryid ID da categoria
     * @param string $statusshortname Shortname do status (ex: 'payment_due')
     * @return array Lista de usuários
     */
    public static function get_users_by_status($categoryid, $statusshortname) {
        global $DB;
        
        if (!self::is_available()) {
            return [];
        }
        
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, 
                       css.name as statusname, css.color as statuscolor,
                       csu.timemodified
                FROM {user} u
                JOIN {enrol_customstatus_user} csu ON csu.userid = u.id
                JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                JOIN {enrol} e ON e.id = csu.enrolid
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                AND css.shortname = :shortname
                ORDER BY u.lastname, u.firstname";
        
        return $DB->get_records_sql($sql, [
            'categoryid' => $categoryid,
            'shortname' => $statusshortname
        ]);
    }
    
    /**
     * Atribuir status para usuário em todos os cursos de uma categoria.
     *
     * @param int $userid ID do usuário
     * @param int $categoryid ID da categoria
     * @param string $statusshortname Shortname do status
     * @param string $message Mensagem personalizada (opcional)
     * @return bool Sucesso
     */
    public static function assign_status_to_user($userid, $categoryid, $statusshortname, $message = '') {
        global $DB;
        
        if (!self::is_available()) {
            return false;
        }
        
        // Obter status ID
        $manager = new \enrol_customstatus\status_manager();
        $status = $manager->get_status_by_shortname($statusshortname);
        
        if (!$status) {
            return false;
        }
        
        // Obter todos os enrols customstatus da categoria
        $sql = "SELECT e.id
                FROM {enrol} e
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                AND e.enrol = 'customstatus'
                AND e.status = 0";
        
        $enrols = $DB->get_records_sql($sql, ['categoryid' => $categoryid]);
        
        $success = true;
        foreach ($enrols as $enrol) {
            // Verificar se usuário está matriculado
            if ($DB->record_exists('user_enrolments', ['enrolid' => $enrol->id, 'userid' => $userid])) {
                $result = $manager->assign_status($userid, $enrol->id, $status->id, null, $message);
                $success = $success && $result;
            }
        }
        
        return $success;
    }
    
    /**
     * Marcar pagamento como atrasado em massa para uma categoria.
     *
     * @param int $categoryid ID da categoria
     * @return int Número de usuários atualizados
     */
    public static function mark_category_overdue($categoryid) {
        global $DB;
        
        if (!self::is_available()) {
            return 0;
        }
        
        // Obter status "payment_due"
        $manager = new \enrol_customstatus\status_manager();
        $status = $manager->get_status_by_shortname('payment_due');
        
        if (!$status) {
            return 0;
        }
        
        // Obter todos os usuários da categoria que não têm status 'paid'
        $sql = "SELECT DISTINCT ue.userid
                FROM {user_enrolments} ue
                JOIN {enrol} e ON e.id = ue.enrolid
                JOIN {course} c ON c.id = e.courseid
                WHERE c.category = :categoryid
                AND e.enrol = 'customstatus'
                AND ue.status = 0
                AND NOT EXISTS (
                    SELECT 1
                    FROM {enrol_customstatus_user} csu
                    JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                    WHERE csu.userid = ue.userid
                    AND csu.enrolid = e.id
                    AND css.shortname = 'paid'
                )";
        
        $users = $DB->get_records_sql($sql, ['categoryid' => $categoryid]);
        
        $count = 0;
        foreach ($users as $user) {
            if (self::assign_status_to_user($user->userid, $categoryid, 'payment_due', 
                'Pagamento em atraso - atualização automática')) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Obter relatório completo de uma categoria.
     *
     * @param int $categoryid ID da categoria
     * @return array Dados do relatório
     */
    public static function get_category_report($categoryid) {
        global $DB;
        
        $report = [
            'category' => null,
            'price' => 0,
            'statistics' => [],
            'revenue' => [
                'expected' => 0,
                'received' => 0,
                'pending' => 0
            ],
            'users' => []
        ];
        
        // Informações da categoria
        $report['category'] = $DB->get_record('course_categories', ['id' => $categoryid]);
        
        // Preço da categoria
        $report['price'] = self::get_category_price($categoryid) ?? 0;
        
        // Estatísticas
        $report['statistics'] = self::get_category_statistics($categoryid);
        
        // Cálculo de receita
        $total = $report['statistics']['total'];
        $paid = $report['statistics']['paid'];
        $price = $report['price'];
        
        $report['revenue']['expected'] = $total * $price;
        $report['revenue']['received'] = $paid * $price;
        $report['revenue']['pending'] = $report['revenue']['expected'] - $report['revenue']['received'];
        
        // Lista de usuários por status
        $report['users']['paid'] = self::get_users_by_status($categoryid, 'paid');
        $report['users']['payment_due'] = self::get_users_by_status($categoryid, 'payment_due');
        $report['users']['blocked'] = self::get_users_by_status($categoryid, 'blocked');
        
        return $report;
    }
}
