<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\MailTemplateGenerator;

	class MailTemplates {
		public static function verify() {
			$mailTokens = array(
				"title" => "Confirm your e-mail",
				"description" => "Your online identity is almost ready. Enter to code to complete your registration",
				"footer" => "",
				"buttonText" => "{code}",
			);

			$mailSubject = "Confirm your e-mail: {code}";
			$mailHtmlBody = MailTemplateGenerator::mailTemplate($mailTokens);

			$mailPlainBody = implode("\n\n", array(
				$mailTokens['title'],
				$mailTokens['buttonText'],
				$mailTokens['description'],
				$mailTokens['footer']
			));

			foreach ($mailTokens as $token => $value) {
				$mailSubject = str_replace("{" . $token . "}", $mailTokens[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailPlainBody);
			}

			return array(
				"mailSubject" => $mailSubject,
				"mailHtmlBody" => $mailHtmlBody,
				"mailPlainBody" => $mailPlainBody
			);
		}
		
		public static function resetPassword() {
			$mailSubject = "Password reset";
			$mailTokens = array(
				"title" => "Password reset",
				"description" => "If the link does not work, copy and paste this in your browser: " . BASEURL . "/change-password/?token={code}",
				"footer" => "",
				"buttonText" => "Reset password",
				"buttonLink" => BASEURL . "/change-password/?token={code}"
			);

			$mailHtmlBody = MailTemplateGenerator::mailTemplate($mailTokens);
			
			$mailPlainBody = implode("\n\n", array(
				$mailTokens['title'],
				$mailTokens['buttonText'],
				$mailTokens['description'],
				$mailTokens['footer']
			));
			$mailPlainBody = $mailPlainBody;
			
			foreach ($mailTokens as $token => $value) {
				$mailSubject = str_replace("{" . $token . "}", $mailTokens[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailPlainBody);
			}

			return array(
				"mailSubject" => $mailSubject,
				"mailHtmlBody" => $mailHtmlBody,
				"mailPlainBody" => $mailPlainBody
			);
		}

		public static function deleteAccount() {
			$mailSubject = "Delete your account";
			$mailTokens = array(
				"title" => "Delete your account",
				"description" => "If the link does not work, copy and paste this in your browser: " . BASEURL . "/account/delete/confirm/?token={code}",
				"footer" => "",
				"buttonText" => "Delete account",
				"buttonLink" => BASEURL . "/account/delete/confirm/?token={code}"
			);

			$mailHtmlBody = MailTemplateGenerator::mailTemplate($mailTokens);
			
			$mailPlainBody = implode("\n\n", array(
				$mailTokens['title'],
				$mailTokens['buttonText'],
				$mailTokens['description'],
				$mailTokens['footer']
			));
			$mailPlainBody = $mailPlainBody;
			
			foreach ($mailTokens as $token => $value) {
				$mailSubject = str_replace("{" . $token . "}", $mailTokens[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailPlainBody);
			}

			return array(
				"mailSubject" => $mailSubject,
				"mailHtmlBody" => $mailHtmlBody,
				"mailPlainBody" => $mailPlainBody
			);
		}
		
		public static function accountCreated() {
			$mailTokens = array(
				"title" => "Welcome to Solid!",
				"description" => "Your online identity is ready to use. Your WebID is: {webId}",
				"footer" => "",
			);

			$mailSubject = "Welcome to Solid!";

			$mailHtmlBody = MailTemplateGenerator::mailTemplate($mailTokens);
			
			$mailPlainBody = implode("\n\n", array(
				$mailTokens['title'],
				// $mailTokens['buttonText'],
				$mailTokens['description'],
				$mailTokens['footer']
			));

			foreach ($mailTokens as $token => $value) {
				$mailSubject = str_replace("{" . $token . "}", $mailTokens[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $mailTokens[$token], $mailPlainBody);
			}

			return array(
				"mailSubject" => $mailSubject,
				"mailHtmlBody" => $mailHtmlBody,
				"mailPlainBody" => $mailPlainBody
			);
		}
	}
