<?php

namespace Pdsinterop\PhpSolid;

// @FIXME: THIS DOES CLASS ABSOLUTELY NOT BELONG HERE! 20260908 @Potherca
// 		   I JUST DO NOT HAVE TIME TO ADD IT IN pdsinterop/solid-auth

class TokenGenerator extends \Pdsinterop\Solid\Auth\TokenGenerator
{
	public function createRefreshToken(string $clientId, string $userId, array $scopes): string
	{
		$payload = [
			'client_id' => $clientId,
			'expire_time' => time() + 30 * 24 * 3600,
			'refresh_token_id' => bin2hex(random_bytes(16)),
			'scopes' => $scopes,
			'user_id' => $userId,
		];

		return $this->encrypt(json_encode($payload, JSON_THROW_ON_ERROR));
	}
}
