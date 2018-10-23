<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Ja, bitte senden Sie mir Neuigkeiten!</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Mit der Bestätigung stimmen Sie zu, dass Sie von uns Kampagnen-E-Mails erhalten. Seien Sie versichert: Wir tun unser Bestes, damit die Neuigkeiten interessant für Sie sind. Sie können sich jederzeit wieder abmelden.</p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;"> Wir nehmen den Schutz Ihrer Daten sehr ernst und werden Ihre Daten ohne Ihre ausdrückliche Zustimmung nicht an Dritte weitergeben. Mehr dazu erfahren Sie in <a href="https://www.wemove.eu/de/privacy-policy">unserer Datenschutzerklärung</a></p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Wenn Sie keine Petitions-E-Mails erhalten möchten, dann können Sie das gerne Ihre Entscheidung hier bestätigen: <a href="{$url_confirm_and_not_receive}">Ich möchte nicht weiter informiert werden</a>. Auch wenn Sie hier nicht klicken, erhalten Sie keine Kampagnen-E-Mails von uns </p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Ja, bitte senden Sie mir Neuigkeiten",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Ja, bitte senden Sie mir Neuigkeiten!"
{rdelim}
</script>
