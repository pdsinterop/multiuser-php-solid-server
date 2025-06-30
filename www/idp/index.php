<?php
	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Server;
	use Pdsinterop\PhpSolid\ClientRegistration;
	use Pdsinterop\PhpSolid\User;
	use Pdsinterop\PhpSolid\Mailer;

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
					header("Content-type: application/json");
					$authServer = Server::getAuthServer();
					$response = $authServer->respondToOpenIdMetadataRequest();
					Server::respond($response);
				break;
				case "/authorize":
				case "/authorize/":
					$user = User::getLoggedInUser();
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

						$token = $jwtConfig->parser()->parse($_GET['request']);
						$_SESSION['nonce'] = $_GET['nonce'] ?? $token->claims()->get('nonce');
						
						if (!isset($getVars["redirect_uri"])) {
							$getVars['redirect_uri'] = $token->claims()->get("redirect_uri");
						}
					}

					$requestFactory = new \Laminas\Diactoros\ServerRequestFactory();
					$request = $requestFactory->fromGlobals($_SERVER, $getVars, $_POST, $_COOKIE, $_FILES);

					$authServer = Server::getAuthServer();
					
					// check clientId approval for the user
					if (!in_array($clientId, ($user['allowedClients'] ?? []))) {
						header('Location: ' . BASEURL . '/sharing/' . "?" . http_build_query(
							array(
								"returnUrl" => urlencode($_SERVER["REQUEST_URI"]),
								"client_id" => $clientId,
								"redirect_uri" => $getVars['redirect_uri']
							)
						));
						exit();
					} else {
						$approval = true;
					}

					$webId = "https://id-" . $user['userId'] . "." . BASEURL . "/#me";
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
				case "/storage/profile":
				case "/storage/profile/":
					$profile = <<<EOF
@prefix : <#>.
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.
@prefix ldp: <http://www.w3.org/ns/ldp#>.
@prefix schema: <http://schema.org/>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.
@prefix space: <http://www.w3.org/ns/pim/space#>.
@prefix vcard: <http://www.w3.org/2006/vcard/ns#>.
@prefix pro: <./>.
@prefix inbox: </inbox/>.
@prefix yle: </storage/>.

<> a foaf:PersonalProfileDocument; foaf:maker :me; foaf:primaryTopic :me.

:me
    a schema:Person, foaf:Person;
    acl:trustedApp
            [
                acl:mode acl:Append, acl:Read, acl:Write;
                acl:origin <http://podpro.dev:443>
            ];
    ldp:inbox inbox:;
    space:preferencesFile </storage/settings/prefs.ttl>;
    space:storage yle:;
    solid:account yle:;
    solid:oidcIssuer <https://solid.local>;
    solid:privateTypeIndex </storage/settings/privateTypeIndex.ttl>;
    solid:publicTypeIndex </storage/settings/publicTypeIndex.ttl>;
    foaf:name "Yvo Brevoort".
EOF;
					header('Content-Type: text/turtle');
					echo $profile;
				break;
				case "/dashboard":
				case "/dashboard/":
					$user = User::getLoggedInUser();
					if (!$user) {
						header("Location: /login/");
						exit();
					}
					echo "Logged in as " . $user['webId'];
				break;
				case "/logout":
				case "/logout/":
					$user = User::getLoggedInUser();
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
					include_once(FRONTENDDIR . "generated.html");
				break;
				case "/sharing":
				case "/sharing/":
					$user = User::getLoggedInUser();
					if (!$user) {
						header("Location: /login/");
						exit();
					}
					include_once(FRONTENDDIR . "generated.html");
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
					$email = $_POST['email'];
					$verifyData = [
						'email' => $email
					];

					$digits = 6;
					$code = rand(0,1000000);
					$code = str_pad($code, $digits, '0', STR_PAD_LEFT);

					$verifyData['code'] = $code;
					$expires = new \DateTime();
					$expires->add(new \DateInterval('PT30M')); // expire after 30 minutes
					$verifyData['expires'] = $expires->getTimestamp();

					User::saveVerifyToken($verifyData);
					$verifyToken = User::getVerifyToken($code);
					
					Mailer::sendVerify($verifyToken);

					$responseData = "OK";
					header("HTTP/1.1 201 Created");
					header("Content-type: application/json");
					echo json_encode($responseData, JSON_PRETTY_PRINT);
				break;
				case "/api/accounts/new":
				case "/api/accounts/new/":
					if (User::userEmailExists($_POST['email'])) {
						header("HTTP/1.1 400 Bad Request");
						exit();
					}
					$verifyToken = User::getVerifyToken($_POST['confirm']);
					if (!$verifyToken) {
						header("HTTP/1.1 400 Bad Request");
						exit();
					}

					if (!$_POST['password'] === $_POST['repeat_password']) {
						header("HTTP/1.1 400 Bad Request");
						exit();
					}

					$newUser = [
						"email" => $_POST['email'],
						"password" => $_POST['password']
					];

					$createdUser = User::createUser($newUser);
					Mailer::sendAccountCreated($createdUser);

					$responseData = array(
						"webId" => $createdUser['webId']
					);
					header("HTTP/1.1 201 Created");
					header("Content-type: application/json");
					echo json_encode($responseData, JSON_PRETTY_PRINT);
				break;
				case "/login/password":
				case "/login/password/":
					if (User::checkPassword($_POST['username'], $_POST['password'])) {
						if (!isset($_POST['redirect_uri']) || $_POST['redirect_uri'] === '') {
							header("Location: /dashboard/");
							exit();
						}
						header("Location: " . urldecode($_POST['redirect_uri'])); // FIXME: Do we need to harden this?
					} else {
						header("Location: /login/");
					}
				break;
				case "/register":
				case "/register/":
					$postData = file_get_contents("php://input");
					$clientData = json_decode($postData, true);
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
						'application_type' => $client['application_type'],
						'client_name' => $client['client_name'],
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
					$user = User::getLoggedInUser();
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
					// FIXME: force user to be logged in
					// FIXME: save the allowed clients in the logged in user;
					
				break;
				case "/token":
				case "/token/":
					$authServer = Server::getAuthServer();
					$tokenGenerator = Server::getTokenGenerator();

					$requestFactory = new \Laminas\Diactoros\ServerRequestFactory();
					$request = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
					
					$code     = $request->getParsedBody()['code'];
					$clientId = $request->getParsedBody()['client_id'];
					$httpDpop = $request->getServerParams()['HTTP_DPOP'];

					$response = $authServer->respondToAccessTokenRequest($request);

					// FIXME: handle refresh token;
					if (isset($code)) {
						$codeInfo = $tokenGenerator->getCodeInfo($code);
						$response = $tokenGenerator->addIdTokenToResponse(
							$response,
							$clientId,
							$codeInfo['user_id'],
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
		break;
		case "OPTIONS":
		break;
		case "PUT":
		default:
			header($_SERVER['SERVER_PROTOCOL'] . " 405 Method not allowed");
		break;
	}
		