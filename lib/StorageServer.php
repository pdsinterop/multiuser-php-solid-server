<?php
	namespace Pdsinterop\PhpSolid;
	
	use Pdsinterop\PhpSolid\Server;
	use Pdsinterop\PhpSolid\User;

	class StorageServer extends Server {
		public static function getFileSystem() {
			$storageId = self::getStorageId();

			// The internal adapter
			$adapter = new \League\Flysystem\Adapter\Local(
			    // Determine root directory
			    STORAGEBASE . "$storageId/"
			);

			$graph = new \EasyRdf\Graph();
			// Create Formats objects
			$formats = new \Pdsinterop\Rdf\Formats();

			$scheme = $_SERVER['REQUEST_SCHEME'];
			$domain = $_SERVER['SERVER_NAME'];
			$path = $_SERVER['REQUEST_URI'];
			$serverUri = "{$scheme}://{$domain}{$path}"; // FIXME: doublecheck that this is the correct url;

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

		private static function getStorageId() {
			$serverName = $_SERVER['SERVER_NAME'];
			$idParts = explode(".", $serverName, 2);
			$storageId = preg_replace("/^storage-/", "", $idParts[0]);
			return $storageId;
		}
		
		public static function getOwner() {
			$storageId = self::getStorageId();
			return User::getUserById($storageId);
		}

		public static function getOwnerWebId() {
			$owner = self::getOwner();
			return $owner['webId'];
		}
		
		public static function initializeStorage() {
			$filesystem = self::getFilesystem();
			if (!$filesystem->has("/.acl")) {
				$defaultAcl = self::generateDefaultAcl();
				$filesystem->write("/.acl", $defaultAcl);
			}

			// Generate default folders and ACLs:
			if (!$filesystem->has("/inbox")) {
				$filesystem->createDir("/inbox");
			}
			if (!$filesystem->has("/inbox/.acl")) {
				$inboxAcl = self::generatePublicAppendAcl();
				$filesystem->write("/inbox/.acl", $inboxAcl);
			}
			if (!$filesystem->has("/settings")) {
				$filesystem->createDir("/settings");
			}
			if (!$filesystem->has("/settings/privateTypeIndex.ttl")) {
				$privateTypeIndex = self::generateDefaultPrivateTypeIndex();
				$filesystem->write("/settings/privateTypeIndex.ttl", $privateTypeIndex);
			}
			if (!$filesystem->has("/settings/publicTypeIndex.ttl")) {
				$publicTypeIndex = self::generateDefaultPublicTypeIndex();
				$filesystem->write("/settings/publicTypeIndex.ttl", $publicTypeIndex);
			}
			if (!$filesystem->has("/settings/preferences.ttl")) {
				$preferences = self::generateDefaultPreferences();
				$filesystem->write("/settings/preferences.ttl", $preferences);
			}
			if (!$filesystem->has("/public")) {
				$filesystem->createDir("/public");
			}
			if (!$filesystem->has("/public/.acl")) {
				$publicAcl = self::generatePublicReadAcl();
				$filesystem->write("/public/.acl", $publicAcl);
			}
			if (!$filesystem->has("/private")) {
				$filesystem->createDir("/private");
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

		public static function generatePublicAppendAcl() {
			$webId = self::getOwnerWebId();
			$acl = <<< "EOF"
# Inbox ACL resource for the user account
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.

<#public>
	a acl:Authorization;
	acl:agentClass foaf:Agent;
	acl:accessTo <./>;
	acl:default <./>;
	acl:mode
		acl:Append.

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

		public static function generatePublicReadAcl() {
			$webId = self::getOwnerWebId();
			$acl = <<< "EOF"
# Inbox ACL resource for the user account
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.

<#public>
	a acl:Authorization;
	acl:agentClass foaf:Agent;
	acl:accessTo <./>;
	acl:default <./>;
	acl:mode
		acl:Read.

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

		public static function generateDefaultPrivateTypeIndex() {
			$typeIndex = <<< "EOF"
# Private type index
@prefix : <#>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
	a solid:UnlistedDocument, solid:TypeIndex.
EOF;
			return $typeIndex;
		}

		public static function generateDefaultPublicTypeIndex() {
			$typeIndex = <<< "EOF"
# Public type index
@prefix : <#>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
	a solid:ListedDocument, solid:TypeIndex.
EOF;
			return $typeIndex;
		}

		public static function generateDefaultPreferences() {
			$webId = self::getOwnerWebId();
			$preferences = <<< "EOF"
# Preferences
@prefix : <#>.
@prefix sp: <http://www.w3.org/ns/pim/space#>.
@prefix dct: <http://purl.org/dc/terms/>.
@prefix profile: <$webId>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
	a sp:ConfigurationFile;
	dct:title "Preferences file".

profile:me
	a solid:Developer;
	solid:privateTypeIndex <privateTypeIndex.ttl>;
	solid:publicTypeIndex <publicTypeIndex.ttl>.
EOF;
			return $preferences;
		}
	}
		