<?php

class CRM_Speakcivi_Logic_Survey {

  /**
   * @param $param
   *
   * @return string
   */
  public static function prepareDetails($param): string {
    if (property_exists($param, 'survey_responses') && $param->survey_responses != '') {
      return json_encode($param->survey_responses);
    }

    return '';
  }

}
