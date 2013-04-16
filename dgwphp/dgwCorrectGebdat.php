<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       3 June 2011                                  |
| Description   :       Corrigeren geboortedatum					 |
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
$selPersQry = "SELECT * FROM ".TABFIRSTPERS;
$daoPers = CRM_Core_DAO::executeQuery( $selPersQry );
while ($daoPers->fetch( ) ) {
	$persNr = null;
	if ( isset( $daoPers->persoonsnummer_first_1 ) ) {
		$persNr = $daoPers->persoonsnummer_first_1;
		$contactID = $daoPers->entity_id;
	}
	/*
	 * ophalen geboortedatum uit correctietabel gebpers
	 */
	if ( !empty( $persNr ) ) { 
		$selGebQry = "SELECT * FROM gebpers WHERE persoonsnummer = $persNr";
		$daoGeb = CRM_Core_DAO::executeQuery( $selGebQry );
		if ( $daoGeb->fetch( ) ) {
			if (isset ($daoGeb->gebdatum ) && !empty( $daoGeb->gebdatum ) ) {
				$geboorteDatum = date("Y-m-d", strtotime( $daoGeb->gebdatum ) );
				/*
				 * bijwerken civicrm_contact
				 */
				 $updContactQry = "UPDATE civicrm_contact SET birth_date = '$geboorteDatum' WHERE id = $contactID";
				 CRM_Core_DAO::executeQuery($updContactQry);
			}
		}	
	}
}
echo "<p>Corrigeren geboortedatum klaar!</p>";
?>
