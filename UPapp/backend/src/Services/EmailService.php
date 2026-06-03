<?php

namespace UpApp\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use UpApp\Config\Environment;

class EmailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // Character encoding for Polish characters
        $this->mailer->CharSet = 'UTF-8';
        $this->mailer->Encoding = 'base64';

        // SMTP configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = Environment::get('SMTP_HOST', 'smtp.gmail.com');
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = Environment::get('SMTP_USERNAME');
        $this->mailer->Password = Environment::get('SMTP_PASSWORD');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = (int) Environment::get('SMTP_PORT', '587');

        // Sender
        $this->mailer->setFrom(
            Environment::get('SMTP_FROM_EMAIL', 'noreply@upapp.local'),
            Environment::get('SMTP_FROM_NAME', 'UPapp')
        );
    }

    public function sendVerificationEmail(string $toEmail, string $toName, string $verificationToken): bool
    {
        try {
            $appUrl = Environment::get('APP_URL', 'http://localhost:5173');
            $verificationLink = "$appUrl/verify-email?token=$verificationToken";

            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Potwierdź swój adres email - UPapp';
            $this->mailer->Body = $this->getVerificationEmailBody($toName, $verificationLink);
            $this->mailer->AltBody = $this->getVerificationEmailPlainText($toName, $verificationLink);

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return false;
        }
    }

    private function getVerificationEmailBody(string $name, string $link): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4a90e2; color: white; padding: 20px; text-align: center; }
        .content { background: #f9f9f9; padding: 30px; }
        .button {
            display: inline-block;
            background: #4a90e2;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer { text-align: center; color: #666; font-size: 12px; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>UPapp - Komunikacja bez Przemocy</h1>
        </div>
        <div class="content">
            <h2>Witaj, $name!</h2>
            <p>Dziękujemy za rejestrację w UPapp. Aby dokończyć proces rejestracji, potwierdź swój adres email klikając poniższy link:</p>
            <p style="text-align: center;">
                <a href="$link" class="button">Potwierdź adres email</a>
            </p>
            <p>Lub skopiuj i wklej poniższy link do przeglądarki:</p>
            <p style="word-break: break-all; color: #666;">$link</p>
            <p>Jeśli nie rejestrowałeś/aś się w UPapp, zignoruj tę wiadomość.</p>
        </div>
        <div class="footer">
            <p>&copy; 2026 UPapp. Wszystkie prawa zastrzeżone.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private function getVerificationEmailPlainText(string $name, string $link): string
    {
        return <<<TEXT
UPapp - Komunikacja bez Przemocy

Witaj, $name!

Dziękujemy za rejestrację w UPapp. Aby dokończyć proces rejestracji, potwierdź swój adres email klikając poniższy link:

$link

Jeśli nie rejestrowałeś/aś się w UPapp, zignoruj tę wiadomość.

---
© 2026 UPapp. Wszystkie prawa zastrzeżone.
TEXT;
    }
}
