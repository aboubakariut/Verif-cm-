<?php
/* ══════════════════════════════════════════════════════════════════
   TONTINES FACILE — mailer.php
   Envoi d'emails : réinitialisation, invitations, rappels, notifications
   ══════════════════════════════════════════════════════════════════ */

declare(strict_types=1);

/* ─── Configuration SMTP (modifier selon votre hébergeur) ─── */
if (!function_exists('env')) {
    function env(string $key, string $default = ''): string {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}
define('MAIL_FROM',      env('MAIL_FROM',     'noreply@tontinesfacile.app'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME','Tontines Facile'));
define('MAIL_REPLY_TO',  env('MAIL_REPLY_TO', 'support@tontinesfacile.app'));
if (!defined('APP_URL'))  define('APP_URL',   env('APP_URL', 'https://tontines-facile.vercel.app'));
define('APP_NAME',       'Tontines Facile');

/* Clé API Brevo (ex-Sendinblue) — à définir dans les variables d'environnement
   Vercel (Project Settings > Environment Variables) sous le nom BREVO_API_KEY.
   Créer un compte gratuit sur https://www.brevo.com (300 emails/jour offerts)
   puis générer la clé dans SMTP & API > API Keys. */
define('BREVO_API_KEY', env('BREVO_API_KEY', ''));

/* ══════════════════════════════════════════════════════════════════
   MAILER CLASS
   ══════════════════════════════════════════════════════════════════ */
class Mailer {

  /* ── Template de base HTML ── */
  private static function wrap(string $title, string $body, string $cta = '', string $ctaUrl = ''): string {
    $ctaBtn = $cta ? "
      <div style='text-align:center;margin:32px 0'>
        <a href='$ctaUrl'
           style='display:inline-block;background:linear-gradient(135deg,#0f6b4a,#22c87e);
                  color:#ffffff;font-family:Sora,Arial,sans-serif;font-size:16px;
                  font-weight:700;text-decoration:none;padding:14px 32px;
                  border-radius:12px;box-shadow:0 4px 16px rgba(15,107,74,0.3)'>
          $cta
        </a>
      </div>" : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>$title — Tontines Facile</title>
</head>
<body style="margin:0;padding:0;background:#f0f7f4;font-family:Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f7f4;padding:40px 20px">
<tr><td align="center">
  <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

    <!-- Header -->
    <tr>
      <td style="background:linear-gradient(135deg,#0f6b4a,#1a9c6e);border-radius:20px 20px 0 0;padding:32px 40px;text-align:center">
        <div style="background:rgba(255,255,255,0.15);width:64px;height:64px;border-radius:16px;
                    margin:0 auto 16px;display:flex;align-items:center;justify-content:center;
                    font-size:32px;line-height:64px">🌿</div>
        <h1 style="color:#ffffff;margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px">
          Tontines<span style="opacity:0.7;font-weight:400">Facile</span>
        </h1>
        <p style="color:rgba(255,255,255,0.8);margin:6px 0 0;font-size:13px">La transparence au cœur de votre épargne</p>
      </td>
    </tr>

    <!-- Body -->
    <tr>
      <td style="background:#ffffff;padding:40px;border-left:1px solid #cde8da;border-right:1px solid #cde8da">
        <h2 style="color:#0d2b1e;font-size:20px;margin:0 0 16px;font-weight:700">$title</h2>
        $body
        $ctaBtn
      </td>
    </tr>

    <!-- Footer -->
    <tr>
      <td style="background:#e8f5ef;border-radius:0 0 20px 20px;padding:20px 40px;
                 border:1px solid #cde8da;border-top:none;text-align:center">
        <p style="color:#6b9b7e;font-size:12px;margin:0">
          © 2025 Tontines Facile · Vous recevez cet email car vous êtes inscrit(e) sur notre plateforme.<br>
          <a href="APP_URL/unsubscribe" style="color:#0f6b4a">Se désabonner</a>
        </p>
      </td>
    </tr>

  </table>
</td></tr>
</table>
</body>
</html>
HTML;
  }

  /* ── Envoi générique via l'API HTTP de Brevo ──
     Important : la fonction native mail() ne fonctionne PAS sur Vercel
     (aucun serveur mail local dans l'environnement serverless), il faut
     donc passer par un service tiers joignable en HTTP. */
  private static function send(string $to, string $subject, string $html): bool {
    error_log("[MAILER] To: $to | Subject: $subject");

    if (!BREVO_API_KEY) {
      error_log("[MAILER] BREVO_API_KEY manquante — email non envoyé (voir Mailer.php).");
      return false;
    }

    $payload = json_encode([
      'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM],
      'to'          => [['email' => $to]],
      'replyTo'     => ['email' => MAIL_REPLY_TO],
      'subject'     => $subject,
      'htmlContent' => $html,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => $payload,
      CURLOPT_HTTPHEADER     => [
        'accept: application/json',
        'content-type: application/json',
        'api-key: ' . BREVO_API_KEY,
      ],
      CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr || $status >= 300) {
      error_log("[MAILER] Échec envoi vers $to — HTTP $status — $curlErr — $response");
      return false;
    }
    return true;
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 1 : Réinitialisation du mot de passe
     ══════════════════════════════════════════════════════ */
  public static function sendPasswordReset(string $to, string $firstname, string $token): bool {
    $resetUrl = APP_URL . "/index.html?action=reset&token=$token";
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Bonjour <strong>$firstname</strong>,
      </p>
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton
        ci-dessous pour en créer un nouveau. Ce lien est valable <strong>1 heure</strong>.
      </p>
      <div style='background:#e8f5ef;border-left:4px solid #0f6b4a;padding:16px;border-radius:8px;margin:20px 0'>
        <p style='margin:0;font-size:13px;color:#3d6b52'>⚠️ Si vous n'avez pas fait cette demande, ignorez cet email. Votre mot de passe ne sera pas modifié.</p>
      </div>";
    $html = self::wrap('Réinitialisation du mot de passe', $body, '🔑 Réinitialiser mon mot de passe', $resetUrl);
    return self::send($to, 'Réinitialisation de votre mot de passe — Tontines Facile', $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 2 : Invitation à rejoindre une tontine
     ══════════════════════════════════════════════════════ */
  public static function sendInvitation(
    string $to, string $inviterName, string $tontineName,
    string $amount, string $frequency, string $inviteUrl,
    string $customMsg = ''
  ): bool {
    $msgBlock = $customMsg ? "
      <div style='background:#f0f7f4;border-radius:12px;padding:16px;margin:16px 0;font-style:italic;color:#3d6b52;font-size:14px'>
        \"$customMsg\"
      </div>" : '';

    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:16px'>
        <strong>$inviterName</strong> vous invite à rejoindre sa tontine sur Tontines Facile !
      </p>
      $msgBlock
      <table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f7f4;border-radius:12px;padding:20px;margin:16px 0'>
        <tr>
          <td style='padding:8px 0'><span style='color:#6b9b7e;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px'>Tontine</span><br><strong style='color:#0d2b1e;font-size:16px'>$tontineName</strong></td>
        </tr>
        <tr>
          <td style='padding:8px 0'><span style='color:#6b9b7e;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px'>Mise</span><br><strong style='color:#0d2b1e'>$amount FCFA</strong></td>
        </tr>
        <tr>
          <td style='padding:8px 0'><span style='color:#6b9b7e;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px'>Fréquence</span><br><strong style='color:#0d2b1e'>$frequency</strong></td>
        </tr>
      </table>
      <p style='color:#6b9b7e;font-size:13px;margin-top:16px'>
        ⏰ Cette invitation expire dans <strong>7 jours</strong>.
      </p>";
    $html = self::wrap("Invitation : rejoignez $tontineName", $body, '✅ Accepter l\'invitation', $inviteUrl);
    return self::send($to, "Invitation à rejoindre \"$tontineName\" — Tontines Facile", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 3 : Rappel de paiement
     ══════════════════════════════════════════════════════ */
  public static function sendPaymentReminder(
    string $to, string $firstname, string $tontineName,
    string $amount, string $dueDate, string $appUrl
  ): bool {
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Bonjour <strong>$firstname</strong>,
      </p>
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:16px'>
        Un rappel amical : votre mise pour la tontine <strong>$tontineName</strong> est attendue.
      </p>
      <div style='background:#fef9ec;border:2px solid #f0d000;border-radius:12px;padding:20px;margin:16px 0;text-align:center'>
        <p style='margin:0;font-size:13px;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:0.5px'>Montant dû</p>
        <p style='margin:8px 0 4px;font-size:28px;font-weight:800;color:#0d2b1e'>$amount FCFA</p>
        <p style='margin:0;font-size:13px;color:#92400e'>Date limite : <strong>$dueDate</strong></p>
      </div>
      <p style='color:#6b9b7e;font-size:13px;margin-top:16px'>
        Connectez-vous à l'application pour signaler votre paiement à l'administrateur.
      </p>";
    $html = self::wrap('Rappel de paiement', $body, '💰 Ouvrir l\'application', $appUrl);
    return self::send($to, "Rappel : mise en attente pour $tontineName — Tontines Facile", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 4 : Paiement confirmé
     ══════════════════════════════════════════════════════ */
  public static function sendPaymentConfirmed(
    string $to, string $firstname, string $tontineName,
    string $amount, string $tour, string $appUrl
  ): bool {
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Bonjour <strong>$firstname</strong>,
      </p>
      <div style='background:#dcfce7;border:2px solid #bbf7d0;border-radius:12px;padding:20px;margin:16px 0;text-align:center'>
        <div style='font-size:40px;margin-bottom:8px'>✅</div>
        <p style='margin:0;font-size:15px;font-weight:700;color:#14532d'>Paiement enregistré !</p>
        <p style='margin:8px 0 0;font-size:13px;color:#166534'>Tontine : $tontineName · Tour $tour · $amount FCFA</p>
      </div>
      <p style='color:#3d6b52;font-size:14px;line-height:1.7'>
        Votre mise a été confirmée par l'administrateur et enregistrée dans le journal d'audit. Tout est transparent !
      </p>";
    $html = self::wrap('Paiement confirmé', $body, '📋 Voir le journal', $appUrl);
    return self::send($to, "Paiement confirmé — $tontineName · Tontines Facile", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 5 : Demande d'adhésion approuvée
     ══════════════════════════════════════════════════════ */
  public static function sendMemberApproved(
    string $to, string $firstname, string $tontineName, string $appUrl
  ): bool {
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>Bonjour <strong>$firstname</strong>,</p>
      <div style='background:#dcfce7;border-radius:12px;padding:24px;text-align:center;margin:16px 0'>
        <div style='font-size:40px;margin-bottom:8px'>🎉</div>
        <p style='font-size:16px;font-weight:700;color:#14532d;margin:0'>Félicitations ! Votre adhésion a été acceptée.</p>
        <p style='font-size:14px;color:#166534;margin:8px 0 0'>Vous êtes maintenant membre de <strong>$tontineName</strong>.</p>
      </div>
      <p style='color:#3d6b52;font-size:14px;line-height:1.7'>
        Connectez-vous à Tontines Facile pour découvrir les membres, l'ordre des tours et les dates de paiement.
      </p>";
    $html = self::wrap('Adhésion acceptée !', $body, '👥 Accéder à ma tontine', $appUrl);
    return self::send($to, "Bienvenue dans $tontineName — Tontines Facile", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 6 : Cagnotte versée (bénéficiaire)
     ══════════════════════════════════════════════════════ */
  public static function sendDisbursement(
    string $to, string $firstname, string $tontineName,
    string $amount, string $tour, string $appUrl
  ): bool {
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>Bonjour <strong>$firstname</strong>,</p>
      <div style='background:linear-gradient(135deg,#0f6b4a,#22c87e);border-radius:16px;padding:32px;text-align:center;margin:16px 0'>
        <div style='font-size:48px;margin-bottom:12px'>🏆</div>
        <p style='color:rgba(255,255,255,0.8);font-size:14px;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin:0'>Cagnotte du tour $tour</p>
        <p style='color:#ffffff;font-size:36px;font-weight:800;margin:8px 0;letter-spacing:-1px'>$amount FCFA</p>
        <p style='color:rgba(255,255,255,0.8);font-size:14px;margin:0'>ont été versés par l'administrateur de <strong>$tontineName</strong></p>
      </div>
      <p style='color:#3d6b52;font-size:14px;line-height:1.7'>
        Ce versement est enregistré dans le journal d'audit de votre tontine, visible par tous les membres.
      </p>";
    $html = self::wrap('🏆 Votre cagnotte est prête !', $body, '📋 Voir les détails', $appUrl);
    return self::send($to, "Cagnotte de $amount FCFA versée ! — Tontines Facile", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 7 : Nouvelle demande d'adhésion (pour admin)
     ══════════════════════════════════════════════════════ */
  public static function sendJoinRequest(
    string $to, string $adminName, string $applicantName,
    string $tontineName, string $appUrl
  ): bool {
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>Bonjour <strong>$adminName</strong>,</p>
      <div style='background:#e8f5ef;border:1.5px solid #cde8da;border-radius:12px;padding:20px;margin:16px 0;display:flex;align-items:center;gap:16px'>
        <div style='width:48px;height:48px;background:linear-gradient(135deg,#0f6b4a,#22c87e);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0'>👤</div>
        <div>
          <p style='margin:0;font-weight:700;color:#0d2b1e;font-size:16px'>$applicantName</p>
          <p style='margin:4px 0 0;font-size:13px;color:#6b9b7e'>souhaite rejoindre <strong>$tontineName</strong></p>
        </div>
      </div>
      <p style='color:#3d6b52;font-size:14px;line-height:1.7'>
        Connectez-vous à Tontines Facile pour approuver ou refuser cette demande depuis l'onglet Membres de votre tontine.
      </p>";
    $html = self::wrap('Nouvelle demande d\'adhésion', $body, '✅ Gérer la demande', $appUrl);
    return self::send($to, "Nouvelle demande : $applicantName veut rejoindre $tontineName", $html);
  }

  /* ══════════════════════════════════════════════════════
     EMAIL 8 : Bienvenue à l'inscription
     ══════════════════════════════════════════════════════ */
  public static function sendWelcome(string $to, string $firstname, string $inviteCode): bool {
    $appUrl = APP_URL;
    $body = "
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Bienvenue <strong>$firstname</strong> ! 🎉
      </p>
      <p style='color:#3d6b52;font-size:15px;line-height:1.7;margin-bottom:20px'>
        Votre compte Tontines Facile a été créé avec succès. Vous pouvez maintenant créer vos propres tontines ou rejoindre celles de vos proches.
      </p>
      <div style='background:#f0f7f4;border:2px dashed #0f6b4a;border-radius:12px;padding:20px;text-align:center;margin:20px 0'>
        <p style='margin:0;font-size:13px;color:#6b9b7e;font-weight:700;text-transform:uppercase;letter-spacing:1px'>Votre code d'invitation personnel</p>
        <p style='margin:8px 0 4px;font-size:28px;font-weight:800;color:#0f6b4a;font-family:monospace;letter-spacing:4px'>$inviteCode</p>
        <p style='margin:0;font-size:12px;color:#6b9b7e'>Partagez ce code pour inviter vos contacts</p>
      </div>";
    $html = self::wrap('Bienvenue sur Tontines Facile !', $body, '🚀 Commencer', $appUrl);
    return self::send($to, 'Bienvenue sur Tontines Facile !', $html);
  }
}
