<?php
	ini_set("log_errors", 1);
	ini_set('session.cookie_httponly', 1);
	ini_set('expose_php', 'off');

	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Server;
	use Pdsinterop\PhpSolid\ClientRegistration;
	use Pdsinterop\PhpSolid\User;
	use Pdsinterop\PhpSolid\Session;
	use Pdsinterop\PhpSolid\Mailer;
	use Pdsinterop\PhpSolid\IpAttempts;
	use Pdsinterop\PhpSolid\JtiStore;

	$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();

	switch($method) {
		case "GET":
			switch ($request) {
				case "/jwks":
				case "/jwks/":
					$authServer = Server::getAuthServer();
					$response = $authServer->respondToJwksMetadataRequest();
					Server::respond($response);
				break;
				case "/.well-known/openid-configuration":
					$authServer = Server::getAuthServer();
					$response = $authServer->respondToOpenIdMetadataRequest();
					Server::respond($response);
				break;
				case "/authorize":
				case "/authorize/":
					$user = User::getUser(Session::getLoggedInUser());
					if (!$user) {
						header("Location: /login/?redirect_uri=" . urlencode($_SERVER['REQUEST_URI']));
						exit();
					}

					$clientId = $_GET['client_id'];
					$getVars = $_GET;
					
					if (!isset($getVars['grant_type'])) {
					    $getVars['grant_type'] = 'implicit';
					}
					$getVars['scope'] = "openid" ;
					$getVars['response_type'] = "token";
					
					$requestedResponseTypes = explode(" ", ($_GET['response_type'] ?? ''));
					foreach ($requestedResponseTypes as $responseType) {
						if ($responseType == "code") {
							$getVars['response_type'] = "code";
						}
					}

					$keys = Server::getKeys();
					if (isset($_GET['request'])) {
						$jwtConfig = \Lcobucci\JWT\Configuration::forSymmetricSigner(
							new \Lcobucci\JWT\Signer\Rsa\Sha256(),
							\Lcobucci\JWT\Signer\Key\InMemory::plainText($keys['privateKey']
						));

						if (isset($_GET['nonce'])) {
							$_SESSION['nonce'] = $_GET['nonce'];
						} else if (isset($_GET['request'])) {
							$token = $jwtConfig->parser()->parse($_GET['request']);
							$_SESSION['nonce'] = $token->claims()->get('nonce');
						}

						if (!isset($getVars["redirect_uri"])) {
							if (isset($token)) {
								$getVars['redirect_uri'] = $token->claims()->get("redirect_uri");
							}
						}
					}

					$requestFactory = new \Laminas\Diactoros\ServerRequestFactory();
					$request = $requestFactory->fromGlobals($_SERVER, $getVars, $_POST, $_COOKIE, $_FILES);

					$authServer = Server::getAuthServer();
					
					$approval = false;
					// check clientId approval for the user
					if (in_array($clientId, ($user['allowedClients'] ?? []))) {
						$approval = true;
					} else {
						$clientRegistration = ClientRegistration::getRegistration($clientId);
						if (in_array($clientRegistration['origin'], TRUSTED_APPS)) {
							$approval = true;
						}
					}

					if (!$approval) {
						header('Location: ' . BASEURL . '/sharing/' . "?" . http_build_query(
							array(
								"returnUrl" => urlencode($_SERVER["REQUEST_URI"]),
								"client_id" => $clientId,
								"redirect_uri" => $getVars['redirect_uri']
							)
						));
						exit();
					}
					
					$webId = "https://id-" . $user['userId'] . "." . BASEDOMAIN . "/#me";
					$user = new \Pdsinterop\Solid\Auth\Entity\User();
					$user->setIdentifier($webId);

					$response = $authServer->respondToAuthorizationRequest($request, $user, $approval);
					    
					$tokenGenerator = Server::getTokenGenerator();

					$response = $tokenGenerator->addIdTokenToResponse(
						$response,
						$clientId,
						$webId,
						$_SESSION['nonce'] ?? '',
						Server::getKeys()["privateKey"]
					);

					Server::respond($response);
				break;
				case "/dashboard":
				case "/dashboard/":
					$user = User::getUser(Session::getLoggedInUser());
					if (!$user) {
						header("Location: /login/");
						exit();
					}
					echo "Logged in as " . $user['webId'];
				break;
				case "/logout":
				case "/logout/":
					$user = User::getUser(Session::getLoggedInUser());
					if ($user) {
						session_destroy();
					}
					header("Location: /login/");
					exit();
				break;
				case "/login/password":
				case "/login/password/":
					header("Location: /dashboard/");
				break;
				case "/":
				case "/login":
				case "/login/":
				case "/register":
				case "/register/":
				case "/reset-password":
				case "/reset-password/":
				case "/change-password":
				case "/change-password/":
				case "/account/delete":
				case "/account/delete/":
				case "/account/delete/confirm":
				case "/account/delete/confirm/":
					include_once(FRONTENDDIR . "generated.html");
				break;
				case "/sharing":
				case "/sharing/":
					$user = User::getUser(Session::getLoggedInUser());
					if (!$user) {
						header("Location: /login/");
						exit();
					}
					include_once(FRONTENDDIR . "generated.html");
				break;
				case '/session':
				case '/session/':
				case '/userinfo':
				case '/userinfo/':
					header("HTTP/1.1 501 Not implemented");
				break;
				default:
					header($_SERVER['SERVER_PROTOCOL'] . " 404 Not found");
				break;
			}
		break;
		case "POST":
			switch ($request) {
				case "/api/accounts/verify":
				case "/api/accounts/verify/":
					$verifyData = [
						'email' => $_POST['email']
					];

					$verifyToken = User::saveVerifyToken('verify', $verifyData);
					Mailer::sendVerify($verifyToken);

					$responseData = "OK";
					header("HTTP/1.1 201 Created");
					header("Content-type: application/json");
					echo json_encode($responseData, JSON_PRETTY_PRINT);
				break;
				case "/api/accounts/new":
				case "/api/accounts/new/":
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
				break;
				case "/api/accounts/reset-password":
				case "/api/accounts/reset-password/":
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
				break;
				case "/api/accounts/change-password":
				case "/api/accounts/change-password/":
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
				break;
				case "/api/accounts/delete":
				case "/api/accounts/delete/":
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
				break;
				case "/api/accounts/delete/confirm":
				case "/api/accounts/delete/confirm/":
					$verifyToken = User::getVerifyToken($_POST['token']);
					if (!$verifyToken) {
						header("HTTP/1.1 400 Bad Request");
						exit();
					}
					User::deleteAccount($verifyToken['email']);
					header("HTTP/1.1 200 OK");
					header("Content-type: application/json");
					echo json_encode("OK");
				break;
				case "/login/password":
				case "/login/password/":
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
				break;
				case "/register":
				case "/register/":
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
				break;
				case "/api/sharing":
				case "/api/sharing/":
					$user = User::getUser(Session::getLoggedInUser());
					if (!$user) {
						header("HTTP/1.1 400 Bad request");
					} else {
						$clientId = $_POST['client_id'];
						$userId = $user['userId'];
						if ($_POST['consent'] === 'true') {
							User::allowClientForUser($clientId, $userId);
						}
						$returnUrl = urldecode($_POST['returnUrl']);
						header("Location: $returnUrl");
					}
				break;
				case "/token":
				case "/token/":
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
				break;
				default:
					header($_SERVER['SERVER_PROTOCOL'] . " 404 Not found");
				break;
			}
			if (!file_exists(CLEANUP_FILE) || (filemtime(CLEANUP_FILE) < time())) {
				touch(CLEANUP_FILE, time() + 3600);
				User::cleanupTokens();
				IpAttempts::cleanupAttempts();
				JtiStore::cleanupJti();
			}
		break;
		case "OPTIONS":
		break;
		case "PUT":
		default:
			header($_SERVER['SERVER_PROTOCOL'] . " 405 Method not allowed");
		break;
	}
		