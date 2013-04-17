<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwDubbelBsn.php                       |
+--------------------------------------------------------------------+
| Project       :       Anna Zeepsop at De Goede Woning              |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       3 juni 2012                                  |
| Description   :       Haalt personen met dubbel BSN uit contact    |
+--------------------------------------------------------------------+
*/
set_time_limit(0);
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
ini_set( 'memory_limit', '512M' );
/*
 * laad configuratiebestand
 */
require_once('dgwConfig.php');
require_once('CRM/Utils/Mail.php');
require_once( 'CRM/Utils/String.php' );  
require_once( 'CRM/Dedupe/Finder.php' );

/*
 * tabel dgw_bsn leegmaken
 */
CRM_Core_DAO::executeQuery( "TRUNCATE TABLE dgw_bsn");

/*
 * haal alle contacten waarbij BSN gevuld is uit custom table
 */
$arrayBsn = array( );
$bronQry = 
"SELECT entity_id, bsn_2 FROM civicrm_value_aanvullende_persoonsgegevens_1 WHERE bsn_2 <> ''";
$daoBron = CRM_Core_DAO::executeQuery( $bronQry );
$i = 0;
while ( $daoBron->fetch() ) {
	$arrayBSN[$i]['contact_id'] = $daoBron->entity_id;
	$arrayBSN[$i]['bsn'] = $daoBron->bsn_2;
	$i++;
}
/*
 * daarna door array lopen en kijken of er een nog een contact is met het 
 * BSN. Zo ja, dubbel record vullen
 */
foreach ( $arrayBSN as $bron ) {
	
	$checkBSN = trim( $bron['bsn'] );
	$checkID = $bron['contact_id'];
	$dubbelQry = 
"SELECT entity_id, bsn_2 FROM civicrm_value_aanvullende_persoonsgegevens_1 WHERE bsn_2 = '$checkBSN' AND entity_id <> $checkID";
	$daoDubbel = CRM_Core_DAO::executeQuery( $dubbelQry );
	while ( $daoDubbel->fetch() ) {
		/*
		 * schrijf record naar dubbelen bestand
		 */
		_writeDubbel( $checkID, $checkBSN, $daoDubbel->entity_id, $daoDubbel->bsn_2 ); 
	}
}
echo "<p>Controle dubbele BSN nummer is klaar!</p> ";
/*-----------------------------------------------------------+
 * functie om dubbelen contacten in tabel te zetten
 */
