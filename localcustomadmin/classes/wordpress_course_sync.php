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
 * WordPress Course Synchronization Handler
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/localcustomadmin/classes/wordpress_api.php');

/**
 * Class for synchronizing Moodle courses with WordPress
 */
class wordpress_course_sync {

    /** @var wordpress_api WordPress API instance */
    private $api;

    /** @var \moodle_database Database instance */
    private $db;

    /** @var string WordPress post type for courses */
    private $post_type = 'curso';

    /** @var string WordPress post type route (plural) */
    private $post_type_route = 'cursos';

    /** @var string WordPress pricing endpoint */
    private $pricing_endpoint = 'fluentelegante/v1/pricing';

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->api = new wordpress_api();
    }

    /**
     * Sync a single course to WordPress
     *
     * @param int $courseid Moodle course ID
     * @return array Result with success status and message
     */
    public function sync_course($courseid) {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        $course = $this->db->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        if ($course->id == SITEID) {
            return ['success' => false, 'message' => 'Cannot sync site course'];
        }

        try {
            // Check if course already synced
            $mapping = $this->db->get_record('local_customadmin_wp_mapping', [
                'moodle_type' => 'course',
                'moodle_id' => $courseid
            ]);

            // Get course price from enrolment
            $price_data = $this->get_course_price($courseid);

            // Prepare course data for WordPress
            $coursedata = $this->prepare_course_data($course, $price_data);

            if ($mapping && $mapping->wordpress_id) {
                // Update existing post
                $result = $this->api->request(
                    "/{$this->post_type_route}/{$mapping->wordpress_id}",
                    'POST',
                    $coursedata
                );

                if ($result['success']) {
                    $this->update_mapping($mapping->id, 'synced');
                    
                    // Sync price separately if available
                    if ($price_data['has_price']) {
                        $this->sync_course_price($mapping->wordpress_id, $price_data);
                    }
                    
                    return [
                        'success' => true,
                        'message' => 'Course updated successfully',
                        'wordpress_id' => $mapping->wordpress_id
                    ];
                } else {
                    $this->update_mapping($mapping->id, 'error', $result['message']);
                    return ['success' => false, 'message' => $result['message']];
                }
            } else {
                // Create new post
                $result = $this->api->request(
                    "/{$this->post_type_route}",
                    'POST',
                    $coursedata
                );

                if ($result['success'] && isset($result['data']->id)) {
                    $wpid = $result['data']->id;
                    
                    // Create or update mapping
                    if ($mapping) {
                        $this->update_mapping_wordpress_id($mapping->id, $wpid, 'synced');
                    } else {
                        $this->create_mapping($courseid, $wpid);
                    }

                    // Sync price separately if available
                    if ($price_data['has_price']) {
                        $this->sync_course_price($wpid, $price_data);
                    }

                    return [
                        'success' => true,
                        'message' => 'Course created successfully',
                        'wordpress_id' => $wpid
                    ];
                } else {
                    if ($mapping) {
                        $this->update_mapping($mapping->id, 'error', $result['message']);
                    }
                    return ['success' => false, 'message' => $result['message']];
                }
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Sync multiple courses to WordPress
     *
     * @param array $courseids Array of course IDs
     * @return array Results for each course
     */
    public function sync_courses($courseids) {
        $results = [];
        foreach ($courseids as $courseid) {
            $results[$courseid] = $this->sync_course($courseid);
        }
        return $results;
    }

    /**
     * Sync all courses to WordPress
     *
     * @param int $categoryid Optional category ID to filter courses
     * @return array Results summary
     */
    public function sync_all_courses($categoryid = null) {
        $params = ['id > ?' => SITEID]; // Exclude site course
        
        if ($categoryid) {
            $params['category'] = $categoryid;
        }

        $courses = $this->db->get_records('course', $params);
        
        $results = [
            'total' => count($courses),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($courses as $course) {
            $result = $this->sync_course($course->id);
            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'course_id' => $course->id,
                    'course_name' => $course->fullname,
                    'message' => $result['message']
                ];
            }
        }

        return $results;
    }

    /**
     * Get course price from enrolment instances
     *
     * @param int $courseid Course ID
     * @return array Price data
     */
    public function get_course_price($courseid) {
        // Check for enrol_fee plugin
        $enrol = $this->db->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'fee',
            'status' => 0 // Active
        ], 'cost, currency');

        if ($enrol && !empty($enrol->cost)) {
            return [
                'has_price' => true,
                'price' => floatval($enrol->cost),
                'currency' => $enrol->currency ?: 'BRL',
                'active' => true
            ];
        }

        // Check for enrol_paypal plugin
        $enrol = $this->db->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'paypal',
            'status' => 0
        ], 'cost, currency');

        if ($enrol && !empty($enrol->cost)) {
            return [
                'has_price' => true,
                'price' => floatval($enrol->cost),
                'currency' => $enrol->currency ?: 'BRL',
                'active' => true
            ];
        }

        return [
            'has_price' => false,
            'price' => 0,
            'currency' => 'BRL',
            'active' => false
        ];
    }

    /**
     * Sync course price to WordPress
     *
     * @param int $wordpress_course_id WordPress course post ID
     * @param array $price_data Price information
     * @return array Result
     */
    private function sync_course_price($wordpress_course_id, $price_data) {
        if (!$price_data['has_price']) {
            return ['success' => false, 'message' => 'No price to sync'];
        }

        $pricing_data = [
            'cursos' => [
                [
                    'id' => $wordpress_course_id,
                    'preco' => $price_data['price'],
                    'moeda' => $price_data['currency'],
                    'ativo' => $price_data['active']
                ]
            ]
        ];

        return $this->api->request(
            "/{$this->pricing_endpoint}/sync",
            'POST',
            $pricing_data
        );
    }

    /**
     * Bulk sync prices for multiple courses
     *
     * @param array $course_prices Array of [wordpress_id => price_data]
     * @return array Result
     */
    public function bulk_sync_prices($course_prices) {
        $cursos = [];
        
        foreach ($course_prices as $wpid => $price_data) {
            if ($price_data['has_price']) {
                $cursos[] = [
                    'id' => $wpid,
                    'preco' => $price_data['price'],
                    'moeda' => $price_data['currency'],
                    'ativo' => $price_data['active']
                ];
            }
        }

        if (empty($cursos)) {
            return ['success' => false, 'message' => 'No prices to sync'];
        }

        return $this->api->request(
            "/{$this->pricing_endpoint}/sync",
            'POST',
            ['cursos' => $cursos]
        );
    }

    /**
     * Prepare course data for WordPress
     *
     * @param object $course Moodle course object
     * @param array $price_data Price information
     * @return array WordPress post data
     */
    private function prepare_course_data($course, $price_data) {
        global $CFG;

        $data = [
            'title' => $course->fullname,
            'content' => $course->summary ?: '',
            'status' => $course->visible ? 'publish' : 'draft',
            'meta' => [
                'moodle_id' => $course->id,
                'moodle_shortname' => $course->shortname,
                'moodle_idnumber' => $course->idnumber ?: '',
                'moodle_url' => $CFG->wwwroot . '/course/view.php?id=' . $course->id,
                'start_date' => $course->startdate,
                'end_date' => $course->enddate ?: 0,
            ]
        ];

        // Add price as metadata
        if ($price_data['has_price']) {
            $data['meta']['price'] = $price_data['price'];
            $data['meta']['currency'] = $price_data['currency'];
            $data['meta']['price_active'] = $price_data['active'];
        }

        // Add category taxonomy if course has category
        if ($course->category) {
            $category = $this->db->get_record('course_categories', ['id' => $course->category]);
            if ($category) {
                $cat_mapping = $this->db->get_record('local_customadmin_wp_mapping', [
                    'moodle_type' => 'category',
                    'moodle_id' => $course->category
                ]);
                
                if ($cat_mapping && $cat_mapping->wordpress_id) {
                    $data['nivel'] = [$cat_mapping->wordpress_id];
                }
            }
        }

        return $data;
    }

    /**
     * Create mapping record
     *
     * @param int $courseid Moodle course ID
     * @param int $wpid WordPress post ID
     */
    private function create_mapping($courseid, $wpid) {
        $mapping = new \stdClass();
        $mapping->moodle_type = 'course';
        $mapping->moodle_id = $courseid;
        $mapping->wordpress_type = 'post';
        $mapping->wordpress_id = $wpid;
        $mapping->wordpress_post_type = $this->post_type;
        $mapping->sync_status = 'synced';
        $mapping->last_synced = time();
        $mapping->timecreated = time();
        $mapping->timemodified = time();

        $this->db->insert_record('local_customadmin_wp_mapping', $mapping);
    }

    /**
     * Update mapping status
     *
     * @param int $mappingid Mapping record ID
     * @param string $status New status
     * @param string $error Optional error message
     */
    private function update_mapping($mappingid, $status, $error = null) {
        $mapping = new \stdClass();
        $mapping->id = $mappingid;
        $mapping->sync_status = $status;
        $mapping->last_synced = time();
        $mapping->timemodified = time();
        
        if ($error) {
            $mapping->sync_error = $error;
        } else {
            $mapping->sync_error = null;
        }

        $this->db->update_record('local_customadmin_wp_mapping', $mapping);
    }

    /**
     * Update mapping with WordPress ID
     *
     * @param int $mappingid Mapping record ID
     * @param int $wpid WordPress post ID
     * @param string $status Sync status
     */
    private function update_mapping_wordpress_id($mappingid, $wpid, $status) {
        $mapping = new \stdClass();
        $mapping->id = $mappingid;
        $mapping->wordpress_id = $wpid;
        $mapping->wordpress_type = 'post';
        $mapping->wordpress_post_type = $this->post_type;
        $mapping->sync_status = $status;
        $mapping->last_synced = time();
        $mapping->timemodified = time();
        $mapping->sync_error = null;

        $this->db->update_record('local_customadmin_wp_mapping', $mapping);
    }

    /**
     * Get sync status for a course
     *
     * @param int $courseid Course ID
     * @return object|false Mapping record or false
     */
    public function get_sync_status($courseid) {
        return $this->db->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => 'course',
            'moodle_id' => $courseid
        ]);
    }

    /**
     * Get all synced courses
     *
     * @return array Array of mapping records
     */
    public function get_synced_courses() {
        return $this->db->get_records('local_customadmin_wp_mapping', [
            'moodle_type' => 'course',
            'sync_status' => 'synced'
        ]);
    }

    /**
     * Get WordPress course ID for a Moodle course
     *
     * @param int $courseid Moodle course ID
     * @return int|false WordPress post ID or false
     */
    public function get_wordpress_course_id($courseid) {
        $mapping = $this->get_sync_status($courseid);
        return $mapping ? $mapping->wordpress_id : false;
    }
}
