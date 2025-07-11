<?php
	namespace Pdsinterop\PhpSolid\Api;

	use Pdsinterop\PhpSolid\User;

	class SolidUserProfile {
		public static function respondToProfile() {
			$serverName = $_SERVER['SERVER_NAME'];
			[$idPart, $rest] = explode(".", $serverName, 2);
			$userId = preg_replace("/^id-/", "", $idPart);

			$user = User::getUserById($userId);
			if (!isset($user['storage']) || !$user['storage']) {
				$user['storage'] = "https://storage-" . $userId . "." . BASEDOMAIN . "/";
			}
			if (is_array($user['storage'])) { // empty array is already handled
				$user['storage'] = array_values($user['storage'])[0]; // FIXME: Handle multiple storage pods
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
		}
	}
			