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
$selPersQry = "SELECT * FROM ".TABFIRSTPERS;
$daoPers = CRM_Core_DAO::executeQuery( $selPersQry );
while ($daoPers->fetch( ) ) {
	$persNr = null;
	$contactID = null;
	if ( isset( $daoPers->persoonsnummer_first_1 ) ) {
		$persNr = $daoPers->persoonsnummer_first_1;
		$contactID = $daoPers->entity_id;
		if ( !empty( $persNr ) && !empty( $contactID ) ) {
			/*
			 * eerst alle bestaande adressen uit CiviCRM verwijderen
			 */
			$adresDelQry = "DELETE FROM civicrm_address WHERE contact_id = $contactID";
			CRM_Core_DAO::executeQuery( $adresDelQry );
			/*
			 * daarna laatste 2 adressen uit correctiebestand selecteren en
			 * in CiviCRM plaatsen
			 */
			$locationTypeID = null;
			$address = null;
			$adresSelQry = "SELECT * FROM adrescor WHERE persoonsnummer = $persNr 
				ORDER BY startdatum DESC LIMIT 2";
			$daoCorAdres = CRM_Core_DAO::executeQuery( $adresSelQry );
			while ( $daoCorAdres->fetch( ) ) {
				$adresInsQry = "INSERT INTO civicrm_address SET contact_id = $contactID, 
					country_id = 1152";
				if ( empty ( $locationTypeID ) ) {
					$locationTypeID = LOCTYPETHUIS;
					$primary = 1;
				} else {
					$primary = 0;
					if ( empty( $daoCorAdres->einddatum ) ) {
						$locationTypeID = LOCTYPEOVERIG;
					} else {
						$locationTypeID = LOCTYPEOUD;
						$supp1 = "(Tot ".date("d-m-Y", strtotime( $daoCorAdres->einddatum  ) ).
							" )";
						$adresInsQry .= ", supplemental_address_1 = '$supp1'";	
					}
				}
				$adresInsQry .= ", location_type_id = $locationTypeID, is_primary = $primary";
			
				if ( !empty( $daoCorAdres->straat  ) ) {
					$streetname = CRM_Core_DAO::escapeString( $daoCorAdres->straat );
					$adresInsQry .= ", street_name = '$streetname'";
					$address = $streetname;
				}
				
				if ( !empty( $daoCorAdres->huisnummer ) ) {
					$streetnumber = $daoCorAdres->huisnummer;
					$adresInsQry .= ", street_number = $streetnumber";
					$address .= " ".$streetnumber;
				}
				
				$streetnumbersuffix = null;
				if ( !empty( $daoCorAdres->huisletter ) ) {
					$streetnumbersuffix = CRM_Core_DAO::escapeString( $daoCorAdres->huisletter  );
				}
				
				if ( !empty( $daoCorAdres->toevoeging  ) ) {
					if ( isset( $streetnumbersuffix ) &&  !empty( $streetnumbersuffix ) ) {
						$streetnumbersuffix .= " ".CRM_Core_DAO::escapeString( $daoCorAdres->toevoeging );
					} else {
						$streetnumbersuffix = CRM_Core_DAO::escapeString( $daoCorAdres->toevoeging );
					}	
				} 
				if ( isset( $streetnumbersuffix ) &&  !empty( $streetnumbersuffix ) ) {
					$adresInsQry .= ", street_number_suffix = '$streetnumbersuffix'";
					$address .= " ".$streetnumbersuffix;
				}
				
				if ( !empty( $address ) ) {
					$adresInsQry .= ", street_address = '$address'";
				}
				
				if ( !empty( $daoCorAdres->plaats ) ) {
					$city = CRM_Core_DAO::escapeString( $daoCorAdres->plaats );
					$adresInsQry .= ", city = '$city'";
				}
				
				if ( !empty( $daoCorAdres->postcode ) ) {
					$postalcode = CRM_Core_DAO::escapeString( $daoCorAdres->postcode );
					$adresInsQry .= ", postal_code = '$postalcode'";
				}
				CRM_Core_DAO::executeQuery( $adresInsQry );
			} 
		}
			
	}
}
echo "<p>Corrigeren adres klaar!</p>";
?>
