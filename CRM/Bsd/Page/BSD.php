<?php

require_once 'CRM/Core/Page.php';

class CRM_Bsd_Page_BSD extends CRM_Core_Page {
  function run() {

  $param=json_decode (file_get_contents('php://input') );

    header('HTTP/1.1 503 Men at work');

    echo json_encode (array (
      "error"=>"not_implemented",
      "error_description"=>"Please retry later"
     ));
    if (!$param)
      die ("missing POST PARAM");

    // TODO Lookup
    $group_id=42;
    $campaign_id=3;

    $h=$param->cons_hash;
       //,'api.email.create'=>array('email'=>$h->emails[0]->email,'is_primary'=>1)

    if ($param->action_type == 'share') {
      CRM_Core_Error::debug_var("api result",$param,true,true);
      return; 
    } 

    $contact=array(
       'sequential' => 1,
       'contact_type' => 'Individual',
       'source' => 'speakout '.$param->action_type . ' '.$param->external_id, 
       'first_name' => $h->firstname
       ,'last_name'=>$h->lastname
       ,'email'=>$h->emails[0]->email
       ,'api.address.create'=>array('postal_code'=>$h->addresses[0]->zip,'is_primary'=>1,'location_type_id'=>1)
       ,'api.group_contact.create'=>array('group_id'=>$group_id,"status"=> "Pending")

    );
    if ($param->action_type == 'petition') {
      $contact['api.activity.create'] = array(
      'source_contact_id'=>'$value.id'
      ,'source_record_id' => $campaign_id
      ,'campaign'=> $campaign_id
      ,"activity_type_id"=> "Petition"
      ,"activity_date_time"=> $param->create_dt
      ,'subject' => $param->action_name
      ,"location"=> $param->action_technical_type
      );
    } else {
      return;
    }

    CRM_Core_Error::debug_var("contact",$contact,true,true);
    $r=civicrm_api3('contact','create',$contact);

    if ( 9 == $param->external_id)
      return; // test campaign for load, no email sent

    CRM_Core_Error::debug_var("api result",$r,true,true);
    if (!$r["is_error"])
      $tplid=69;
      if ($param->external_id == 8) {
        $tplid=70;
      }
      $s=civicrm_api3("speakout","sendconfirm", array(
        'sequential' => 1,
        'messageTemplateID' => $tplid,
        'toEmail' => $h->emails[0]->email,
         'contact_id' => $r["id"]
      ));
    CRM_Core_Error::debug_var("mail",$sr,true,true);
     
//    parent::run();
  }


}
