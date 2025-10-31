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
 * English language strings for Local Custom Admin plugin.
 *
 * @package    local_localcustomadmin
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Local Custom Admin';
$string['localcustomadmin'] = 'Local Custom Admin';
$string['localcustomadmin:view'] = 'View Local Custom Admin';
$string['localcustomadmin:manage'] = 'Manage Local Custom Admin';

// Settings
$string['displayname'] = 'Display Name';
$string['displayname_desc'] = 'Custom name that will be displayed instead of the default plugin name throughout the interface';

// WordPress Integration Settings
$string['wordpress_integration'] = 'WordPress Integration';
$string['wordpress_integration_desc'] = 'Manage the synchronization of categories, courses, and prices with WordPress.';
$string['wordpress_integration_help_title'] = 'How to use WordPress Integration';
$string['wordpress_integration_help_1'] = 'Synchronize Moodle categories with WordPress.';
$string['wordpress_integration_help_2'] = 'Synchronize Moodle courses with WordPress.';
$string['wordpress_integration_help_3'] = 'Synchronize course prices with WordPress.';
$string['sync_categories'] = 'Sync Categories';
$string['sync_categories_desc'] = 'Synchronize Moodle categories with WordPress.';
$string['sync_courses'] = 'Sync Courses';
$string['sync_courses_desc'] = 'Synchronize Moodle courses with WordPress.';
$string['sync_prices'] = 'Sync Prices';
$string['sync_prices_desc'] = 'Synchronize course prices with WordPress.';
$string['total_courses'] = 'Total Courses';
$string['visible_courses'] = 'Visible Courses';
$string['hidden_courses'] = 'Hidden Courses';
$string['manage_courses'] = 'Manage Courses';
$string['manage_courses_desc'] = 'View and manage all Moodle courses.';
$string['manage_categories'] = 'Manage Categories';
$string['manage_categories_desc'] = 'View and manage all Moodle categories.';
$string['course_backups'] = 'Course Backups';
$string['course_backups_desc'] = 'Manage and restore course backups.';
$string['back'] = 'Back';
$string['help'] = 'Help';
$string['manage'] = 'Manage';

// Modal form strings
$string['addcategory'] = 'Add Category';
$string['editcategory'] = 'Edit Category';
$string['categoryname'] = 'Category Name';
$string['categoryimage'] = 'Category Image';
$string['categoryimage_help'] = 'Upload an image to represent this category';
$string['categorysaved'] = 'Category saved successfully';
$string['categoryupdated'] = 'Category updated successfully';
$string['categoryadded'] = 'Category added successfully';
$string['categorynameexists'] = 'A category with this name already exists at this level';
$string['idnumberexists'] = 'A category with this ID number already exists';

// General strings.
$string['administration'] = 'Administration';
$string['dashboard'] = 'Dashboard';
$string['settings'] = 'Settings';
$string['users'] = 'Users';
$string['courses'] = 'Courses';
$string['manage'] = 'Manage';
$string['view_report'] = 'View Report';

// Enrolment Management
$string['enrolment_management'] = 'Enrolment Management';
$string['enrolment_management_desc'] = 'Manage enrolments, category prices, and payment status reports.';
$string['enrolment_methods'] = 'Enrolment Methods';
$string['enrolment_methods_desc'] = 'Configure and manage available enrolment methods in the system.';
$string['statusreport_desc'] = 'View detailed reports of student payment status and overdue payments.';

// CustomStatus Plugin Operations
$string['customstatus_operations'] = 'CustomStatus Plugin Operations';
$string['customstatus_matricula'] = 'Enrolment';
$string['customstatus_manage'] = 'Manage Statuses';
$string['customstatus_assign'] = 'Assign Status';
$string['customstatus_edit'] = 'Edit Status';
$string['customstatus_report'] = 'Reports';
$string['customstatus_blocked'] = 'Blocked Users';

// Users management strings
$string['users_management'] = 'Users Management';
$string['users_management_desc'] = 'Manage and view system users with advanced filtering options.';
$string['users_desc'] = 'Comprehensive user management with filtering and actions.';
$string['open_users'] = 'Manage Users';

// Page titles.
$string['admindashboard'] = 'Administrative Dashboard';
$string['adminsettings'] = 'Administrative Settings';
$string['courses_management'] = 'Courses Management';

