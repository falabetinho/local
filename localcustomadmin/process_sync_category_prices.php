<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

// Get parameters
$action = required_param('action', PARAM_ALPHA);

// Process the action
try {
    if ($action === 'sync_all') {
        $result = sync_prices_from_wordpress();
    } else {
        throw new moodle_exception('invalidaction', 'local_localcustomadmin');
    }

    if ($result['success']) {
        redirect(new moodle_url('/local/localcustomadmin/categorias.php'), $result['message'], null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(new moodle_url('/local/localcustomadmin/categorias.php'), $result['message'], null, \core\output\notification::NOTIFY_ERROR);
    }

} catch (Exception $e) {
    redirect(new moodle_url('/local/localcustomadmin/categorias.php'), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
}

// Function to sync prices from WordPress
function sync_prices_from_wordpress() {
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

    // Fetch prices from WordPress API (assuming endpoint /wp-json/wp/v2/prices)
    $url = rtrim($baseurl, '/') . '/wp-json/wp/v2/prices';
    $response = make_api_call('GET', $url, null, $username, $password);

    if (!$response['success']) {
        return [
            'success' => false,
            'message' => $response['error']
        ];
    }

    $wp_prices = $response['data'];
    $synced_count = 0;

    foreach ($wp_prices as $wp_price) {
        // Assume wp_price has 'post_id' (course ID), 'cost', 'currency', etc.
        $course_id = $wp_price['post_id'];
        $cost = $wp_price['cost'];
        $currency = $wp_price['currency'] ?? 'BRL';

        // Find or create enrol for the course
        $enrol = $DB->get_record('enrol', ['courseid' => $course_id, 'enrol' => 'customstatus']);
        if (!$enrol) {
            // Create new enrol
            $enrol = new stdClass();
            $enrol->enrol = 'customstatus';
            $enrol->courseid = $course_id;
            $enrol->cost = $cost;
            $enrol->currency = $currency;
            $enrol->customint2 = $wp_price['ispromotional'] ?? 0;
            $enrol->customint3 = $wp_price['isenrollmentfee'] ?? 0;
            $enrol->customint4 = $wp_price['installments'] ?? 1;
            $enrol->timecreated = time();
            $enrol->timemodified = time();
            $DB->insert_record('enrol', $enrol);
        } else {
            // Update existing enrol
            $enrol->cost = $cost;
            $enrol->currency = $currency;
            $enrol->customint2 = $wp_price['ispromotional'] ?? $enrol->customint2;
            $enrol->customint3 = $wp_price['isenrollmentfee'] ?? $enrol->customint3;
            $enrol->customint4 = $wp_price['installments'] ?? $enrol->customint4;
            $enrol->timemodified = time();
            $DB->update_record('enrol', $enrol);
        }

        // Propagate to subcategories and courses if it's a parent category price
        // Assuming post_id could be category ID if not course
        $category = $DB->get_record('course_categories', ['id' => $course_id]);
        if ($category) {
            propagate_price_to_subcategories($category->id, $cost, $currency, $wp_price);
        }

        $synced_count++;
    }

    return [
        'success' => true,
        'message' => get_string('prices_synced', 'local_localcustomadmin', $synced_count)
    ];
}

// Function to propagate price to subcategories and their courses
function propagate_price_to_subcategories($parent_category_id, $cost, $currency, $wp_price) {
    global $DB;

    // Get subcategories
    $subcategories = $DB->get_records('course_categories', ['parent' => $parent_category_id]);

    foreach ($subcategories as $subcat) {
        // Get courses in subcategory
        $courses = $DB->get_records('course', ['category' => $subcat->id]);

        foreach ($courses as $course) {
            // Create or update enrol for each course
            $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'customstatus']);
            if (!$enrol) {
                $enrol = new stdClass();
                $enrol->enrol = 'customstatus';
                $enrol->courseid = $course->id;
                $enrol->cost = $cost;
                $enrol->currency = $currency;
                $enrol->customint2 = $wp_price['ispromotional'] ?? 0;
                $enrol->customint3 = $wp_price['isenrollmentfee'] ?? 0;
                $enrol->customint4 = $wp_price['installments'] ?? 1;
                $enrol->timecreated = time();
                $enrol->timemodified = time();
                $DB->insert_record('enrol', $enrol);
            } else {
                $enrol->cost = $cost;
                $enrol->currency = $currency;
                $enrol->customint2 = $wp_price['ispromotional'] ?? $enrol->customint2;
                $enrol->customint3 = $wp_price['isenrollmentfee'] ?? $enrol->customint3;
                $enrol->customint4 = $wp_price['installments'] ?? $enrol->customint4;
                $enrol->timemodified = time();
                $DB->update_record('enrol', $enrol);
            }
        }

        // Recursively propagate to deeper subcategories
        propagate_price_to_subcategories($subcat->id, $cost, $currency, $wp_price);
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
