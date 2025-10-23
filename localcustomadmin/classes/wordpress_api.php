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
    
    /** @var string WordPress API endpoint */
    private $endpoint;
    
    /** @var string WordPress username */
    private $username;
    
    /** @var string WordPress application password */
    private $apppassword;
    
    /** @var array Last error information */
    private $lasterror = null;
    
    /**
     * Constructor
     *
     * @param string|null $endpoint WordPress API endpoint (optional, uses config if not provided)
     * @param string|null $username WordPress username (optional, uses config if not provided)
     * @param string|null $apppassword WordPress application password (optional, uses config if not provided)
     */
    public function __construct($endpoint = null, $username = null, $apppassword = null) {
        $this->endpoint = $endpoint ?: get_config('local_localcustomadmin', 'wordpress_endpoint');
        $this->username = $username ?: get_config('local_localcustomadmin', 'wordpress_username');
        $this->apppassword = $apppassword ?: get_config('local_localcustomadmin', 'wordpress_apppassword');
        
        // Remove trailing slash from endpoint
        $this->endpoint = rtrim($this->endpoint, '/');
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
            $response = $this->request('GET', "/{$taxonomy}{$querystring}");
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
            $response = $this->request('GET', "/{$taxonomy}/{$termid}");
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
            $response = $this->request('POST', "/{$taxonomy}", $data);
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
            $response = $this->request('POST', "/{$taxonomy}/{$termid}", $data);
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
            $this->request('DELETE', "/{$taxonomy}/{$termid}?force=true");
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
            $response = $this->request('GET', "/{$posttype}{$querystring}");
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
            $response = $this->request('GET', "/{$posttype}/{$postid}");
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
            $response = $this->request('POST', "/{$posttype}", $data);
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
            $response = $this->request('POST', "/{$posttype}/{$postid}", $data);
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
            $this->request('DELETE', "/{$posttype}/{$postid}?force=true");
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
     * @param string $path API path (relative to endpoint)
     * @param array $data Request data (for POST/PUT requests)
     * @return array Response data
     * @throws \Exception On request failure
     */
    private function request($method, $path, $data = []) {
        $url = $this->endpoint . $path;
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Set Basic Authentication for Application Passwords
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->apppassword);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method and data
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (!empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
            case 'GET':
            default:
                // GET is the default
                break;
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Check for cURL errors
        if ($error) {
            throw new \Exception("cURL Error: {$error}");
        }
        
        // Check HTTP status code
        if ($httpcode < 200 || $httpcode >= 300) {
            $errormsg = "HTTP {$httpcode}";
            if ($response) {
                $decoded = json_decode($response, true);
                if (isset($decoded['message'])) {
                    $errormsg .= ": {$decoded['message']}";
                }
            }
            throw new \Exception($errormsg);
        }
        
        // Decode and return response
        return json_decode($response, true);
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
