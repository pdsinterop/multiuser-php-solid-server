<?php
	namespace Pdsinterop\PhpSolid\Routes;

        use Pdsinterop\PhpSolid\Server;
        use Pdsinterop\PhpSolid\ClientRegistration;
        use Pdsinterop\PhpSolid\User;
        use Pdsinterop\PhpSolid\Session;
        use Pdsinterop\PhpSolid\Mailer;
        use Pdsinterop\PhpSolid\IpAttempts;
	
	class Account {
		public static function requireLoggedInUser() {
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

		public static function respondToDashboard() {
			$user = User::getUser(Session::getLoggedInUser());
			echo "Logged in as " . $user['webId'];
		}

		public static function respondToLogout() {
			$user = User::getUser(Session::getLoggedInUser());
			if ($user) {
				session_destroy();
			}
			header("Location: /login/");
			exit();
		}

		public static function respondToAccountVerify() {
			$verifyData = [
				'email' => $_POST['email']
			];

			$verifyToken = User::saveVerifyToken('verify', $verifyData);
			Mailer::sendVerify($verifyToken);

			$responseData = "OK";
			header("HTTP/1.1 201 Created");
			header("Content-type: application/json");
			echo json_encode($responseData, JSON_PRETTY_PRINT);
		}

		public static function respondToAccountNew() {
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
			Mailer::sendAccountCreated($createdUser);

			$responseData = array(
				"webId" => $createdUser['webId']
			);
			header("HTTP/1.1 201 Created");
			header("Content-type: application/json");
			Session::start($_POST['email']);
			echo json_encode($responseData, JSON_PRETTY_PRINT);
		}
		
		public static function respondToAccountResetPassword() {
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
		
		public static function respondToAccountChangePassword() {
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
		
		public static function respondToAccountDelete() {
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
		
		public static function respondToAccountDeleteConfirm() {
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
		
		public static function respondToLogin() {
			$failureCount = IpAttempts::getAttemptsCount($_SERVER['REMOTE_ADDR'], "login");
			if ($failureCount > 5) {
				header("HTTP/1.1 400 Bad Request");
				exit();
			}
			if (User::checkPassword($_POST['username'], $_POST['password'])) {
				Session::start($_POST['username']);
				if (!isset($_POST['redirect_uri']) || $_POST['redirect_uri'] === '') {
					header("Location: /dashboard/");
					exit();
				}
				header("Location: " . urldecode($_POST['redirect_uri'])); // FIXME: Do we need to harden this?
			} else {
				IpAttempts::logFailedAttempt($_SERVER['REMOTE_ADDR'], "login", time() + 3600);
				header("Location: /login/");
			}
		}
		
		public static function respondToRegister() {
			$postData = file_get_contents("php://input");
			$clientData = json_decode($postData, true);
			if (!isset($clientData)) {
				header("HTTP/1.1 400 Bad request");
				return;
			}
			$parsedOrigin = parse_url($clientData['redirect_uris'][0]);
			$origin = $parsedOrigin['scheme'] . '://' . $parsedOrigin['host'];
			if (isset($parsedOrigin['port'])) {
				$origin .= ":" . $parsedOrigin['port'];
			}


			$generatedClientId = md5(random_bytes(32));
			$generatedClientSecret = md5(random_bytes(32));

			$clientData['client_id_issued_at'] = time();
			$clientData['client_id'] = $generatedClientId;
			$clientData['client_secret'] = $generatedClientSecret;
			$clientData['origin'] = $origin;
			ClientRegistration::saveClientRegistration($clientData);
			
			$client = ClientRegistration::getRegistration($generatedClientId);

			$responseData = array(
				'redirect_uris' => $client['redirect_uris'],
				'client_id' => $client['client_id'],
				'client_secret' => $client['client_secret'],
				'response_types' => array('code'),
				'grant_types' => array('authorization_code', 'refresh_token'),
				'application_type' => $client['application_type'] ?? 'web',
				'client_name' => $client['client_name'] ?? $client['client_id'],
				'id_token_signed_response_alg' => 'RS256',
				'token_endpoint_auth_method' => 'client_secret_basic',
				'client_id_issued_at' => $client['client_id_issued_at'],
				'client_secret_expires_at' => 0
			);
			header("HTTP/1.1 201 Created");
			header("Content-type: application/json");
			echo json_encode($responseData, JSON_PRETTY_PRINT);
		}
		
		public static function respondToSharing() {
			$clientId = $_POST['client_id'];
			$userId = $user['userId'];
			if ($_POST['consent'] === 'true') {
				User::allowClientForUser($clientId, $userId);
			}
			$returnUrl = urldecode($_POST['returnUrl']);
			header("Location: $returnUrl");
		}
		
		public static function respondToToken() {
			$authServer = Server::getAuthServer();
			$tokenGenerator = Server::getTokenGenerator();

			$requestFactory = new \Laminas\Diactoros\ServerRequestFactory();
			$request = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
			$requestBody = $request->getParsedBody();

			$grantType = isset($requestBody['grant_type']) ? $requestBody['grant_type'] : null;
			$clientId = isset($requestBody['client_id']) ? $requestBody['client_id'] : null;
			switch ($grantType) {
				case "authorization_code":
					$code = $requestBody['code'];
					$codeInfo = $tokenGenerator->getCodeInfo($code);
					$userId = $codeInfo['user_id'];
					if (!$clientId) {
						$clientId = $codeInfo['client_id'];
					}
				break;
				case "refresh_token":
					$refreshToken = $requestBody['refresh_token'];
					$tokenInfo = $tokenGenerator->getCodeInfo($refreshToken); // FIXME: getCodeInfo should be named 'decrypt' or 'getInfo'?
					$userId = $tokenInfo['user_id'];
					if (!$clientId) {
						$clientId = $tokenInfo['client_id'];
					}
				break;
				default:
					$userId = false;
				break;
			}
			
			$httpDpop = $request->getServerParams()['HTTP_DPOP'];

			$response = $authServer->respondToAccessTokenRequest($request);

			if (isset($userId)) {
				$response = $tokenGenerator->addIdTokenToResponse(
					$response,
					$clientId,
					$userId,
					($_SESSION['nonce'] ?? ''),
					Server::getKeys()['privateKey'],
					$httpDpop
				);
			}

			Server::respond($response);
		}
	}
