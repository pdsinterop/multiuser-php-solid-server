<?php

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps

namespace Pdsinterop\PhpSolid;

class Util
{
	public function base64_url_encode($text)
	{
		return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
	}

	public function base64_url_decode($text)
	{
		return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $text));
	}

	public function getServerName()
	{
		// FIXME: Depending on the setup, SERVER_NAME might not be the domain of the server.
		return $_SERVER['SERVER_NAME'];
	}

	public function getServerUri()
	{
		$scheme = $_SERVER['REQUEST_SCHEME'];
		$domain = $_SERVER['SERVER_NAME'];
		$path = $_SERVER['REQUEST_URI'];
		return "{$scheme}://{$domain}{$path}";
	}

	public function getServerBaseUrl()
	{
		$scheme = $_SERVER['REQUEST_SCHEME'];
		$domain = $_SERVER['SERVER_NAME'];
		return "{$scheme}://{$domain}";
	}
}
