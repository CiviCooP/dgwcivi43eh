<?php

require_once 'sync.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function sync_civicrm_config(&$config) {
  _sync_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function sync_civicrm_xmlMenu(&$files) {
  _sync_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function sync_civicrm_install() {
  return _sync_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function sync_civicrm_uninstall() {
  return _sync_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 */
function sync_civicrm_enable() {
    /*
     * check if extension org.civicoop.dgw.custom enabled. Is required
     */

    /*
     * check if required MySQL tables exist. If not, create
     */

  return _sync_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function sync_civicrm_disable() {
  return _sync_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function sync_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sync_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function sync_civicrm_managed(&$entities) {
  return _sync_civix_civicrm_managed($entities);
}
/**
 * Implementation of hook_civicrm_pre
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 * - check for changes to individual or organization that requires
 *   synchronization work.
 *
 */
function sync_civicrm_pre( $op, $objectName, $objectId, &$objectRef ) {
    /*
     * only for some objects
     */
    $syncedObjects = array( "Individual", "Organization", "Address", "Email", "Phone" );
    if (in_array( $objectName, $syncedObjects ) ) {
        /*
         * check if sync action is required
         */
        $syncRequired = _checkSyncRequired ( $objectName, $objectId, $objectRef );
        /*
         * if syncAction is required, process sync
         */
        if ( $syncRequired ) {
            switch ( $objectName ) {
                case "Individual":
                    $syncResult = _syncIndividual( $objectId, $objectRef );
                    break;
                case "Organization":
                    $syncResult = _syncOrganization( $objectId, $objectRef );
                    break;
                case "Address":
                    $syncResult = _syncAddress( $objectId, $objectRef );
                    break;
                case "Email":
                    $syncResult = _syncEmail( $objectId, $objectRef );
                    break;
                case "Phone":
                    $syncResult = _syncPhone( $objectId, $objectRef );
                    break;
            }
        }

    }
    return;
}
/**
 * Function to check if synchronization is required
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @params $objectName, $objectId, $objectRef
 * @return $syncRequired (boolean)
 */
function _checkSyncRequired( $objectName, $objectId, $objectRef ) {
    $syncRequired = false;

    $apiParams = array(
        'version'   => 3,
        'id'        => $objectId
    );
    if ( $objectName == "Individual" || $objectName == "Organization" ) {
        $resultCheck = civicrm_api( 'Contact', 'Getsingle', $apiParams );
    } else {
        $resultCheck = civicrm_api( $objectName, 'Getsingle', $apiParams );
    }
    /*
     * return false if error in api
     */
    if ( $resultCheck['is_error'] == 1 ) {
        return $syncRequired;
    }
    /*
     * check fields in object against database fields depending on object
     */
    switch ( $objectName ) {
        case "Individual":
            if ( isset( $resultCheck['gender_id'] ) ) {
                if ( $resultCheck['gender_id'] != $objectRef['gender_id'] ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['first_name'] ) ) {
                if ( $resultCheck['first_name'] != $objectRef['first_name'] ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['middle_name'] ) ) {
                if ( $resultCheck['middle_name'] != $objectRef['middle_name'] ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['last_name'] ) ) {
                if ( $resultCheck['last_name'] != $objectRef['last_name'] ) {
                    $syncRequired = true;
                }
            }
            /*
             * reformat birth date because API and object use diffent formats
             */
            if ( isset( $resultCheck['birth_date'] ) ) {
                if ( strtotime($resultCheck['birth_date'] ) !=
                        strtotime($objectRef['birth_date'] ) ) {
                    $syncRequired = true;
                }
            }
            /*
             * if still no sync required, also check custom field for
             * burgerlijke staat
             */
            if ( !$syncRequired ) {
                require_once 'CRM/Utils/DgwUtils.php';
                $customFieldData = CRM_Utils_DgwUtils::getCustomField ( array( 'label' => "burgerlijke staat") );
                if ( isset( $customFieldData['id'] ) ) {
                    $apiParams = array(
                        'version'   =>  3,
                        'entity_id' =>  $objectId
                    );
                    $customValues = civicrm_api('CustomValue', 'Get', $apiParams );
                    if ( $customValues['is_error'] == 0 ) {
                        foreach ( $customValues['values'] as $customId => $customValue ) {
                            /*
                             * if id of custom field retrieved from API is the same as
                             * the id of the custom field for burgerlijke staat
                             */
                            if ( $customFieldData['id'] == $customValue['id'] ) {
                                require_once 'CRM/Utils/DgwApiUtils.php';
                                $customElement = CRM_Utils_DgwApiUtils::getCustomValueTableElement( $customValue );
                                if ( !empty( $customElement ) ) {
                                    // safe to use array element 0 because it is not a repeating group
                                    $customCompare = "custom_".$customElement[0]['custom_id']."_".$customElement[0]['record_id'];
                                    if ( $customElement[0]['value'] != $objectRef[$customCompare] ) {
                                        $syncRequired = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            break;
        case "Organization":
            if ( isset( $resultCheck['organization_name'] ) ) {
                if ( $resultCheck['organization_name'] != $objectRef->organization_name ) {
                    $syncRequired = true;
                }
            }
            break;
        case "Address":
            if ( isset( $resultCheck['street_address'] ) ) {
                if ( $resultCheck['street_address'] != $objectRef->street_address ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['street_name'] ) ) {
                if ( $resultCheck['street_name'] != $objectRef->street_name ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['street_number'] ) ) {
                if ( $resultCheck['street_number'] != $objectRef->street_number ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['street_unit'] ) ) {
                if ( $resultCheck['street_unit'] != $objectRef->street_unit ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['postal_code'] ) ) {
                if ( $resultCheck['postal_code'] != $objectRef->postal_code ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['city'] ) ) {
                if ( $resultCheck['city'] != $objectRef->city ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['country_id'] ) ) {
                if ( $resultCheck['country_id'] != $objectRef->country_id ) {
                    $syncRequired = true;
                }
            }
            break;
        case "Phone":
            if ( isset( $resultCheck['location_type_id'] ) ) {
                if ( $resultCheck['location_type_id'] != $objectRef->location_type_id ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['phone_type_id'] ) ) {
                if ( $resultCheck['phone_type_id'] != $objectRef->phone_type_id ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['phone'] ) ) {
                if ( $resultCheck['phone'] != $objectRef->phone ) {
                    $syncRequired = true;
                }
            }
            break;
        case "Email":
            if ( isset( $resultCheck['location_type_id'] ) ) {
                if ( $resultCheck['location_type_id'] != $objectRef->location_type_id ) {
                    $syncRequired = true;
                }
            }
            if ( isset( $resultCheck['email'] ) ) {
                if ( $resultCheck['email'] != $objectRef->email ) {
                    $syncRequired = true;
                }
            }
            break;
    }
    return $syncRequired;
}
