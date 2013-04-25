<?php
/*
+--------------------------------------------------------------------+
| Project       :   CiviCRM De Goede Woning - Upgrade CiviCRM 4.3    |
| Author        :   Erik Hommel (CiviCooP, erik.hommel@civicoop.org  |
| Date          :   16 April 20134                                   |
| Description   :   Class with DGW helper functions                  |
+--------------------------------------------------------------------+
*/

/**
*
* @package CRM
* @copyright CiviCRM LLC (c) 2004-2013
* $Id$
*
*/
class CRM_Utils_DgwUtils {
    /**
     * static function to retrieve a custom field ID with a label (passed in
     * params
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @param array with at least the value ['label'] or ['name'] to retrieve id with
     * @return $result array with ['is_error']. If error, then also ['error_message']
     *         if no error, id is passed with ['custom_id']
     */
    static function getCustomFieldId( $params ) {
        $result = array( );       
        /*
         * error if no title and no label in params
         */
        if ( !isset( $params['label'] ) && !isset( $params['name'] ) ) {
            $result['is_error'] = 1;
            $result['error_message'] = "No label or name given in params array";
            return $result;
        }
        $result['is_error'] = 0;
        /*
         * no result if both label and name are empty
         */
        $apiParams = array( 'version' => 3 );
        /*
         * use label if entered
         */
        if ( isset( $params['label'] ) ) {
            $apiParams['label'] = $params['label'];
        } else {
            /*
             * use name if entered
             */
            $apiParams['name'] = $params['name'];
        }
        $customField = civicrm_api( 'CustomField', 'getsingle', $apiParams );
        if ( isset($customField['is_error']) && $customField['is_error'] == '1' ) {
            $result['is_error'] = '1';
            $result['error_message'] = $customField['error_message'];
        } else {
            $result['custom_id'] = "custom_".$customField['id'];
        }
        return $result;
    }
    /**
     * Static function to retrieve a custom field with the Custom Field API
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @params params array
     * @return result array
     */
    static function getCustomField( $params ) {
        $result = array( );
        /*
         * error if no label and no value in params
         */
        if ( !isset( $params['label']) && !isset( $params['value'] ) ) {
            $result['is_error'] = 1;
            $result['error_message'] = "Params need to contain either ['label'] or ['value'] (or both)";
            return $result;
        }
        /*
         * error if dgw_config does not exist
         */
        $dgwConfigExists = CRM_Core_DAO::checkTableExists( 'dgw_config' );
        if ( !$dgwConfigExists ) {
            $result['is_error'] = 1;
            $result['error_message'] = "Configuration table De Goede Woning does not exist";
            return $result;
        }
        /*
         * retrieve value from dgw_config with label if no value in params
         */
        $customLabel = "";
        if ( isset( $params['label'] ) )  {
            $selConfig = "SELECT value FROM dgw_config WHERE label = '{$params['label']}'";
            $daoConfig = CRM_Core_DAO::executeQuery( $selConfig );
            if ( $daoConfig->fetch () ) {
                $customLabel = $daoConfig->value;
            }
        } else {
            if ( isset( $params['value'] ) ) {
                $customLabel = $params['value'];
            }
        }
        $apiParams = array(
            'version'   =>  3,
            'label'     =>  $customLabel
        );
        $customField = civicrm_api( 'CustomField', 'Getsingle', $apiParams );
        if ( isset( $customField['is_error'] ) && $customField['is_error'] == 1 ) {
            $result['is_error'] = 1;
            if ( isset( $customField['error_message'] ) ) {
                $result['error_message'] = $customField['error_message'];
            } else {
                $result['error_message'] = "Unknown error in API entity CustomField action Getsingle";
            }
            return $result;
        }
        $result = $customField;
        return $result;
    }
    /**
     * Static function to parse street_address in NL_nl format
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @params params array
     * @return result array
     */
    static function parseStreetAddressNl( $params ) {
        $result = array( );
        /*
         * error if no street_name in array
         */
        if ( !isset( $params['street_name'] ) ) {
            $result['is_error'] = 1;
            $result['error_message'] = "Parsing of street address requires street_name in params";
            return $result;
        }
        $parsedStreetAddressNl = trim( $params['street_name'] );
        if ( isset( $params['street_number'] ) && !empty( $params['street_number'] ) ) {
            $parsedStreetAddressNl .= " ".$params['street_number'];
        }
        if ( isset( $params['street_unit'] ) && !empty( $params['street_unit'] ) ) {
            $parsedStreetAddressNl .= " ".$params['street_unit'];
        }
        $result['is_error'] = 0;
        $result['parsed_street_address'] = $parsedStreetAddressNl;
        return $result;
    }
    
    /*
     * function to check the date format
    */
    static function checkDateFormat($indate) {
    	/*
    	 * default false
    	*/
    	$valid = false;
    	/*
    	 * if length date = 8, not separated
    	*/
    	if (strlen($indate) == 8) {
    		$year = substr($indate,0,4);
    		$month = substr($indate,4,2);
    		$day = substr($indate, 6, 2);
    	} else {
    		/*
    		 * date parts separated by "-"
    		*/
    		$dateparts = explode("-",$indate);
    		if (isset($dateparts[2]) && !isset($dateparts[3])) {
    			$month = $dateparts[1];
    			if(strlen($dateparts[0]) == 2) {
    				$day = (int) $dateparts[0];
    				$year = (int) $dateparts[2];
    			} else {
    				$day = (int) $dateparts[2];
    				$year = (int) $dateparts[0];
    			}
    		} else {
    			/*
    			 * separated by "/"
    			*/
    			$dateparts = explode("/", $indate);
    			if (isset($dateparts[2]) && !isset($dateparts[3])) {
    				$year = $dateparts[0];
    				$month = $dateparts[1];
    				$day = $dateparts[2];
    			}
    		}
    	}
    	if (isset($year) && isset($month) && isset($day)) {
    		/*
    		 * only valid if all numeric
    		*/
    		if (is_numeric($year) && is_numeric($month) && is_numeric($day)) {
    			if ($month > 0 && $month < 13) {
    				if ($year > 1800 && $year < 2500) {
    					if ($day > 0 && $day < 32) {
    						switch($month) {
    							case 1:
    								$valid = true;
    								break;
    							case 2:
    								if ($day < 30) {
    									$valid = true;
    								}
    								break;
    							case 3:
    								$valid = true;
    								break;
    							case 4:
    								if ($day < 31) {
    									$valid = true;
    								}
    								break;
    							case 5:
    								$valid = true;
    								break;
    							case 6:
    								if ($day < 31) {
    									$valid = true;
    								}
    								break;
    							case 7:
    								$valid = true;
    								break;
    							case 8:
    								$valid = true;
    								break;
    							case 9:
    								if ($day < 31) {
    									$valid = true;
    								}
    								break;
    							case 10:
    								$valid = true;
    								break;
    							case 11:
    								if ($day < 31) {
    									$valid = true;
    								}
    								break;
    							case 12:
    								$valid = true;
    								break;
    						}
    
    					}
    				}
    			}
    		}
    	}
    	return $valid;
    }
}