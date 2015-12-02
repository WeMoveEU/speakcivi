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
    if (
      array_key_exists($locale, $this->emailGreetingIds) &&
      array_key_exists($genderShortcut, $this->emailGreetingIds[$locale])
    ) {
      return $this->emailGreetingIds[$locale][$genderShortcut];
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
        return 'Vous avez presque terminé';
        break;

      case 'es_ES':
        return 'Ya casi has terminado. Confirma tu acción por favor.';
        break;

      case 'it_IT':
        return 'Hai quasi finito';
        break;

      case 'pl_PL':
        return 'Prawie skończone - potwierdź podpisanie petycji';
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
        return 'Sie sind fast fertig. Bitte helfen Sie nun mit, diese Aktion weiterzuverbreiten.';
        break;

      case 'fr_FR':
        return 'Démultipliez votre impact';
        break;

      case 'es_ES':
        return 'Ya casi has terminado. Ahora multiplica el impacto de tu acción.';
        break;

      case 'it_IT':
        return "Moltiplica l'impatto della tua azione";
        break;

      case 'pl_PL':
        return 'Prawie skończone - powiadom znajomych o petycji';
        break;

      default:
        return 'You are almost done - now multiply your impact';
    }
  }
}
