<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$action = required_param('action', PARAM_ALPHA);
$categoryid = required_param('categoryid', PARAM_INT);

// Process the action using the handler logic
try {
    // Fetch category
    $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);

    // Perform action based on the handler
    switch ($action) {
        case 'sync':
            $result = sync_category_to_wordpress($category);
            break;
        case 'update':
            $result = update_category_in_wordpress($category);
            break;
        case 'delete':
            $result = delete_category_from_wordpress($category);
            break;
        default:
            throw new moodle_exception('invalidaction', 'local_localcustomadmin');
    }

    if ($result['success']) {
        redirect(new moodle_url('/local/localcustomadmin/sync_categories.php'), $result['message'], null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/localcustomadmin/sync_categories.php'), $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }

} catch (Exception $e) {
    redirect(new moodle_url('/local/localcustomadmin/sync_categories.php'), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}

// Include the handler functions (adapted from sync_categories_handler.php)
function sync_category_to_wordpress($category) {
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

    // Prepare category data
    $data = [
        'name' => format_string($category->name),
        'description' => format_text($category->description, $category->descriptionformat, ['noclean' => true]),
        'slug' => generate_slug($category->name)
    ];

    // Handle parent category
    if ($category->parent > 0) {
        $parent_mapping = $DB->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'moodle_id' => $category->parent
        ]);
        if ($parent_mapping && $parent_mapping->wordpress_id) {
            $data['parent'] = $parent_mapping->wordpress_id;
        }
    }

    // Make API call
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/niveis';
    $response = make_api_call('POST', $url, $data, $username, $password);

    if ($response['success'] && isset($response['data']['id'])) {
        // Create mapping
        $mapping = new \stdClass();
        $mapping->moodle_type = 'category';
        $mapping->moodle_id = $category->id;
        $mapping->wordpress_type = 'term';
        $mapping->wordpress_id = $response['data']['id'];
        $mapping->wordpress_taxonomy = 'niveis';
        $mapping->sync_status = 'synced';
        $mapping->last_synced = time();
        $mapping->timecreated = time();
        $mapping->timemodified = time();

        $DB->insert_record('local_customadmin_wp_mapping', $mapping);

        return [
            'success' => true,
            'message' => get_string('category_synced', 'local_localcustomadmin', $category->name)
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('sync_failed', 'local_localcustomadmin')
        ];
    }
}

function update_category_in_wordpress($category) {
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
        'moodle_type' => 'category',
        'moodle_id' => $category->id
    ]);

    if (!$mapping || !$mapping->wordpress_id) {
        return [
            'success' => false,
            'message' => get_string('mapping_not_found', 'local_localcustomadmin')
        ];
    }

    // Prepare category data
    $data = [
        'name' => format_string($category->name),
        'description' => format_text($category->description, $category->descriptionformat, ['noclean' => true]),
        'slug' => generate_slug($category->name)
    ];

    // Handle parent category
    if ($category->parent > 0) {
        $parent_mapping = $DB->get_record('local_customadmin_wp_mapping', [
            'moodle_type' => 'category',
            'moodle_id' => $category->parent
        ]);
        if ($parent_mapping && $parent_mapping->wordpress_id) {
            $data['parent'] = $parent_mapping->wordpress_id;
        }
    }

    // Make API call
    $url = rtrim($baseurl, '/') . "/wp-json/wp/v2/niveis/{$mapping->wordpress_id}";
    $response = make_api_call('POST', $url, $data, $username, $password);

    if ($response['success']) {
        // Update mapping
        $mapping->sync_status = 'synced';
        $mapping->last_synced = time();
        $mapping->timemodified = time();
        $DB->update_record('local_customadmin_wp_mapping', $mapping);

        return [
            'success' => true,
            'message' => get_string('category_updated', 'local_localcustomadmin', $category->name)
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

function delete_category_from_wordpress($category) {
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
        'moodle_type' => 'category',
        'moodle_id' => $category->id
    ]);

    if (!$mapping || !$mapping->wordpress_id) {
        return [
            'success' => false,
            'message' => get_string('mapping_not_found', 'local_localcustomadmin')
        ];
    }

    // Make API call
    $url = rtrim($baseurl, '/') . "/wp-json/wp/v2/niveis/{$mapping->wordpress_id}?force=true";
    $response = make_api_call('DELETE', $url, null, $username, $password);

    if ($response['success']) {
        // Delete mapping
        $DB->delete_records('local_customadmin_wp_mapping', ['id' => $mapping->id]);

        return [
            'success' => true,
            'message' => get_string('category_deleted', 'local_localcustomadmin', $category->name)
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('delete_failed', 'local_localcustomadmin')
        ];
    }
}

function generate_slug($name) {
    $slug = strtolower($name);
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

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
        return [
            'success' => true,
            'data' => json_decode($response, true)
        ];
    } else {
        return [
            'success' => false,
            'error' => "HTTP $http_code: $response"
        ];
    }
}
