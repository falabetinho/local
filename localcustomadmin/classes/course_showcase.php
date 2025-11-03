<?php
// This file is part of Moodle - http://moodle.org/

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * Course showcase helper class
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_showcase {
    
    /**
     * Get courses with prices from customstatus enrolment
     */
    public function get_courses_with_prices($categoryid = 0, $search = '', $sort = 'name') {
        global $DB;

        $params = [];
        $where = ['c.visible = 1', 'c.id != :siteid'];
        $params['siteid'] = SITEID;

        // Category filter
        if ($categoryid > 0) {
            $where[] = 'c.category = :categoryid';
            $params['categoryid'] = $categoryid;
        }

        // Search filter
        if (!empty($search)) {
            $where[] = '(c.fullname LIKE :search1 OR c.shortname LIKE :search2 OR c.summary LIKE :search3)';
            $searchparam = '%' . $DB->sql_like_escape($search) . '%';
            $params['search1'] = $searchparam;
            $params['search2'] = $searchparam;
            $params['search3'] = $searchparam;
        }

        // Build ORDER BY
        $orderby = 'c.fullname ASC';
        if ($sort === 'popular') {
            $orderby = 'enrollments DESC, c.fullname ASC';
        } else if ($sort === 'price') {
            $orderby = 'min_price ASC, c.fullname ASC';
        }

        $sql = "SELECT
                    c.id,
                    c.fullname,
                    c.shortname,
                    c.summary,
                    c.category,
                    COUNT(DISTINCT ue.userid) AS enrollments,
                    MIN(CASE WHEN e.enrol = 'customstatus' AND e.status = 0 THEN e.cost END) AS min_price,
                    MAX(CASE WHEN e.enrol = 'customstatus' AND e.status = 0 THEN e.cost END) AS max_price
                FROM {course} c
                LEFT JOIN {enrol} e ON e.courseid = c.id
                LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE " . implode(' AND ', $where) . "
                GROUP BY c.id, c.fullname, c.shortname, c.summary, c.category
                ORDER BY {$orderby}";

        $courses = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($courses as $course) {
            // Imagem via File API (sem depender de campos inexistentes)
            $courseimage = $this->get_course_image((int)$course->id);

            // Categoria
            $category = $DB->get_record('course_categories', ['id' => $course->category]);

            // Preço formatado
            $priceinfo = $this->format_price_info($course->min_price, $course->max_price);

            $result[] = [
                'id' => $course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname),
                'summary' => format_text($course->summary, FORMAT_HTML),
                'summary_short' => shorten_text(strip_tags($course->summary), 120),
                'categoryname' => $category ? format_string($category->name) : '',
                'enrollments' => (int)$course->enrollments,
                'courseimage' => $courseimage,
                'courseurl' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(),
                'price_display' => $priceinfo['display'],
                'has_price' => $priceinfo['has_price'],
                'price_class' => $priceinfo['class'],
                'min_price_formatted' => $priceinfo['min_formatted'],
                'max_price_formatted' => $priceinfo['max_formatted']
            ];
        }

        return $result;
    }

    /**
     * Get course image using Moodle File API
     */
    private function get_course_image(int $courseid) {
        global $OUTPUT;

        $fs = get_file_storage();
        $context = \context_course::instance($courseid);

        // Busca primeira imagem válida na área course/overviewfiles
        $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', 0, 'filename', false);
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                $url = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    null,
                    $file->get_filepath(),
                    $file->get_filename()
                );
                return $url->out(false);
            }
        }

        // Fallback
        return $OUTPUT->get_generated_image_for_id($courseid);
    }

    /**
     * Format price information
     */
    private function format_price_info($min_price, $max_price) {
        if ($min_price === null && $max_price === null) {
            return [
                'display' => get_string('free', 'local_localcustomadmin'),
                'has_price' => false,
                'class' => 'price-free',
                'min_formatted' => '',
                'max_formatted' => ''
            ];
        }

        $min = (float)$min_price;
        $max = (float)$max_price;

        $min_formatted = 'R$ ' . number_format($min, 2, ',', '.');
        $max_formatted = 'R$ ' . number_format($max, 2, ',', '.');

        $display = ($min == $max) ? $min_formatted : ($min_formatted . ' - ' . $max_formatted);

        return [
                'display' => $display,
                'has_price' => true,
                'class' => 'price-paid',
                'min_formatted' => $min_formatted,
                'max_formatted' => $max_formatted
        ];
    }
    
    /**
     * Get categories
     */
    public function get_categories() {
        global $DB;
        
        $categories = $DB->get_records('course_categories', ['visible' => 1], 'name ASC');
        
        $result = [];
        foreach ($categories as $cat) {
            $result[] = [
                'id' => $cat->id,
                'name' => format_string($cat->name)
            ];
        }
        
        return $result;
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        global $DB;
        
        $total_courses = $DB->count_records('course', ['visible' => 1]) - 1;
        $total_students = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT ue.userid)
             FROM {user_enrolments} ue
             JOIN {enrol} e ON e.id = ue.enrolid
             JOIN {course} c ON c.id = e.courseid
             WHERE c.visible = 1 AND c.id != ?",
            [SITEID]
        );
        
        return [
            [
                'icon' => 'fa-graduation-cap',
                'value' => $total_courses,
                'label' => get_string('total_courses', 'local_localcustomadmin'),
                'class' => 'stat-primary'
            ],
            [
                'icon' => 'fa-users',
                'value' => $total_students,
                'label' => get_string('total_students', 'local_localcustomadmin'),
                'class' => 'stat-success'
            ]
        ];
    }
}
