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
        if ( isset( $params['label'] ) )  {
            $customLabel = self::getDgwConfigValue( $params['label'] );
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
     * Static function to glue street_address in NL_nl format from components
     * (street_name, street_number, street_unit)
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @params params array
     * @return result array
     */
    static function glueStreetAddressNl( $params ) {
        $result = array( );
        /*
         * error if no street_name in array
         */
        if ( !isset( $params['street_name'] ) ) {
            $result['is_error'] = 1;
            $result['error_message'] = "Glueing of street address requires street_name in params";
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

    /**
     * Static function to split street_address in components street_name,
     * street_number and street_unit
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @params street_address
     * @return $result array
     */
    static function splitStreetAddressNl ( $streetAddress ) {
        $result = array( );
        $result['is_error'] = 0;
        $result['street_name'] = null;
        $result['street_number'] = null;
        $result['street_unit'] = null;
        /*
         * empty array in return if streetAddress is empty
         */
        if ( empty( $streetAddress ) ) {
            return $result;
        }
        $foundNumber = false;
        $parts = explode( " ", $streetAddress );
        $splitFields = array ( );
        $splitFields['street_name'] = null;
        $splitFields['street_number'] = null;
        $splitFields['street_unit'] = null;
        /*
         * check all parts
         */
        foreach ( $parts as $key => $part ) {
            /*
             * if part is numeric
             */
            if ( is_numeric( $part ) ) {
                /*
                 * if key = 0, add to street_name
                 */
                if ( $key == 0 ) {
                    $splitFields['street_name'] .= $part;
                } else {
                    /*
                     * else add to street_number if not found, else add to unit
                     */
                    if ( $foundNumber == false ) {
                        $splitFields['street_number'] .= $part;
                        $foundNumber = true;
                    } else {
                        $splitFields['street_unit'] .= " ".$part;
                    }
                }
            } else {
                /*
                 * if not numeric and no foundNumber, add to street_name
                 */
                if ( $foundNumber == false ) {
                    /*
                     * if part is first part, set to street_name
                     */
                    if ( $key == 0 ) {
                        $splitFields['street_name'] .= " ".$part;
                    } else {
                        /*
                         * if part has numbers first and non-numbers later put number
                         * into street_number and rest in unit and set foundNumber = true
                         */
                        $length = strlen( $part );
                        if ( is_numeric( substr( $part, 0, 1 ) ) ) {
                            for ( $i=0; $i<$length; $i++ ) {
                                if ( is_numeric( substr( $part, $i, 1 ) ) ) {
                                    $splitFields['street_number'] .= substr( $part, $i, 1 );
                                    $foundNumber = true;
                                } else {
                                    $splitFields['street_unit'] .= substr( $part, $i, 1 );
                                }
                            }
                        } else {
                            $splitFields['street_name'] .= " ".$part;
                        }
                    }
                } else {
                    $splitFields['street_unit'] .= " ".$part;
                }
            }
        }
        $result['street_name'] = trim( $splitFields['street_name'] );
        $result['street_number'] = $splitFields['street_number'];
        $result['street_unit'] = $splitFields['street_unit'];
        return $result;
    }

    /*
     * Function to check format of postcode (Dutch) 1234AA or 1234 AA
    */
    public static function checkPostcodeFormat($postcode) {
    	/*
    	 * if postcode empty, false
    	*/
    	if (empty($postcode)) {
    		return false;
    	}
    	/*
    	 * if length postcode not 6 or 7, error
    	*/
    	if (strlen($postcode) != 6 && strlen($postcode) != 7) {
    		return false;
    	}
    	/*
    	 * split in 2 parts depending on length
    	*/
    	$num = substr($postcode,0,3);
    	if (strlen($postcode == 6)) {
    		$alpha = substr($postcode,4,2);
    	} else {
    		$alpha = substr($postcode,5,2);
    	}
    	/*
    	 * if $num is not numeric, error
    	*/
    	if (!is_numeric(($num))) {
    		return false;
    	}
    	/*
    	 * if $alpha not letters, error
    	*/
    	if (!ctype_alpha($alpha)) {
    		return false;
    	}
    	return true;
    }

    /*
     * Function to check BSN with 11-check
    */
    public static function validateBsn($bsn) {
    	$bsn = trim(strip_tags($bsn));
    	/*
    	 * if bsn is empty, return false
    	*/
    	if (empty($bsn)) {
    		return false;
    	}
    	/*
    	 * if bsn contains non-numeric digits, return false
    	*/
    	if (!is_numeric($bsn)) {
    		return false;
    	}
    	/*
    	 * if bsn has 8 digits, put '0' in front
    	*/
    	if (strlen($bsn) == 8) {
    		$bsn = "0".$bsn;
    	}
    	/*
    	 * if length bsn is not 9 now, return false
    	*/
    	if (strlen($bsn) != 9) {
    		return false;
    	}

    	$digits = array("");
    	/*
    	 * put each digit in array
    	*/
    	$i = 0;
    	while ($i < 9) {
    		$digits[$i] = substr($bsn,$i,1);
    		$i++;
    	}
    	/*
    	 * compute total for 11 check
    	*/
    	$check = 0;
    	$number1 = $digits[0] * 9;
    	$number2 = $digits[1] * 8;
    	$number3 = $digits[2] * 7;
    	$number4 = $digits[3] * 6;
    	$number5 = $digits[4] * 5;
    	$number6 = $digits[5] * 4;
    	$number7 = $digits[6] * 3;
    	$number8 = $digits[7] * 2;
    	$number9 = $digits[8] * -1;
    	$check = $number1 + $number2 + $number3 + $number4 + $number5 + $number6 +
    	$number7 + $number8 + $number9;
    	/*
    	 * divide check by 11 and use remainder
    	*/
    	$remain = $check % 11;
    	if ($remain == 0) {
    		return true;
    	} else {
    		return false;
    	}
    }
    /**
     * Static function to retrieve dgw_config value by label
     *
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @params $label
     * @return $value
     */
    static function getDgwConfigValue( $label ) {
        $value = null;
        if ( empty( $label ) ) {
            return $value;
        }
        $selDgwConfig =
"SELECT value FROM dgw_config WHERE label = '$label'";
        $daoDgwConfig = CRM_Core_DAO::executeQuery( $selDgwConfig );
        if ( $daoDgwConfig->fetch() ) {
            if ( isset( $daoDgwConfig->value ) ) {
                $value = $daoDgwConfig->value;
            }
        }
        return $value;
    }
    /**
     * function to retrieve custom group table name with custom group title
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @param $label
     * @return $tableName
     */
    static function getCustomGroupTableName( $title ) {
        $tableName = "";
        /*
         * return empty name if title empty
         */
        if ( empty( $title ) ) {
            return $tableName;
        }
        $apiParams = array(
            'version'   =>  3,
            'title'     =>  $title
            );
        $customGroup = civicrm_api( 'CustomGroup', 'Getsingle', $apiParams );
        if ( isset( $customGroup[ 'is_error'] ) ) {
            if ( $customGroup['is_error'] == 1) {
                return $tableName;
            }
        }
        if ( isset( $customGroup['table_name'] ) ) {
            $tableName = $customGroup['table_name'];
        }
        return $tableName;
    }
    /**
     * static function to check if contact is hoofdhuurder
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @param $contactId
     * @return $hoofdHuuder boolean
     */
    static function checkContactHoofdhuurder( $contactId ) {
        $hoofdHuurder = false;
        if ( empty( $contactId ) ) {
            return $hoofdHuurder;
        }
        $relHoofdHuurder = self::getDgwConfigValue( 'relatie hoofdhuurder' );
        $relTypeParams = array(
            'version'   =>  3,
            'label_a_b' =>  $relHoofdHuurder
        );
        $relType = civicrm_api( 'RelationshipType', 'Getsingle' , $relTypeParams );
        if ( !isset( $relType['is_error'] ) || $relType['is_error'] == 0 ) {
            $relParams = array(
                'version'               =>  3,
                'relationship_type_id'  =>  $relType['id'],
                'contact_id_a'          =>  $contactId
            );
            $rel = civicrm_api( 'Relationship', 'Getsingle', $relParams );
            if ( !isset( $rel['is_error'] ) || $rel['is_error'] == 0 ) {
                $hoofdHuurder = true;
            }
        }
        return $hoofdHuurder;
    }
    /**
     * static function to retrieve contactId of medehuurder for contact
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @param $huisHoudenId contact_id of huishouden
     * @return $medeHuurders array contact_id, start date and end date of medehuurder
     */
    static function getMedeHuurders( $huisHoudenId ) {
        $medeHuurders = array( );
        if ( empty( $huisHoudenId ) ) {
            return $medeHuurders;
        }
        $relMedeHuurder = self::getDgwConfigValue( 'relatie medehuurder' );
        $relTypeParams = array(
            'version'   =>  3,
            'label_a_b' =>  $relMedeHuurder
        );
        $relType = civicrm_api( 'RelationshipType', 'Get' , $relTypeParams );
        if ( !isset( $relType['is_error'] ) || $relType['is_error'] == 0 ) {
            $relParams = array(
                'version'               =>  3,
                'relationship_type_id'  =>  $relType['id'],
                'contact_id_b'          =>  $huisHoudenId
            );
            $rel = civicrm_api( 'Relationship', 'Get', $relParams );
            if ( !isset( $rel['is_error'] ) || $rel['is_error'] == 0 ) {
                foreach ( $rel['values'] as $relValue ) {
                    $medeHuurder = array( );
                    if ( isset( $relValue['contact_id_a'] ) ) {
                        $medeHuurder['medehuurder_id'] = $relValue['contact_id_a'];
                    }
                    if ( isset( $relValue['start_date'] ) ) {
                        $medeHuurder['start_date'] = $relValue['start_date'];
                    }
                    if ( isset( $relValue['end_date'] ) ) {
                        $medeHuurder['end_date'] = $relValue['end_date'];
                    }
                    if ( !empty( $medeHuurder ) ) {
                        $medeHuurders[] = $medeHuurder;
                    }
                }
            }
        }
        return $medeHuurders;
    }
    /**
     * static function to retrieve contactId of huishouden for contact
     * @author Erik Hommel (erik.hommel@civicoop.org)
     * @param $hoofdHuurderId contact_id of hoofdhuurder
     * @return $huisHoudens array contact_id, start_date, end_date of huishouden
     */
    static function getHuishoudens( $hoofdHuurderId ) {
        $huisHoudens = array( );
        if ( empty( $hoofdHuurderId ) ) {
            return $huisHoudens;
        }
        $relHoofdHuurder = self::getDgwConfigValue( 'relatie hoofdhuurder' );
        $relTypeParams = array(
            'version'   =>  3,
            'label_a_b' =>  $relHoofdHuurder
        );
        $relType = civicrm_api( 'RelationshipType', 'Getsingle' , $relTypeParams );
        if ( !isset( $relType['is_error'] ) || $relType['is_error'] == 0 ) {
            $relParams = array(
                'version'               =>  3,
                'relationship_type_id'  =>  $relType['id'],
                'contact_id_a'          =>  $hoofdHuurderId
            );
            $rel = civicrm_api( 'Relationship', 'Get', $relParams );
            if ( !isset( $rel['is_error'] ) || $rel['is_error'] == 0 ) {
                foreach ( $rel['values'] as $relValue ) {
                    $huisHouden = array( );
                    if ( isset( $relValue['contact_id_b'] ) ) {
                        $huisHouden['huishouden_id'] = $relValue['contact_id_b'];
                    }
                    if ( isset( $relValue['start_date'] ) ) {
                        $huisHouden['start_date'] = $relValue['start_date'];
                    }
                    if ( isset( $relValue['end_date'] ) ) {
                        $huisHouden['end_date'] = $relValue['end_date'];
                    }
                    if ( !empty( $huisHouden ) ) {
                        $huisHoudens[] = $huisHouden;
                    }
                }
            }
        }
        return $huisHoudens;
    }
}