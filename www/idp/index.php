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
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();

	switch($method) {
		case "GET":
			switch ($request) {
				case "/jwks":
				case "/jwks/":
					SolidIdp::respondToJwks();
				break;
				case "/.well-known/openid-configuration":
					SolidIdp::respondToWellKnownOpenIdConfiguration();
				break;
				case "/authorize":
				case "/authorize/":
					Account::requireLoggedInUser();
					SolidIdp::respondToAuthorize();
				break;
				case "/dashboard":
				case "/dashboard/":
					Account::requireLoggedInUser();
					Account::respondToDashboard();
				break;
				case "/logout":
				case "/logout/":
					Account::respondToLogout();
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
					Account::requireLoggedInUser();
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
					Account::respondToAccountVerify();
				break;
				case "/api/accounts/new":
				case "/api/accounts/new/":
					Account::respondToAccountNew();
				break;
				case "/api/accounts/reset-password":
				case "/api/accounts/reset-password/":
					Account::respondToAccountResetPassword();
				break;
				case "/api/accounts/change-password":
				case "/api/accounts/change-password/":
					Account::respondToAccountChangePassword();
				break;
				case "/api/accounts/delete":
				case "/api/accounts/delete/":
					Account::respondToAccountDelete();
				break;
				case "/api/accounts/delete/confirm":
				case "/api/accounts/delete/confirm/":
					Account::respondToAccountDeleteConfirm();
				break;
				case "/login/password":
				case "/login/password/":
					Account::respondToLogin();
				break;
				case "/register":
				case "/register/":
					SolidIdp::respondToRegister();
				break;
				case "/api/sharing":
				case "/api/sharing/":
					SolidIdp::respondToSharing();
				break;
				case "/token":
				case "/token/":
					SolidIdp::respondToToken();
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
		User::cleanupTokens();
		IpAttempts::cleanupAttempts();
		JtiStore::cleanupJti();
	}
		