// Categories Management.
$string['categories'] = 'Categories';
$string['categories_management'] = 'Categories Management';
$string['categories_management_desc'] = 'Manage course categories, view statistics and organize your educational content structure.';
$string['add_category'] = 'Add Category';
$string['edit_category'] = 'Edit Category';
$string['view_subcategories'] = 'View Subcategories';
$string['no_categories'] = 'No categories found';
$string['create_first_category'] = 'Create first category';
$string['category_created'] = 'Category created successfully';
$string['category_updated'] = 'Category updated successfully';
$string['category_deleted'] = 'Category deleted successfully';

// WordPress Mappings.
$string['wordpress_mappings'] = 'WordPress Mappings';
$string['wordpress_mappings_desc'] = 'View and manage all synchronization mappings between Moodle and WordPress';
$string['synced'] = 'Synced';
$string['pending'] = 'Pending';
$string['notfound'] = 'Not Found';
$string['type'] = 'Type';
$string['last_sync'] = 'Last Sync';
$string['showdetails'] = 'Show Details';
$string['error_message'] = 'Error Message';
$string['no_mappings_found'] = 'No Mappings Found';
$string['no_mappings_found_desc'] = 'There are no synchronization mappings between Moodle and WordPress. Sync categories or courses to create mappings.';
$string['timecreated'] = 'Created Date';
$string['timemodified'] = 'Modified Date';

// WordPress Courses Sync.
$string['wordpress_integration_courses'] = 'WordPress Integration - Courses';
$string['sync_all_courses'] = 'Sync All Courses';
$string['sync_all_courses_desc'] = 'Send all courses and their prices to WordPress';
$string['sync_prices'] = 'Sync Prices';
$string['sync_prices_desc'] = 'Update course prices in WordPress from enrolment data';
$string['not_synced'] = 'Not Synced';
$string['sync_course'] = 'Sync Course';
$string['sync_status'] = 'Sync Status';
$string['no_courses_found'] = 'No Courses Found';
$string['no_courses_found_desc'] = 'There are no courses to display. Create courses to start syncing with WordPress.';
$string['toggle_visibility'] = 'Toggle Visibility';
$string['delete_from_wp'] = 'Delete from WordPress';
$string['course_published'] = 'Course published on WordPress';
$string['course_hidden'] = 'Course hidden on WordPress';
$string['course_deleted_wp'] = 'Course deleted from WordPress';

// Messages.
$string['welcome'] = 'Welcome to Local Custom Admin';
$string['nopermission'] = 'You do not have permission to access this page.';
$string['notfound'] = 'Page not found.';
$string['success'] = 'Operation completed successfully.';
$string['error'] = 'An error occurred while processing your request.';

// Template strings.
$string['no_admin_tools'] = 'No administrative tools are currently available for your user role.';

// Card descriptions.
$string['dashboard_desc'] = 'Access the administrative dashboard to view system statistics and quick actions.';
$string['settings_desc'] = 'Configure and manage administrative settings for the plugin.';
$string['courses_desc'] = 'Manage and monitor all courses in the system';

// Button texts.
$string['open_dashboard'] = 'Open Dashboard';
$string['open_settings'] = 'Open Settings';
$string['open_courses'] = 'Open Courses';

// Course related strings.
$string['total_courses'] = 'Total Courses';
$string['visible_courses'] = 'Visible Courses';
$string['hidden_courses'] = 'Hidden Courses';
$string['create_course'] = 'Create Course';
$string['create_course_desc'] = 'Create a new course in the system';
$string['manage_courses'] = 'Manage Courses';
$string['manage_courses_desc'] = 'Manage all courses in the system';
$string['manage_categories'] = 'Manage Categories';
$string['manage_categories_desc'] = 'Organize courses into categories';
$string['course_backups'] = 'Course Backups';
$string['course_backups_desc'] = 'Restore courses from backup files';
$string['manage_categories'] = 'Manage Categories';
$string['manage_categories_desc'] = 'Organize courses into categories';
$string['course_backups'] = 'Course Backups';
$string['course_backups_desc'] = 'Restore courses from backup files';

