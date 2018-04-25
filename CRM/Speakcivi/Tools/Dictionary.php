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
   * Get sender email for confirmation mail
   * @param string $locale
   *
   * @return string
   */
  public static function getSenderMail($locale) {
    switch ($locale) {
      case 'de_DE':
        return '"Jörg Rohwedder - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'fr_FR':
        return '"Mika Leandro - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'en_GB':
        return '"David - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'es_ES':
        return '"Virginia - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'it_IT':
        return '"Olga - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'pl_PL':
        return '"Julia - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      case 'ro_RO':
        return '"Doina - WeMove.EU" &lt;info@wemove.eu&gt;';
        break;

      default:
        return CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'from');
    }
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
        return 'Confirmez votre action';
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
        return 'You are almost done - please confirm your action';
        break;

      case 'ro_RO':
        return 'Ultimul pas: confirmă acțiunea';
        break;

      default:
        return 'You are almost done - please confirm your action';
    }
  }


  /**
   * Get subject of email for impact mail (when contact is already confirmed)
   * @param string $locale
   *
   * @return string
   */
  public static function getSubjectImpact($locale) {
    switch ($locale) {
      case 'de_DE':
        return 'Bitte helfen Sie mit, diese Aktion weiterzuverbreiten.';
        break;

      case 'fr_FR':
        return 'Démultipliez votre impact';
        break;

      case 'da_DK':
        return "You are almost done - now multiply your impact";
        break;

      case 'el_GR':
        return "You are almost done - now multiply your impact";
        break;

      case 'es_ES':
        return 'Ya casi has terminado. Ahora multiplica el impacto de tu acción.';
        break;

      case 'it_IT':
        return "Moltiplica l'impatto della tua azione";
        break;

      case 'nl_NL':
        return "You are almost done - now multiply your impact";
        break;

      case 'pl_PL':
        return 'Prawie skończone - powiadom znajomych o petycji';
        break;

      case 'pt_PT':
        return "You are almost done - now multiply your impact";
        break;

      case 'ro_RO':
        return "Următorul pas: dă de veste!";
        break;

      default:
        return 'You are almost done - now multiply your impact';
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
        return "Share on Facebook";
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
        return 'Share on Twitter';
        break;

      case 'ro_RO':
        return 'Distribuie pe Twitter';
        break;

      default:
        return 'Share on Twitter';
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
   * Get default content of message for current user
   * @param $locale
   *
   * @return mixed|string
   */
  public static function getMessageCurrent($locale) {
    $filename = dirname(__FILE__).'/../../../templates/CRM/Speakcivi/Page/ConfirmationMessageCurrent.'.$locale.'.tpl';
    $default = dirname(__FILE__).'/../../../templates/CRM/Speakcivi/Page/ConfirmationMessageCurrent.tpl';
    return self::getMessageContent($filename, $default);
  }


  /**
   * Get content of message (file) for localized version or default in case of localized isn't exist
   * @param string $filename Path to localize filename
   * @param string $default Path to default filename
   *
   * @return string
   */
  private static function getMessageContent($filename, $default) {
    $content = '';
    if (file_exists($filename)) {
      $content = implode('', file($filename));
    } elseif (file_exists($default)) {
      $content = implode('', file($default));
    }
    return $content;
  }
}
