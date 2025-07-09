<?php
	namespace Pdsinterop\PhpSolid;
	
	use Pdsinterop\PhpSolid\MailTemplates;
	use PHPMailer\PHPMailer\PHPMailer;

	class Mailer {
		public static $mailer = null;
		public static function getMailer() {
			if (self::$mailer) {
				return self::$mailer;
			}
			$mailer = new PHPMailer();
			// Settings
			$mailer->IsSMTP();
			$mailer->CharSet = 'UTF-8';
			$mailer->Host       = MAILER['host'];
			$mailer->SMTPDebug  = 0;
			$mailer->Port       = MAILER['port'];
			if (isset(MAILER['user'])) {
				$mailer->SMTPAuth   = true;
				$mailer->Username   = MAILER['user'];
				$mailer->Password   = MAILER['password'];
			}
			$mailer->isHTML(true);
			$mailer->setFrom(MAILER['from']);
			$mailer->XMailer = null; // don't add PHPmailer user agent information;
			return $mailer;
		}
		
		public static function sendAccountCreated($data) {
			$mailTemplate = MailTemplates::accountCreated();
			$mailSubject = $mailTemplate["mailSubject"];
			$mailHtmlBody = $mailTemplate["mailHtmlBody"];
			$mailPlainBody = $mailTemplate["mailPlainBody"];

			$mailTo = $data['email'];
			$mailTokens = array("webId", "email");

			foreach ($mailTokens as $token) {
				$mailSubject = str_replace("{" . $token . "}", $data[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $data[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $data[$token], $mailPlainBody);
			}
			
			//	$emailCheck = "#^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})" . '$#';

			$mailer = self::getMailer();
			// Content
			$mailer->addAddress($mailTo);
			
			$mailer->Subject = $mailSubject;
			$mailer->Body    = $mailHtmlBody;
			$mailer->AltBody = $mailPlainBody;

			$mailer->send();
		}

		public static function sendVerify($data) {
			$mailTemplate = MailTemplates::verify();

			$mailSubject = $mailTemplate["mailSubject"];
			$mailHtmlBody = $mailTemplate["mailHtmlBody"];
			$mailPlainBody = $mailTemplate["mailPlainBody"];

			$mailTo = $data['email'];
			$mailTokens = array("code", "email");

			foreach ($mailTokens as $token) {
				$mailSubject = str_replace("{" . $token . "}", $data[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $data[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $data[$token], $mailPlainBody);
			}

			//	$emailCheck = "#^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})" . '$#';

			$mailer = self::getMailer();
			// Content
			$mailer->addAddress($mailTo);
			
			$mailer->Subject = $mailSubject;
			$mailer->Body    = $mailHtmlBody;
			$mailer->AltBody = $mailPlainBody;

			$mailer->send();
		}

		public static function sendResetPassword($data) {
			$mailTemplate = MailTemplates::resetPassword();
			$mailSubject = $mailTemplate["mailSubject"];
			$mailHtmlBody = $mailTemplate["mailHtmlBody"];
			$mailPlainBody = $mailTemplate["mailPlainBody"];

			$mailTo = $data['email'];
			$mailTokens = array("code", "email");

			foreach ($mailTokens as $token) {
				$mailSubject = str_replace("{" . $token . "}", $data[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $data[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $data[$token], $mailPlainBody);
			}
			
			//	$emailCheck = "#^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})" . '$#';

			$mailer = self::getMailer();
			// Content
			$mailer->addAddress($mailTo);
			
			$mailer->Subject = $mailSubject;
			$mailer->Body    = $mailHtmlBody;
			$mailer->AltBody = $mailPlainBody;

			$mailer->send();
		}

		public static function sendDeleteAccount($data) {
			$mailTemplate = MailTemplates::deleteAccount();
			$mailSubject = $mailTemplate["mailSubject"];
			$mailHtmlBody = $mailTemplate["mailHtmlBody"];
			$mailPlainBody = $mailTemplate["mailPlainBody"];

			$mailTo = $data['email'];
			$mailTokens = array("code", "email");

			foreach ($mailTokens as $token) {
				$mailSubject = str_replace("{" . $token . "}", $data[$token], $mailSubject);
				$mailHtmlBody = str_replace("{" . $token . "}", $data[$token], $mailHtmlBody);
				$mailPlainBody = str_replace("{" . $token . "}", $data[$token], $mailPlainBody);
			}
			
			//	$emailCheck = "#^[a-z0-9_\+-]+(\.[a-z0-9_\+-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*\.([a-z]{2,4})" . '$#';

			$mailer = self::getMailer();
			// Content
			$mailer->addAddress($mailTo);
			
			$mailer->Subject = $mailSubject;
			$mailer->Body    = $mailHtmlBody;
			$mailer->AltBody = $mailPlainBody;

			$mailer->send();
		}
	}
