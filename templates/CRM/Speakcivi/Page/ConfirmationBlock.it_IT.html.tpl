<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Sì, mandatemi gli aggiornamenti!</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Con questa conferma acconsenti a ricevere, di tanto in tanto, le e-mail informative sulle campagne di WeMove Europe. Non preoccuparti: facciamo del nostro meglio per mandarti solo aggiornamenti che pensiamo possano interessarti e puoi, in qualunque momento, revocare la tua autorizzazione. Per noi la tua privacy è molto importante e i tuoi dati non verranno condivisi con terzi senza il tuo esplicito consenso. Per saperne di più, consulta la <a href="https://www.wemove.eu/it/privacy-policy">nostra politica sulla privacy</a>.</p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Se invece preferisci non ricevere informazioni sulle nostre campagne, conferma qui la tua decisione: <a href="{$url_confirm_and_not_receive}">preferisco non ricevere aggiornamenti</a>.</p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Sì, mandatemi gli aggiornamenti",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Sì, mandatemi gli aggiornamenti!"
{rdelim}
</script>
