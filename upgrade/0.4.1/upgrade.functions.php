<?php

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../vendor/autoload.php");

use Pdsinterop\PhpSolid\Server;

function upgradeDatabase()
{
	$statements = [
		'BEGIN TRANSACTION',
		'CREATE TABLE allowedClients_new (userId VARCHAR(255) NOT NULL, clientId VARCHAR(255) NOT NULL)',
		'INSERT INTO allowedClients_new (userId, clientId) SELECT userId, clientId FROM allowedClients',
		'DROP TABLE allowedClients',
		'ALTER TABLE allowedClients_new RENAME TO allowedClients',
		'COMMIT'
	];

	try {
		$pdo = new \PDO("sqlite:" . DBPATH);

		// create tables
		foreach ($statements as $statement) {
			$pdo->exec($statement);
		}
	} catch (\PDOException $e) {
		echo $e->getMessage();
	}
}
