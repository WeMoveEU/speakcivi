<?php

class CRM_Speakcivi_Tools_Dictionary {

  /** @var array Array of ids of email greeting indexed by locale and genderShortcut */
  public $emailGreetingIds = array();


  /**
   * Parse all email greeting types in array of locale and gender shortcut
   */
  public function parseGroupEmailGreeting() {
    CRM_Core_OptionGroup::getAssoc('email_greeting', $group, false, 'name');
    foreach ($group['description'] as $id => $description) {
      $tab = $this->parseLocaleGenderShortcut($description);
      if (is_array($tab) && count($tab) == 2) {
        $this->emailGreetingIds[$tab['locale']][$tab['genderShortcut']] = $id;
      }
    }
  }


  /**
   * Parse description of email greeting type in array of locale and gender shortcut
   * @param string $description description of email greeting type in format [locale]:[genderShortcut] ex. fr_FR:M
   *
   * @return array
   */
  public function parseLocaleGenderShortcut($description) {
    $re = '/^([a-z]{2,3}_[A-Z]{2})\:(.{0,1})/';
    if (preg_match($re, $description, $matches)) {
      return array(
        'locale' => $matches[1],
        'genderShortcut' => $matches[2],
      );
    }
    return array();
  }


  /**
   * Get email greeting Id for locale and gender shortcut
   * @param string $locale
   * @param string $genderShortcut
   *
   * @return int
   */
  public function getEmailGreetingId($locale, $genderShortcut) {
    if (array_key_exists($locale, $this->emailGreetingIds)) {
      if (
        array_key_exists($genderShortcut, $this->emailGreetingIds[$locale]) &&
        $this->emailGreetingIds[$locale][$genderShortcut] > 0
      ) {
        return $this->emailGreetingIds[$locale][$genderShortcut];
      }
      return $this->emailGreetingIds[$locale][''];
    }
    return 0;
  }


  /**
   * Get prefix based on gender (F or M)
   * @param string $genderShortcut
   *
   * @return string
   */
  public static function getPrefix($genderShortcut) {
    $array = array(
      'F' => 'Mrs.',
      'M' => 'Mr.',
    );
    $default = '';
    return self::getValue($genderShortcut, $array, $default);
  }


  /**
   * @param string $key
   * @param array $array
   * @param string $default
   *
   * @return string
   */
  private static function getValue($key, $array, $default = '') {
    if (array_key_exists($key, $array) && $array[$key] != '') {
      return $array[$key];
    }
    return $default;
  }

