<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td>
      <table border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td align="center" style="-webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px;" bgcolor="#941b80"><a href="{$url_confirm_and_keep}" target="_blank" style="font-size: 16px; font-family: Helvetica, Arial, sans-serif; color: #ffffff; text-decoration: none; color: #ffffff; text-decoration: none; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; padding: 12px 18px; border: 1px solid #7e176d; display: inline-block;">Please send me updates!</a></td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">By confirming you are agreeing to receive WeMove Europe campaign emails from time to time. Don’t worry: we do our best to make the updates interesting to you, and you can unsubscribe at any point. We take your privacy very seriously and will never share your data with a third party without your explicit consent. You can learn more about that in <a href="https://www.wemove.eu/privacy-policy">our privacy policy</a>.</p>

<p style="font-size:14px;font-family: arial,helvetica,sans-serif;">If you would rather not receive any campaign emails, please confirm your decision here: <a href="{$url_confirm_and_not_receive}">I don’t want to be informed</a>.</p>

<script type="application/ld+json">
{ldelim}
  "@context": "http://schema.org",
  "@type": "EmailMessage",
  "potentialAction": {ldelim}
    "@type": "ConfirmAction",
    "name": "Please send me updates",
    "handler": {ldelim}
      "@type": "HttpActionHandler",
      "url": "{$url_confirm_and_keep}-schema"
    {rdelim}
  {rdelim},
  "description": "Please send me updates!"
{rdelim}
</script>
