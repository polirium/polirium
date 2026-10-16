<?php

return [
    /*
    |--------------------------------------------------------------------------
    | General UI
    |--------------------------------------------------------------------------
    */
    'title' => ':name - Installation',
    'step_of' => 'Step :current of :total',
    'copyright' => '© :year :name. All rights reserved.',
    'btn_back' => 'Back',
    'btn_continue' => 'Continue',
    'btn_complete' => 'Complete Installation',
    'btn_processing' => 'Processing...',
    'btn_finalizing' => 'Finalizing...',
    'error_generic' => 'Please check your inputs and try again.',

    /*
    |--------------------------------------------------------------------------
    | Requirements Step
    |--------------------------------------------------------------------------
    */
    'requirements_title' => 'Server Requirements',
    'requirements_subtitle' => 'Checking your server compatibility.',
    'requirements_passed_label' => 'Passed',
    'requirements_failed_label' => 'Action Required',
    'requirements_all_passed' => 'All requirements passed! Click Continue to proceed.',
    'requirements_action_needed' => 'Action Required: Please install the missing PHP extensions or increase the limits above, then refresh this page.',
    'requirements_fix_php' => 'Update to PHP :version or later, or contact your hosting provider.',
    'requirements_fix_extension' => 'Install or enable the PHP ":name" extension (e.g. apt install php-:name).',
    'requirements_fix_memory' => 'Increase memory_limit in your php.ini file (currently :current, need :min).',

    /*
    |--------------------------------------------------------------------------
    | Permissions Step
    |--------------------------------------------------------------------------
    */
    'permissions_title' => 'Directory Permissions',
    'permissions_subtitle' => 'Ensuring application directories are writable.',
    'permissions_writable' => 'Writable',
    'permissions_fix' => 'Perms 775',
    'permissions_action_needed' => 'Action Required: Set proper permissions (chmod 775) on the directories above.',
    'permissions_all_correct' => 'All permissions correct! Click Continue to proceed.',

    /*
    |--------------------------------------------------------------------------
    | Environment Step
    |--------------------------------------------------------------------------
    */
    'environment_title' => 'Database Connection',
    'environment_subtitle' => 'Configure your database settings.',
    'environment_connection_type' => 'Connection Type',
    'environment_host' => 'Host',
    'environment_port' => 'Port',
    'environment_database_name' => 'Database Name',
    'environment_database_hint' => "If it doesn't exist, we'll attempt to create it.",
    'environment_username' => 'Username',
    'environment_password' => 'Password',
    'environment_test_connection' => 'Test Connection',
    'environment_testing' => 'Testing...',
    'environment_test_success' => 'Connection successful! Database is ready.',

    'database_action_title' => 'Existing database',
    'database_action_subtitle' => 'Choose how Polirium should handle database ":database".',
    'database_empty_title' => 'The database is empty',
    'database_empty_description' => 'A new installation can continue safely. No existing data will be removed.',
    'database_existing_polirium_title' => 'An existing Polirium installation was detected',
    'database_existing_polirium_description' => ':count tables were found. Back up the database before applying updates.',
    'database_existing_unknown_title' => 'This database is not recognized as Polirium',
    'database_existing_unknown_description' => ':count tables were found. Keeping and upgrading is disabled to prevent corrupting unrelated data.',
    'database_keep_title' => 'Keep data and update',
    'database_keep_description' => 'Run pending migrations, preserve users and business data, then create the installed lock file.',
    'database_reinstall_title' => 'Delete data and reinstall',
    'database_reinstall_description' => 'Permanently delete every table in this database and create a new Polirium installation.',
    'database_reinstall_confirmation' => 'Type :database to confirm permanent deletion',
    'database_action_required' => 'Choose whether to keep the existing database or reinstall it.',
    'database_upgrade_unrecognized' => 'This database cannot be safely identified as an existing Polirium installation.',
    'database_reinstall_confirmation_invalid' => 'The database name confirmation does not match. No data was deleted.',
    'database_action_expired' => 'The database choice is missing or no longer matches the configured database. Return to the previous step and choose again.',

    /*
    |--------------------------------------------------------------------------
    | Migrations Step
    |--------------------------------------------------------------------------
    */
    'migrations_title' => 'Database Setup',
    'migrations_subtitle' => 'Ready to install database tables.',
    'migrations_heading' => 'Migration & Seeding',
    'migrations_description' => "We're about to run the standard migration to set up your database schema. You can optionally seed the database with demo content to get started quickly.",
    'migrations_upgrade_title' => 'Update existing data',
    'migrations_upgrade_subtitle' => 'Ready to apply pending Polirium database migrations.',
    'migrations_upgrade_heading' => 'Safe database update',
    'migrations_upgrade_description' => 'Pending migrations will be applied without deleting existing users or business data. A lock file is created only after every stage succeeds.',
    'migrations_demo_label' => 'Install Demo Data',
    'migrations_demo_hint' => 'Recommended for development',
    'migrations_help_toggle_show' => 'Having trouble? Check common solutions',
    'migrations_help_toggle_hide' => 'Hide solutions',
    'migrations_help_1' => 'Ensure your database server is running and accessible from this server.',
    'migrations_help_2' => 'Verify the database credentials in the previous step (host, port, username, password).',
    'migrations_help_3' => 'Make sure your database user has CREATE TABLE privileges.',
    'migrations_help_4' => 'Check the database server error logs for more specific details.',
    'migrations_help_5' => 'If all else fails, review storage/logs/laravel.log for the full error trace.',
    'migrations_error_prefix' => 'Database setup failed. We have reverted all changes to leave a clean state.',
    'migrations_error_access_denied' => 'The database credentials in the previous step appear to be incorrect.',
    'migrations_error_table_exists' => 'A table already exists. Please drop the database and start fresh.',
    'migrations_error_no_driver' => 'The PHP extension for the selected database type is not installed.',
    'migrations_error_connection_refused' => 'The database server refused the connection. Please check if it is running.',
    'migrations_error_fallback' => 'Database setup could not be completed. No automatic cleanup was performed; review storage/logs/laravel.log before retrying.',
    'progress_starting' => 'Starting the Polirium installation.',
    'progress_upgrade_starting' => 'Starting a safe update of the existing Polirium database.',
    'progress_wiping_database' => 'Confirmed: deleting the existing database tables for a clean installation.',
    'progress_core_migrations' => 'Creating the core database structure.',
    'progress_core_ready' => 'Core database structure is ready.',
    'progress_modules_discovered' => 'Available modules have been discovered.',
    'progress_enabling_module' => 'Enabling module: :module.',
    'progress_module_migrations' => 'Creating database tables for enabled modules.',
    'progress_module_migrations_ready' => 'Module database tables are ready.',
    'progress_administrative_units' => 'Synchronizing Vietnam administrative units.',
    'progress_administrative_units_ready' => 'Vietnam administrative units are ready.',
    'progress_assets' => 'Publishing application assets.',
    'progress_complete' => 'System installation completed.',
    'progress_failed' => 'Installation failed. Existing database contents were left in place; review the log before retrying.',
    'progress_title' => 'Installation progress',
    'progress_waiting' => 'Preparing installation...',

    /*
    |--------------------------------------------------------------------------
    | Admin Step
    |--------------------------------------------------------------------------
    */
    'admin_title' => 'Create Administrator',
    'admin_subtitle' => 'Setup your primary access account.',
    'admin_name' => 'Full Name',
    'admin_name_placeholder' => 'John Doe',
    'admin_email' => 'Email Address',
    'admin_email_placeholder' => 'admin@example.com',
    'admin_password' => 'Password',
    'admin_password_placeholder' => 'Min. 8 characters',
    'admin_password_confirm' => 'Confirm Password',
    'admin_password_confirm_placeholder' => 'Repeat password',
    'admin_error_email_required' => 'An email address is required.',
    'admin_error_password_required' => 'A password is required.',
    'admin_error_password_mismatch' => 'The passwords do not match.',
    'admin_first_name' => 'First name',
    'admin_last_name' => 'Last name',
    'admin_username' => 'Username',
    'migrations_database_not_empty' => 'This database is not empty. Choose a new empty database to protect existing data.',
    'success_title' => 'Installation successful!',
    'success_message' => ':name is ready to use.',
    'success_login' => 'Go to login',

    /*
    |--------------------------------------------------------------------------
    | Step Labels
    |--------------------------------------------------------------------------
    */
    'step_requirements' => 'Server Requirements',
    'step_permissions' => 'Directory Permissions',
    'step_environment' => 'Database Setup',
    'step_database_action' => 'Existing Data',
    'step_migrations' => 'Migrate Database',
    'step_admin' => 'Create Admin',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    */
    'not_installed' => 'Application not installed.',
];
