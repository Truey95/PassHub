<?php

function output($val)
{
    echo $val . "\r\n";
    flush();
    usleep(100000);
}

// Recursive remove directory and files
// http://php.net/manual/en/function.rmdir.php#117354
function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}

// Recursive copy directory and files
// https://www.codelibrary.me/2013/10/17/recursively-copy-files-with-php/
function recurse_copy($src, $dst) { 
    $dir = opendir($src); 
    @mkdir($dst); 
    while(false !== ( $file = readdir($dir)) ) { 
        if (( $file != '.' ) && ( $file != '..' )) { 
            if ( is_dir($src . '/' . $file) ) { 
                recurse_copy($src . '/' . $file,$dst . '/' . $file); 
            } 
            else { 
                copy($src . '/' . $file,$dst . '/' . $file); 
            } 
        } 
    } 
    closedir($dir); 
} 

// Run SQL and die with error if query failed
function runSqlOrDie($db_connection, $sql, $error_on_failure) {
    if(!$result = $db_connection->query($sql)) {
        die($error_on_failure . ' Details: ' .  $db_connection->error);
    }
}

// Rename a MySQL table
function renameTableOrDie($db_connection, $old_table_name, $new_table_name) {
    
    $sql = "SELECT 1 FROM `$old_table_name` LIMIT 1;";

    if(!$result = $db_connection->query($sql)) {
        output('Skipping rename of ' . $old_table_name . ' table - does not exist.');
        return false;
    }

    runSqlOrDie(
        $db_connection, 
        "ALTER TABLE `$old_table_name` RENAME TO `{$new_table_name}temp`;", 
        "Could not rename $old_table_name table."
    );

    runSqlOrDie(
        $db_connection, 
        "ALTER TABLE `{$new_table_name}temp` RENAME TO `$new_table_name`;", 
        "Could not rename $old_table_name table."
    );
}

// Detect current PassHub version
function getCurrentPassHubVersion($install_dir, $config) {
    $version = '';
    $config_version = '';
    // 1.0 unique feature: does not have Groups.php file
    // 1.1 unique feature: has app/models/PassHub/Groups.php file
    // 1.2.0+: config file has PASSHUB_VERSION constant
    $settings_file_path = $install_dir . 'app/models/PassHub/Settings.php';
    $groups_file_path = $install_dir . 'app/models/PassHub/Groups.php';
    if(file_exists($groups_file_path) === true) {
        $version = '1.1.0';
    } else {
        $version = '1.0.X';
    }
    if(isset($config['PASSHUB_VERSION'])) {
        $version = $config['PASSHUB_VERSION'];
    }
    return $version;
}