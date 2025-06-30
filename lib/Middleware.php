<?php
	namespace PdsInterop\PhpSolid;

	class Middleware {
		public static function cors() {
			$corsMethods="GET, PUT, POST, OPTIONS, DELETE, PATCH";
			$corsAllowedHeaders="*, allow, accept, authorization, content-type, dpop, slug, link";
			$corsMaxAge="1728000";
			$corsExposeHeaders="Authorization, User, Location, Link, Vary, Last-Modified, ETag, Accept-Patch, Accept-Post, Updates-Via, Allow, WAC-Allow, Content-Length, WWW-Authenticate, MS-Author-Via";
			$corsAllowCredentials="true";

			if (isset($_REQUEST['HTTP_ORIGIN'])) {
				$corsAllowOrigin = $_REQUEST['HTTP_ORIGIN'];
			} else {
				$corsAllowOrigin = '*';
			}

			header( 'Access-Control-Allow-Origin: ' . $corsAllowOrigin );
			header( 'Access-Control-Allow-Headers: ' . $corsAllowedHeaders );
			header( 'Access-Control-Allow-Methods: ' . $corsMethods);
			header( 'Access-Control-Allow-Headers: ' . $corsAllowedHeaders);
			header( 'Access-Control-Max-Age: ' . $corsMaxAge);
			header( 'Access-Control-Allow-Credentials: ' . $corsAllowCredentials);
			header( 'Access-Control-Expose-Headers: ' . $corsExposeHeaders);
			header( 'Accept-Patch: text/n3');
		}
	}
