<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       1 June 2011                                  |
| Description   :       Omzetten dossiers van huishouden naar hoofd- |
|						huurder										 |
+--------------------------------------------------------------------+
*/
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranet/sites/all/modules/dgwphp/dgwConvConfig.php' );
/*
 * lezen alle cases uit civicrm_case_contact
 */
$selCaseContactQry = "SELECT * FROM civicrm_case_contact";
$daoCaseContact = CRM_Core_DAO::executeQuery( $selCaseContactQry );

while ( $daoCaseContact->fetch( ) ) {
	/*
	 * check of contact een huishouden is en zo ja, het omzetproces in
	 */
	$selCheckHHQry = "SELECT contact_type FROM civicrm_contact WHERE id = ".
		$daoCaseContact->contact_id;
	$daoCheckHH = CRM_Core_DAO::executeQuery( $selCheckHHQry );
	if ( $daoCheckHH->fetch( ) ) {
		if ( $daoCheckHH->contact_type == "Household" ) {
			
			swopCase( $daoCaseContact->contact_id, $daoCaseContact->case_id );
		}
	}
}
echo "<p>Omzetten cases is klaar!</p>";

function swopCase( $contactID, $caseID ) {
	/*
	 * haal de hoofdhuurder bij de case
	 */
	$selHoofdQry = "SELECT * FROM civicrm_relationship WHERE 
		relationship_type_id = ".RELHOOFD." AND contact_id_b = $contactID"; 
	$daoHoofd = CRM_Core_DAO::executeQuery( $selHoofdQry );
	if ( $daoHoofd->fetch ( ) ) {
		if ( isset ( $daoHoofd->contact_id_a ) ) {
			$hoofdID = $daoHoofd->contact_id_a;
		}
	}
	if ( isset( $hoofdID ) && !empty ($hoofdID ) ) {
		/*
		 * change all relationships that have case ID 
		 */
		$updRelAQry = "UPDATE civicrm_relationship SET contact_id_a = $hoofdID 
			WHERE case_id = $caseID AND contact_id_a = $contactID";
		CRM_Core_DAO::executeQuery( $updRelAQry );	
		/*
		 * retrieve activities for case
		 */
		$selCaseActQry = "SELECT * FROM civicrm_case_activity WHERE case_id = $caseID";
		$daoCaseAct = CRM_Core_DAO::executeQuery( $selCaseActQry ); 
		while ( $daoCaseAct->fetch( ) ) {
			$actID = $daoCaseAct->activity_id;
			/*
			 * update source_contact_id in activities
			 */
			$updActQry = "UPDATE civicrm_activity SET source_contact_id = 
				$hoofdID WHERE id = $actID AND source_contact_id = 
				$contactID";
			CRM_Core_DAO::executeQuery( $updActQry );
			/*
			 * update target_contact_id in activity_target
			 */
			$updTargetQry = "UPDATE civicrm_activity_target SET target_contact_id = 
				$hoofdID WHERE activity_id = $actID AND target_contact_id = 
				$contactID"; 	 
			CRM_Core_DAO::executeQuery( $updTargetQry );	
			/*
			 * update assignee in activity_assignment
			 */
			$updAssignQry = "UPDATE civicrm_activity_assignment SET 
				assignee_contact_id = $hoofdID WHERE activity_id = $actID AND 
				assignee_contact_id = 			$contactID"; 	 
			CRM_Core_DAO::executeQuery( $updAssignQry );	
		}
		/*
		 * update case activity
		 */
		$updCaseActQry = "UPDATE civicrm_case_contact SET contact_id = 
			$hoofdID WHERE case_id = $caseID AND contact_id = $contactID";
		CRM_Core_DAO::executeQuery( $updCaseActQry );	
	}
}
?>
