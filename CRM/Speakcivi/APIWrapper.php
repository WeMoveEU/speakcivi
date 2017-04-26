<?php

class CRM_Speakcivi_APIWrapper implements API_Wrapper {

  public function fromApiInput($apiRequest) {
    return $apiRequest;
  }

  public function toApiOutput($apiRequest, $result) {
    CRM_Core_Error::debug_var('$apiRequest', $apiRequest);
    CRM_Core_Error::debug_var('$result', $result);
    return $result;
  }
}
