<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_Confirm extends CRM_Core_Page {
  function run() {
     $id=CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
     $hash=CRM_Utils_Request::retrieve('hash', 'String', $this, TRUE);
     $hash1= sha1(CIVICRM_SITE_KEY.$cgid);
     if ($hash !== $hash) 
      CRM_Core_Error::fatal("hash not matching");
    $result = civicrm_api3('GroupContact', 'get', array(
      'sequential' => 1,
      'contact_id' => $id,
      'group_id' => 42,
      'status' => "Pending"
    ));

    civicrm_api3('GroupContact','create',array('id'=>$result["id"]
      ,'status'=>"Added"));
   $url= "/post_confirm";
   return CRM_Utils_System::redirect($url);

    parent::run();
  }
}
