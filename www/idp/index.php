<?php

ini_set("log_errors", 1);
ini_set('session.cookie_httponly', 1);
ini_set('expose_php', 'off');

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../vendor/autoload.php");

use Pdsinterop\PhpSolid\Middleware;
use Pdsinterop\PhpSolid\Routes\Account;
use Pdsinterop\PhpSolid\Routes\SolidIdp;

use Pdsinterop\PhpSolid\User;
use Pdsinterop\PhpSolid\IpAttempts;
use Pdsinterop\PhpSolid\JtiStore;

$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
$request = rtrim($request, '/');
$method = $_SERVER['REQUEST_METHOD'];

$account = new Account();
$middleware = new Middleware();
$solidIdp = new SolidIdp();

$middleware->cors();

switch ($method) {
	case "GET":
		switch ($request) {
			case "/jwks":
				$solidIdp->respondToJwks();
			break;
			case "/.well-known/openid-configuration":
				$solidIdp->respondToWellKnownOpenIdConfiguration();
			break;
			case "/authorize":
				$account->requireLoggedInUser();
				$solidIdp->respondToAuthorize();
			break;
			case "/dashboard":
				$account->requireLoggedInUser();
				$account->respondToDashboard();
			break;
			case "/logout":
				$account->respondToLogout();
			break;
			case "/login/password":
				header("Location: /dashboard/");
			break;
			case "":
			case "/login":
			case "/register":
			case "/reset-password":
			case "/change-password":
			case "/account/delete":
			case "/account/delete/confirm":
				include_once(FRONTENDDIR . "generated.html");
			break;
			case "/sharing":
				$account->requireLoggedInUser();
				include_once(FRONTENDDIR . "generated.html");
			break;
			case '/session':
			case '/userinfo':
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
				$account->respondToAccountVerify();
			break;
			case "/api/accounts/new":
				$account->respondToAccountNew();
			break;
			case "/api/accounts/reset-password":
				$account->respondToAccountResetPassword();
			break;
			case "/api/accounts/change-password":
				$account->respondToAccountChangePassword();
			break;
			case "/api/accounts/delete":
				$account->respondToAccountDelete();
			break;
			case "/api/accounts/delete/confirm":
				$account->respondToAccountDeleteConfirm();
			break;
			case "/login/password":
				$account->respondToLogin();
			break;
			case "/register":
				$solidIdp->respondToRegister();
			break;
			case "/api/sharing":
				$solidIdp->respondToSharing();
			break;
			case "/token":
				$solidIdp->respondToToken();
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
if (!file_exists(CLEANUP_FILE) || (filemtime(CLEANUP_FILE) < time())) {
	touch(CLEANUP_FILE, time() + 3600);
	$ipAttempts = new IpAttempts();
	$jtiStore = new JtiStore();
	$user = new User();

	$user::cleanupTokens();
	$ipAttempts::cleanupAttempts();
	$jtiStore::cleanupJti();
}
