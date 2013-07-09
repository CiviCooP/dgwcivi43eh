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

      
      
      $this->addSelectOther('hov', 'Huurovereenkomst', array(), array(), true);
	  $hovs = $this->getElement('hov_id');
	  $hovs = $this->getHuurOvereenkomsten($this->_contactIds[0], $hovs);
	  
      $this->addDate('verwachte_einddatum', 'Verwachte einddatum', true, array('formatType' => 'activityDate'));	
      $this->addDefaultButtons($label, 'done', 'cancel');
    }
    else {
      $this->addDefaultButtons($label, 'done');
    }
  }
  
  protected function getHuurOvereenkomsten($contact_id, $hovs) {
  	$hovs->addOption( '- Selecteer een huurovereenkomst -', '');
  	
  	$gid = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomGroupByName('Huurovereenkomst');
  	$values = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomValuesForContactAndCustomGroupSorted($contact_id, $gid);
  	
  	$case_type_id = $this->getCaseTypeId('DossierOpzeggingHuurcontract');
  	$already_case = array();
  	if ($case_type_id) {
  		$result = civicrm_api('Case', 'get', array('version'=>3, 'contact_id'=>$contact_id, 'case_type_id' => $case_type_id));
  		if (isset($result['values']) && is_array($result['values'])) {
  			foreach($result['values'] as $val) {
  				$custom_values = civicrm_api('CustomValue', 'get', array('version'=>3, 'entity_table'=>'', 'entity_id'=>$val['id'], 'return.einde_huurcontract:hov_nr'=>1));
  				if (isset($custom_values['values']) && is_array($custom_values['values'])) {
  					foreach($custom_values['values'] as $custom_value) {
  						$already_case[] = $custom_value['latest'];
  					}
  				}
  			}
  		}
  	}
  	
  	
  	foreach($values as $id => $value) {
  		if (!in_array($value['HOV_nummer_First'], $already_case)) {
  			$hovs->addOption($value['VGE_adres_First'].' (HOV: '.$value['HOV_nummer_First'].', VGE: '.$value['VGE_nummer_First'].')', $id);
  		}
  	}
  	
  	return $hovs;
  }
  
  protected function getCaseTypeId($case) {
  	$option_group = civicrm_api('OptionGroup', 'getsingle', array('name' => 'case_type', 'version' => '3'));
  	$option_group_id = false;
  	if (isset($option_group['id'])) {
  		$option_group_id = $option_group['id'];
  	} else {
  		return false;
  	}
  	$option_value = civicrm_api('OptionValue', 'getsingle', array('option_group_id' => $option_group_id, 'name' => $case, 'version' => '3'));
  	$option_value_id = false;
  	$option_value_value = false;
  	if (isset($option_value['id'])) {
  		$option_value_id = $option_value['id'];
  		$option_value_value = $option_value['value'];
  	}
  	
  	return $option_value_value;
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
				
		$hov_id = $this->getSubmitValue('hov_id');
		$gid = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomGroupByName('Huurovereenkomst');
		$values = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomValuesForContactAndCustomGroupSorted($cid, $gid);
		$hov = false; 
		foreach($values as $id => $value) {
			if ($id == $hov_id) {
				$hov = $value;
				break;
			}
		}
		
		$einddatum = $this->getSubmitValue('verwachte_einddatum');
		$begindatum = $hov['Begindatum_HOV'];
		$begindatum = date('Ymd', strtotime($begindatum));
		$einddatum = date('Ymd', strtotime($einddatum));
		
		$urlParams = "reset=1&cid=".$cid;
		$urlString = 'civicrm/contact/view';
		
		$params['version'] = 3;
		$params['contact_id'] = $cid;
		$params['case_type'] = 'DossierOpzeggingHuurcontract';
		$params['subject']  = 'Opzegging huurcontract';
		
		$result = civicrm_api('Case', 'create', $params);
		
		if ($result['is_error'] == '0' && $result['id']) {
			$case_id = $result['id'];
			
			$custom_group_id = false;
			unset($params);
			$params['version']  = 3;
			$params['name'] = 'einde_huurcontract';
			$result = civicrm_api('CustomGroup', 'getsingle', $params);
			if (isset($result['id'])) {
				$custom_group_id = $result['id'];
			}
			
			unset($params);
			$params['version'] = 3;
			$params['entity_id'] = $case_id;
			
			$hov_nr_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('hov_nr', $custom_group_id);
			$params['custom_'.$hov_nr_field['id']] = $hov['HOV_nummer_First'];
			$hov_start_datum_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('hov_start_datum', $custom_group_id);
			$params['custom_'.$hov_start_datum_field['id']] = $begindatum;
			$vge_nr_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('vge_nr', $custom_group_id);
			$params['custom_'.$vge_nr_field['id']] = $hov['VGE_nummer_First'];
			$vge_adres_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('vge_adres', $custom_group_id);
			$params['custom_'.$vge_adres_field['id']] = $hov['VGE_adres_First'];
			$verwachte_eind_datum_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('verwachte_eind_datum', $custom_group_id);
			$params['custom_'.$verwachte_eind_datum_field['id']] = $einddatum;
			
			$hoofdhurder_first = CRM_Utils_DgwMutatieprocesUtils::getPersoonsnummerFirstByRelation($cid, 'Hoofdhuurder');
			if ($hoofdhurder_first) {
				$hoofdhuurder_first_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('hoofdhuurder_first', $custom_group_id);
				$params['custom_'.$hoofdhuurder_first_field['id']] = $hoofdhurder_first;
			}
			
			$medehuurder_first = CRM_Utils_DgwMutatieprocesUtils::getPersoonsnummerFirstByRelation($cid, 'Medehuurder');
			if ($medehuurder_first) {
				$medehuurder_first_field = CRM_Utils_DgwMutatieprocesUtils::retrieveCustomFieldByName('medehuurder_first', $custom_group_id);
				$params['custom_'.$medehuurder_first_field['id']] = $medehuurder_first;
			}
			
			$result = civicrm_api('CustomValue', 'Create', $params);
			
			$tag_id = CRM_Utils_DgwMutatieprocesUtils::createTag('Huuropzegging ontvangen');
			if ($tag_id !== false) {
				CRM_Utils_DgwMutatieprocesUtils::addTag($tag_id, $cid);
				$hoofdhurder_id = CRM_Utils_DgwMutatieprocesUtils::getContactIdByRelation($cid, 'Hoofdhuurder');
				if ($hoofdhurder_id) {
					CRM_Utils_DgwMutatieprocesUtils::addTag($tag_id, $hoofdhurder_id);
				}
				$medehuurder_id = CRM_Utils_DgwMutatieprocesUtils::getContactIdByRelation($cid, 'Medehuurder');
				if ($medehuurder_id) {
					CRM_Utils_DgwMutatieprocesUtils::addTag($tag_id, $medehuurder_id);
				}
			}
		
			$urlString = 'civicrm/contact/view/case';
			$urlParams = "reset=1&id=".$case_id."&cid=".$cid."&action=view";
		}
	}
	
	$session->replaceUserContext(CRM_Utils_System::url($urlString, $urlParams));
  }
  //end of function
  
}

