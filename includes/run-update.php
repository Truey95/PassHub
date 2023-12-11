<?php

/**
 * PassHub Run Update
 *
 * This script performs the tasks necessary to update PassHub
 * to the version included with this updater.
 *
 * This script is designed to upgrade all older versions to 1.2.5.
 */

// Silence errors
error_reporting(0);
ini_set('display_errors', 0);

// Show all errors
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set("UTC");

// Directory containing existing PassHub installation
$install_dir = '../../';
// Directory containing upgrade files
$upgrade_files_dir = '../upgrade-files/';
// Database backup path
$db_backup_path = '../backup/database-backup-' . date('Y-m-d') . '.sql';
// Text to prepend with each error message
$failed_label = '<span class="red-text">Upgrade failed.</span>';
// Database connection object
$db_connection;
// Current PassHub version number (before upgrading)
$passhub_current_version;

header('Content-type: text/html; charset=utf-8');

// Includes
require_once('../vendor/autoload.php'); // Composer
require_once('../lib/mysqldump-php-2.2/src/Ifsnop/Mysqldump/Mysqldump.php');
require_once('functions.php');
require_once('Defuse/CryptoCompatibility/autoload.php'); // PassHub 1.0 & 1.1 version of Crypto, necessary for transferring old encrypted fields

output('Starting update process...');

/*
 * Outline:
 * 1. Back up existing installation
 * 2. Detect currently installed version of PassHub
 * 3. Open a database connection
 * 4. Database changes
 * 5. Replace all files with new version
 * 6. Write new config.ini to the fresh installation
 * 7. Clean up temporary files
 * 8. Done!
 */

/*
 * 1. Back up existing installation
 * =======================================================================================
 *      - Copy files to the /updater/backup directory
 *      - Store config.ini settings
 *      - Back up the database to the same directory
 */

// Copy files to the /updater/backup directory
$config_original_path = '../../app/config/config.ini';
$config_backup_path = '../backup/config.ini';
if(!copy($config_original_path, $config_backup_path)) {
    die($failed_label . ' Could not copy config.ini');
}

// Store config.ini settings
if(!$config = parse_ini_file($config_backup_path)) {
    die($failed_label . ' Could not parse config.ini');
};

// Back up the database table data to the same directory
$dump = new Ifsnop\Mysqldump\Mysqldump(
    "mysql:host={$config['DBHOST']};dbname={$config['DBNAME']}",
    $config['DBUSER'],
    $config['DBPASS']
);
$dump->start($db_backup_path);


/*
 * 2. Detect currently installed version of PassHub
 * =======================================================================================
 */

$passhub_current_version = getCurrentPassHubVersion($install_dir, $config);

/*
 * 3. Open MySQL database connection
 * =======================================================================================
 */

$db_connection = new mysqli(
    $config['DBHOST'],
    $config['DBUSER'],
    $config['DBPASS'],
    $config['DBNAME']
);

if($db_connection->connect_errno) {
    die($failed_label . ' Could not connect to existing PassHub database. Details: ' . $db_connection->connect_error);
}

/*
 * 4. Database changes
 * =======================================================================================
 */

// 1.0 to 1.1
if($passhub_current_version === '1.0.X') {
    require_once('1.0-to-1.1.php');
}

// 1.1 to 1.2
if($passhub_current_version === '1.0.X' || $passhub_current_version === '1.1.0') {
    require_once('1.1-to-1.2.php');
}

//  1.2.X to 1.2.2
if(version_compare($passhub_current_version, '1.2.2', '<')) {
    require_once('1.2-to-1.2.2.php');
}

//  1.2.X to 1.2.2
if(version_compare($passhub_current_version, '1.2.2', '<')) {
    require_once('1.2-to-1.2.2.php');
}

//  1.2.4 to 1.2.5
if(version_compare($passhub_current_version, '1.2.5', '<')) {
    require_once('1.2.4-to-1.2.5.php');
}

/*
 * 5. Replace all files with new version
 * =======================================================================================
 *      - Delete existing PassHub files in install directory
 *      - Copy new PassHub files to install directory
 */

if($passhub_current_version === '1.0.X' || $passhub_current_version === '1.1.0') {
    // Delete existing PassHub files in install directory
    rrmdir($install_dir . 'app/');
    rrmdir($install_dir . 'assets/');
    rrmdir($install_dir . 'tmp/');
    unlink($install_dir . 'index.php');

    // Copy new PassHub files to to install directory for 1.2.0
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.0/', $install_dir);
}

// Copy new PassHub files to to install directory for 1.2.1
if(version_compare($passhub_current_version, '1.2.1', '<')) {
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.1/', $install_dir);
}

// Copy new PassHub files to to install directory for 1.2.2
if(version_compare($passhub_current_version, '1.2.2', '<')) {
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.2/', $install_dir);
}

// Copy new PassHub files to to install directory for 1.2.3
if(version_compare($passhub_current_version, '1.2.3', '<')) {
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.3/', $install_dir);
}

// Copy new PassHub files to to install directory for 1.2.4
if(version_compare($passhub_current_version, '1.2.4', '<')) {
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.4/', $install_dir);
}

// Copy new PassHub files to to install directory for 1.2.5
if(version_compare($passhub_current_version, '1.2.5', '<')) {
    // Delete depencies folder, they've changed in this release
    rrmdir($install_dir . 'vendor/');
    recurse_copy($upgrade_files_dir . 'passhub-partial-1.2.5/', $install_dir);
}

/*
 * 6. Write new config.ini to the fresh installation
 * =======================================================================================
 */

$new_config_contents = "[globals]

PASSHUB_VERSION=\"1.2.5\"

LOCALES=assets/languages/

DEBUG=0
CACHE=true
ENCODING=utf-8
TZ=UTC

ENABLEINSTALLER=false

UI=app/views/
CSS=assets/css/
JS=assets/js/
LOGS=app/logs/
AUTOLOAD=app/inc/|app/models/

DBHOST=\"{$config['DBHOST']}\"
DBNAME=\"{$config['DBNAME']}\"
DBUSER=\"{$config['DBUSER']}\"
DBPASS=\"{$config['DBPASS']}\"
DBPORT={$config['DBPORT']}

NAME=PassHub
EMAIL=\"{$config['EMAIL']}\"
EMAIL_PW=\"{$config['EMAIL_PW']}\"
SMTP_SERVER=\"{$config['SMTP_SERVER']}\"
SMTP_PORT={$config['SMTP_PORT']}
SMTP_SCHEME={$config['SMTP_SCHEME']}

CRYPTKEY=\"{$config['CRYPTKEY']}\"

[configs]
app/config/routes.ini=true
app/config/locales.ini=false
";

if(file_put_contents($config_original_path, $new_config_contents) === false) {
    die($failed_label . ' Could not write new config.ini');
}

/*
 * 7. Clean up temporary files
 * =======================================================================================
 */

unlink($config_backup_path);
unlink($db_backup_path);

/*
 * 8. Done!
 * =======================================================================================
 */

output('Update complete!');