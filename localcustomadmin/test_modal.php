<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Test file to verify category modal functionality
 *
 * @package   local_localcustomadmin
 * @copyright 2025 Heber
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/localcustomadmin:manage', $context);

$PAGE->set_url(new moodle_url('/local/localcustomadmin/test_modal.php'));
$PAGE->set_context($context);
$PAGE->set_pagelayout('base');
$PAGE->set_title('Test Category Modal');

echo $OUTPUT->header();

echo '<div class="container-fluid">';
echo '<h2>Category Modal Test</h2>';
echo '<p>This page tests the category modal functionality.</p>';

// Test buttons
echo '<div class="mt-4">';
echo '<button type="button" class="btn btn-primary me-2" id="add-category-btn" data-url="' . 
     (new moodle_url('/local/localcustomadmin/form_categoria.php'))->out() . '">';
echo '<i class="fas fa-plus-circle me-2"></i>Test Add Category Modal';
echo '</button>';

// Test edit button (using a fake category ID)
echo '<button type="button" class="btn btn-outline-primary edit-category-modal" ';
echo 'data-url="' . (new moodle_url('/local/localcustomadmin/form_categoria.php', ['id' => 1]))->out() . '" ';
echo 'data-category-name="Test Category">';
echo '<i class="fas fa-edit me-2"></i>Test Edit Category Modal';
echo '</button>';
echo '</div>';

echo '<div class="mt-4">';
echo '<a href="' . (new moodle_url('/local/localcustomadmin/categorias.php'))->out() . '" class="btn btn-secondary">';
echo '<i class="fas fa-arrow-left me-2"></i>Back to Categories';
echo '</a>';
echo '</div>';

echo '</div>';

// Include the JavaScript
echo '<script>';
echo 'require([\'local_localcustomadmin/category_modal\'], function(CategoryModal) {';
echo '    CategoryModal.init();';
echo '});';
echo '</script>';

echo $OUTPUT->footer();