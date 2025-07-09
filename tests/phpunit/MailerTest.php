<?php
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

    const BASEURL = "https://example.com";

    class MailerMock {
        public $Subject;
        public $Body;
        public $AltBody;
        public $addresses = [];

        public function addAddress($address) {
            $this->addresses[] = $address;
        }
        public function send() {
            return true;
        }
    }

    class MailerTest extends \PHPUnit\Framework\TestCase
    {
        public function testGetMailer() {
            $mailer = Mailer::getMailer();
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

        public function testAccountCreated() {
            Mailer::$mailer = new MailerMock();
            Mailer::sendAccountCreated([
                'email' => 'alice@example.com',
                'webId' => 'aliceWebId'
            ]);
            $this->assertContains("alice@example.com", Mailer::$mailer->addresses);
            $this->assertMatchesRegularExpression("/aliceWebId/", Mailer::$mailer->AltBody);
            $this->assertMatchesRegularExpression("/aliceWebId/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Header -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Footer -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Call to action -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<html>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<body style=.*>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</body|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</html>|", Mailer::$mailer->Body);
            $doc = new \DOMDocument();
            $doc->loadHTML(Mailer::$mailer->Body);
            $this->assertEquals("Welkom bij Solid!", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
        }

        public function testVerify() {
            Mailer::$mailer = new MailerMock();
            Mailer::sendVerify([
                'email' => 'alice@example.com',
                'code' => '654321'
            ]);
            $this->assertContains("alice@example.com", Mailer::$mailer->addresses);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->AltBody);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Header -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Footer -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Call to action -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<html>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<body style=.*>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</body|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</html>|", Mailer::$mailer->Body);
            $doc = new \DOMDocument();
            $doc->loadHTML(Mailer::$mailer->Body);
            $this->assertEquals("Bevestig je e-mail", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
        }

        public function testResetPassword() {
            Mailer::$mailer = new MailerMock();
            Mailer::sendResetPassword([
                'email' => 'alice@example.com',
                'code' => '654321'
            ]);
            $this->assertContains("alice@example.com", Mailer::$mailer->addresses);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->AltBody);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Header -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Footer -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Call to action -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<html>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<body style=.*>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</body|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</html>|", Mailer::$mailer->Body);
            $doc = new \DOMDocument();
            $doc->loadHTML(Mailer::$mailer->Body);
            $this->assertEquals("Wachtwoordherstel", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
        }

        public function testDeleteAccount() {
            Mailer::$mailer = new MailerMock();
            Mailer::sendDeleteAccount([
                'email' => 'alice@example.com',
                'code' => '654321'
            ]);
            $this->assertContains("alice@example.com", Mailer::$mailer->addresses);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->AltBody);
            $this->assertMatchesRegularExpression("/654321/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Header -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Footer -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("/<!-- Call to action -->/", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<html>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|<body style=.*>|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</body|", Mailer::$mailer->Body);
            $this->assertMatchesRegularExpression("|</html>|", Mailer::$mailer->Body);
            $doc = new \DOMDocument();
            $doc->loadHTML(Mailer::$mailer->Body);
            $this->assertEquals("Je account verwijderen", $doc->getElementsByTagName("title")[0]->textContent); // If this works, I'm assuming it is valid HTML.
        }
    }
