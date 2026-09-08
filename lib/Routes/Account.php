<?php

namespace Pdsinterop\PhpSolid\Routes;

use Laminas\Diactoros\ServerRequestFactory;
use Pdsinterop\PhpSolid\User;
use Pdsinterop\PhpSolid\Session;
use Pdsinterop\PhpSolid\Mailer;
use Pdsinterop\PhpSolid\IpAttempts;
use Pdsinterop\PhpSolid\StorageServer;

class Account
{
	public static function requireApiAuthentication()
	{
		// @CHECKME: Use origin? Or, as we already require client_id, use that? Or both?

		$request = ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
		$origin = $_REQUEST['origin'] ?? $request->getHeaderLine('Origin');
		$auth = $request->getHeaderLine('Authorization');
		$apiKey = substr($auth, 7); // 7 chars = 'Bearer '

		if (! defined('API_KEYS')) {
			http_response_code(404); // Not Found
			exit();
		} elseif (! is_array(API_KEYS)) {
			http_response_code(500); //Internal Server Error
			header('Content-type: application/json');
			echo <<<'JSON'
{
    "title": "API_KEYS Misconfiguration",
    "errors": [
        {
            "detail": "Configured API_KEYS must be an array"
        }
    ]
}
JSON;
			exit();
		} elseif (! $origin) {
			http_response_code(422); //Unprocessable Entity
			header('Content-type: application/json');
			echo '{"title": "Missing Origin","errors": [{"detail": "Caller must provide an origin"}]}';
			exit();
		} elseif (! $auth) {
			http_response_code(401); // Unauthorized
			header('Content-type: application/json');
			echo '{"title": "Missing authentication","errors": [{"detail": "Caller must provide authentication"}]}';
			exit();
		} elseif (! str_starts_with($auth, 'Bearer ')) {
			http_response_code(400); //Bad Request
			header('Content-type: application/json');
			echo <<<'JSON'
{
    "title": "Missing bearer",
    "errors": [
        {
            "detail": "Provided authentication type must be 'Bearer'"
        }
    ]
}
JSON;
			exit();
		} elseif (! in_array($apiKey, API_KEYS, true)) {
			http_response_code(403); //Forbidden
			header('Content-type: application/json');
			echo <<<'JSON'
{
    "title": "Missing priviliges",
    "errors": [
        {
            "detail": "Provided authentication has insufficient privileges"
        }
    ]
}
JSON;
			exit();
		} else {
			$origins = array_filter(API_KEYS, static function ($value) use ($apiKey) {
				return $value === $apiKey;
			});

			if (! array_key_exists($origin, $origins)) {
				http_response_code(403); // Forbidden
				header('Content-type: application/json');
				echo <<<'JSON'
{
    "title": "Incorrect origin",
    "errors": [
        {
            "detail": "Provided authentication is not for provided origin"
        }
    ]
}
JSON;
				exit();
			}
		}
	}

