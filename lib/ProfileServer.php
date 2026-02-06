<?php
	namespace Pdsinterop\PhpSolid;
	
	use Pdsinterop\PhpSolid\Server;
	use Pdsinterop\PhpSolid\User;
	use Pdsinterop\PhpSolid\Util;

	class ProfileServer extends Server {
		public static function getFileSystem() {
			$profileId = self::getProfileId();

			// The internal adapter
			$adapter = new \League\Flysystem\Adapter\Local(
				// Determine root directory
				PROFILEBASE . "$profileId/"
			);

			$graph = new \EasyRdf\Graph();
			// Create Formats objects
			$formats = new \Pdsinterop\Rdf\Formats();
			$serverUri = Util::getServerUri();

			// Create the RDF Adapter
			$rdfAdapter = new \Pdsinterop\Rdf\Flysystem\Adapter\Rdf($adapter, $graph, $formats, $serverUri);

			$filesystem = new \League\Flysystem\Filesystem($rdfAdapter);
			$filesystem->addPlugin(new \Pdsinterop\Rdf\Flysystem\Plugin\AsMime($formats));
			$plugin = new \Pdsinterop\Rdf\Flysystem\Plugin\ReadRdf($graph);
			$filesystem->addPlugin($plugin);
			return $filesystem;
		}

		public static function respond($response) {
			$statusCode = $response->getStatusCode();
			$response->getBody()->rewind();
			$headers = $response->getHeaders();

			$body = $response->getBody()->getContents();
			header("HTTP/1.1 $statusCode");
			foreach ($headers as $header => $values) {
				foreach ($values as $value) {
					if ($header == "Location") {
						$value = preg_replace("|%26%2334%3B|", "%22", $value); // odoo weird encoding
					}
					header($header . ":" . $value);
				}
			}
			echo $body;
		}

		public static function getWebId($rawRequest) {
			$dpop = self::getDpop();
			$webId = $dpop->getWebId($rawRequest);
			if (!isset($webId)) {
				$bearer = self::getBearer();
				$webId = $bearer->getWebId($rawRequest);
			}
			return $webId;
		}

		private static function getProfileId() {
			$serverName = Util::getServerName();
			$idParts = explode(".", $serverName, 2);
			$profileId = preg_replace("/^id-/", "", $idParts[0]);
			return $profileId;
		}
		
		public static function getOwner() {
			$profileId = self::getProfileId();
			return User::getUserById($profileId);
		}

		public static function getOwnerWebId() {
			$owner = self::getOwner();
			return $owner['webId'];
		}
		
		public static function initializeProfile() {
			$filesystem = self::getFilesystem();
			if (!$filesystem->has("/.acl")) {
				$defaultAcl = self::generateDefaultAcl();
				$filesystem->write("/.acl", $defaultAcl);
			}

			// Generate default folders and ACLs:
			if (!$filesystem->has("/profile.ttl")) {
				$profile = self::generateDefaultProfile();
				$filesystem->write("/profile.ttl", $profile);
			}
		}
		
		public static function generateDefaultAcl() {
			$webId = self::getOwnerWebId();
			$acl = <<< "EOF"
# Root ACL resource for the user account
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.

# The homepage is readable by the public
<#public>
    a acl:Authorization;
    acl:agentClass foaf:Agent;
    acl:accessTo <./>;
	acl:mode acl:Read.

# The owner has full access to every resource in their pod.
# Other agents have no access rights,
# unless specifically authorized in other .acl resources.
<#owner>
	a acl:Authorization;
	acl:agent <$webId>;
	# Set the access to the root storage folder itself
	acl:accessTo <./>;
	# All resources will inherit this authorization, by default
	acl:default <./>;
	# The owner has all of the access modes allowed
	acl:mode
	    acl:Read, acl:Write, acl:Control.
EOF;
			return $acl;
		}

		public static function generateDefaultProfile() {
			$user = self::getOwner();
			if (!isset($user['storage']) || !$user['storage']) {
				$user['storage'] = "https://storage-" . $userId . "." . BASEDOMAIN . "/";
			}
			if (is_array($user['storage'])) { // empty array is already handled
				$user['storage'] = array_values($user['storage'])[0]; // FIXME: Handle multiple storage pods
			}
			if (!isset($user['issuer'])) {
				$user['issuer'] = BASEURL;
			}

			$profile = <<< "EOF"
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
    space:preferencesFile <{$user['storage']}settings/preferences.ttl>;
    space:storage <{$user['storage']}>;
    solid:account <{$user['storage']}>;
    solid:oidcIssuer <{$user['issuer']}>;
    solid:privateTypeIndex <{$user['storage']}settings/privateTypeIndex.ttl>;
    solid:publicTypeIndex <{$user['storage']}settings/publicTypeIndex.ttl>.
EOF;
			return $profile;
		}
	}
