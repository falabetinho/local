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
 * WordPress Category Synchronization
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

use core_course_category;

// Require the WordPress API class
require_once(__DIR__ . '/wordpress_api.php');

/**
 * WordPress Category Sync Class
 */
class wordpress_category_sync {
    
    /** @var wordpress_api WordPress API client */
    private $api;
    
    /** @var \stdClass Database manager */
    private $db;
    
    /** @var string WordPress taxonomy name */
    private $taxonomy = 'niveis';
    
    /** @var array Sync results */
    private $results = [
        'success' => 0,
        'updated' => 0,
        'errors' => 0,
        'skipped' => 0,
        'messages' => []
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->api = new wordpress_api();
    }
    
    /**
     * Sync all categories from Moodle to WordPress
     *
     * @param bool $forcesync Force sync even if already synced
     * @return array Sync results
     */
    public function sync_all_categories($forcesync = false) {
        // Reset results
        $this->results = [
            'success' => 0,
            'updated' => 0,
            'errors' => 0,
            'skipped' => 0,
            'messages' => []
        ];
        
        // Get all Moodle categories
        $categories = $this->db->get_records('course_categories', null, 'depth ASC, sortorder ASC');
        
        if (empty($categories)) {
            $this->results['messages'][] = get_string('no_categories_found', 'local_localcustomadmin');
            return $this->results;
        }
        
        foreach ($categories as $category) {
            try {
                $this->sync_category($category, $forcesync);
            } catch (\Exception $e) {
                $this->results['errors']++;
                $this->results['messages'][] = "Error syncing category {$category->name}: " . $e->getMessage();
            }
        }
        
        return $this->results;
    }
    
    /**
     * Sync a single category to WordPress
     *
     * @param \stdClass $category Moodle category object
     * @param bool $forcesync Force sync even if already synced
     * @return bool True on success
     * @throws \Exception On sync failure
     */
    public function sync_category($category, $forcesync = false) {
        // Check if category already has a mapping
        $mapping = $this->get_mapping('category', $category->id);
        
        if ($mapping && !$forcesync && $mapping->sync_status === 'synced') {
            $this->results['skipped']++;
            return true;
        }
        
        // Prepare WordPress term data
        $termdata = $this->prepare_category_data($category);
        
        // If mapping exists, try to update the term
        if ($mapping && $mapping->wordpress_id > 0) {
            $result = $this->api->update_term($this->taxonomy, $mapping->wordpress_id, $termdata);
            if ($result) {
                $this->update_mapping($mapping->id, 'synced');
                $this->results['updated']++;
                $this->results['messages'][] = "Updated category: {$category->name}";
                return true;
            } else {
                $error = $this->api->get_last_error();
                
                // If term doesn't exist (404), delete mapping and recreate
                if (strpos($error['message'], 'HTTP 404') !== false || strpos($error['message'], 'does not exist') !== false) {
                    // Delete old mapping
                    $this->db->delete_records('local_customadmin_wp_mapping', ['id' => $mapping->id]);
                    
                    // Create new term
                    $result = $this->api->create_term($this->taxonomy, $termdata);
                    if ($result) {
                        $this->create_mapping('category', $category->id, 'term', $result['id'], $this->taxonomy);
                        $this->results['success']++;
                        $this->results['messages'][] = "Recreated category (was deleted in WP): {$category->name}";
                        return true;
                    } else {
                        $newerror = $this->api->get_last_error();
                        throw new \Exception($newerror['message']);
                    }
                }
                
                // Other error, update mapping with error status
                $this->update_mapping($mapping->id, 'error', $error['message']);
                throw new \Exception($error['message']);
            }
        } else {
            // Create new term (no mapping or invalid wordpress_id)
            if ($mapping) {
                // Delete invalid mapping
                $this->db->delete_records('local_customadmin_wp_mapping', ['id' => $mapping->id]);
            }
            
            $result = $this->api->create_term($this->taxonomy, $termdata);
            if ($result) {
                $this->create_mapping('category', $category->id, 'term', $result['id'], $this->taxonomy);
                $this->results['success']++;
                $this->results['messages'][] = "Created category: {$category->name}";
                return true;
            } else {
                $error = $this->api->get_last_error();
                // Create mapping with error status
                $mappingid = $this->create_mapping('category', $category->id, 'term', 0, $this->taxonomy, 'error', $error['message']);
                throw new \Exception($error['message']);
            }
        }
    }
    
