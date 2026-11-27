<?php

namespace Pdsinterop\PhpSolid;

class StorageServer extends Server
{
	public function getStorage($storageId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT * FROM storage WHERE storage_id=:storageId'
		);
		$query->execute([
			':storageId' => $storageId
		]);
		return $query->fetchAll();
	}

	public function setStorageOwner($storageId, $owner)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'UPDATE storage SET owner=:owner WHERE storage_id=:storageId'
		);
		$query->execute([
			':storageId' => $storageId,
			':owner' => $owner
		]);
	}

	public function createStorage($ownerWebId)
	{
		$generatedStorageId = bin2hex(random_bytes(16));
		while ($this->storageIdExists($generatedStorageId)) {
			$generatedStorageId = bin2hex(random_bytes(16));
		}
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'INSERT OR REPLACE INTO storage VALUES(:storageId, :owner)'
		);
		$query->execute([
			':storageId' => $generatedStorageId,
			':owner' => $ownerWebId
		]);
		return [
			"storageId" => $generatedStorageId,
			"storageUrl" => "https://storage-" . $generatedStorageId . "." . BASEDOMAIN . "/"
		];
	}

	public function getPodCount()
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT count(storage_id) as podCount FROM storage'
		);
		$query->execute();
		$result = $query->fetchAll();

		if (sizeof($result) === 1) {
			return $result[0]['podCount'];
		}
		return 0;
	}

	public function storageIdExists($storageId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT storage_id FROM storage WHERE storage_id=:storageId'
		);
		$query->execute([
			':storageId' => $storageId
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			return true;
		}
		return false;
	}

	public function getOwnerWebId()
	{
		$storageId = $this->getStorageId();
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT owner FROM storage WHERE storage_id=:storageId'
		);
		$query->execute([
			':storageId' => $storageId
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			return $result[0]['owner'];
		}
		return false;
	}

	public function getMimeDetector()
	{
		$extensionMap = new \League\MimeTypeDetection\OverridingExtensionToMimeTypeMap(
			new \League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap(),
			[
				'acl' => 'text/turtle'
			]
		);

		$mimeTypeDetector = new \League\MimeTypeDetection\FinfoMimeTypeDetector(
			'',
			$extensionMap
		);
		return $mimeTypeDetector;
	}

	public function getRdfAdapter()
	{
		$storageId = $this->getStorageId();
		if (! $this->storageIdExists($storageId)) {
			throw new \Exception("Storage does not exist");
		}
		if (is_dir(STORAGEBASE . "$storageId/")) { // backwards compatiblity check
			$storagePath = $storageId;
		} else {
			$storagePath = implode("/", str_split($storageId, 4));
		}

		// The internal adapter
		$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(
			// Determine root directory
			STORAGEBASE . "$storagePath/",
			null, // visibilityConverter
			LOCK_EX, // writeFlags
			0002, // disallow links
			$this->getMimeDetector()
		);

		$graph = new \EasyRdf\Graph();
		// Create Formats objects
		$formats = new \Pdsinterop\Rdf\Formats();
		$util = new Util();
		$serverUri = $util->getServerUri();

		// Create the RDF Adapter
		return new \Pdsinterop\Rdf\Flysystem\Adapter\Rdf($adapter, $graph, $formats, $serverUri);
	}

	public function getFileSystem($rdfAdapter)
	{
		$filesystem = new \League\Flysystem\Filesystem($rdfAdapter);
		return $filesystem;
	}

	public function respond($response)
	{
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

	public function getWebId($rawRequest)
	{
		$dpop = $this->getDpop();
		$webId = $dpop->getWebId($rawRequest);
		if (!isset($webId)) {
			$bearer = $this->getBearer();
			$webId = $bearer->getWebId($rawRequest);
		}
		return $webId;
	}

	private function getStorageId()
	{
		$util = new Util();
		$serverName = $util->getServerName();
		$idParts = explode(".", $serverName, 2);
		$storageId = preg_replace("/^storage-/", "", $idParts[0]);
		return $storageId;
	}

	public function initializeStorage()
	{
		$rdfAdapter = $this->getRdfAdapter();
		$filesystem = $this->getFilesystem($rdfAdapter);
		if (!$filesystem->fileExists("/.acl")) {
			$defaultAcl = $this->generateDefaultAcl();
			$filesystem->write("/.acl", $defaultAcl);
		}

		// Generate default folders and ACLs:
		if (!$filesystem->directoryExists("/inbox")) {
			$filesystem->createDirectory("/inbox");
		}
		if (!$filesystem->fileExists("/inbox/.acl")) {
			$inboxAcl = $this->generatePublicAppendAcl();
			$filesystem->write("/inbox/.acl", $inboxAcl);
		}
		if (!$filesystem->directoryExists("/settings")) {
			$filesystem->createDirectory("/settings");
		}
		if (!$filesystem->fileExists("/settings/privateTypeIndex.ttl")) {
			$privateTypeIndex = $this->generateDefaultPrivateTypeIndex();
			$filesystem->write("/settings/privateTypeIndex.ttl", $privateTypeIndex);
		}
		if (!$filesystem->fileExists("/settings/publicTypeIndex.ttl")) {
			$publicTypeIndex = $this->generateDefaultPublicTypeIndex();
			$filesystem->write("/settings/publicTypeIndex.ttl", $publicTypeIndex);
		}
		if (!$filesystem->fileExists("/settings/preferences.ttl")) {
			$preferences = $this->generateDefaultPreferences();
			$filesystem->write("/settings/preferences.ttl", $preferences);
		}
		if (!$filesystem->directoryExists("/public")) {
			$filesystem->createDirectory("/public");
		}
		if (!$filesystem->fileExists("/public/.acl")) {
			$publicAcl = $this->generatePublicReadAcl();
			$filesystem->write("/public/.acl", $publicAcl);
		}
		if (!$filesystem->directoryExists("/private")) {
			$filesystem->createDirectory("/private");
		}
	}

	public function generateDefaultAcl()
	{
		$webId = $this->getOwnerWebId();
		if (!$webId) {
			throw new \Exception("No owner found for storage");
		}
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

	public function generatePublicAppendAcl()
	{
		$webId = $this->getOwnerWebId();
		if (!$webId) {
			throw new \Exception("No owner found for storage ID");
		}
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

	public function generatePublicReadAcl()
	{
		$webId = $this->getOwnerWebId();
		if (!$webId) {
			throw new \Exception("No owner found for storage ID");
		}
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

	public function generateDefaultPrivateTypeIndex()
	{
		$typeIndex = <<< "EOF"
# Private type index
@prefix : <#>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
a solid:UnlistedDocument, solid:TypeIndex.
EOF;
		return $typeIndex;
	}

	public function generateDefaultPublicTypeIndex()
	{
		$typeIndex = <<< "EOF"
# Public type index
@prefix : <#>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
a solid:ListedDocument, solid:TypeIndex.
EOF;
		return $typeIndex;
	}

	public function generateDefaultPreferences()
	{
		$webId = $this->getOwnerWebId();
		$preferences = <<< "EOF"
# Preferences
@prefix : <#>.
@prefix sp: <http://www.w3.org/ns/pim/space#>.
@prefix dct: <http://purl.org/dc/terms/>.
@prefix solid: <http://www.w3.org/ns/solid/terms#>.

<>
a sp:ConfigurationFile;
dct:title "Preferences file".

<$webId>
a solid:Developer;
solid:privateTypeIndex <privateTypeIndex.ttl>;
solid:publicTypeIndex <publicTypeIndex.ttl>.
EOF;
		return $preferences;
	}
}
