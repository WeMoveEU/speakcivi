<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Para terminar de firmar la petición pincha en este enlace:</p>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Confirmo mi acción; me gustaría saber más sobre otras campañas</a></td>
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
    "name": "Confirmo mi acción",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Confirmo mi acción; me gustaría saber más sobre otras campañas"
{rdelim}
</script>


<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Confirma aquí si prefieres no recibir más información sobre esta y otras <a href="{$url_confirm_and_not_receive}">campañas</a>.</p>
