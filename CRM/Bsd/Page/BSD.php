<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_BSD extends CRM_Core_Page {
  function run() {

  $param=json_decode (file_get_contents('php://input') );

  CRM_Core_Error::debug_var("BSD", array($param ,$_GET), true, true);


    header('HTTP/1.1 421 Not working');

    echo json_encode (array (
      "error"=>"not_implemented",
      "error_description"=>"Please retry later"
     ));
    if (!$param)
      die ("missing POST PARAM");
    $h=$param->cons_hash;
       //,'api.email.create'=>array('email'=>$h->emails[0]->email,'is_primary'=>1)
    $contact=array(
       'contact_type' => 'Individual',
       'source' => 'speakout '.$param->action_type . ' '.$param->external_id, 
       'first_name' => $h->firstname
       ,'last_name'=>$h->lastname
       ,'email'=>$h->emails[0]->email
       ,'api.address.create'=>array('postal_code'=>$h->addresses[0]->zip,'is_primary'=>1,'location_type_id'=>1)
       ,'api.group_contact.create'=>array('group_id'=>42,"status"=> "Pending")

    );
    CRM_Core_Error::debug_var("contact",$contact,true,true);
    $r=civicrm_api3('contact','create',$contact);
    CRM_Core_Error::debug_var("api result",$r,true,true);
     
    die ("Should I be here?");
//    parent::run();
  }
}
