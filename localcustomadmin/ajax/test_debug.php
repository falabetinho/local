<?php
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

header('Content-Type: application/json');

echo json_encode([
    'GET' => $_GET,
    'POST' => $_POST,
    'REQUEST' => $_REQUEST,
    'action_optional' => optional_param('action', 'NOT_FOUND', PARAM_ALPHA),
    'sesskey_ok' => sesskey() === optional_param('sesskey', '', PARAM_RAW)
]);
