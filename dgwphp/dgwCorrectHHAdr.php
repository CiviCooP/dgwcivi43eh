<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       3 June 2011                                  |
| Description   :       Corrigeren adres huishouden					 |
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
 * lezen alle contacten die hoofdhuurder zijn
 */
$selRelQry = "SELECT * FROM civicrm_relationship WHERE relationship_type_id = ".RELHOOFD;
$daoRel = CRM_Core_DAO::executeQuery( $selRelQry );
while ($daoRel->fetch( ) ) {
	/*
	 * select all addresses from hoofdhuurder and copy those to
	 * huishouden
	 */
	$contactID = $daoRel->contact_id_a;
	$huishoudID = $daoRel->contact_id_b;
	/*
	 * verwijder alle adressen huishouden
	 */
	$delHHQry = "DELETE FROM civicrm_address WHERE contact_id = $huishoudID";
	CRM_Core_DAO::executeQuery( $delHHQry );  
	require_once('CRM/Core/DAO/Address.php');
	$addrDAO = new CRM_Core_DAO_Address();
	$fields = $addrDAO->fields();
	$adrSelQry = "SELECT * FROM civicrm_address WHERE contact_id = $contactID";
	$adrHoofd = CRM_Core_DAO::executeQuery($adrSelQry);
	while ($adrHoofd->fetch()) {
		 $adrInsQry = "INSERT INTO civicrm_address SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($adrHoofd->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $adrHoofd->$field['name'] );
						$adrInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$adrInsQry .= $field['name']. " = ".
							$adrHoofd->$field['name'].", ";
					}
				}
		 }
		 $adrInsQry .= "contact_id = $huishoudID";
		 CRM_Core_DAO::executeQuery($adrInsQry);
	}
}
echo "<p>Corrigeren adres klaar!</p>";
?>
