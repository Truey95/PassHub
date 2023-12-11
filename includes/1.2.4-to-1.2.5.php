<?php

/*
 *  Database changes for 1.2.4 to 1.2.5
 *      a. Add last_modified column to logins table
 */

$sql = 'ALTER TABLE `logins` ADD `last_modified` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `category_id`;';

runSqlOrDie(
    $db_connection,
    $sql,
    'Could not add `last_modified` column to `logins` table.'
);