// Password reset strings
$string['resetpassword'] = 'Reset Password';
$string['confirm_reset_password'] = 'Are you sure you want to reset the password for user ID {$a}?';
$string['reset'] = 'Reset';
$string['newpassword'] = 'New Password';
$string['confirmpassword'] = 'Confirm Password';
$string['passwordmustmatch'] = 'The passwords do not match. Please try again.';
$string['passwordempty'] = 'The password field cannot be empty.';
$string['passwordchanged'] = 'Password changed successfully!';
$string['passwordpolicyerror'] = 'The password does not meet password policy requirements.';
$string['passwordresetalert'] = 'Alert';
$string['resetpasswordtitle'] = 'Reset User Password';
$string['passwordresetsuccess'] = 'Password changed successfully!';
$string['passwordresetfailed'] = 'Error resetting password. Please try again.';
$string['modalopenerror'] = 'Error opening password reset modal.';
$string['stringsloaderror'] = 'Error loading language strings.';

// Privacy API.
$string['privacy:metadata'] = 'The Local Custom Admin plugin does not store any personal data.';

// Category pricing management strings
$string['categoryprices'] = 'Category Prices';
$string['categoryprices_management'] = 'Category Pricing Management';
$string['categoryprices_management_desc'] = 'Manage category prices, discounts, and enrollment fees.';
$string['add_price'] = 'Add Price';
$string['edit_price'] = 'Edit Price';
$string['delete_price'] = 'Delete Price';
$string['pricename'] = 'Price Name';
$string['pricevalue'] = 'Price Value';
$string['startdate'] = 'Start Date';
$string['enddate'] = 'End Date';
$string['ispromotional'] = 'Is Promotional';
$string['isenrollmentfee'] = 'Is Enrollment Fee';
$string['nofees'] = 'No fees';
$string['status'] = 'Status';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['installments'] = 'Number of Installments';
$string['pricecreatorsuccess'] = 'Price created successfully!';
$string['priceupdatesuccess'] = 'Price updated successfully!';
$string['pricedeletesuccess'] = 'Price deleted successfully!';
$string['pricedeletefailed'] = 'Failed to delete price.';

// Validation error strings
$string['errorcategoryid'] = 'Category ID is required';
$string['errorcategorynotfound'] = 'Category not found';
$string['errorname'] = 'Price name is required';
$string['errornametoolong'] = 'Price name must not exceed 255 characters';
$string['errorprice'] = 'Price value is required';
$string['errorpriceinvalid'] = 'Price must be a valid positive number';
$string['errorstartdateinvalid'] = 'Start date must be a valid timestamp';
$string['errorenddateinvalid'] = 'End date must be a valid timestamp';
$string['errordaterange'] = 'Start date must be before end date';
$string['errordateoverlap'] = 'This price period overlaps with an existing active price';
$string['errorinstallments'] = 'Number of installments must be between 0 and 12';
$string['errorstatus'] = 'Status must be 0 or 1';
$string['errorispromotional'] = 'Promotional flag must be 0 or 1';
$string['errorisenrollmentfee'] = 'Enrollment fee flag must be 0 or 1';

// Form categoria strings
$string['back'] = 'Back';
$string['categoryparent'] = 'Parent Category';
$string['categorydescription'] = 'Category Description';
$string['categorytheme'] = 'Category Theme';
$string['categorycreated'] = 'Category created successfully';
$string['categoryupdated'] = 'Category updated successfully';
$string['categoryduplicate'] = 'A category with this name already exists at this level';

// Course form strings
$string['addcourse'] = 'Add Course';
$string['editcourse'] = 'Edit Course';
$string['coursecreated'] = 'Course created successfully';
$string['courseupdated'] = 'Course updated successfully';
$string['shortnametaken'] = 'Short name already exists';
$string['general'] = 'General';
$string['course_enrolments_info'] = 'View and manage enrollment methods for this course. Use the link below to import prices from the category.';
$string['save_course_first'] = 'Please save the course first to manage enrollments.';
$string['enrolled_methods'] = 'Enrollment Methods';
$string['no_enrolment_methods'] = 'No enrollment methods configured for this course.';
$string['enrolment_method'] = 'Enrollment Method';
$string['edit'] = 'Edit';

