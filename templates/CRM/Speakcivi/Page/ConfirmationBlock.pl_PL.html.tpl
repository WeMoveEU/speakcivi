<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Aby potwierdzić podpisanie petycji kliknij w poniższy link:</p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Potwierdź podpisanie petycji i bądź na bieżąco informowany o tej kampanii</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Jeśli nie chcesz otrzymywać wiadomości nt. kampanii, potwierdź podpisanie petycji w tym <a href="{$url_confirm_and_not_receive}">linku</a></p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Potwierdź podpisanie petycji",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Potwierdź podpisanie petycji i bądź na bieżąco informowany o tej kampanii"
{rdelim}
</script>
