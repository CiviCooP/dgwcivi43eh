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
function custom_civicrm_pre( $op, $objectName, $objectId, &$objectRef ) {
    /*
     * only for some objects
     */
    $syncedObjects = array( "Individual", "Organization", "Address", "Email", "Phone" );
    if (in_array( $objectName, $syncedObjects ) ) {
        /*
         * check if sync action is required
         */
        $syncAction = _checkSyncRequired ( $objectName, $objectId, $objectRef );
        /*
         * if syncAction is required, process sync
         */
        if ( $syncAction ) {
            $syncResult = _processSync ( $objectName, $objectId, $objectRef );
        }
            
    }
    return;

}

