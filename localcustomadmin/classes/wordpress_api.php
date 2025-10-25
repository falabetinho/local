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
 * WordPress API Client
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_localcustomadmin;

defined('MOODLE_INTERNAL') || die();

/**
 * WordPress REST API Client Class
 */
class wordpress_api {
    private $base_url;
    private $username;
    private $password;

    /** @var string Resource path for cursos */
    public $resource_path_cursos = 'wp-json/wp/v2/cursos';

    /** @var string Resource path for niveis */
    public $resource_path_niveis = 'wp-json/wp/v2/niveis';

    /**
     * Constructor
     *
     * @param string $base_url WordPress base URL
     * @param string $username WordPress username
     * @param string $password WordPress application password
     */
    public function __construct($base_url, $username, $password) {
        $this->base_url = rtrim($base_url, '/');
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Test connection to WordPress API
     *
     * @return bool True if connection is successful
     */
    public function test_connection() {
        try {
            $response = $this->request('GET', '');
            return !empty($response);
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Get all terms from a taxonomy
     *
     * @param string $taxonomy Taxonomy name (e.g., 'niveis')
     * @param array $params Additional query parameters
     * @return array|false Array of terms or false on error
     */
    public function get_taxonomy_terms($taxonomy, $params = []) {
        try {
            $querystring = !empty($params) ? '?' . http_build_query($params) : '';
            $response = $this->request('GET', "wp-json/wp/v2/{$taxonomy}{$querystring}");
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Get a single term from a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @param int $termid Term ID
     * @return array|false Term data or false on error
     */
    public function get_term($taxonomy, $termid) {
        try {
            $response = $this->request('GET', "wp-json/wp/v2/{$taxonomy}/{$termid}");
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Create a new term in a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @param array $data Term data (name, description, slug, parent, etc.)
     * @return array|false Created term data or false on error
     */
    public function create_term($taxonomy, $data) {
        try {
            $response = $this->request('POST', "wp-json/wp/v2/{$taxonomy}", $data);
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Update an existing term in a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @param int $termid Term ID
     * @param array $data Term data to update
     * @return array|false Updated term data or false on error
     */
    public function update_term($taxonomy, $termid, $data) {
        try {
            $response = $this->request('POST', "wp-json/wp/v2/{$taxonomy}/{$termid}", $data);
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Delete a term from a taxonomy
     *
     * @param string $taxonomy Taxonomy name
     * @param int $termid Term ID
     * @return bool True on success, false on error
     */
    public function delete_term($taxonomy, $termid) {
        try {
            $this->request('DELETE', "wp-json/wp/v2/{$taxonomy}/{$termid}?force=true");
            return true;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Get posts from a custom post type
     *
     * @param string $posttype Post type name (e.g., 'cursos')
     * @param array $params Additional query parameters
     * @return array|false Array of posts or false on error
     */
    public function get_posts($posttype, $params = []) {
        try {
            $querystring = !empty($params) ? '?' . http_build_query($params) : '';
            $response = $this->request('GET', "wp-json/wp/v2/{$posttype}{$querystring}");
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Get a single post
     *
     * @param string $posttype Post type name
     * @param int $postid Post ID
     * @return array|false Post data or false on error
     */
    public function get_post($posttype, $postid) {
        try {
            $response = $this->request('GET', "wp-json/wp/v2/{$posttype}/{$postid}");
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Create a new post
     *
     * @param string $posttype Post type name
     * @param array $data Post data
     * @return array|false Created post data or false on error
     */
    public function create_post($posttype, $data) {
        try {
            $response = $this->request('POST', "wp-json/wp/v2/{$posttype}", $data);
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Update an existing post
     *
     * @param string $posttype Post type name
     * @param int $postid Post ID
     * @param array $data Post data to update
     * @return array|false Updated post data or false on error
     */
    public function update_post($posttype, $postid, $data) {
        try {
            $response = $this->request('POST', "wp-json/wp/v2/{$posttype}/{$postid}", $data);
            return $response;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Delete a post
     *
     * @param string $posttype Post type name
     * @param int $postid Post ID
     * @return bool True on success, false on error
     */
    public function delete_post($posttype, $postid) {
        try {
            $this->request('DELETE', "wp-json/wp/v2/{$posttype}/{$postid}?force=true");
            return true;
        } catch (\Exception $e) {
            $this->lasterror = ['message' => $e->getMessage()];
            return false;
        }
    }
    
    /**
     * Make HTTP request to WordPress API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $resource_path Resource path (e.g., wp-json/wp/v2/cursos)
     * @param array|null $data Data to send (for POST/PUT requests)
     * @return array|false Response data or false on failure
     */
    public function request($method, $resource_path, $data = null) {
        $url = "{$this->base_url}/{$resource_path}";
        error_log("WordPress API: Starting cURL request to $url with method $method");

        $curl = curl_init($url);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($curl, CURLOPT_USERPWD, "{$this->username}:{$this->password}");

        if ($data !== null) {
            $json_data = json_encode($data);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
            error_log("WordPress API: Request payload: $json_data");
        }

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);

        if ($curl_error) {
            error_log("WordPress API: cURL error: $curl_error");
            return false;
        }

        error_log("WordPress API: HTTP response code: $http_code");
        error_log("WordPress API: Response body: $response");

        if ($http_code >= 200 && $http_code < 300) {
            return json_decode($response, true);
        } else {
            error_log("WordPress API: Request failed with HTTP code $http_code");
            return false;
        }
    }
    
    /**
     * Get last error information
     *
     * @return array|null Last error or null if no error
     */
    public function get_last_error() {
        return $this->lasterror;
    }
}
