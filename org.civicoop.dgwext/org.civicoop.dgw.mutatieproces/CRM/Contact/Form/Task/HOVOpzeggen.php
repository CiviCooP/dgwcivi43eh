<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class provides the functionality to delete a group of
 * contacts. This class provides functionality for the actual
 * deletion.
 */
class CRM_Contact_Form_Task_HOVOpzeggen extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive',
      $this, FALSE
    );    

    if ($cid) {
      $this->_contactIds = array($cid);
      $this->assign('totalSelectedContacts', 1);
    }
    else {
      parent::preProcess();
    }
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    $label = ts('Huurcontract opzeggen');

    if (isset($this->_contactIds[0])) {
      $session = CRM_Core_Session::singleton();
      $session->replaceUserContext(CRM_Utils_System::url('civicrm/contact/view',
          'reset=1&cid=' . $this->_contactIds[0]
        ));
      $this->addDefaultButtons($label, 'done', 'cancel');
    }
    else {
      $this->addDefaultButtons($label, 'done');
    }
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $session = CRM_Core_Session::singleton();
    $currentUserId = $session->get('userID');

    $urlParams = "reset=1";
    $urlString = 'civicrm/dashboard';
	
	//generate dossier opzeggen huurovereenkomst
	if (isset($this->_contactIds[0])) {
		$cid = $this->_contactIds[0];
		
		$urlParams = "reset=1&cid=".$cid;
		$urlString = 'civicrm/contact/view';
		
		$params['contact_id'] = $cid;
		$params['case_type'] = 'DossierOpzeggingHuurcontract';
		$params['subject']  = 'Opzegging huurcontract';
		$params['version'] = 3;
		$result = civicrm_api('Case', 'create', $params);
		
		if ($result['is_error'] == '0' && $result['id']) {
			$case_id = $result['id'];
		
			$urlString = 'civicrm/contact/view/case';
			$urlParams = "reset=1&id=".$case_id."&cid=".$cid."&action=view";
		}
	}
	
	$session->replaceUserContext(CRM_Utils_System::url($urlString, $urlParams));
  }
  //end of function
  
}

