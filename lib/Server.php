<?php
	namespace PdsInterop\PhpSolid;
	
	use Pdsinterop\Solid\Auth\Factory\AuthorizationServerFactory;
	use Laminas\Diactoros\Response;
	use Pdsinterop\Solid\Auth\Server as SolidAuthServer;
	use Pdsinterop\Solid\Auth\Factory\ConfigFactory;
	use Pdsinterop\Solid\Auth\Config\Client as ConfigClient;
	use Pdsinterop\Solid\Auth\Utils\ReplayDetector;
	use Pdsinterop\Solid\Auth\Utils\DPop;
	use Pdsinterop\Solid\Auth\Utils\JtiValidator;
	use Pdsinterop\Solid\Auth\TokenGenerator;
	use Pdsinterop\PhpSolid\ClientRegistration;

	class Server {
		public static function generateKeySet() {
			$config = array(
				"digest_alg" => "sha256",
				"private_key_bits" => 2048,
				"private_key_type" => OPENSSL_KEYTYPE_RSA,
			);
			// Create the private and public key
			$key = openssl_pkey_new($config);
			$publicKey     = openssl_pkey_get_details($key)['key'];

			// Extract the private key from $key to $privateKey
			openssl_pkey_export($key, $privateKey);
			$encryptionKey = base64_encode(random_bytes(32));
			$result = array(
				"privateKey" => $privateKey,
				"publicKey" => $publicKey,
				"encryptionKey" => $encryptionKey
			);

			return $result;
		}

		public static function getAuthServer() {
			$authServerConfig = self::getAuthServerConfig();
			$authServerFactory = new AuthorizationServerFactory($authServerConfig);
			$authServer = $authServerFactory->create();
			$response = new Response();
			$server = new SolidAuthServer($authServer, $authServerConfig, $response);
			return $server;
		}

		public static function getAuthServerConfig() {
			$keys = self::getKeys();
			$configClient = self::getConfigClient();
			$endpoints = self::getEndpoints();
			$authServerConfigFactory = new ConfigFactory(
				$configClient,
				$keys['encryptionKey'],
				$keys['privateKey'],
				$keys['publicKey'],
				$endpoints
			);
			$authServerConfig = $authServerConfigFactory->create();
			return $authServerConfig;
		}
		
		public static function getConfigClient() {
			$clientId = $_GET['client_id'] ?? $_POST['client_id'] ?? null;
			if ($clientId) { 
				$registeredClient = ClientRegistration::getRegistration($clientId);
			}
			if (isset($registeredClient)) {
				return new ConfigClient(
					$clientId,
					$registeredClient['client_secret'] ?? '',
					$registeredClient['redirect_uris'],
					$registeredClient['client_name']
				);
			} else {
				return new ConfigClient(
					'',
					'',
					array(),
					''
				);
			}
		}
		
		public static function getDpop() {
			$replayDetector = new ReplayDetector(
				function($jti, $targetUri) {
					// FIXME: check if the JTI exists in our store.
					// FIXME: store the JTI;
					return false;
				}
			);
			$jtiValidator = new JtiValidator($replayDetector);
			return new DPop($jtiValidator);
	    	}
		
		public static function getEndpoints() {
			return array(
				"issuer"                          => BASEURL,
				"jwks_uri"                        => BASEURL . "/jwks/",
				"check_session_iframe"            => BASEURL . "/session/",
				"end_session_endpoint"            => BASEURL . "/logout/",
				"authorization_endpoint"          => BASEURL . "/authorize/",
				"token_endpoint"                  => BASEURL . "/token/",
				"userinfo_endpoint"               => BASEURL . "/userinfo/",
				"registration_endpoint"           => BASEURL . "/register/"
			);
		}
		
		public static function getKeys() {
			$keys = array(
				'encryptionKey' => file_get_contents(KEYDIR . "encryption.key"),
				'privateKey' => file_get_contents(KEYDIR . "private.key"),
				'publicKey' => file_get_contents(KEYDIR . "public.key")
			);
			return $keys;
		}

		public static function getTokenGenerator() {
			$dpopValidFor = new \DateInterval('PT10M');
			return new TokenGenerator(
				self::getAuthServerConfig(),
				$dpopValidFor,
				self::getDpop()
			);
		}
		
		public static function respond($response) {
			$statusCode = $response->getStatusCode();
			$response->getBody()->rewind();
			$headers = $response->getHeaders();

			$body = json_decode($response->getBody()->getContents());
			header("HTTP/1.1 $statusCode");
			foreach ($headers as $header => $values) {
				foreach ($values as $value) {
					if ($header == "Location") {
						$value = preg_replace("|%26%2334%3B|", "%22", $value); // odoo weird encoding
					}
					header($header . ":" . $value);
				}
			}
			echo json_encode($body, JSON_PRETTY_PRINT);
		}
		
		public static function getUser() {
			return [
				"id" => "1234567", // FIXME: Get the ID of the currently logged in user
				"allowedClients" => ["93b01f0953b07394bbe59217b3876041", "ebc22eebeecbe0e703647c229e18ddac"]
			];
		}
	}
	