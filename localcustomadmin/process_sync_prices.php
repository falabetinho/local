<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$action = required_param('action', PARAM_ALPHA);
$priceid = required_param('priceid', PARAM_INT); // enrol id

// Process the action
try {
    // Fetch enrol
    $enrol = $DB->get_record('enrol', ['id' => $priceid, 'enrol' => 'customstatus'], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $enrol->courseid]);
    
    // Check if course is synced
    $course_synced = $DB->get_record('local_customadmin_wp_mapping', [
        'moodle_type' => 'course',
        'moodle_id' => $course->id,
        'sync_status' => 'synced'
    ]);
    
    if (!$course_synced) {
        redirect(new moodle_url('/local/localcustomadmin/sync_prices.php'), get_string('course_not_synced', 'local_localcustomadmin'), null, \core\output\notification::NOTIFY_ERROR);
    }
    
    // Perform action
    switch ($action) {
        case 'sync':
            $result = sync_price_to_wordpress($enrol, $course_synced);
            break;
        case 'update':
            $result = update_price_in_wordpress($enrol, $course_synced);
            break;
        case 'delete':
            $result = delete_price_from_wordpress($enrol, $course_synced);
            break;
        default:
            throw new moodle_exception('invalidaction', 'local_localcustomadmin');
    }

    if ($result['success']) {
        redirect(new moodle_url('/local/localcustomadmin/sync_prices.php'), $result['message'], null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/localcustomadmin/sync_prices.php'), $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }

} catch (Exception $e) {
    redirect(new moodle_url('/local/localcustomadmin/sync_prices.php'), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}

// Include the handler functions
function sync_price_to_wordpress($enrol, $course_synced) {
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

    // Fetch course for name concatenation
    $course = $DB->get_record('course', ['id' => $enrol->courseid]);

    // Prepare price data
    $data = [
        'post_id' => $course_synced->wordpress_id,
        'name' => 'Preço do ' . $course->fullname, // Concatenated with course name
        'cost' => $enrol->cost ?: 0,
        'currency' => 'BRL',
        'installments' => $enrol->customint4 ?: 1,
        'ispromotional' => $enrol->customint2 ? 1 : 0,
        'isenrollmentfee' => $enrol->customint3 ? 1 : 0,
        'startdate' => date('Y-m-d H:i:s', $enrol->enrolstartdate ?: time()),
        'enddate' => date('Y-m-d H:i:s', $enrol->enrolenddate ?: time() + 365*24*3600)
    ];

    // Make API call
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/prices';  // Updated endpoint
    $response = make_api_call('POST', $url, $data, $username, $password);

    if ($response['success'] && isset($response['data']['id'])) {
        // Optionally store the WordPress price ID somewhere, e.g., in enrol custom fields or a custom table
        // For now, assume enrol->id is used for updates/deletes
        return [
            'success' => true,
            'message' => get_string('price_synced', 'local_localcustomadmin', $data['name'])
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('sync_failed', 'local_localcustomadmin')
        ];
    }
}

function update_price_in_wordpress($enrol, $course_synced) {
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

    // Prepare price data
    $data = [
        'cost' => $enrol->cost ?: 0,
        'name' => 'Preço ' . $enrol->id
    ];

    // Make API call
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/prices/' . $enrol->id;  // Updated endpoint, assuming enrol->id is price_id
    $response = make_api_call('PUT', $url, $data, $username, $password);

    if ($response['success']) {
        return [
            'success' => true,
            'message' => get_string('price_updated', 'local_localcustomadmin', $data['name'])
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('update_failed', 'local_localcustomadmin')
        ];
    }
}

function delete_price_from_wordpress($enrol, $course_synced) {
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

    // Make API call
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/prices/' . $enrol->id;  // Updated endpoint, assuming enrol->id is price_id
    $response = make_api_call('DELETE', $url, null, $username, $password);

    if ($response['success']) {
        return [
            'success' => true,
            'message' => get_string('price_deleted', 'local_localcustomadmin', 'Preço ' . $enrol->id)
        ];
    } else {
        return [
            'success' => false,
            'message' => $response['error'] ?: get_string('delete_failed', 'local_localcustomadmin')
        ];
    }
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
        error_log("WordPress API Error: $curl_error");
        return [
            'success' => false,
            'error' => "cURL Error: $curl_error"
        ];
    }

    if ($http_code >= 200 && $http_code < 300) {
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("WordPress API JSON Error: " . json_last_error_msg());
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
        error_log("WordPress API HTTP Error: $http_code - $response");
        return [
            'success' => false,
            'error' => "HTTP $http_code: $response"
        ];
    }
}
