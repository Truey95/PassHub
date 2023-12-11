<?php

/*
 *  Database changes for 1.1 to 1.2
 *      a. Insert new database tables and their data
 *      b. Modify database schema on existing tables
 *      c. Modify database rows
 */

/*
 * a. Insert new database tables and their data
 * =======================================================================================
 *      - activity
 *      - settings
 */

// Activity Table
// -------------------------

// Remove table `activity` if it already exists

runSqlOrDie(
    $db_connection,
    'DROP TABLE IF EXISTS `activity`',
    'Could not drop existing `activity` table.'
);

// Insert table `activity`

$activity_sql = "
CREATE TABLE `activity` (
  `id` int(10) UNSIGNED NOT NULL,
  `action` enum('create','edit','delete','') NOT NULL DEFAULT 'edit',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subject` enum('category','login','page','setting','user') NOT NULL DEFAULT 'login',
  `subject_name` varchar(128) NOT NULL DEFAULT '',  
  `subject_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `user_name` varchar(128) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

runSqlOrDie(
    $db_connection,
    $activity_sql,
    'Could not insert `activity` table.'
);

// Indexes for table `activity`

$activity_sql = "
ALTER TABLE `activity`
  ADD PRIMARY KEY (`id`);
";

runSqlOrDie(
    $db_connection,
    $activity_sql,
    'Could not set indexes for activity table.'
);

// Autoincrement for table `activity`

$activity_sql = "
ALTER TABLE `activity`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
";

runSqlOrDie(
    $db_connection,
    $activity_sql,
    'Could not set autoincrement for activity table.'
);


// Settings Table
// -------------------------

// Remove table `settings` if it already exists

runSqlOrDie(
    $db_connection,
    'DROP TABLE IF EXISTS `settings`',
    'Could not drop existing `settings` table.'
);

// Insert table `settings`

$settings_sql = "
CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(128) NOT NULL DEFAULT '',
  `value` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";

runSqlOrDie(
    $db_connection,
    $settings_sql,
    'Could not insert `settings` table.'
);

// Insert data for table `settings`

$settings_sql = "
INSERT INTO `settings` (`id`, `name`, `value`) VALUES
(1, 'language', 'en'),
(2, 'session_timeout', '86400'),
(3, 'show_category_badge', '0'),
(4, 'force_ssl', '0'),
(5, 'enable_2_step_verification', '0'),
(6, 'enable_activity_log', '1'),
(7, 'show_last_edit', '1');
";

runSqlOrDie(
    $db_connection,
    $settings_sql,
    'Could not insert settings data.'
);

// Indexes for table `settings`

$settings_sql = "
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);
";

runSqlOrDie(
    $db_connection,
    $settings_sql,
    'Could not set indexes for settings table.'
);

// Autoincrement for table `settings`

$settings_sql = "
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
";

runSqlOrDie(
    $db_connection,
    $settings_sql,
    'Could not set autoincrement for settings table.'
);

// Pages Table
// -------------------------

// Add new "Settings" page

$pages_sql = "
    INSERT INTO `pages` SET id = '6', name = 'Settings';
";

runSqlOrDie(
    $db_connection,
    $pages_sql,
    'Could not update pages table.'
);

/*
 * b. Modify database schema on existing tables
 * =======================================================================================
 *      - Add new table columns
 */

// Add new table columns
// - 'users' table
//      - otpSecret
//      - protected

// Insert otpSecret after resetKey

runSqlOrDie(
    $db_connection,
    "ALTER TABLE `users` ADD `otpSecret` varchar(512) NOT NULL DEFAULT '' AFTER `resetKey`;",
    'Could not add `otpSecret` column to `users` table.'
);

// Insert protected after otpSecret

runSqlOrDie(
    $db_connection,
    "ALTER TABLE `users` ADD `protected` tinyint(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `otpSecret`;",
    'Could not add `protected` column to `users` table.'
);

/*
 * c. Modify database rows
 * =======================================================================================
 *      - set protected column for admin
 *      - add otpSecret values for each user
 *      - generate stronger symmetric encryption key for config.php
 *      - update encryption key in existing $config array (from run-update.php file)
 *      - decrypt existing fields, re-encrypt them with new key
 */

// Set protected column for admin

runSqlOrDie(
    $db_connection,
    "UPDATE `users` SET protected = '1' WHERE groupId = 1 LIMIT 1",
    "Could not set admin user protected flag."
);

// Add otpSecret to each user row

$users = [];
$users_rows = $db_connection->query("SELECT * FROM `users`");

while ($row = $users_rows->fetch_assoc()) {
    $users[] = $row;
}

foreach ($users as $user) {
    // Generate OTP Secret (base32 encoded)
    $secret = openssl_random_pseudo_bytes(32);
    $encoded_secret = ParagonIE\ConstantTime\Base32::encode($secret);
    $encoded_secret = str_replace('=', '', $encoded_secret); // remove padding characters
    // Insert into user's row
    $db_connection->query("UPDATE `users` SET otpSecret = '$encoded_secret' WHERE id = '{$user['id']}' LIMIT 1");
}

// ------------------------------------------------------------- 
// NOTE
// ------------------------------------------------------------- 
// Fields from 1.0 and 1.1 installs use a shorter encryption key.
// 1.2 uses a much longer key, which is a requirment of the updated
// encryption/decryption library (php-encryption).
// This necessitates the existing field values are decrypted first,
// Then encrypted using the new key and updated in the database.

// Generate stronger symmetric encryption key for config.php

$new_encryption_key = \Defuse\Crypto\Key::createNewRandomKey();
$new_encryption_key = $new_encryption_key->saveToAsciiSafeString();

// Update encryption key in existing $config array (from run-update.php file)
// But first store old key for decrypting current fields!
$old_encryption_key = $config['CRYPTKEY'];
$config['CRYPTKEY'] = $new_encryption_key;

// Decrypt existing fields, re-encrypt them with new key

$fields = [];
$fields_rows = $db_connection->query("SELECT * FROM `fields`");

while ($row = $fields_rows->fetch_assoc()) {
    $fields[] = $row;
}

// Convert old encryption key for Crypto class
$old_encryption_key = \Defuse\CryptoCompatibility\CryptoCompatibility::hexToBin($old_encryption_key);
$new_encryption_key = \Defuse\Crypto\Key::loadFromAsciiSafeString($new_encryption_key);

foreach ($fields as $field) {
    // Decrypt field
    $fieldValue = \Defuse\CryptoCompatibility\CryptoCompatibility::hexToBin($field['value']);
    try {
        $fieldValue = \Defuse\CryptoCompatibility\CryptoCompatibility::decrypt($fieldValue, $old_encryption_key);
    } catch (\Ex\InvalidCiphertextException $ex) {
        die('DANGER! The ciphertext has been tampered with!');
    } catch (\Ex\CryptoTestFailedException $ex) {
        $fieldValue = '';
        //die('Cannot safely perform decryption');
    } catch (\Ex\CannotPerformOperationException $ex) {
        $fieldValue = '';
    }

    //output('new_encryption_key before: '. $new_encryption_key);

    // Encrypt using new key
    
    $fieldValue = \Defuse\Crypto\Crypto::encrypt($fieldValue, $new_encryption_key);

    // Update row with new value
    $db_connection->query("UPDATE `fields` SET value = '$fieldValue' WHERE id = '{$field['id']}' LIMIT 1");
}