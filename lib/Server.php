<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\Solid\Auth\Factory\AuthorizationServerFactory;
use Laminas\Diactoros\Response;
use Pdsinterop\Solid\Auth\Server as SolidAuthServer;
use Pdsinterop\Solid\Auth\Factory\ConfigFactory;
use Pdsinterop\Solid\Auth\Config\Client as ConfigClient;
use Pdsinterop\Solid\Auth\Utils\ReplayDetector;
use Pdsinterop\Solid\Auth\Utils\DPop;
use Pdsinterop\Solid\Auth\Utils\Bearer;
use Pdsinterop\Solid\Auth\Utils\JtiValidator;
use Pdsinterop\Solid\Auth\TokenGenerator;
use Pdsinterop\PhpSolid\ClientRegistration;
use Pdsinterop\PhpSolid\JtiStore;

class Server
{
	public function generateKeySet()
	{
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

	public function getAuthServer()
	{
		$authServerConfig = $this->getAuthServerConfig();
		$authServerFactory = new AuthorizationServerFactory($authServerConfig);
		$authServer = $authServerFactory->create();
		$response = new Response();
		$server = new SolidAuthServer($authServer, $authServerConfig, $response);
		return $server;
	}

	public function getAuthServerConfig()
	{
		$keys = $this->getKeys();
		$configClient = $this->getConfigClient();
		$endpoints = $this->getEndpoints();
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

	public function getConfigClient()
	{
		$clientId = $_GET['client_id'] ?? $_POST['client_id'] ?? null;
		if ($clientId) {
			$clientRegistration = new ClientRegistration();
			$registeredClient = $clientRegistration->getRegistration($clientId);
			if (isset($registeredClient) && isset($registeredClient['redirect_uris'])) {
				/*
					If the token_endpoint_auth_method is 'none',
					this means we are dealing with a public
					client that is not able to securely store
					the client secret.  In that case, we remove
					the client_secret so we treat the client as
					a public client
				*/
				if (
					isset($registeredClient['token_endpoint_auth_method']) &&
					($registeredClient['token_endpoint_auth_method'] === "none")
				) {
					unset($registeredClient['client_secret']);
				}
				return new ConfigClient(
					$clientId,
					$registeredClient['client_secret'] ?? '',
					$registeredClient['redirect_uris'],
					$registeredClient['client_name']
				);
			} else {
				throw new \Exception("Invalid client ID");
			}
		} else {
			return new ConfigClient(
				'',
				'',
				array(),
				''
			);
		}
	}

	public function getDpop()
	{
		$replayDetector = new ReplayDetector(
			function ($jti, $targetUri) {
				$jtiStore = new JtiStore();
				if ($jtiStore->hasJti($jti)) {
					return true;
				} else {
					$jtiStore->saveJti($jti);
					return false;
				}
				return false;
			}
		);
		$jtiValidator = new JtiValidator($replayDetector);
		return new DPop($jtiValidator);
	}

	public function getBearer()
	{
		$replayDetector = new ReplayDetector(
			function ($jti, $targetUri) {
				$jtiStore = new JtiStore();
				if ($jtiStore->hasJti($jti)) {
					return true;
				} else {
					$jtiStore->saveJti($jti);
					return false;
				}
				return false;
			}
		);
		$jtiValidator = new JtiValidator($replayDetector);
		return new Bearer($jtiValidator);
	}

	public function getEndpoints()
	{
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

	public function getKeys()
	{
		$keys = array(
			'encryptionKey' => file_get_contents(KEYDIR . "encryption.key"),
			'privateKey' => file_get_contents(KEYDIR . "private.key"),
			'publicKey' => file_get_contents(KEYDIR . "public.key")
		);
		return $keys;
	}

	public function getTokenGenerator()
	{
		$dpopValidFor = new \DateInterval('PT10M');
		return new TokenGenerator(
			$this->getAuthServerConfig(),
			$dpopValidFor,
			$this->getDpop()
		);
	}

	public function respond($response)
	{
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
		echo json_encode($body, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}

	public function respondPodPro($response, $body)
	{
		$statusCode = $response->getStatusCode();
		$response->getBody()->rewind();
		$headers = $response->getHeaders();

		header("HTTP/1.1 $statusCode");
		foreach ($headers as $header => $values) {
			foreach ($values as $value) {
				if ($header == "Location") {
					$value = preg_replace("|%26%2334%3B|", "%22", $value); // odoo weird encoding
				}
				header($header . ":" . $value);
			}
		}
		echo json_encode($body, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
	}
}
