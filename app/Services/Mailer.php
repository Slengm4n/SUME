<?php

declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Envio de e-mails.
 *  - driver "smtp": envia de verdade pelo Gmail (senha de app) via PHPMailer
 *  - driver "log":  grava em storage/logs/mail.log (desenvolvimento e testes)
 */
final class Mailer
{
    public static function isConfigured(): bool
    {
        $mail = config('mail');
        if ($mail['driver'] === 'log') {
            return true;
        }

        return $mail['host'] !== '' && $mail['username'] !== '' && $mail['password'] !== '' && $mail['from'] !== '';
    }

    /**
     * @throws \RuntimeException quando o envio falha (a mensagem não expõe detalhes ao usuário)
     */
    public static function send(
        string $to,
        string $toName,
        string $subject,
        string $html,
        string $text,
        ?array $replyTo = null
    ): void {
        $mail = config('mail');

        if ($mail['driver'] === 'log') {
            self::log($to, $subject, $text);
            return;
        }

        if (!self::isConfigured()) {
            throw new \RuntimeException('E-mail não configurado.');
        }

        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->SMTPAuth = true;
            $mailer->Host = $mail['host'];
            $mailer->Port = $mail['port'];
            $mailer->SMTPSecure = $mail['port'] === 465 ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Username = $mail['username'];
            $mailer->Password = $mail['password'];
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Timeout = 15;

            $mailer->setFrom($mail['from'], $mail['from_name']);
            $mailer->addAddress($to, $toName);
            if ($replyTo) {
                $mailer->addReplyTo($replyTo[0], $replyTo[1] ?? '');
            }

            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $html;
            $mailer->AltBody = $text;
            $mailer->send();
        } catch (MailException $e) {
            error_log('Falha ao enviar e-mail: ' . $e->getMessage());
            throw new \RuntimeException('Falha ao enviar e-mail.', 0, $e);
        }
    }

    /** Monta um e-mail HTML simples com a identidade do SUME. */
    public static function layout(string $title, string $bodyHtml, ?string $buttonUrl = null, ?string $buttonLabel = null): string
    {
        $button = $buttonUrl
            ? '<p style="margin:28px 0"><a href="' . e($buttonUrl) . '" style="background:#d0192a;color:#fff;text-decoration:none;'
              . 'padding:12px 24px;border-radius:999px;font-weight:700;display:inline-block">' . e((string) $buttonLabel) . '</a></p>'
            : '';

        return '<!DOCTYPE html><html lang="pt-BR"><body style="margin:0;background:#f7f6f4;font-family:Segoe UI,Arial,sans-serif;color:#1c1917">'
            . '<div style="max-width:520px;margin:0 auto;padding:32px 20px">'
            . '<p style="font-weight:800;font-size:20px;letter-spacing:.04em;margin:0 0 24px">SUM<span style="color:#a2a1aa">E</span></p>'
            . '<div style="background:#fff;border:1px solid #e6e3de;border-radius:16px;padding:28px">'
            . '<h1 style="font-size:20px;margin:0 0 12px">' . e($title) . '</h1>'
            . $bodyHtml . $button
            . '</div>'
            . '<p style="color:#67615b;font-size:12px;margin-top:20px">SUME · Sistema Único de Merenda Escolar</p>'
            . '</div></body></html>';
    }

    private static function log(string $to, string $subject, string $text): void
    {
        $dir = BASE_PATH . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $entry = sprintf("=== %s\nPara: %s\nAssunto: %s\n\n%s\n\n", date('Y-m-d H:i:s'), $to, $subject, $text);
        file_put_contents($dir . '/mail.log', $entry, FILE_APPEND | LOCK_EX);
    }
}
