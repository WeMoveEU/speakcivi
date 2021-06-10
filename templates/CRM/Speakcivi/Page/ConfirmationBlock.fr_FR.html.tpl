<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Merci de m’envoyer des informations!</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">En confirmant votre inscription, vous acceptez de recevoir occasionnellement des emails de campagne de WeMove Europe. Pas d’inquiétude ! Nous nous efforçons de vous envoyer des informations qui vous intéressent, et vous pouvez vous désinscrire à tout moment. Nous prenons le respect de votre vie privée très au sérieux et ne transmettrons jamais vos données personnelles à des tiers sans votre accord explicite. Pour en savoir plus : <a href="https://www.wemove.eu/fr/privacy-policy">politique de confidentialité</a></p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Si vous ne souhaitez pas recevoir d’emails de campagne, merci de cliquer sur le lien ci-dessous : <a href="{$url_confirm_and_not_receive}">Je ne veux pas recevoir d’informations</a></p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Merci de m’envoyer des informations",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Merci de m’envoyer des informations!"
{rdelim}
</script>