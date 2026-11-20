<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Server;
use Pdsinterop\PhpSolid\User;
use Pdsinterop\PhpSolid\Util;

class ProfileServer extends Server
{
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
		$profileId = $this->getProfileId();

		if (is_dir(PROFILEBASE . "$profileId/")) { // backwards compatiblity check
			$profilePath = $profileId;
		} else {
			$profilePath = implode("/", str_split($profileId, 4));
		}

		// The internal adapter
		$adapter = new \League\Flysystem\Local\LocalFilesystemAdapter(
			// Determine root directory
			PROFILEBASE . $profilePath,
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

	private function getProfileId()
	{
		$util = new Util();
		$serverName = $util->getServerName();
		$idParts = explode(".", $serverName, 2);
		$profileId = preg_replace("/^id-/", "", $idParts[0]);
		return $profileId;
	}

	public function getOwner()
	{
		$profileId = $this->getProfileId();
		$user = new User();

		return $user->getUserById($profileId);
	}

	public function getOwnerWebId()
	{
		$owner = $this->getOwner();
		return $owner['webId'];
	}

	public function initializeProfile()
	{
		$user = $this->getOwner();

		if ($user) {
			$rdfAdapter = $this->getRdfAdapter();
			$filesystem = $this->getFilesystem($rdfAdapter);
			if (!$filesystem->fileExists("/.acl")) {
				$defaultAcl = $this->generateDefaultAcl();
				$filesystem->write("/.acl", $defaultAcl);
			}

			// Generate default folders and ACLs:
			if (!$filesystem->fileExists("/profile.ttl")) {
				$profile = $this->generateDefaultProfile($user);
				$filesystem->write("/profile.ttl", $profile);
			}
		}
	}

	public function generateDefaultAcl()
	{
		$webId = $this->getOwnerWebId();
		$acl = <<< "EOF"
# Root ACL resource for the user account
@prefix acl: <http://www.w3.org/ns/auth/acl#>.
@prefix foaf: <http://xmlns.com/foaf/0.1/>.

# The homepage is readable by the public
<#public>
a acl:Authorization;
acl:agentClass foaf:Agent;
acl:accessTo <./>;
# All resources will inherit this authorization, by default
acl:default <./>;
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

	public function generateDefaultProfile($user)
	{
		if (!isset($user['storage']) || !$user['storage']) {
			$user['storage'] = "https://storage-" . $this->getProfileId() . "." . BASEDOMAIN . "/";
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
