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
 */
function sync_civicrm_enable() {
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
 * @param $objectName, $objectId, $objectRef
 * @return $syncRequired (boolean)
 */
function _checkSyncRequired( $objectName, $objectId, $objectRef ) {
    $syncRequired = false;
    /*
     * sync is only required if contact also exists in NCCW First.
     * This is checked against the synchronization table
     */
    $objectInFirst = _checkObjectInFirst( $objectName, $objectId );
    if ( $objectInFirst ) {
        CRM_Core_Error::debug("syncRequired bij aanvang", $syncRequired );
        CRM_Core_Error::debug( "object", $objectName );

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
                    if ( $resultCheck['organization_name'] != $objectRef['organization_name'] ) {
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
    }
    CRM_Core_Error::debug( "syncRequired voor return", $syncRequired );
    exit();
    return $syncRequired;
}
/**
 * Function to check if object exists in First Noa
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @param $objectName, $objectId
 * @return $objectInFirst (boolean)
 */
function _checkObjectInFirst( $objectName, $objectId ) {
    $objectInFirst = false;
    /*
     * no synchronization required for household
     */
    $lowerObject = strtolower( $objectName );
    if ( $lowerObject == "household" ) {
        return $objectInFirst;
    }
    /*
     * retrieve record for object from synchronization table
     */
    if ( $lowerObject == "individual" || $lowerObject == "organization" ) {
        $syncObject = "contact";
    } else {
        $syncObject = $lowerObject;
    }
    require_once "CRM/Utils/DgwUtils.php";
    $customTableTitle = CRM_Utils_DgwUtils::getDgwConfigValue( 'synchronisatietabel first' );
    $customTable = CRM_Utils_DgwUtils::getCustomGroupTableName( $customTableTitle );
    $customField = CRM_Utils_DgwUtils::getCustomField( array('label' => 'sync first entity veld' ) );
    if ( isset( $customField['column_name'] ) ) {
        $customEntityColumn = $customField['column_name'];
        $selSync =
"SELECT COUNT(*) AS aantal FROM $customTable WHERE entity_id = $objectId and $customEntityColumn = '$syncObject'";
        $daoSync = CRM_Core_DAO::executeQuery( $selSync );
        if ( $daoSync->fetch() ) {
            if ( $daoSync->aantal > 0 ) {
                $objectInFirst = true;
            }
        }
    }
    return $objectInFirst;
}
/**
 * Function to add contact to group for synchronization First Noa
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @param $params with contact_id
 * @return none
 */
function _addContactSyncGroup( $contactId ) {
    if ( !empty( $contactId ) ) {
        require_once 'CRM/Utils/DgwUtils.php';
        $groupTitle = CRM_Utils_DgwUtils::getDgwConfigValue( 'groep sync first' );
        $groupParams = array(
            'version'   =>  3,
            'title'     =>  $groupTitle
        );
        $groupData = civicrm_api( 'Group', 'Getsingle', $groupParams );
        if ( !isset( $groupData['is_error'] ) || $groupData['is_error'] == 0 ) {
            $addParams = array(
                'version'       =>  3,
                'contact_id'    =>  $contactId,
                'group_id'      =>  $groupData['id']
            );
            $addResult = civicrm_api( 'GroupContact', 'Create', $addParams );
        }
    }
    return;
}
/**
 * Function to add or update record in synchronization table
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @param $action = operation (create, update or delete)
 * @param $contactId = id of the contact the sync record
 * @param $entityId = CiviCRM id of the entity affected
 * @param $entityName = name of the entity
 * @return none
 */
function _setSyncRecord( $action, $contactId, $entityId, $entityName, $keyFirst = null ) {
    /*
     * return without further processing if one of the params is empty
     */
    if ( empty( $action ) || empty( $contactId ) || empty( $entityId ) || empty( $entityName ) ) {
        return;
    }
    /*
     * retrieve custom_id's of the required fields
     */
    require_once 'CRM/Utils/DgwUtils.php';
    $customLabel = CRM_Utils_DgwUtils::getDgwConfigValue( 'first sync action veld' );
    $customId = CRM_Utils_DgwUtils::getCustomFieldId( array( 'label' => $customLabel ) );
    $actionFldName = "custom_".$customId;

    $customLabel = CRM_Utils_DgwUtils::getDgwConfigValue( 'first sync entity veld' );
    $customId = CRM_Utils_DgwUtils::getCustomFieldId( array( 'label' => $customLabel ) );
    $entityFldName = "custom_".$customId;

    $customLabel = CRM_Utils_DgwUtils::getDgwConfigValue( 'first sync entity_id veld' );
    $customId = CRM_Utils_DgwUtils::getCustomFieldId( array( 'label' => $customLabel ) );
    $entityIdFldName = "custom_".$customId;

    $customLabel = CRM_Utils_DgwUtils::getDgwConfigValue( 'first sync key_first veld' );
    $customId = CRM_Utils_DgwUtils::getCustomFieldId( array( 'label' => $customLabel ) );
    $keyFirstFldName = "custom_".$customId;

    $customLabel = CRM_Utils_DgwUtils::getDgwConfigValue( 'first sync change_date veld' );
    $customId = CRM_Utils_DgwUtils::getCustomFieldId( array( 'label' => $customLabel ) );
    $changeDateFldName = "custom_".$customId;

    /*
     * process based on action
     */
    $customValueParams = array(
        'version'   =>  3,
        'entity_id' =>  $contactId
    );
    switch( $action ) {
        case "create":
            $customValueParams[$actionFldName] = "ins";
            $customValueParams[$entityFldName] = $entityName;
            $customValueParams[$entityIdFldName] = $entityId;
            $customValueParams[$changeDateFldName] = date('Ymd');
            $resultSync = civicrm_api( 'CustomValue', 'Create', $customValueParams );
            break;
        case "update":
            /*
             * retrieve record id of the relevant custom group record
             */
            $customRecordId = _retrieveSyncRecordId ( $entityName, $contactId, $entityId, $entityFldName, $entityIdFldName );
            if ( $customRecordId != 0 ) {
                $customValueParams[$actionFldName.":".$customRecordId] = "upd";
                if ( isset( $keyFirst ) ) {
                    $customValueParams[$keyFirstFldName.":".$customRecordId] = $keyFirst;
                }
                $customValueParams[$changeDateFldName.":".$customRecordId] = date('Ymd');
                $resultSync = civicrm_api( 'CustomValue', 'Create', $customValueParams );
            }
            break;
        case "delete":
            $customRecordId = _retrieveSyncRecordId ( $entityName, $contactId, $entityId, $entityFldName, $entityIdFldName );
            if ( $customRecordId != 0 ) {
                $customValueParams[$actionFldName.":".$customRecordId] = "del";
                $customValueParams[$changeDateFldName.":".$customRecordId] = date('Ymd');
                $resultSync = civicrm_api( 'CustomValue', 'Create', $customValueParams );
            }
    }
    return;
}
/**
 * function to retrieve the sync table custom table id. This should really
 * be possible with the API, but probably no time to fix in the core API
 */
function _retrieveSyncRecordId( $entityName, $contactId, $entityId, $entityFldName, $entityIdFldName ) {
    $recordId = 0;
    if ( empty( $entityName ) || empty( $contactId ) || empty( $entityId ) ) {
        return $recordId;
    }
    $customTableTitle = CRM_Utils_DgwUtils::getDgwConfigValue( 'synchronisatietabel first' );
    $customTable = CRM_Utils_DgwUtils::getCustomGroupTableName( $customTableTitle );
    $selRecord =
"SELECT id FROM $customTable WHERE entity_id = $contactId AND $entityFldName = '$entityName' AND $entityIdFldName = $entityId";
    $daoRecord = CRM_Core_DAO::executeQuery( $selRecord );
    if ( $daoRecord->fetch() ) {
        $recordId = $daoRecord->id;
    }
    return $recordId;
}