function _writeDubbel ( $contact_id_1, $bsn_1, $contact_id_2, $bsn_2 ) {
	
	/*
	 * alleen als omgekeerd geval niet al in bestand staat
	 */
	$omgekeerdQry = 
"SELECT COUNT(*) AS aantal FROM dgw_bsn WHERE contact_id_1 = $contact_id_2 AND contact_id_2 = $contact_id_1"; 
	$daoOmgekeerd = CRM_Core_DAO::executeQuery( $omgekeerdQry );
	if ( $daoOmgekeerd->fetch() ) {
		if ( $daoOmgekeerd->aantal > 0 ) {
			return false;
		}
	}
	/*
	 * basis insert
	 */
	$insert = 
"INSERT INTO dgw_bsn SET contact_id_1 = $contact_id_1, contact_id_2 = $contact_id_2, bsn_1 = '$bsn_1', bsn_2 = '$bsn_2'";
	/*
	 * met API gegevens van contact 1 en 2 ophalen
	 */

	require_once('api/v2/Contact.php');
	$contactParams = array( 'contact_id' => $contact_id_1 ); 
	$contactData = civicrm_contact_get( $contactParams );
	if ( !civicrm_error( $contactData ) ) {
		if ( isset( $contactData[$contact_id_1]['first_name'] ) ) {
			$contactData[$contact_id_1]['first_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['first_name']);
			$insert .= ", first_name_1 = '{$contactData[$contact_id_1]['first_name']}'";
		}
		if ( isset( $contactData[$contact_id_1]['last_name'] ) ) {
			$contactData[$contact_id_1]['last_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['last_name']);
			$insert .= ", last_name_1 = '{$contactData[$contact_id_1]['last_name']}'";
		}
		if ( isset( $contactData[$contact_id_1]['middle_name'] ) ) {
			$contactData[$contact_id_1]['middle_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['middle_name']);
			$insert .= ", middle_name_1 = '{$contactData[$contact_id_1]['middle_name']}'";
		}
		if ( isset( $contactData[$contact_id_1]['birth_date'] ) ) {
			$contactData[$contact_id_1]['birth_date'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['birth_date']);
			$insert .= ", birth_date_1 = '{$contactData[$contact_id_1]['birth_date']}'";
		}
		if ( isset( $contactData[$contact_id_1]['street_address'] ) ) {
			$contactData[$contact_id_1]['street_address'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['street_address']);
			$insert .= ", address_1 = '{$contactData[$contact_id_1]['street_address']}'";
		}
		if ( isset( $contactData[$contact_id_1]['city'] ) ) {
			$contactData[$contact_id_1]['city'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['city']);
			$insert .= ", city_1 = '{$contactData[$contact_id_1]['city']}'";
		}
		if ( isset( $contactData[$contact_id_1]['postal_code'] ) ) {
			$contactData[$contact_id_1]['postal_code'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['postal_code']);
			$insert .= ", post_1 = '{$contactData[$contact_id_1]['postal_code']}'";
		}
		if ( isset( $contactData[$contact_id_1]['phone'] ) ) {
			$contactData[$contact_id_1]['phone'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['phone']);
			$insert .= ", phone_1 = '{$contactData[$contact_id_1]['phone']}'";
		}
		if ( isset( $contactData['email'] ) ) {
			$contactData[$contact_id_1]['email'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_1]['email']);
			$insert .= ", email_1 = '{$contactData[$contact_id_1]['email']}'";
		}
	}
	
	$contactParams = array( 'contact_id' => $contact_id_2 ); 
	$contactData = civicrm_contact_get( $contactParams );
	if ( !civicrm_error( $contactData ) ) {
		if ( isset( $contactData[$contact_id_2]['first_name'] ) ) {
			$contactData[$contact_id_2]['first_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['first_name']);
			$insert .= ", first_name_2 = '{$contactData[$contact_id_2]['first_name']}'";
		}
		if ( isset( $contactData[$contact_id_2]['last_name'] ) ) {
			$contactData[$contact_id_2]['last_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['last_name']);
			$insert .= ", last_name_2 = '{$contactData[$contact_id_2]['last_name']}'";
		}
		if ( isset( $contactData[$contact_id_2]['middle_name'] ) ) {
			$contactData[$contact_id_2]['middle_name'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['middle_name']);
			$insert .= ", middle_name_2 = '{$contactData[$contact_id_2]['middle_name']}'";
		}
		if ( isset( $contactData[$contact_id_2]['birth_date'] ) ) {
			$contactData[$contact_id_2]['birth_date'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['birth_date']);
			$insert .= ", birth_date_2 = '{$contactData[$contact_id_2]['birth_date']}'";
		}
		if ( isset( $contactData[$contact_id_2]['street_address'] ) ) {
			$contactData[$contact_id_2]['street_address'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['street_address']);
			$insert .= ", address_2 = '{$contactData[$contact_id_2]['street_address']}'";
		}
		if ( isset( $contactData[$contact_id_2]['city'] ) ) {
			$contactData[$contact_id_2]['city'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['city']);
			$insert .= ", city_2 = '{$contactData[$contact_id_2]['city']}'";
		}
		if ( isset( $contactData[$contact_id_2]['postal_code'] ) ) {
			$contactData[$contact_id_2]['postal_code'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['postal_code']);
			$insert .= ", post_2 = '{$contactData[$contact_id_2]['postal_code']}'";
		}
		if ( isset( $contactData[$contact_id_2]['phone'] ) ) {
			$contactData[$contact_id_2]['phone'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['phone']);
			$insert .= ", phone_2 = '{$contactData[$contact_id_2]['phone']}'";
		}
		if ( isset( $contactData['email'] ) ) {
			$contactData[$contact_id_2]['email'] = 
				CRM_Core_DAO::escapeString($contactData[$contact_id_2]['email']);
			$insert .= ", email_2 = '{$contactData[$contact_id_2]['email']}'";
		}
	}
	CRM_Core_DAO::executeQuery($insert);
}
	
    