// Pricing tab strings
$string['pricing'] = 'Pricing';
$string['category_prices'] = 'Category Prices';
$string['add_price'] = 'Add Price';
$string['price'] = 'Price';
$string['price_name'] = 'Price Name';
$string['validity_start'] = 'Start Date';
$string['validity_end'] = 'End Date';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['active'] = 'Active';
$string['inactive'] = 'Inactive';
$string['cancel'] = 'Cancel';
$string['save'] = 'Save';
$string['create_category_first'] = 'Please create the category first to manage prices';
$string['promotional'] = 'Promotional Price';
$string['enrollment_fee'] = 'Enrollment Fee';
$string['scheduled_task'] = 'Scheduled Task';
$string['installments'] = 'Number of Installments';

// Enrollment prices management
$string['manage_enrol_prices'] = 'Manage Enrollment Prices';
$string['import_category_prices'] = 'Import Category Prices';
$string['imported_prices'] = 'Imported Prices';
$string['available_prices'] = 'Available Prices for Import';
$string['no_prices_imported'] = 'No prices have been imported yet';
$string['no_prices_imported_help'] = 'Use the form below to import category prices.';
$string['no_prices_available'] = 'There are no prices available in this course category';
$string['price_already_imported'] = 'Already imported';
$string['unlink_price'] = 'Unlink';
$string['confirm_unlink'] = 'Do you really want to unlink this price?';
$string['prices_imported_success'] = '{$a} price(s) imported successfully!';
$string['price_unlinked_success'] = 'Link removed successfully!';
$string['import_selected_prices'] = 'Import Selected Prices';
$string['back_to_course_edit'] = 'Back to Course Edit';
$string['course'] = 'Course';
$string['price_value'] = 'Value';
$string['installments_short'] = 'Installments';
$string['type'] = 'Type';
$string['validity_period'] = 'Validity Period';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['undefined'] = 'Undefined';

// Custom Status Integration
$string['statusreport'] = 'Status Report';
$string['customstatus_integration'] = 'Custom Status Integration';
$string['customstatus_notavailable'] = 'Custom Status plugin is not installed or enabled';
$string['totalstudents'] = 'Total Students';
$string['paidstudents'] = 'Paid Students';
$string['paymentdue'] = 'Payment Due';
$string['blockedstudents'] = 'Blocked Students';
$string['expectedrevenue'] = 'Expected Revenue';
$string['receivedrevenue'] = 'Received Revenue';
$string['pendingrevenue'] = 'Pending Revenue';
$string['markoverdue'] = 'Mark Overdue';
$string['markoverdue_confirm'] = 'Are you sure you want to mark all unpaid students as overdue?';
$string['overdue_marked'] = '{$a} student(s) marked as overdue';
$string['viewfullreport'] = 'View Full Report';
$string['quickactions'] = 'Quick Actions';
$string['sendreminder'] = 'Send Reminder';
$string['contactstudent'] = 'Contact Student';
$string['checkoverduepayments'] = 'Check overdue payments and update student status';

// Enrolment Management
$string['enrolment'] = 'Enrolment Management';
$string['enrolment_desc'] = 'Manage student enrolments with custom status';
$string['open_enrolment'] = 'Open Enrolment';
$string['enrolment_management'] = 'Enrolment Management';
$string['selectcoursetoenrol'] = 'Select Course to Enrol';
$string['selectcategory'] = 'Select a category';
$string['selectcourse'] = 'Select a course';
$string['selectedcourse'] = 'Selected Course';
$string['nocustomstatusenrol'] = 'Custom Status enrolment method not found';
$string['nocustomstatusenrol_help'] = 'This course does not have the Custom Status enrolment method enabled. Add it to start managing student enrolments.';
$string['addcustomstatusenrol'] = 'Add Custom Status Method';
$string['enrolusers'] = 'Enrol Users';
$string['enrolusers_desc'] = 'Enrol new students or update existing enrolments';
$string['enrolnow'] = 'Enrol Now';
$string['managestatus'] = 'Manage Status';
$string['managestatus_desc'] = 'Assign or update student status';
$string['assignstatus'] = 'Assign Status';
$string['viewreport'] = 'View Report';
$string['viewreport_desc'] = 'View detailed enrolment and status report';
$string['openreport'] = 'Open Report';
$string['statusreport_desc'] = 'Integrated payment status report';
$string['open_statusreport'] = 'Open Report';
$string['totalenrolled'] = 'Total Enrolled';
$string['paidstudents_count'] = 'Paid Students';
$string['paymentdue_count'] = 'Payment Due';

