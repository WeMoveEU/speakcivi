<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_Confirm extends CRM_Core_Page {
  function run() {
     $id=CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
     $hash=CRM_Utils_Request::retrieve('sha1', 'String', $this, TRUE);

    parent::run();
  }
}
