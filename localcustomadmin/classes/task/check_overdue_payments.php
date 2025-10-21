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
 * Scheduled task to check overdue payments
 *
 * @package    local_localcustomadmin
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/localcustomadmin/classes/api/customstatus_integration.php');

use local_localcustomadmin\api\customstatus_integration;

/**
 * Task para verificar pagamentos vencidos e atualizar status automaticamente.
 */
class check_overdue_payments extends \core\task\scheduled_task {
    
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('checkoverduepayments', 'local_localcustomadmin');
    }
    
    /**
     * Execute the task
     */
    public function execute() {
        global $DB;
        
        mtrace('Checking overdue payments...');
        
        // Verificar se Custom Status está disponível
        if (!customstatus_integration::is_available()) {
            mtrace('Custom Status plugin not available. Skipping task.');
            return;
        }
        
        // Obter todas as categorias com preços definidos
        $sql = "SELECT DISTINCT categoryid 
                FROM {local_customadmin_category_prices}
                WHERE price > 0";
        
        $categories = $DB->get_records_sql($sql);
        
        $totalupdated = 0;
        
        foreach ($categories as $category) {
            mtrace("Processing category ID: {$category->categoryid}");
            
            // Obter estatísticas atuais
            $stats = customstatus_integration::get_category_statistics($category->categoryid);
            mtrace("  - Total students: {$stats['total']}");
            mtrace("  - Paid: {$stats['paid']}");
            mtrace("  - Payment due: {$stats['payment_due']}");
            
            // Obter usuários que precisam ser marcados como inadimplentes
            // Critério: matriculados há mais de 7 dias sem status 'paid'
            $sql = "SELECT DISTINCT ue.userid
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON e.id = ue.enrolid
                    JOIN {course} c ON c.id = e.courseid
                    WHERE c.category = :categoryid
                    AND e.enrol = 'customstatus'
                    AND ue.status = 0
                    AND ue.timecreated < :threshold
                    AND NOT EXISTS (
                        SELECT 1
                        FROM {enrol_customstatus_user} csu
                        JOIN {enrol_customstatus_status} css ON css.id = csu.statusid
                        WHERE csu.userid = ue.userid
                        AND csu.enrolid = e.id
                        AND css.shortname IN ('paid', 'scholarship', 'complimentary')
                    )";
            
            // 7 dias atrás
            $threshold = time() - (7 * 24 * 60 * 60);
            
            $overdueusers = $DB->get_records_sql($sql, [
                'categoryid' => $category->categoryid,
                'threshold' => $threshold
            ]);
            
            mtrace("  - Found " . count($overdueusers) . " overdue users");
            
            // Marcar como payment_due
            foreach ($overdueusers as $user) {
                $result = customstatus_integration::assign_status_to_user(
                    $user->userid,
                    $category->categoryid,
                    'payment_due',
                    'Atualização automática - Pagamento em atraso'
                );
                
                if ($result) {
                    $totalupdated++;
                    mtrace("  - Updated user ID: {$user->userid}");
                    
                    // Opcional: Enviar notificação por email
                    $this->send_overdue_notification($user->userid, $category->categoryid);
                }
            }
        }
        
        mtrace("Task completed. Total users updated: $totalupdated");
    }
    
    /**
     * Enviar notificação de pagamento em atraso.
     *
     * @param int $userid ID do usuário
     * @param int $categoryid ID da categoria
     */
    private function send_overdue_notification($userid, $categoryid) {
        global $DB;
        
        $user = $DB->get_record('user', ['id' => $userid]);
        $category = $DB->get_record('course_categories', ['id' => $categoryid]);
        
        if (!$user || !$category) {
            return;
        }
        
        // Obter preço da categoria
        $price = customstatus_integration::get_category_price($categoryid);
        
        // Preparar mensagem
        $subject = 'Lembrete de Pagamento - ' . format_string($category->name);
        
        $message = "Olá " . fullname($user) . ",\n\n";
        $message .= "Identificamos que o pagamento referente à categoria '" . format_string($category->name) . "' está pendente.\n\n";
        
        if ($price) {
            $message .= "Valor: R$ " . number_format($price, 2, ',', '.') . "\n\n";
        }
        
        $message .= "Por favor, regularize sua situação para continuar acessando os cursos.\n\n";
        $message .= "Atenciosamente,\n";
        $message .= "Equipe Acadêmica";
        
        // Enviar email
        $admin = get_admin();
        email_to_user($user, $admin, $subject, $message);
        
        mtrace("    - Notification sent to: {$user->email}");
    }
}
