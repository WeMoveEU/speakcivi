<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_BSD extends CRM_Core_Page {
  function run() {

    header('HTTP/1.1 421 Not working');

    echo json_encode (array (
      "error"=>"not_implemented",
      "error_description"=>"Please retry later"
     ));
  }
    if (!$_POST)
      die ("missing POST PARAM");
    CRM_Core_Error::debug_var("POST",$_POST,true,true);
    print_r($_POST);

    die ("Should I be here?");
//    parent::run();
  }
}
