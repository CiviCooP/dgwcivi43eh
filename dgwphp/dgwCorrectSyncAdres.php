<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       3 June 2011                                  |
| Description   :       Corrigeren adres        					 |
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
 * lezen alle contacten met een persoonsnummer
 */
$selPersQry = "SELECT * FROM civicrm_value_aanvullende_persoonsgegevens_1 
	WHERE entity_id >= 10000 ORDER BY entity_id";
$daoPers = CRM_Core_DAO::executeQuery( $selPersQry );
while ( $daoPers->fetch( ) ) {
	$contactID = $daoPers->entity_id;
	$persoonsID = $daoPers->persoonsnummer_first_1;
	/*
	 * alle syncrecords zonder key first verwijderen
	 */
	$delSyncQry = "DELETE FROM civicrm_value_synchronisatie_first_noa_8 
		WHERE entity_id = $contactID AND entity_49 = 'address' AND key_first_51 = ''";
	CRM_Core_DAO::executeQuery( $delSyncQry );	
	/*
	 * ophalen primair adres van persoon in civicrm_address
	 */
	$selPrimAdrQry = "SELECT * FROM civicrm_address WHERE contact_id = $contactID 
		AND is_primary = 1";
	$daoPrimAdr = CRM_Core_DAO::executeQuery( $selPrimAdrQry );
	if ( $daoPrimAdr->fetch( ) ) {
		/*
		 * ophalen adr_refno dat bij adres hoort
		 */
		$addressID = $daoPrimAdr->id; 
		$selRefQry = "SELECT adr_refno FROM adrrefno WHERE par_refno = 
			'$persoonsID' AND end_date = ''";
		$daoRef = CRM_Core_DAO::executeQuery( $selRefQry );
		if ( $daoRef->fetch( ) ) {

			/*
			 * record in synctabel ophalen en bijwerken
			 */
			$adrRefno = $daoRef->adr_refno;
			$selSyncQry = "SELECT * FROM civicrm_value_synchronisatie_first_noa_8 
				WHERE key_first_51 = '$adrRefno' AND entity_id = $contactID";
			$daoSync = CRM_Core_DAO::executeQuery( $selSyncQry );
			if ( $daoSync->fetch( ) ) {
				$syncID = $daoSync->id;
				$updSyncQry = "UPDATE civicrm_value_synchronisatie_first_noa_8 
					SET entity_id_50 = $addressID WHERE id = $syncID";	
				CRM_Core_DAO::executeQuery( $updSyncQry );
			} else {
				/*
				 * voeg toe als het nog niet bestaat
				 */
				$insSyncQry = "INSERT INTO civicrm_value_synchronisatie_first_noa_8 
					SET entity_id = $contactID, action_48 = 'none', entity_49 = 
					'address', entity_id_50 = $addressID, key_first_51 = 
					'$adrRefno'";
				CRM_Core_DAO::executeQuery( $insSyncQry );
			}
		} else {
			/*
			 * als er geen refno is, sync record toevoegen
			 */		
			$insSyncQry = "INSERT INTO civicrm_value_synchronisatie_first_noa_8 
				SET entity_id = $contactID, action_48 = 'none', entity_49 = 
				'address', entity_id_50 = $addressID";
			CRM_Core_DAO::executeQuery( $insSyncQry );
		}
	}
	/*
	 * ophalen oud adres van persoon in civicrm_address
	 */
	$selSecAdrQry = "SELECT * FROM civicrm_address WHERE contact_id = $contactID 
		AND location_type_id = 6";
	$daoSecAdr = CRM_Core_DAO::executeQuery( $selSecAdrQry );
	if ( $daoSecAdr->fetch( ) ) {
		/*
		 * ophalen adr_refno dat bij adres hoort (eerste niet lege einddatum met hoogste startdatum)
		 */
		$addressID = $daoSecAdr->id; 
		$selRefQry = "SELECT adr_refno FROM adrrefno WHERE par_refno = 
			'$persoonsID' AND end_date <> '' ORDER BY start_date DESC";
		$daoRef = CRM_Core_DAO::executeQuery( $selRefQry );
		if ( $daoRef->fetch( ) ) {
			/*
			 * record in synctabel ophalen en bijwerken
			 */
			$adrRefno = $daoRef->adr_refno;
			$selSyncQry = "SELECT * FROM civicrm_value_synchronisatie_first_noa_8 
				WHERE key_first_51 = '$adrRefno' AND entity_id = $contactID";
			$daoSync = CRM_Core_DAO::executeQuery( $selSyncQry );
			if ( $daoSync->fetch( ) ) {
				$syncID = $daoSync->id;
				$updSyncQry = "UPDATE civicrm_value_synchronisatie_first_noa_8 
					SET entity_id_50 = $addressID WHERE id = $syncID";	
				CRM_Core_DAO::executeQuery( $updSyncQry );
			} else {
				/*
				 * voeg toe als het nog niet bestaat
				 */
				$insSyncQry = "INSERT INTO civicrm_value_synchronisatie_first_noa_8 
					SET entity_id = $contactID, action_48 = 'none', entity_49 = 
					'address', entity_id_50 = $addressID, key_first_51 = 
					'$adrRefno'";
				CRM_Core_DAO::executeQuery( $insSyncQry );
			}
		} else {
			/*
			 * als er geen refno is, sync record toevoegen
			 */		
			$insSyncQry = "INSERT INTO civicrm_value_synchronisatie_first_noa_8 
				SET entity_id = $contactID, action_48 = 'none', entity_49 = 
				'address', entity_id_50 = $addressID";
			CRM_Core_DAO::executeQuery( $insSyncQry );
		}
	}
}
echo "<p>Corrigeren sync adres 2e deel klaar!</p>";
?>
