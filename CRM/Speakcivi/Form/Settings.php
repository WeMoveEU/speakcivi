<?php
/**
 * Form controller class
 *
 */
class CRM_Speakcivi_Form_Settings extends CRM_Core_Form {

  private $settingFilter = array('group' => 'speakcivi');

  private $submittedValues = array();

  // todo check using of this variable
  private $settings = array();


  function buildQuickForm() {
    $settings = $this->getFormSettings();
    $exclude = array('country_lang_mapping');
    foreach ($settings as $name => $setting) {
      if (isset($setting['quick_form_type']) && !in_array($name, $exclude)) {
        $add = 'add' . $setting['quick_form_type'];
        if ($add == 'addElement') {
          $this->$add($setting['html_type'], $name, ts($setting['title']), CRM_Utils_Array::value('html_attributes', $setting, array ()));
        } else {
          $this->$add($name, ts($setting['title']));
        }
        $this->assign("{$setting['description']}_description", ts('description'));
      }
    }
    $this->addButtons(array(
      array (
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      )
    ));
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->assign('country_lang_mapping_title', $settings['country_lang_mapping']['title']);
    $this->assign('country_lang_mapping', CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'country_lang_mapping'));
    parent::buildQuickForm();
  }


  function postProcess() {
    $this->submittedValues = $this->exportValues();
    $this->saveSettings();
    parent::postProcess();
  }


  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }


  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function getFormSettings() {
    if (empty($this->settings)) {
      $settings = civicrm_api3('Setting', 'getfields', array('filters' => $this->settingFilter));
    }
    $extraSettings = civicrm_api3('Setting', 'getfields', array('filters' => array('group' => 'accountsync')));
    $settings = $settings['values'] + $extraSettings['values'];
    return $settings;
  }


  /**
   * Get the settings we are going to allow to be set on this form.
   *
   * @return array
   */
  function saveSettings() {
    $settings = $this->getFormSettings();
    $values = array_intersect_key($this->submittedValues, $settings);
    civicrm_api3('Setting', 'create', $values);
  }


  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  function setDefaultValues() {
    $existing = civicrm_api3('Setting', 'get', array('return' => array_keys($this->getFormSettings())));
    $defaults = array();
    $domainID = CRM_Core_Config::domainID();
    foreach ($existing['values'][$domainID] as $name => $value) {
      $defaults[$name] = $value;
    }
    return $defaults;
  }

}
