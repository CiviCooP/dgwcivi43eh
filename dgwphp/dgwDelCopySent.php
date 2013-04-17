<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwDelCopySent.php                     |
+--------------------------------------------------------------------+
| Project       :   Maintenance CiviCRM De Goede Woning              |
| Author        :   Erik Hommel (EE-atWork, erik.hommel@civicoop.org |
| Date          :   21 March 2013                                    |
| Description   :   Incident 20 06 12 004 remove all activities      |
|                   with - copy sent to from database                |      
+--------------------------------------------------------------------+
*/
ini_set('display_errors', '1');
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
require_once 'dgwConfig.php';
/*
 * retrieve all activities with activity_type 3 (email)
 */
$selAct = 
"SELECT id, source_record_id, subject FROM civicrm_activity WHERE activity_type_id = 3";
$daoAct = CRM_Core_DAO::executeQuery( $selAct );
$delArray = array();
while ( $daoAct->fetch() ) {
    /*
     * only remove if source_record_id is not empty and if subject contains
     * - copy sent to at the start
     */
    if ( !empty( $daoAct->source_record_id ) ) {
        if ( trim( substr( $daoAct->subject, 0, 15 ) ) == '- copy sent to') {
            /*
             * remove all targets and assignees for activity
             */
            $delTargets = 
"DELETE FROM civicrm_activity_target WHERE activity_id = {$daoAct->id}";
            CRM_Core_DAO::executeQuery( $delTargets );
            $delAssignees = 
"DELETE FROM civicrm_activity_assignment WHERE activity_id = {$daoAct->id}";            
            CRM_Core_DAO::executeQuery( $delAssignees );
            $delArray[] = $daoAct->id;
        }
    }
}
/*
 * remove activities
 */
foreach ( $delArray as $delActId ) {
    $delAct =
"DELETE FROM civicrm_activity WHERE id = $delActId";
    CRM_Core_DAO::executeQuery( $delAct );
}
echo "<p>Alle activiteiten - copy sent verwijderd";
