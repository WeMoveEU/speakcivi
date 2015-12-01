<?php

class CRM_Speakcivi_Tools_Dictionary {


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


  private static function getValue($locale, $array, $default) {
    if (array_key_exists($locale, $array)) {
      return $array[$locale];
    }
    return $default;
  }
}
