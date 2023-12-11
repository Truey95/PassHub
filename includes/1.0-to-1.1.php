<?php

/*
 *  Database changes for 1.0 to 1.1
 *      a. Insert new database tables and their data
 *      b. Modify database schema on existing tables
 *      c. Modify database rows to conform to new schema
 *      d. Delete old database columns
*/

/*
 * a. Insert new database tables and their data
 * =======================================================================================
 *      - pages
 */

// Remove table `pages` if it already exists

runSqlOrDie(
    $db_connection,
    'DROP TABLE IF EXISTS `pages`',
    'Could not drop existing `pages` table.'
);

// Insert table `pages`

$pages_sql = "
CREATE TABLE `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
";

runSqlOrDie(
    $db_connection,
    $pages_sql,
    'Could not insert `pages` table.'
);

// Insert data for table `pages`

$pages_sql = "
INSERT INTO `pages` (`id`, `name`) VALUES
(1, 'Logins'),
(2, 'Categories'),
(3, 'Users'),
(4, 'Groups'),
(5, 'Tools');
";

runSqlOrDie(
    $db_connection,
    $pages_sql,
    'Could not insert page data.'
);

/*
 * b. Modify database schema on existing tables
 * =======================================================================================
 *      - Rename tables to be all lowercase
 *      - Add new table columns
 *      - Modify existing table columns
 */

// Rename tables to be all lowercase

renameTableOrDie($db_connection, 'ACL', 'acl');
renameTableOrDie($db_connection, 'Groups', 'groups');
renameTableOrDie($db_connection, 'Users', 'users');

// Add new table columns
// - 'acl' table
//      - type
//      - foreignId

// Insert type after groupId

runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` ADD `type` enum('page','category') NOT NULL DEFAULT 'page' AFTER `groupId`;",
    'Could not add `type` column to `acl` table.'
);

// Insert foreignId after type

runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` ADD `foreignId` int(11) unsigned NOT NULL DEFAULT '0' AFTER `type`;",
    'Could not add `foreignId` column to `acl` table.'
);

// Modify existing table columns
// - 'acl' table
//      - the 'rule' column can be safely renamed to accessLevel

runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` CHANGE `rule` `accessLevel` INT(11) UNSIGNED NULL DEFAULT '0' COMMENT '1 = Read, 2 = Create, 4 = Edit, 8 = Delete';",
    'Could not rename `rule` column to `accessLevel` in `acl` table.'
);

/*
 * c. Modify database rows to conform to new schema
 * =======================================================================================
 *      - acl table: traslate action into type and foreignId
 *      - add new indexes
 *      - add new constraints
 *      - acl table: delete unnecessary rows
 */

// - 'acl' table
//      - the 'action' column contained lowercase page names. Those need to be translated  
//        into type="page" and the page's matching foreignId in the new structure

// Translate old action column into foreignId

// Key: new page ID from pages table
// Value: page name from old ACL table
$pages = [
    1 => 'logins',
    2 => 'categories',
    3 => 'users'
];

foreach($pages as $id => $old_name) {
    // Update matching database rows
    $sql = "UPDATE acl SET foreignId = '{$id}' WHERE action = '{$old_name}';";
    if(!$result = $db_connection->query($sql)) {
        die($failed_label . 'Could not update acl row. Details: ' .  $db_connection->error);
    }
}

// Delete unnecessary acl rows with "0" permission
// No need to report errors here - harmless if it fails
$db_connection->query("DELETE FROM acl WHERE accessLevel = '0'");

// Add new indexes

// acl groupId index
runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` ADD KEY `groupId` (`groupId`);",
    'Could not add index to `groupId` column in `acl` table.'
);

// Add new constraints

// acl to groups
runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` ADD CONSTRAINT `acl to groups` FOREIGN KEY (`groupId`) REFERENCES `groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;",
    'Could not add constraint for acl to groups.'
);

/*
 * d. Delete old database columns
 * =======================================================================================
 */

// - 'acl' table
//      - action
runSqlOrDie(
    $db_connection,
    "ALTER TABLE `acl` DROP `action`;",
    'Could not add remove `action` column from `acl` table.'
);