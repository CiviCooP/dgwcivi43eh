<?php
ini_set( 'display_errors', '1' );
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranet/sites/all/modules/dgwphp/dgwConvConfig.php' );
/*
 * lezen alle first addressen met einddatum leeg, gesorteerd op startdatum aflopend
 */
$selRefQry = "SELECT DISTINCT(par_refno), adr_refno FROM adrrefno WHERE 
	end_date = '0000-00-00' ORDER BY par_refno, start_date DESC";
$daoRef = CRM_Core_DAO::executeQuery( $selRefQry );
while ( $daoRef->fetch( ) ) {
	if ( isset( $daoRef->adr_refno ) && !empty( $daoRef->adr_refno ) ) {
		$adrRefno = $daoRef->adr_refno;
		/*
		 * ophalen synchronisatierecord dat bij het adrRefno hoort
		 */
		$selSyncQry = "SELECT id, entity_id FROM civicrm_value_synchronisatie_first_noa_8 
			WHERE entity_49 = 'address' AND key_first_51 = '$adrRefno'";
		$daoSync = CRM_Core_DAO::executeQuery( $selSyncQry );
		if ( $daoSync->fetch( ) ) {
			if ( isset( $daoSync->id ) && isset( $daoSync->entity_id ) ) {
				$syncID = $daoSync->id;
				$entityID = $daoSync->entity_id;
				/*
				 * ophalen primair adres van contact uit civicrm_address
				 */
				$selAdrQry = "SELECT id FROM civicrm_address WHERE contact_id = 
					$entityID AND is_primary=1";
				$daoAdr = CRM_Core_DAO::executeQuery( $selAdrQry );
				if ( $daoAdr->fetch( ) ) {
					if ( isset( $daoAdr->id ) ) {
						$addressID = $daoAdr->id;
						/*
						 * bijwerken syncrecord
						 */
						$updSyncQry = "UPDATE civicrm_value_synchronisatie_first_noa_8 
							SET entity_id_50 = $addressID WHERE id = $syncID";
						CRM_Core_DAO::executeQuery( $updSyncQry );
					}
				}
			}
		}
	}
}	
						 
echo "<p>Corrigeren primair adres klaar!</p>";
?>
