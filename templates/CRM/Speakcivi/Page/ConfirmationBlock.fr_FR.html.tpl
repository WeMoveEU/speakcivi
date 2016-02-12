<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">
Il est très important pour nous de savoir que c’est bien vous qui nous avez confié votre adresse e-mail, et non pas quelqu’un d’autre sans votre permission.
</p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">C’est pour cette raison que nous vous demandons de bien vouloir confirmer votre action</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Confirme ma signature",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Je confirme mon action et veux des mises à jour sur cette campagne et d'autres similaires"
{rdelim}
</script>


<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Vous recevrez des mises à jour sur cette campagne et d’autres campagnes similaires; si vous ne souhaitez pas en recevoir, cliquez <a href="{$url_confirm_and_not_receive}">ici</a></p>