	public static function requireLoggedInUser()
	{
		$user = User::getUser(Session::getLoggedInUser());
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

	public static function respondToDashboard()
	{
		$user = User::getUser(Session::getLoggedInUser());
		include_once(FRONTENDDIR . "bootloader.html");
	}

	public static function respondToLogout()
	{
		$user = User::getUser(Session::getLoggedInUser());
		if ($user) {
			session_destroy();
		}
		header("Location: /login/");
		exit();
	}

	public static function respondToAccountVerify()
	{
		$verifyData = [
			'email' => $_POST['email']
		];

		$verifyToken = User::saveVerifyToken('verify', $verifyData);

		try {
			Mailer::sendVerify($verifyToken);

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

	public static function respondToApiCreate()
	{
		// Create a Solid Pod through an API call.
		// This means that a verification email has not been sent and the verify code does not exist.

		// @TODO: Large parts of this method have been copied over from Account::respondToAccountNew()
		// 		  and User::createUser(). Either methods need to be combined, or duplicate code needs to be extracted.

		// @TODO: Replace x-form-encode / multipart with JSON ?

		if (empty($_POST['password'])) {
			http_response_code(400); // Bad Request
			header('Content-type: application/json');
			echo <<<'JSON'
{
    "title": "Missing required parameter(s)",
    "errors": [
        {
            "detail": "Required parameter 'password' has not been provided",
            "pointer": "#/password"
        }
    ]
}
JSON;
			exit();
		} elseif (empty($_POST['client_id'])) {
			http_response_code(400); // Bad Request
			header('Content-type: application/json');
			echo <<<'JSON'
{
    "title": "Missing required parameter(s)",
    "errors": [
        {
            "detail": "Required parameter 'client_id' has not been provided",
            "pointer": "#/client_id"
        }
    ]
}
JSON;
			exit();
		} elseif (! empty($_POST['user_id']) && User::userIdExists($_POST['user_id'])) {
			http_response_code(422); // Unprocessable Content
			header('Content-type: application/json');
			$user_id = $_POST['user_id'];
			echo <<<JSON
{"title": "Cannot create user $user_id",
    "errors": [
        {
            "detail": "User ID for provided username already exists",
            "pointer": "#/user_id"
        }
    ]
}
JSON;
			exit();
		} elseif (! empty($_POST['user_id']) && StorageServer::storageIdExists($_POST['user_id'])) {
			http_response_code(422); // Unprocessable Content
			header('Content-type: application/json');
			$user_id = $_POST['user_id'];
			echo <<<JSON
{"title": "Cannot create user '$user_id'",
    "errors": [
        {
            "detail": "Storage for provided username already exists",
            "pointer": "#/user_id"
        }
    ]
}
JSON;
			exit();
		} else {
			if (! empty($_POST['user_id'])) {
				$userId = $_POST['user_id'];
			} else {
				$generatedUserId = bin2hex(random_bytes(16));

				while (User::userIdExists($generatedUserId)) {
					$generatedUserId = bin2hex(random_bytes(16));
				}

				$userId = $generatedUserId;
			}

			$password = $_POST['password'];

			$email = vsprintf('%s@%s', [
				$userId,
				BASEDOMAIN,
			]);

			$newUser = [
				'email' => $email,
				'userId' => $userId,
				'password' => $password,
			];

			$createdUser = User::createUser($newUser);

			if (! $createdUser) {
				http_response_code(422); // Unprocessable Content
				echo <<<'JSON'
{
    "title": "Failed to create user",
    "errors": [
        {
            "detail": "Provided pasword does not satisfy minimum entropy",
            "pointer": "#/password"
        }
    ]
}
JSON;
				exit();
			} else {
				// @TODO: (?) Add the user-id specific code to StorageServer (see closed MR).
				$createdStorage = StorageServer::createStorage($createdUser['webId']/*, $userId*/);
				User::setStorage($createdUser['userId'], $createdStorage['storageUrl']);

				$clientId = $_POST['client_id'];

				$tokenGenerator = \Pdsinterop\PhpSolid\Server::getTokenGenerator();

				$accessTokenPayload = $tokenGenerator->generateAccessToken($clientId, $createdUser['webId']);
				$accessToken = $tokenGenerator->signToken($accessTokenPayload);

				$idTokenPayload = $tokenGenerator->generateIdToken($clientId, $createdUser['webId']);
				$idTokenPayload = $tokenGenerator->bindAccessToken($accessToken, $idTokenPayload);
				$idToken = $tokenGenerator->signToken($idTokenPayload);

				$refreshToken = $tokenGenerator->createRefreshToken(
					$clientId,
					$createdUser['userId'],
					['openid', 'webid', 'offline_access']
				);

				$responseData = [
					'access_token' => $accessToken,
					'email' => $email,
					'expires_in' => 3600,
					'id_token' => $idToken,
					'refresh_token' => $refreshToken,
					'storageUrl' => $createdStorage['storageUrl'],
					'token_type' => 'Bearer',
					'webId' => $createdUser['webId'],
				];

				http_response_code(201);
				header('Content-type: application/json');
				header('Location: ' . $createdUser['webId']);
				echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
				exit();
			}
		}
	}

	public static function respondToAccountNew()
	{
		$verifyToken = User::getVerifyToken($_POST['confirm']);
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
		if (User::userEmailExists($_POST['email'])) {
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

		$createdUser = User::createUser($newUser);
		if (!$createdUser) {
			error_log("Failed to create user");
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$createdStorage = StorageServer::createStorage($createdUser['webId']);
		User::setStorage($createdUser['userId'], $createdStorage['storageUrl']);

		Mailer::sendAccountCreated($createdUser);

		$responseData = array(
			"webId" => $createdUser['webId'],
			"storageUrl" => $createdStorage['storageUrl']
		);

		header("HTTP/1.1 201 Created");
		header("Content-type: application/json");
		Session::start($_POST['email']);
		echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

	public static function respondToAccountResetPassword()
	{
		if (!User::userEmailExists($_POST['email'])) {
			header("HTTP/1.1 200 OK"); // Return OK even when user is not found;
			header("Content-type: application/json");
			echo json_encode("OK");
			exit();
		}
		$verifyData = [
			'email' => $_POST['email']
		];

		$verifyToken = User::saveVerifyToken('passwordReset', $verifyData);
		Mailer::sendResetPassword($verifyToken);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public static function respondToAccountChangePassword()
	{
		$verifyToken = User::getVerifyToken($_POST['token']);
		if (!$verifyToken) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		$result = User::setUserPassword($verifyToken['email'], $_POST['newPassword']);
		if (!$result) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public static function respondToAccountDelete()
	{
		if (!User::userEmailExists($_POST['email'])) {
			header("HTTP/1.1 200 OK"); // Return OK even when user is not found;
			header("Content-type: application/json");
			echo json_encode("OK");
			exit();
		}
		$verifyData = [
			'email' => $_POST['email']
		];

		$verifyToken = User::saveVerifyToken('deleteAccount', $verifyData);
		Mailer::sendDeleteAccount($verifyToken);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public static function respondToAccountDeleteConfirm()
	{
		$verifyToken = User::getVerifyToken($_POST['token']);
		if (!$verifyToken) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		User::deleteAccount($verifyToken['email']);
		header("HTTP/1.1 200 OK");
		header("Content-type: application/json");
		echo json_encode("OK");
	}

	public static function respondToLogin()
	{
		$failureCount = IpAttempts::getAttemptsCount($_SERVER['REMOTE_ADDR'], "login");
		if ($failureCount > 5) {
			header("HTTP/1.1 400 Bad Request");
			exit();
		}
		if (User::checkPassword($_POST['username'], $_POST['password'])) {
			Session::start($_POST['username']);
			$user = User::getUser($_POST['username']);
			if (!isset($_POST['redirect_uri']) || $_POST['redirect_uri'] === '') {
				header("Location: /dashboard/#autologin/" . urlencode($user['webId']));
				exit();
			}
			header("Location: " . urldecode($_POST['redirect_uri'])); // FIXME: Do we need to harden this?
		} else {
			IpAttempts::logFailedAttempt($_SERVER['REMOTE_ADDR'], "login", time() + 3600);
			header("Location: /login/");
		}
	}
}