// Enrolment Data Management
$string['enrolmentdata'] = 'Enrolment Management';
$string['enrolmentdata_desc'] = 'Complete student enrolment records with personal, address and payment data';
$string['manage_enrolmentdata'] = 'Manage';

// WordPress Integration Card
$string['wordpress_card_title'] = 'WordPress Integration';
$string['wordpress_card_desc'] = 'Synchronize and manage data between Moodle and WordPress';
$string['manage_wordpress'] = 'Manage';

// WordPress Integration Page
$string['wordpress_integration_disabled'] = 'WordPress integration is disabled. Enable it in settings.';
$string['wordpress_settings_incomplete'] = 'WordPress settings are incomplete. Configure base URL, username, and password.';
$string['category_synced'] = 'Category "{$a}" synced successfully.';
$string['category_updated'] = 'Category "{$a}" updated successfully.';
$string['category_deleted'] = 'Category "{$a}" deleted successfully.';
$string['mapping_not_found'] = 'Mapping not found for this category.';
$string['sync_failed'] = 'Sync failed.';
$string['update_failed'] = 'Update failed.';
$string['delete_failed'] = 'Delete failed.';

// WordPress Integration Main Page
$string['sync_categories_title'] = 'Sync Categories';
$string['sync_categories_title_desc'] = 'Synchronize Moodle categories with WordPress "niveis" taxonomy';
$string['sync_courses_title'] = 'Sync Courses';
$string['sync_courses_title_desc'] = 'Synchronize Moodle courses as custom posts in WordPress';
$string['total_courses'] = 'Total Courses';
$string['connection_active'] = 'Active connection with WordPress';
$string['connection_failed'] = 'Failed connection with WordPress';
$string['wordpress_integration_help_title'] = 'How to use WordPress Integration';
$string['wordpress_integration_help_1'] = 'Use "Sync Categories" to send Moodle categories to WordPress "niveis" taxonomy';
$string['wordpress_integration_help_2'] = 'Use "Sync Courses" to create or update course posts in WordPress';
$string['wordpress_integration_help_3'] = 'Make sure the endpoint and API key are correctly configured in plugin settings';

// WordPress Integration Courses
$string['synced_courses'] = 'Synced Courses';
$string['pending_courses'] = 'Pending Courses';
$string['sync_courses'] = 'Sync Courses';
$string['sync_courses_action_desc'] = 'Send all Moodle courses as "curso" post type in WordPress';
$string['view_course_mappings_desc'] = 'View mapping between Moodle courses and WordPress posts';
$string['recent_courses'] = 'Recent Courses';
$string['no_courses_found'] = 'No courses found';
$string['sync_category_button'] = 'Sync';
$string['update'] = 'Update';
$string['delete'] = 'Delete';

// JavaScript strings
$string['processing'] = 'Processing...';
$string['ajax_error'] = 'AJAX Error occurred. Please try again.';

$string['sync_courses'] = 'Sync Courses';
$string['course'] = 'Course';
$string['shortname'] = 'Short Name';
$string['category_not_synced'] = 'Category not synced';
$string['category_not_synced_alert'] = 'Category not synced - sync not allowed';
$string['sync_course_button'] = 'Sync';
$string['update_course_button'] = 'Update';
$string['delete_course_button'] = 'Delete';
$string['course_synced'] = 'Course "{$a}" synced successfully.';
$string['course_updated'] = 'Course "{$a}" updated successfully.';
$string['course_deleted'] = 'Course "{$a}" deleted successfully.';

// Price synchronization strings
$string['sync_prices'] = 'Sync Prices';
$string['price_name'] = 'Price Name';
$string['price_value'] = 'Price Value';
$string['sync_price_button'] = 'Sync';
$string['update_price_button'] = 'Update';
$string['delete_price_button'] = 'Delete';
$string['price_synced'] = 'Price "{$a}" synced successfully.';
$string['price_updated'] = 'Price "{$a}" updated successfully.';
$string['price_deleted'] = 'Price "{$a}" deleted successfully.';
$string['course_not_synced'] = 'Course not synced - price sync not allowed.';$string['course_not_synced'] = 'Course not synced - price sync not allowed.';
$string['prices_synced'] = '{$a} prices synced from WordPress.';

