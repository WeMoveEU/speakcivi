<?php

class CRM_Speakcivi_Tools_Dictionary {

  /**
   * Get salutation based on given gender
   * @param string $locale
   * @param string $genderShortcut Female -> F or Male -> M
   *
   * @return mixed
   */
  public static function getSalutation($locale, $genderShortcut) {
    if ($genderShortcut == 'F') {
      return self::getSalutationFemale($locale);
    } elseif ($genderShortcut == 'M') {
      return self::getSalutationMale($locale);
    }
    return self::getSalutationUnspecific($locale);
  }


  /**
   * Get salutation for female
   * @param string $locale
   *
   * @return string
   */
  public static function getSalutationFemale($locale) {
    $array = array(
      'de_DE' => 'Liebe',
      'es_ES' => 'Hola',
      'fr_FR' => 'Chère',
      'it_IT' => 'Cara',
    );
    $default = 'Dear';
    return self::getValue($locale, $array, $default);
  }


  /**
   * Get salutation for male
   * @param string $locale
   *
   * @return string
   */
  public static function getSalutationMale($locale) {
    $array = array(
      'de_DE' => 'Lieber',
      'es_ES' => 'Hola',
      'fr_FR' => 'Chèr',
      'it_IT' => 'Caro',
    );
    $default = 'Dear';
    return self::getValue($locale, $array, $default);
  }


  /**
   * Get salutation for unspecific gender
   * @param string $locale
   *
   * @return string
   */
  public static function getSalutationUnspecific($locale) {
    $array = array(
      'de_DE' => 'Hallo',
      'es_ES' => 'Hola',
      'fr_FR' => 'Bonjour',
      'it_IT' => 'Ciao',
    );
    $default = 'Dear';
    return self::getValue($locale, $array, $default);
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
