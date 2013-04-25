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
        $result['street_unit'] = ( $splitFields['street_unit'] );
        return $result;
    }
}
