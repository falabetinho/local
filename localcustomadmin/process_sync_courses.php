<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$action = required_param('action', PARAM_ALPHA);
$courseid = required_param('courseid', PARAM_INT);

// Process the action
try {
    // Fetch course
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    
    // Check if category is synced (otimizado)
    $category_synced = false;
    if ($course->category) {
        $cat_mappings = $DB->get_records('local_customadmin_wp_mapping', ['moodle_type' => 'category'], '', 'moodle_id, sync_status');
        $category_synced = isset($cat_mappings[$course->category]) && $cat_mappings[$course->category]->sync_status == 'synced';
    }
    
    if (!$category_synced) {
        redirect(new moodle_url('/local/localcustomadmin/sync_courses.php'), get_string('category_not_synced', 'local_localcustomadmin'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Perform action
    switch ($action) {
        case 'sync':
            $result = sync_course_to_wordpress($course);
            break;
        case 'update':
            $result = update_course_in_wordpress($course);
            break;
        case 'delete':
            $result = delete_course_from_wordpress($course);
            break;
        default:
            throw new moodle_exception('invalidaction', 'local_localcustomadmin');
    }

    if ($result['success']) {
        redirect(new moodle_url('/local/localcustomadmin/sync_courses.php'), $result['message'], null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/localcustomadmin/sync_courses.php'), $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }

} catch (Exception $e) {
    redirect(new moodle_url('/local/localcustomadmin/sync_courses.php'), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}

// Include the handler functions
function sync_course_to_wordpress($course) {
    global $DB;

    // Get WordPress settings
    $baseurl = get_config('local_localcustomadmin', 'wordpress_baseurl');
    $username = get_config('local_localcustomadmin', 'wordpress_username');
    $password = get_config('local_localcustomadmin', 'wordpress_apppassword');

    if (!$baseurl || !$username || !$password) {
        return [
            'success' => false,
            'message' => get_string('wordpress_settings_incomplete', 'local_localcustomadmin')
        ];
    }

    // Prepare course data
    $data = [
        'title' => ['raw' => format_string($course->fullname)],
        'content' => ['raw' => format_text($course->summary, $course->summaryformat)],
        'status' => $course->visible ? 'publish' : 'draft',
        'meta' => [
            'moodle_id' => $course->id,
            'moodle_shortname' => $course->shortname,
            'moodle_idnumber' => $course->idnumber ?: '',
            'start_date' => $course->startdate,
            'end_date' => $course->enddate ?: 0,
        ]
    ];

    // Add category taxonomy
    if ($course->category) {
        $cat_mapping = $DB->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'moodle_id' => $course->category
        ]);
        if ($cat_mapping && $cat_mapping->wordpress_id) {
            $data['niveis'] = [$cat_mapping->wordpress_id];
        }
    }

    // Make API call
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/cursos';
    $response = make_api_call('POST', $url, $data, $username, $password);

    if ($response['success'] && isset($response['data']['id'])) {
        // Create mapping
        $mapping = new \stdClass();
        $mapping->moodle_type = 'course';
        $mapping->moodle_id = $course->id;
        $mapping->wordpress_type = 'post';
        $mapping->wordpress_id = $response['data']['id'];
        $mapping->wordpress_post_type = 'cursos';
        $mapping->sync_status = 'synced';
        $mapping->last_synced = time();
        $mapping->timecreated = time();
        $mapping->timemodified = time();

        $DB->insert_record('local_customadmin_wp_mapping', $mapping);

        return [
            'success' => true,
            'message' => get_string('course_synced', 'local_localcustomadmin', $course->fullname)
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('sync_failed', 'local_localcustomadmin')
        ];
    }
}

function update_course_in_wordpress($course) {
    global $DB;

    // Get WordPress settings
    $baseurl = get_config('local_localcustomadmin', 'wordpress_baseurl');
    $username = get_config('local_localcustomadmin', 'wordpress_username');
    $password = get_config('local_localcustomadmin', 'wordpress_apppassword');

    if (!$baseurl || !$username || !$password) {
        return [
            'success' => false,
            'message' => get_string('wordpress_settings_incomplete', 'local_localcustomadmin')
        ];
    }

    // Get existing mapping
    $mapping = $DB->get_record('local_customadmin_wp_mapping', [
        'moodle_type' => 'course',
        'moodle_id' => $course->id
    ]);

    if (!$mapping || !$mapping->wordpress_id) {
        return [
            'success' => false,
            'message' => get_string('mapping_not_found', 'local_localcustomadmin')
        ];
    }

    // Prepare course data
    $data = [
        'title' => ['raw' => format_string($course->fullname)],
        'content' => ['raw' => format_text($course->summary, $course->summaryformat)],
        'status' => $course->visible ? 'publish' : 'draft',
        'meta' => [
            'moodle_id' => $course->id,
            'moodle_shortname' => $course->shortname,
            'moodle_idnumber' => $course->idnumber ?: '',
            'start_date' => $course->startdate,
            'end_date' => $course->enddate ?: 0,
        ]
    ];

    // Add category taxonomy
    if ($course->category) {
        $cat_mapping = $DB->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'moodle_id' => $course->category
        ]);
        if ($cat_mapping && $cat_mapping->wordpress_id) {
            $data['niveis'] = [$cat_mapping->wordpress_id];
        }
    }

    // Make API call
    $url = rtrim($baseurl, '/') . "/wp-json/wp/v2/cursos/{$mapping->wordpress_id}";
    $response = make_api_call('POST', $url, $data, $username, $password);

    if ($response['success']) {
        // Update mapping
        $mapping->sync_status = 'synced';
        $mapping->last_synced = time();
        $mapping->timemodified = time();
        $DB->update_record('local_customadmin_wp_mapping', $mapping);

        return [
            'success' => true,
            'message' => get_string('course_updated', 'local_localcustomadmin', $course->fullname)
        ];
    } else {
        $mapping->sync_status = 'error';
        $mapping->sync_error = $response['error'];
        $mapping->timemodified = time();
        $DB->update_record('local_customadmin_wp_mapping', $mapping);

        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('update_failed', 'local_localcustomadmin')
        ];
    }
}

function delete_course_from_wordpress($course) {
    global $DB;

    // Get WordPress settings
    $baseurl = get_config('local_localcustomadmin', 'wordpress_baseurl');
    $username = get_config('local_localcustomadmin', 'wordpress_username');
    $password = get_config('local_localcustomadmin', 'wordpress_apppassword');

    if (!$baseurl || !$username || !$password) {
        return [
            'success' => false,
            'message' => get_string('wordpress_settings_incomplete', 'local_localcustomadmin')
        ];
    }

    // Get existing mapping
    $mapping = $DB->get_record('local_customadmin_wp_mapping', [
        'moodle_type' => 'course',
        'moodle_id' => $course->id
    ]);

    if (!$mapping || !$mapping->wordpress_id) {
        return [
            'success' => false,
            'message' => get_string('mapping_not_found', 'local_localcustomadmin')
        ];
    }

    // Make API call
    $url = rtrim($baseurl, '/') . "/wp-json/wp/v2/cursos/{$mapping->wordpress_id}?force=true";
    $response = make_api_call('DELETE', $url, null, $username, $password);

    if ($response['success']) {
        // Delete mapping
        $DB->delete_records('local_customadmin_wp_mapping', ['id' => $mapping->id]);

        return [
            'success' => true,
            'message' => get_string('course_deleted', 'local_localcustomadmin', $course->fullname)
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('delete_failed', 'local_localcustomadmin')
        ];
    }
}

// Melhoria na função make_api_call para validação de JSON
function make_api_call($method, $url, $data = null, $username, $password) {
    $curl = curl_init($url);

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_USERPWD, "{$username}:{$password}");

    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);

    if ($curl_error) {
        return [
            'success' => false,
            'error' => "cURL Error: $curl_error"
        ];
    }

    if ($http_code >= 200 && $http_code < 300) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Invalid JSON response from WordPress'
            ];
        }
        return [
            'success' => true,
            'data' => $decoded
        ];
    } else {
        return [
            'success' => false,
            'error' => "HTTP $http_code: $response"
        ];
    }
}
