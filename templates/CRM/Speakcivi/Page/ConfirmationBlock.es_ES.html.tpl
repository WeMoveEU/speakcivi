<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 40px; border: 1px solid #7e176d; display: inline-block;">Quiero recibir noticias</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Pinchando en el botón de arriba, confirmas que estás de acuerdo en recibir correos de campaña de Movemos Europa de manera ocasional. No te preocupes: intentaremos mandarte solo información que te sea relevante, y en cualquier momento tienes la opción de darte de baja. Tu privacidad es muy importante para nosotros. Nunca compartiremos tus datos personales con un tercero sin tu consentimiento explícito. Para más información, echa un vistazo a <a href="https://www.wemove.eu/es/privacy-policy">nuestra Política de Privacidad</a>.</p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Si prefieres no recibir nuestros correos de campaña, confirma pinchando en el siguiente enlace: <a href="{$url_confirm_and_not_receive}">no quiero recibir noticias</a>.</p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Quiero recibir noticias",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Quiero recibir noticias"
{rdelim}
</script>
