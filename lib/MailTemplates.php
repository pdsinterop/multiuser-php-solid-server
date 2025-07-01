<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\MailTemplateGenerator;

	class MailTemplates {
		public static function verify() {
			$mailTokens = array(
				"title" => "Bevestig je e-mail",
				"description" => "Je online identiteit is bijna klaar. Vul de code in om je registratie te voltooien.",
				"footer" => "&copy; Muze 2025",
				"buttonText" => "{code}",
			);

			$mailSubject = "Bevestig je e-mail: {code}";
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
			$mailSubject = "Wachtwoordherstel";
			$mailTokens = array(
				"title" => "Wachtwoordherstel",
				"description" => "Werkt de link niet? Knip en plak dan deze in je browser: " . BASEURL . "/change-password/?token={code}",
				"footer" => "&copy; Muze 2025",
				"buttonText" => "Herstel wachtwoord",
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
			$mailSubject = "Je account verwijderen";
			$mailTokens = array(
				"title" => "Je account verwijderen",
				"description" => "Werkt de link niet? Knip en plak dan deze in je browser: " . BASEURL . "/account/delete/confirm/?token={code}",
				"footer" => "&copy; Muze 2025",
				"buttonText" => "Verwijder account",
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
				"title" => "Welkom bij Solid!",
				"description" => "Je online identiteit is klaar om te gebruiken. Je WebID is: {webId}",
				"footer" => "&copy; Muze 2025",
			);

			$mailSubject = "Welkom bij Solid!";

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