    /**
     * Prepare category data for WordPress
     *
     * @param \stdClass $category Moodle category object
     * @return array WordPress term data
     */
    private function prepare_category_data($category) {
        $data = [
            'name' => format_string($category->name),
            'description' => format_text($category->description, $category->descriptionformat, ['noclean' => true]),
            'slug' => $this->generate_slug($category->name),
        ];
        
        // Handle parent category
        if ($category->parent > 0) {
            $parentmapping = $this->get_mapping('category', $category->parent);
            if ($parentmapping && $parentmapping->sync_status === 'synced') {
                $data['parent'] = $parentmapping->wordpress_id;
            }
        }
        
        return $data;
    }
    
    /**
     * Generate a WordPress-friendly slug
     *
     * @param string $name Category name
     * @return string Slug
     */
    private function generate_slug($name) {
        // Remove special characters and convert to lowercase
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    /**
     * Get mapping for a Moodle item
     *
     * @param string $moodletype Moodle item type
     * @param int $moodleid Moodle item ID
     * @return \stdClass|false Mapping record or false
     */
    private function get_mapping($moodletype, $moodleid) {
        return $this->db->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => $moodletype,
            'moodle_id' => $moodleid
        ]);
    }
    
    /**
     * Create a new mapping
     *
     * @param string $moodletype Moodle item type
     * @param int $moodleid Moodle item ID
     * @param string $wordpresstype WordPress item type
     * @param int $wordpressid WordPress item ID
     * @param string $taxonomy WordPress taxonomy (for terms)
     * @param string $syncstatus Sync status (default: 'synced')
     * @param string $error Error message (optional)
     * @return int Mapping ID
     */
    private function create_mapping($moodletype, $moodleid, $wordpresstype, $wordpressid, $taxonomy = null, $syncstatus = 'synced', $error = null) {
        $record = new \stdClass();
        $record->moodle_type = $moodletype;
        $record->moodle_id = $moodleid;
        $record->wordpress_type = $wordpresstype;
        $record->wordpress_id = $wordpressid;
        $record->wordpress_taxonomy = $taxonomy;
        $record->sync_status = $syncstatus;
        $record->last_synced = time();
        $record->sync_error = $error;
        $record->timecreated = time();
        $record->timemodified = time();
        
        return $this->db->insert_record('local_customadmin_wp_mapping', $record);
    }
    
    /**
     * Update an existing mapping
     *
     * @param int $mappingid Mapping ID
     * @param string $syncstatus Sync status
     * @param string $error Error message (optional)
     * @return bool True on success
     */
    private function update_mapping($mappingid, $syncstatus, $error = null) {
        $record = new \stdClass();
        $record->id = $mappingid;
        $record->sync_status = $syncstatus;
        $record->last_synced = time();
        $record->sync_error = $error;
        $record->timemodified = time();
        
        return $this->db->update_record('local_customadmin_wp_mapping', $record);
    }
    
    /**
     * Get sync statistics
     *
     * @return array Statistics
     */
    public function get_sync_stats() {
        $total = $this->db->count_records('course_categories');
        $synced = $this->db->count_records('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'sync_status' => 'synced'
        ]);
        $errors = $this->db->count_records('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'sync_status' => 'error'
        ]);
        
        return [
            'total' => $total,
            'synced' => $synced,
            'pending' => $total - $synced,
            'errors' => $errors
        ];
    }
    
    /**
     * Get last sync time
     *
     * @return int|null Timestamp or null if never synced
     */
    public function get_last_sync_time() {
        $record = $this->db->get_record_sql(
            "SELECT MAX(last_synced) as last_sync 
             FROM {local_customadmin_wp_mapping} 
             WHERE moodle_type = :type AND sync_status = :status",
            ['type' => 'category', 'status' => 'synced']
        );
        
        return $record && $record->last_sync ? $record->last_sync : null;
    }
    
    /**
     * Check if a category is synced
     *
     * @param int $categoryid Category ID
     * @return bool True if synced
     */
    public function is_category_synced($categoryid) {
        return $this->db->record_exists('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'moodle_id' => $categoryid,
            'sync_status' => 'synced'
        ]);
    }
    
    /**
     * Get sync results
     *
     * @return array Results
     */
    public function get_results() {
        return $this->results;
    }
}
