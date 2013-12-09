<?php

require_once 'CRM/Core/Page.php';

class CRM_Custom_Page_LaadKov extends CRM_Core_Page {

  function run() {
    CRM_Utils_System::setTitle(ts('Laden koopovereenkomsten'));
    $this->assign('processUrl', CRM_Utils_System::url('civicrm/processkov', null, true));
    parent::run();
  }
}