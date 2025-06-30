<?php
	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Server;
	use Pdsinterop\PhpSolid\User;

	$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();

	switch($method) {
		case "GET":
			switch ($request) {
				case "/":
					$serverName = $_SERVER['SERVER_NAME'];
					[$idPart, $rest] = explode(".", $serverName, 2);
					$userId = preg_replace("/^id-/", "", $idPart);

					$user = User::getUserById($userId);
					if (!isset($user['storage'])) {
						$user['storage'] = "https://storage-" . $userId . "." . BASEDOMAIN . "/";
					}
					if (!isset($user['issuer'])) {
						$user['issuer'] = BASEURL;
					}

					$profile = <<<"EOF"
@prefix : <#>.
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.
@prefix ldp: <http://www.w3.org/ns/ldp#>.
@prefix schema: <http://schema.org/>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.
@prefix space: <http://www.w3.org/ns/pim/space#>.
@prefix vcard: <http://www.w3.org/2006/vcard/ns#>.
@prefix pro: <./>.
@prefix inbox: <{$user['storage']}inbox/>.

<> a foaf:PersonalProfileDocument; foaf:maker :me; foaf:primaryTopic :me.

:me
    a schema:Person, foaf:Person;
    ldp:inbox inbox:;
    space:preferencesFile <{$user['storage']}settings/prefs.ttl>;
    space:storage <{$user['storage']}>;
    solid:account <{$user['storage']}>;
    solid:oidcIssuer <{$user['issuer']}>;
    solid:privateTypeIndex <{$user['storage']}settings/privateTypeIndex.ttl>;
    solid:publicTypeIndex <{$user['storage']}settings/publicTypeIndex.ttl>.
EOF;
					header('Content-Type: text/turtle');
					echo $profile;
				break;
			}
		break;
		case "OPTIONS":
		break;
		case "POST":
		case "PUT":
		default:
			header($_SERVER['SERVER_PROTOCOL'] . " 405 Method not allowed");
		break;
	}
		