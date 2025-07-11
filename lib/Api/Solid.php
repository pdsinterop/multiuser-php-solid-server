<?php
	namespace Pdsinterop\PhpSolid\Api;

        use Pdsinterop\PhpSolid\Server;
        use Pdsinterop\PhpSolid\ClientRegistration;
        use Pdsinterop\PhpSolid\User;
        use Pdsinterop\PhpSolid\Session;
	
	class Solid {
		public static function respondToJwks() {
			$authServer = Server::getAuthServer();
			$response = $authServer->respondToJwksMetadataRequest();
			Server::respond($response);
		}

		public static function respondToWellKnownOpenIdConfiguration() {
			$authServer = Server::getAuthServer();
			$response = $authServer->respondToOpenIdMetadataRequest();
			Server::respond($response);
		}

		public static function respondToAuthorize() {
			$user = User::getUser(Session::getLoggedInUser());

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
			$user = User::getUser(Session::getLoggedInUser());
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
