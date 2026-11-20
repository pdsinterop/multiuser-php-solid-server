<?php

namespace Pdsinterop\PhpSolid\Routes;

use Pdsinterop\PhpSolid\Server;
use Pdsinterop\PhpSolid\ClientRegistration;
use Pdsinterop\PhpSolid\User;
use Pdsinterop\PhpSolid\Session;
use Pdsinterop\PhpSolid\Mailer;
use Pdsinterop\PhpSolid\IpAttempts;
use Pdsinterop\PhpSolid\StorageServer;

class Account
{
	public function requireLoggedInUser()
	{
		$userObject = new User();
		$session = new Session();
		$user = $userObject::getUser($session::getLoggedInUser());
		if (!$user) {
			switch ($_SERVER['REQUEST_METHOD']) {
				case "GET":
					header("Location: /login/?redirect_uri=" . urlencode($_SERVER['REQUEST_URI']));
					exit();
				break;
				default:
					header("HTTP/1.0 400 Bad Request");
					exit();
				break;
			}
		}
	}

	public function respondToDashboard()
	{
		$userObject = new User();
		$session = new Session();
		$user = $userObject::getUser($session::getLoggedInUser());
		include_once(FRONTENDDIR . "bootloader.html");
	}

	public function respondToLogout()
	{
		$userObject = new User();
		$session = new Session();
		$user = $userObject::getUser($session::getLoggedInUser());
		if ($user) {
			session_destroy();
		}
		header("Location: /login/");
		exit();
	}

	public function respondToAccountVerify()
	{
		$verifyData = [
			'email' => $_POST['email']
		];

		$user = new User();
		$verifyToken = $user::saveVerifyToken('verify', $verifyData);

		try {
			$mailer = new Mailer();
			$mailer::sendVerify($verifyToken);

			$responseCode = 201;
			$responseData = "OK";
		} catch (\Throwable $e) {
			error_log('Could not send verification email (' . get_class($e) . '):' . $e->getMessage());

			$responseCode = 502;
			$responseData = [
				'title' => 'Mail could not be sent',
				'errors' => [['detail' => 'Failed to send verification email']]
			];
		}

		http_response_code($responseCode);
		header("Content-type: application/json");
		echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

	public function respondToAccountNew()
	{
		$user = new User();
		$verifyToken = $user::getVerifyToken($_POST['confirm']);
		if (!$verifyToken) {
			error_log("Could not read verify token");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		if ($verifyToken['email'] !== $_POST['email']) {
			error_log("Verify token does not match email");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		if ($user::userEmailExists($_POST['email'])) {
			error_log("Account already exists");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		if (!$_POST['password'] === $_POST['repeat_password']) {
			error_log("Password repeat does not match");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}

		$newUser = [
			"email" => $_POST['email'],
			"password" => $_POST['password']
		];

		$createdUser = $user::createUser($newUser);
		if (!$createdUser) {
			error_log("Failed to create user");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$storageServer = new StorageServer();
		$createdStorage = $storageServer::createStorage($createdUser['webId']);
		$user::setStorage($createdUser['userId'], $createdStorage['storageUrl']);

		$mailer = new Mailer();
		$mailer::sendAccountCreated($createdUser);

		$responseData = array(
			"webId" => $createdUser['webId'],
			"storageUrl" => $createdStorage['storageUrl']
		);

		header("HTTP/1.1 201 Created");
		header("Content-type: application/json");
		$session = new Session();
		$session::start($_POST['email']);
		echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

	public function respondToAccountResetPassword()
	{
		$user = new User();
		if (! $user::userEmailExists($_POST['email'])) {
			header("HTTP/1.1 200 OK"); // Return OK even when user is not found;
			header("Content-type: application/json");
			echo json_encode("OK");
			exit();
		}
		$verifyData = [
			'email' => $_POST['email']
		];

		$verifyToken = $user::saveVerifyToken('passwordReset', $verifyData);
		$mailer = new Mailer();
		$mailer::sendResetPassword($verifyToken);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public function respondToAccountChangePassword()
	{
		$user = new User();
		$verifyToken = $user::getVerifyToken($_POST['token']);
		if (!$verifyToken) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$result = $user::setUserPassword($verifyToken['email'], $_POST['newPassword']);
		if (!$result) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public function respondToAccountDelete()
	{
		$user = new User();
		if (! $user::userEmailExists($_POST['email'])) {
			header("HTTP/1.1 200 OK"); // Return OK even when user is not found;
			header("Content-type: application/json");
			echo json_encode("OK");
			exit();
		}
		$verifyData = [
			'email' => $_POST['email']
		];

		$verifyToken = $user::saveVerifyToken('deleteAccount', $verifyData);
		$mailer = new Mailer();
		$mailer::sendDeleteAccount($verifyToken);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public function respondToAccountDeleteConfirm()
	{
		$user = new User();
		$verifyToken = $user::getVerifyToken($_POST['token']);
		if (!$verifyToken) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$user::deleteAccount($verifyToken['email']);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public function respondToLogin()
	{
		$ipAttempts = new IpAttempts();
		$failureCount = $ipAttempts::getAttemptsCount($_SERVER['REMOTE_ADDR'], "login");
		if ($failureCount > 5) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$userObject = new User();
		if ($userObject::checkPassword($_POST['username'], $_POST['password'])) {
			$session = new Session();
			$session::start($_POST['username']);
			$user = $userObject::getUser($_POST['username']);
			if (!isset($_POST['redirect_uri']) || $_POST['redirect_uri'] === '') {
				header("Location: /dashboard/#autologin/" . urlencode($user['webId']));
				exit();
			}
			header("Location: " . urldecode($_POST['redirect_uri'])); // FIXME: Do we need to harden this?
		} else {
			$ipAttempts::logFailedAttempt($_SERVER['REMOTE_ADDR'], "login", time() + 3600);
			header("Location: /login/");
		}
	}
}
