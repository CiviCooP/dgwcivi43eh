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
    /*
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
}