  /**
   * Get subject of email for confirmation mail
   * @param string $locale
   *
   * @return string
   */
  public static function getSubjectConfirm($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Sie sind fast fertig. Bitte bestätigen Sie Ihre Unterschrift.';
        break;

      case 'fr_FR':
        return "C'est bientôt fini - confirmez votre action";
        break;

      case 'da_DK':
        return 'You are almost done - please confirm your action';
        break;

      case 'el_GR':
        return 'You are almost done - please confirm your action';
        break;

      case 'es_ES':
        return 'Ya casi has terminado. Confirma tu acción por favor.';
        break;

      case 'it_IT':
        return 'Hai quasi finito';
        break;

      case 'nl_NL':
        return 'You are almost done - please confirm your action';
        break;

      case 'pl_PL':
        return 'Prawie skończone - potwierdź podpisanie petycji';
        break;

      case 'pt_PT':
        return 'Está quase no fim - por favor, confirme a sua acção';
        break;

      case 'ro_RO':
        return 'Ultimul pas: confirmă acțiunea';
        break;

      default:
        return 'You are almost done - please confirm your action';
    }
  }

  /**
   * Button label for language conversion block
   */
  public static function getLanguageButton($locale) {
    switch ($locale) {
      case 'de_DE':
        return "Lassen Sie mich die Sprache wählen!";
        break;

      case 'es_ES':
        return "¡Déjame que elija el idioma!";
        break;

      case 'fr_FR':
        return "Je choisis ma langue !";
        break;

      case 'it_IT':
        return "Fammi scegliere la Lingua!";
        break;

      case 'pt_PT':
        return "Deixe-me escolher o idioma!";
        break;

      case 'pl_PL':
        return "Chcę wybrać język!";
        break;

      default:
        return "Let me choose the language!";
    }
  }

  /**
   * Get default value for Share by email (Forward as Email)
   * @param string $locale
   *
   * @return string
   */
  public static function getShareEmail($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Per E-Mail teilen';
        break;
      case 'fr_FR':
        return 'Partager par email';
        break;
      case 'da_DK':
        return 'Videresend som e-mail';
        break;
      case 'el_GR':
        return 'Προώθηση ως Email';
        break;
      case 'es_ES':
        return 'Comparte por email';
        break;
      case 'it_IT':
        return "Condividi tramite email";
        break;
      case 'nl_NL':
        return 'Forward als e-mail';
        break;
      case 'pl_PL':
        return 'Udostępnij przez e-mail';
        break;
      case 'pt_PT':
        return 'Encaminhar como Email';
        break;
      default:
        return 'Share by e-mail';
    }
  }


  /**
   * Get default value for Share on Facebook
   * @param string $locale
   *
   * @return string
   */
  public static function getShareFacebook($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Teilen auf Facebook';
        break;

      case 'fr_FR':
        return 'Partager sur Facebook';
        break;

      case 'da_DK':
        return "Share on Facebook";
        break;

      case 'el_GR':
        return "Share on Facebook";
        break;

      case 'es_ES':
        return 'Comparte en Facebook';
        break;

      case 'it_IT':
        return "Condividi su Facebook";
        break;

      case 'nl_NL':
        return "Share on Facebook";
        break;

      case 'pl_PL':
        return "Udostępnij na Facebooku";
        break;

      case 'pt_PT':
        return "Partilhe no Facebook";
        break;

      case 'ro_RO':
        return "Distribuie pe Facebook";
        break;

      default:
        return 'Share on Facebook';
    }
  }


  /**
   * Get default value for Share on Twitter
   * @param string $locale
   *
   * @return string
   */
  public static function getShareTwitter($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Teilen auf Twitter';
        break;

      case 'fr_FR':
        return 'Tweeter à vos abonnés';
        break;

      case 'da_DK':
        return 'Share on Twitter';
        break;

      case 'el_GR':
        return 'Share on Twitter';
        break;

      case 'es_ES':
        return 'Comparte en Twitter';
        break;

      case 'it_IT':
        return "Condividi su Twitter";
        break;

      case 'nl_NL':
        return 'Share on Twitter';
        break;

      case 'pl_PL':
        return 'Udostępnij na Twitterze';
        break;

      case 'pt_PT':
        return 'Partilhe no Twitter';
        break;

      case 'ro_RO':
        return 'Distribuie pe Twitter';
        break;

      default:
        return 'Share on Twitter';
    }
  }


  /**
   * Get default value for Share on WhatsApp
   * @param string $locale
   *
   * @return string
   */
  public static function getShareWhatsApp($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Teilen auf WhatsApp';
        break;

      case 'fr_FR':
        return 'Partager sur WhatsApp';
        break;

      case 'da_DK':
        return 'Share on WhatsApp';
        break;

      case 'el_GR':
        return 'Share on WhatsApp';
        break;

      case 'es_ES':
        return 'Comparte en WhatsApp';
        break;

      case 'it_IT':
        return "Condividi su WhatsApp";
        break;

      case 'nl_NL':
        return 'Share on WhatsApp';
        break;

      case 'pl_PL':
        return 'Udostępnij przez WhatsApp';
        break;

      case 'pt_PT':
        return 'Partilhe no WhatsApp';
        break;

      case 'ro_RO':
        return 'Distribuie pe WhatsApp';
        break;

      default:
        return 'Share on WhatsApp';
    }
  }

  public static function getShareWhatsappWeb($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Dieser Button funktioniert nur auf dem Handy. Von einem Computer aus können Sie <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a> verwenden.';
        break;

      case 'fr_FR':
        return 'Ce bouton fonctionne uniquement sur mobile. Depuis un ordinateur, vous pouvez utiliser <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'da_DK':
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'el_GR':
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'es_ES':
        return 'Este botón funciona solo en dispositivos móviles o tablets. Si te conectas desde un ordenador, podrás utilizar <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'it_IT':
        return 'Questo bottone funziona solo via smartphone o tablet. Se stai usando un computer puoi utilizzare <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'nl_NL':
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'pl_PL':
        return 'Ten przycisk działa tylko na urządzeniach mobilnych. Na komputerze możesz skorzystać z <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'pt_PT':
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      case 'ro_RO':
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
        break;

      default:
        return 'This button works only on mobile. From a computer, you can use <a href="https://web.whatsapp.com/send?text={$twitter_share_text}%20-%20{$url_campaign}utm_medium=whatsapp-web%26utm_source={$share_utm_source}%26utm_campaign={$utm_campaign}">Whatsapp web</a>.';
    }
  }

  /**
   * Get default content of message for new user
   * @param $locale
   *
   * @return mixed|string
   */
  public static function getMessageNew($locale) {
    $filename = dirname(__FILE__).'/../../../templates/CRM/Speakcivi/Page/ConfirmationMessageNew.'.$locale.'.tpl';
    $default = dirname(__FILE__).'/../../../templates/CRM/Speakcivi/Page/ConfirmationMessageNew.tpl';
    return self::getMessageContent($filename, $default);
  }

  /**
   * Get content of message (file) for localized version or default in case of localized isn't exist
   * @param string $filename Path to localize filename
   * @param string $default Path to default filename
   *
   * @return string
   */
  public static function getMessageContent($filename, $default) {
    $content = '';
    if (file_exists($filename)) {
      $content = implode('', file($filename));
    } elseif (file_exists($default)) {
      $content = implode('', file($default));
    }
    return $content;
  }
}
