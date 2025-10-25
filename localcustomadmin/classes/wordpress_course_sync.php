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

    /** @var string WordPress courses resource path */
    private $courses_path;

    /** @var string WordPress prices resource path */
    private $prices_path;

    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;

        // Load WordPress API credentials and base URL from plugin settings
        $wordpress_base_url = get_config('local_localcustomadmin', 'wordpress_base_url');
        $username = get_config('local_localcustomadmin', 'wordpress_username');
        $apppassword = get_config('local_localcustomadmin', 'wordpress_apppassword');
        
        $this->courses_path = get_config('local_localcustomadmin', 'wordpress_courses_path');
        $this->prices_path = get_config('local_localcustomadmin', 'wordpress_prices_path');

        // Validate configurations
        if (!$wordpress_base_url) {
            throw new \moodle_exception('missingconfig', 'local_localcustomadmin', '', 'WordPress Base URL is not configured');
        }
        if (!$username) {
            throw new \moodle_exception('missingconfig', 'local_localcustomadmin', '', 'WordPress Username is not configured');
        }
        if (!$apppassword) {
            throw new \moodle_exception('missingconfig', 'local_localcustomadmin', '', 'WordPress Application Password is not configured');
        }
        if (!$this->courses_path) {
            throw new \moodle_exception('missingconfig', 'local_localcustomadmin', '', 'WordPress Courses Path is not configured');
        }
        if (!$this->prices_path) {
            throw new \moodle_exception('missingconfig', 'local_localcustomadmin', '', 'WordPress Prices Path is not configured');
        }

        // Initialize WordPress API client
        $this->api = new wordpress_api($wordpress_base_url, $username, $apppassword);
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

        error_log("WordPress Course Sync: Starting sync for course ID {$courseid}");

        $course = $this->db->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        if ($course->id == SITEID) {
            error_log("WordPress Course Sync: Cannot sync site course");
            return ['success' => false, 'message' => 'Cannot sync site course'];
        }

        try {
            // Check if course already synced
            $mapping = $this->db->get_record('local_customadmin_wp_mapping', [
                'moodle_type' => 'course',
                'moodle_id' => $courseid
            ]);

            error_log("WordPress Course Sync: Mapping exists: " . ($mapping ? 'yes' : 'no'));

            // Prepare course data for WordPress
            $coursedata = $this->prepare_course_data($course);
            error_log("WordPress Course Sync: Course data prepared: " . json_encode($coursedata));

            if ($mapping && $mapping->wordpress_id) {
                // Update existing post
                $result = $this->api->request(
                    'POST',
                    "{$this->courses_path}/{$mapping->wordpress_id}",
                    $coursedata
                );

                if ($result !== false) {
                    $this->update_mapping($mapping->id, 'synced');
                    return [
                        'success' => true,
                        'message' => 'Course updated successfully',
                        'wordpress_id' => $mapping->wordpress_id
                    ];

                    
                } else {
                    $error = $this->api->get_last_error();
                    $errormsg = $error ? $error['message'] : 'Unknown error';
                    
                    // Check if post was deleted in WordPress and remove stale mapping
                    if ($this->handle_invalid_post_error($errormsg, $mapping->id)) {
                        error_log("WordPress Course Sync: Mapping removed, retrying sync as new post for course ID {$courseid}");
                        // Recursively call to create a new post since mapping was removed
                        return $this->sync_course($courseid);
                    }
                    
                    $this->update_mapping($mapping->id, 'error', $errormsg);
                    return ['success' => false, 'message' => $errormsg];
                }
            } else {
                // Create new post
                $result = $this->api->request(
                    'POST',
                    $this->courses_path,
                    $coursedata
                );

                if ($result !== false && isset($result['id'])) {
                    $wpid = $result['id'];
                    
                    // Create or update mapping
                    if ($mapping) {
                        $this->update_mapping_wordpress_id($mapping->id, $wpid, 'synced');
                    } else {
                        $this->create_mapping($courseid, $wpid);
                    }

                    return [
                        'success' => true,
                        'message' => 'Course created successfully',
                        'wordpress_id' => $wpid
                    ];
                } else {
                    $error = $this->api->get_last_error();
                    $errormsg = $error ? $error['message'] : 'Unknown error creating course';
                    if ($mapping) {
                        $this->update_mapping($mapping->id, 'error', $errormsg);
                    }
                    return ['success' => false, 'message' => $errormsg];
                }
            }
        } catch (\Exception $e) {
            error_log("WordPress Course Sync ERROR: " . $e->getMessage());
            error_log("WordPress Course Sync TRACE: " . $e->getTraceAsString());
            
            // Check if error indicates invalid post and remove mapping if so
            if ($mapping && $mapping->id) {
                if ($this->handle_invalid_post_error($e->getMessage(), $mapping->id)) {
                    error_log("WordPress Course Sync: Mapping removed in catch block, retrying sync for course ID {$courseid}");
                    // Retry as new post since mapping was removed
                    return $this->sync_course($courseid);
                }
            }
            
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
     * Prepare course data for WordPress
     *
     * @param object $course Moodle course object
     * @return array WordPress post data
     */
    private function prepare_course_data($course) {
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

        // Add category taxonomy if course has category
        if ($course->category) {
            $category = $this->db->get_record('course_categories', ['id' => $course->category]);
            if ($category) {
                $cat_mapping = $this->db->get_record('local_customadmin_wp_mapping', [
                    'moodle_type' => 'category',
                    'moodle_id' => $course->category
                ]);
                
                if ($cat_mapping && $cat_mapping->wordpress_id) {
                    // WordPress REST API expects taxonomy field as array of integers
                    $niveis = [intval($cat_mapping->wordpress_id)];
                    
                    // Check if category has parent and add it too
                    if ($category->parent > 0) {
                        $parent_mapping = $this->db->get_record('local_customadmin_wp_mapping', [
                            'moodle_type' => 'category',
                            'moodle_id' => $category->parent
                        ]);
                        
                        if ($parent_mapping && $parent_mapping->wordpress_id) {
                            // Add parent first (WordPress hierarchical taxonomy convention)
                            array_unshift($niveis, intval($parent_mapping->wordpress_id));
                            error_log("WordPress Course Sync: Adding parent nivel taxonomy ID: " . $parent_mapping->wordpress_id);
                        } else {
                            error_log("WordPress Course Sync: Parent category {$category->parent} not synced with WordPress");
                        }
                    }
                    
                    $data['niveis'] = $niveis;
                    error_log("WordPress Course Sync: Adding niveis taxonomy IDs: " . json_encode($niveis) . " for category: {$category->name}");
                } else {
                    error_log("WordPress Course Sync: WARNING - Category {$course->category} ({$category->name}) not synced with WordPress. Please sync categories first.");
                }
            } else {
                error_log("WordPress Course Sync: Category ID {$course->category} not found in Moodle");
            }
        } else {
            error_log("WordPress Course Sync: Course {$course->id} has no category");
        }

        error_log("WordPress Course Sync: Final course data: " . json_encode($data));
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
     * Check if error indicates invalid/deleted WordPress post
     * If so, remove the mapping to allow resync
     *
     * @param string $error Error message
     * @param int $mappingid Mapping ID
     * @return bool True if mapping was removed
     */
    private function handle_invalid_post_error($error, $mappingid) {
        // Log the error for debugging
        error_log("WordPress Course Sync: Checking error for mapping ID {$mappingid}: " . $error);

        // Check for common "invalid post" error messages
        $invalid_post_errors = [
            'invalid post id',
            'invalid_post_id',
            'post_not_found',
            'no post found',
            'http 404',
            'http/1.1 404',
            'rest_post_invalid_id',
            'rest_forbidden',
            'rest_post_invalid_page_number'
        ];

        $error_lower = strtolower($error);
        foreach ($invalid_post_errors as $invalid_error) {
            if (strpos($error_lower, $invalid_error) !== false) {
                error_log("WordPress Course Sync: Detected invalid post error ('{$invalid_error}'), removing mapping ID {$mappingid}");
                $deleted = $this->db->delete_records('local_customadmin_wp_mapping', ['id' => $mappingid]);
                error_log("WordPress Course Sync: Delete result: " . ($deleted ? 'SUCCESS' : 'FAILED'));
                return true;
            }
        }

        // Log additional details for HTTP 0 errors
        if (strpos($error_lower, 'http 0') !== false) {
            error_log("WordPress Course Sync: HTTP 0 detected. Possible connectivity issue or invalid endpoint.");
        }

        error_log("WordPress Course Sync: Error does not match invalid post patterns");
        return false;
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

    /**
     * Toggle course visibility (hide/show)
     *
     * @param int $courseid Moodle course ID
     * @param bool $visible Visibility status
     * @return array Result
     */
    public function toggle_visibility($courseid, $visible) {
        $mapping = $this->get_sync_status($courseid);
        
        if (!$mapping || !$mapping->wordpress_id) {
            return ['success' => false, 'message' => 'Course not synced with WordPress'];
        }

        try {
            $data = [
                'status' => $visible ? 'publish' : 'draft'
            ];

            $result = $this->api->request(
                'POST',
                "/{$this->post_type_route}/{$mapping->wordpress_id}",
                $data
            );

            if ($result !== false) {
                return [
                    'success' => true,
                    'message' => $visible ? 'Course published' : 'Course hidden',
                    'visible' => $visible
                ];
            } else {
                $error = $this->api->get_last_error();
                $errormsg = $error ? $error['message'] : 'Unknown error';
                
                // Check if post was deleted in WordPress and remove stale mapping
                if ($this->handle_invalid_post_error($errormsg, $mapping->id)) {
                    return [
                        'success' => false,
                        'message' => 'Post not found in WordPress. Mapping removed. Please sync the course again.',
                        'mapping_removed' => true
                    ];
                }
                
                return ['success' => false, 'message' => $errormsg];
            }
        } catch (\Exception $e) {
            // Check if error indicates invalid post and remove mapping if so
            if ($this->handle_invalid_post_error($e->getMessage(), $mapping->id)) {
                return [
                    'success' => false,
                    'message' => 'Post not found in WordPress. Mapping removed. Please sync the course again.',
                    'mapping_removed' => true
                ];
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete course from WordPress
     *
     * @param int $courseid Moodle course ID
     * @param bool $remove_mapping Remove mapping record
     * @return array Result
     */
    public function delete_course($courseid, $remove_mapping = false) {
        $mapping = $this->get_sync_status($courseid);
        
        if (!$mapping || !$mapping->wordpress_id) {
            return ['success' => false, 'message' => 'Course not synced with WordPress'];
        }

        try {
            $result = $this->api->request(
                'DELETE',
                "/{$this->post_type_route}/{$mapping->wordpress_id}?force=true"
            );
            if ($result !== false) {
                if ($remove_mapping) {
                    // Delete mapping record
                    $this->db->delete_records('local_customadmin_wp_mapping', ['id' => $mapping->id]);
                } else {
                    // Mark as pending resync
                    $this->update_mapping($mapping->id, 'pending');
                }
                return [
                    'success' => true,
                    'message' => 'Course deleted from WordPress'
                ];
            } else {
                $error = $this->api->get_last_error();
                $errormsg = $error ? $error['message'] : 'Unknown error';
                // Check if post was already deleted in WordPress
                if ($this->handle_invalid_post_error($errormsg, $mapping->id)) {
                    return [
                        'success' => true,
                        'message' => 'Post already deleted in WordPress. Mapping removed.',
                        'already_deleted' => true
                    ];
                }
                return ['success' => false, 'message' => $errormsg];
            }
        } catch (\Exception $e) {
            // Check if post was already deleted in WordPress
            if ($this->handle_invalid_post_error($e->getMessage(), $mapping->id)) {
                return [
                    'success' => true,
                    'message' => 'Post already deleted in WordPress. Mapping removed.',
                    'already_deleted' => true
                ];
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
