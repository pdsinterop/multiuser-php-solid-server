<?php
    require_once(__DIR__ . "/config.php");
    require_once(__DIR__ . "/vendor/autoload.php");

    function addStorage($userId, $ownerWebId) {
            $storageId = $userId;
            try {
                $pdo = new \PDO("sqlite:" . DBPATH);
                $query = $pdo->prepare('INSERT INTO storage VALUES(:storageId, :owner)');
                $query->execute([
                    ':storageId' => $storageId,
                    ':owner' => $ownerWebId
                ]);
            } catch(\PDOException $e) {
                echo $e->getMessage();
            }
    }

    function addUser($userId) {
            try {
                $pdo = new \PDO("sqlite:" . DBPATH);
                $query = $pdo->prepare('INSERT INTO users VALUES (:userId, :email, :passwordHash, :data)');
                $webId = "https://id-" . $userId . "." . BASEDOMAIN . "/#me";
                $userData = [
                    "id" => $userId,
                    "email" => $userId,
                    "webId" => $webId
                ];
                $query->execute([
                    ':userId' => $userId,
                    ':email' => $userId,
                    ':passwordHash' => password_hash($userId, PASSWORD_BCRYPT),
                    ':data' => json_encode($userData)
                ]);
            } catch(\PDOException $e) {
                echo $e->getMessage();
            }
            return $webId;
    }

    $users = [
        'alice',
        'bob'
    ];

    foreach ($users as $user) {
        $webId = addUser($user);
        addStorage($user, $webId);
    }
    