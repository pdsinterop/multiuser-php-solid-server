<?php
	require_once(__DIR__ . "/config.php");
	require_once(__DIR__ . "/vendor/autoload.php");
	
	use Pdsinterop\PhpSolid\Server;
	
	function initKeys() {
		$keys = Server::generateKeySet();
		file_put_contents(KEYDIR . "public.key", $keys['publicKey']);
		file_put_contents(KEYDIR . "private.key", $keys['privateKey']);
		file_put_contents(KEYDIR . "encryption.key", $keys['encryptionKey']);
	}

	function initDatabase() {
		$statements = [
		    'CREATE TABLE IF NOT EXISTS clients (
			clientId VARCHAR(255) NOT NULL PRIMARY KEY,
			origin TEXT NOT NULL,
			clientData TEXT NOT NULL
		    )',
		    'CREATE TABLE IF NOT EXISTS allowedClients (
			userId VARCHAR(255) NOT NULL PRIMARY KEY,
			clientId VARCHAR(255) NOT NULL
		    )',
		    'CREATE TABLE IF NOT EXISTS userStorage (
			userId VARCHAR(255) NOT NULL PRIMARY KEY,
			storageUrl VARCHAR(255) NOT NULL
		    )',
		    'CREATE TABLE IF NOT EXISTS verify (
			code VARCHAR(255) NOT NULL PRIMARY KEY,
			data TEXT NOT NULL
		    )',
		    'CREATE TABLE IF NOT EXISTS jti (
			jti VARCHAR(255) NOT NULL PRIMARY KEY,
			expires TEXT
		    )',
		    'CREATE TABLE IF NOT EXISTS users (
			user_id VARCHAR(255) NOT NULL PRIMARY KEY,
			email TEXT NOT NULL,
			password TEXT NOT NULL,
			data TEXT
		    )',
		    'CREATE TABLE IF NOT EXISTS ipAttempts (
			ip VARCHAR(255) NOT NULL PRIMARY KEY,
			type VARCHAR(255) NOT NULL,
			expires NOT NULL
		    )',
		];
		      
		try {
		    $pdo = new \PDO("sqlite:" . DBPATH);

		    // create tables
		    foreach($statements as $statement){
			$pdo->exec($statement);
		    }
		} catch(\PDOException $e) {
		    echo $e->getMessage();
		}
	}
		
	initKeys();
	initDatabase();
