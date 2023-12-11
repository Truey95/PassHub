<?php

/*
 *  Database changes for 1.2 to 1.22
 *      a. Upgrade character encodings
 */

// Categories Table
// -------------------------

// Update `name` column encoding

// Standard utf8
$sql_1 = 'ALTER TABLE `categories` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
$sql_2 = 'ALTER TABLE `fields` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';


// If MYSQL version supports it (5.5.3 or higher), use utf8 multi-byte
if ($db_connection->server_version >= 50503) {
    $sql_1 = 'ALTER TABLE `categories` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;';
    $sql_2 = 'ALTER TABLE `fields` CHANGE `name` `name` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;';
}

runSqlOrDie(
    $db_connection,
    $sql_1,
    'Could not change character encoding on `categories` table.'
);

runSqlOrDie(
    $db_connection,
    $sql_2,
    'Could not change character encoding on `categories` table.'
);
