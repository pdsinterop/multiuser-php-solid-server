<?php

// phpcs:disable Generic.Files.LineLength.TooLong
// phpcs:disable PSR1.Classes.ClassDeclaration.MultipleClasses

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Mailer;

const MAILER = [
	"host" => "mailerHost",
	"user" => "mailerUser",
	"password" => "mailerPass",
	"port" => "1337",
	"from" => "alice@example.com"
];

const MAILSTYLES = [];

defined('BASEURL') || define('BASEURL', "https://example.com");

class MailerMock
{
	public $Subject;
	public $Body;
	public $AltBody;
	public $addresses = [];
	public $ErrorInfo = '';

	public function addAddress($address)
	{
		$this->addresses[] = $address;
	}

	public function isError()
	{
		return $this->ErrorInfo !== '';
	}

	public function send()
	{
		return true;
	}
}

class MailerTest extends \PHPUnit\Framework\TestCase
{
	public function testGetMailer()
	{
		$mailerObject = new Mailer();
		$mailer = $mailerObject->getMailer();
		$this->assertInstanceOf('\PHPMailer\PHPMailer\PHPMailer', $mailer);
		$this->assertEquals($mailer->Host, MAILER['host']);
		$this->assertEquals($mailer->From, MAILER['from']);
		$this->assertEquals($mailer->Port, MAILER['port']);
		$this->assertEquals($mailer->Username, MAILER['user']);
		$this->assertEquals($mailer->Password, MAILER['password']);
		$this->assertEquals($mailer->SMTPAuth, true);
		$this->assertEquals($mailer->SMTPDebug, 0);
		$this->assertEquals($mailer->XMailer, null);
		$this->assertEquals($mailer->ContentType, "text/html");
		$this->assertEquals($mailer->Mailer, "smtp");
	}

	public function testAccountCreated()
	{
		$mailer = new Mailer();
		$mailer->mailer = new MailerMock();
		$mailer->sendAccountCreated([
			'email' => 'alice@example.com',
			'webId' => 'aliceWebId'
		]);
		$this->assertContains("alice@example.com", $mailer->mailer->addresses);
		$this->assertMatchesRegularExpression("/aliceWebId/", $mailer->mailer->AltBody);
		$this->assertMatchesRegularExpression("/aliceWebId/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Header -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Footer -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Call to action -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<html>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<body style=.*>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</body|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</html>|", $mailer->mailer->Body);
		$doc = new \DOMDocument();
		$doc->loadHTML($mailer->mailer->Body);
		$this->assertEquals("Welcome to Solid!", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
	}

	public function testVerifySucces()
	{
		$mailer = new Mailer();
		$mailer->mailer = new MailerMock();
		$mailer->sendVerify([
			'email' => 'alice@example.com',
			'code' => '654321'
		]);
		$this->assertContains("alice@example.com", $mailer->mailer->addresses);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->AltBody);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Header -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Footer -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Call to action -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<html>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<body style=.*>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</body|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</html>|", $mailer->mailer->Body);
		$doc = new \DOMDocument();
		$doc->loadHTML($mailer->mailer->Body);
		$this->assertEquals("Confirm your e-mail", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
	}

	public function testVerifyFailure()
	{
		$this->expectException(\Exception::class);

		$mailer = new Mailer();
		$mailer->mailer = new MailerMock();
		$mailer->mailer->ErrorInfo = 'Mock Error';
		$mailer->sendVerify([
			'email' => 'alice@example.com',
			'code' => '654321'
		]);
	}

	public function testResetPassword()
	{
		$mailer = new Mailer();
		$mailer->mailer = new MailerMock();
		$mailer->sendResetPassword([
			'email' => 'alice@example.com',
			'code' => '654321'
		]);
		$this->assertContains("alice@example.com", $mailer->mailer->addresses);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->AltBody);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Header -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Footer -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Call to action -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<html>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<body style=.*>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</body|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</html>|", $mailer->mailer->Body);
		$doc = new \DOMDocument();
		$doc->loadHTML($mailer->mailer->Body);
		$this->assertEquals("Password reset", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
	}

	public function testDeleteAccount()
	{
		$mailer = new Mailer();
		$mailer->mailer = new MailerMock();
		$mailer->sendDeleteAccount([
			'email' => 'alice@example.com',
			'code' => '654321'
		]);
		$this->assertContains("alice@example.com", $mailer->mailer->addresses);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->AltBody);
		$this->assertMatchesRegularExpression("/654321/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Header -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Footer -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("/<!-- Call to action -->/", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<html>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|<body style=.*>|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</body|", $mailer->mailer->Body);
		$this->assertMatchesRegularExpression("|</html>|", $mailer->mailer->Body);
		$doc = new \DOMDocument();
		$doc->loadHTML($mailer->mailer->Body);
		$this->assertEquals("Delete your account", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
	}
}
