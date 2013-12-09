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
 * Business object for managing custom data groups
 *
 */
class CRM_Core_BAO_HovCustomGroup extends CRM_Core_BAO_CustomGroup {

	/**
   * Build custom data view
   *  @param object  $form page object
   *  @param array   $groupTree associated array
   *  @param boolean $returnCount true if customValue count needs to be returned
   */
  static function buildCustomDataView(&$form, &$groupTree, $returnCount = FALSE, $gID = NULL, $prefix = NULL) {
    foreach ($groupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }
	  
	  $sortings = array();

      foreach ($group['fields'] as $k => $properties) {
        $groupID = $group['id'];
        if (!empty($properties['customValue'])) {
          foreach ($properties['customValue'] as $values) {
            $details[$groupID][$values['id']]['title'] = CRM_Utils_Array::value('title', $group);
            $details[$groupID][$values['id']]['name'] = CRM_Utils_Array::value('name', $group);
            $details[$groupID][$values['id']]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
            $details[$groupID][$values['id']]['help_post'] = CRM_Utils_Array::value('help_post', $group);
            $details[$groupID][$values['id']]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
            $details[$groupID][$values['id']]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
            $details[$groupID][$values['id']]['fields'][$k] = array('field_title' => CRM_Utils_Array::value('label', $properties),
              'field_type' => CRM_Utils_Array::value('html_type',
                $properties
              ),
              'field_data_type' => CRM_Utils_Array::value('data_type',
                $properties
              ),
              'field_value' => self::formatCustomValues($values,
                $properties
              ),
              'options_per_line' => CRM_Utils_Array::value('options_per_line',
                $properties
              ),
            );
			
			if (!in_array($values['id'], $sorting) && $details[$groupID][$values['id']]['fields'][$k]['field_data_type'] =='Date') {
				$date = new Datetime($details[$groupID][$values['id']]['fields'][$k]['field_value']);
				$sorting[$date->getTimestamp()] = $values['id'];
			}
			
            // also return contact reference contact id if user has view all or edit all contacts perm
            if ((CRM_Core_Permission::check('view all contacts') || CRM_Core_Permission::check('edit all contacts'))
              && $details[$groupID][$values['id']]['fields'][$k]['field_data_type'] == 'ContactReference'
            ) {
              $details[$groupID][$values['id']]['fields'][$k]['contact_ref_id'] = CRM_Utils_Array::value('data', $values);
            }
          }
        }
        else {
          $details[$groupID][0]['title'] = CRM_Utils_Array::value('title', $group);
          $details[$groupID][0]['name'] = CRM_Utils_Array::value('name', $group);
          $details[$groupID][0]['help_pre'] = CRM_Utils_Array::value('help_pre', $group);
          $details[$groupID][0]['help_post'] = CRM_Utils_Array::value('help_post', $group);
          $details[$groupID][0]['collapse_display'] = CRM_Utils_Array::value('collapse_display', $group);
          $details[$groupID][0]['collapse_adv_display'] = CRM_Utils_Array::value('collapse_adv_display', $group);
          $details[$groupID][0]['fields'][$k] = array('field_title' => CRM_Utils_Array::value('label', $properties));
        }
      }
    }
	
	if (isset($sorting) && is_array($sorting) && count($sorting) >0) {
		$details2[$groupID] = array();
		krsort($sorting);
		$i=0;
		$details2 = $details;
		unset($details);
		foreach($sorting as $sort => $fid) {
			$details[$groupID][$i] = $details2[$groupID][$fid];
			$i++;
		}
	}

    if ($returnCount) {
      //return a single value count if group id is passed to function
      //else return a groupId and count mapped array
      if (!empty($gID)){
        return count($details[$gID]);
      }
      else {
        foreach( $details as $key => $value ) {
          $countValue[$key] = count($details[$key]);
        }
        return $countValue;
      }
    }
    else {
      $form->assign_by_ref("{$prefix}viewCustomData", $details);
      return $details;
    }
  }

}