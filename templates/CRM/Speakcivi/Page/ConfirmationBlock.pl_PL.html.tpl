<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Chcę otrzymywać informacje!</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Twoje potwierdzenie oznacza, że zgadzasz się otrzymywać wiadomości e-mail o kampaniach WeMove Europe, które od czasu do czasu wysyłamy. Nie martw się: dokładamy starań, aby te wiadomości były dla Ciebie interesujące. W każdej chwili możesz się wypisać. Szanujemy Twoją prywatność i nigdy nie udostępnimy Twoich danych postronnym osobom lub instytucjom bez Twojej wyraźnej zgody. Więcej informacji znajdziesz w naszej <a href="https://www.wemove.eu/pl/privacy-policy">polityce prywatności</a>.</p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">Jeśli nie życzysz sobie otrzymywać wiadomości e-mail o naszych kampaniach, potwierdź swoją decyzję tutaj: <a href="{$url_confirm_and_not_receive}">nie chcę być informowany</a>.</p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Chcę otrzymywać informacje!",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Chcę otrzymywać informacje!"
{rdelim}
</script>
