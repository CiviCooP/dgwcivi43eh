<?php
/*
+--------------------------------------------------------------------+
| Project       :   CiviCRM at De Goede Woning                       |
| Author        :   Erik Hommel (CiviCooP, erik.hommel@civicoop.org  |
| Date          :   11 March 2013                                    |
| Description   :   Class with DGW helper functions                  |
+--------------------------------------------------------------------+
*/

/**
*
* @package CRM
* @copyright CiviCRM LLC (c) 2004-2010
* $Id$
*
*/
class CRM_Utils_DgwUtils {
    /*
     * function to retrieve all groups of the current user and check
     * if user Admin, Consulent and Dir/Best (DGW31 and incident 14 01 13 003)
     */
    static function getGroupsCurrentUser ( $params ) {
        $resultParams = array( );
        /*
         * check if we are in test or prod environment so we check for the
         * right group numbers
         */
        $environment = self::checkEnvironment();
        
        if ( !isset( $params['user_id'] ) || empty( $params['user_id'] ) ) {
            $resultParams['is_error'] = 1;
            $resultParams['error_message'] = "No user_id passed or user_id empty";
            return $resultParams;
        }
        $userID  = $params[ user_id];
        require_once('api/v2/GroupContact.php');
        $groupParms = array (
            'version'       =>  2,
            'contact_id'    =>  $userID);
        $userGroups = civicrm_group_contact_get( $groupParms );
        if ( civicrm_error( $userGroups ) ) {
            $resultParams['is_error'] == 1;
            $resultParams['error_message'] == "Error from group_contact API: ".$userGroups['error_message'];
            return $resultParams;
        }
        $resultParams['dirbest'] = false;
        $resultParams['wijk'] = false;
        $resultParams['admin'] = false;
        $resultParams['groups'] = $userGroups;
        foreach( $userGroups as $keyGroup => $userGroup ) {
            if ( $environment === "test" ) {
                if ( $userGroup['group_id'] == 28 ) {
                    if ( isset( $params['is_dirbest'] ) && $params['is_dirbest'] == 1 ) {
                        $resultParams['dirbest'] = true;
                    }
                }
            } else {
                if ( $userGroup['group_id'] == 24 ) {
                    if ( isset( $params['is_dirbest'] ) && $params['is_dirbest'] == 1 ) {
                        $resultParams['dirbest'] = true;
                    }
                }
            }
            if ( $userGroup['group_id'] == 18 ) {
                if ( isset( $params['is_wijk'] ) && $params['is_wijk'] == 1 ) {
                    $resultParams['wijk'] = true;
                }
            }
            if ( $userGroup['group_id'] == 1 ) {
                if ( isset( $params['is_admin'] ) && $params['is_admin'] == 1 ) {
                    $resultParams['admin'] = true;
                }
            }
        }
        return $resultParams;
    }
    /*
     * function to determine if we are in test or prod environment
     * @params - none
     * @return - $environment containing "test" or "prod"
     */
    static function checkEnvironment( ) {
        if ( !$config ) {
            $config = CRM_Core_Config::singleton( );
        }
        if ( $config->userFrameworkBaseURL === "http://insitetest2/" ) {
            $environment = "test";
        } else {
            $environment = "prod";
        }
        return $environment;
    }
    /*
     * function to set greeting_display
     * @params - $genderId, $lastName, $middleName
     * @return - array $displayGreetings
     */
    static function setDisplayGreetings( $genderId, $middleName, $lastName ) {
        $displayGreetings = array();
        /*
         * nothing to do if all params are empty
         */
        if ( empty( $genderId ) && empty( $lastName ) && empty( $middleName ) ) {
            $displayGreetings['is_error'] = 1;
            $displayGreetings['error_message'] = "All params are empty, nothing to do in function";
            return $displayGreetings;
        }
        $displayGreetings['is_error'] = 0;
        $greetings = null;
        switch( $genderId ) {
            case 1:
                $greetings = "Geachte mevrouw ";
                break;
            case 2:
                $greetings = "Geachte heer ";
                break;
            case 3:
                $greetings = "Geachte mevrouw/heer ";
                break;
            default:
                $greetings = "";
                break;
        }
        if ( !empty( $middleName ) ) {
            if ( !is_null( $middleName ) && strtolower( $middleName ) !== 'null' ) {
                $greetings .= strtolower( $middleName )." ";
            }
        }
        if ( !empty( $lastName ) ) {
            if ( !is_null( $lastName ) && strtolower( $lastName ) !== 'null' ) {
                $greetings .= $lastName;
            }
        }
        $displayGreetings['greetings'] = $greetings;
        return $displayGreetings;
    }
}