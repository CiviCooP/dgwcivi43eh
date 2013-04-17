<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwDedupe1.php                         |
+--------------------------------------------------------------------+
| Project       :       Anna Zeepsop at De Goede Woning              |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       30 mei 2012                                  |
| Description   :       Eerste laag deDupe Anna Zeepsop              |
|						Pas regel 12 toe                             |
+--------------------------------------------------------------------+
*/
set_time_limit(0);
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
ini_set( 'memory_limit', '5120M' );
/*
 * laad configuratiebestand
 */
require_once( 'dgwConfig.php' );
require_once( 'CRM/Utils/Mail.php' );
require_once( 'CRM/Utils/String.php' );  
require_once( 'CRM/Dedupe/Finder.php' );
require_once( 'CRM/Core/Permission.php' );

/*
 * tabel dgw_suspects1 leegmaken
 */
CRM_Core_DAO::executeQuery( "TRUNCATE TABLE dgw_suspects1");

/*
 * check of regel in GET zit, anders fout
 */
if ( !isset( $_GET['regel']) || empty( $_GET['regel'] ) )  {
	die("Je moet een te hanteren regel opgeven!");
}
$ruleID = trim( $_GET['regel'] );
unset($_GET);

$suspectsDupe = CRM_Dedupe_Finder::dupes( $ruleID );


if ( empty( $suspectsDupe ) ) {
	die("Er zijn geen dubbele contacten gevonden met regel $ruleID");
}
foreach ( $suspectsDupe as $suspect ) {
	$c1 = $suspect[0];
	$c2 = $suspect[1];
	$w = $suspect[2];
	/*
	 * ophalen details van contact 1
	 */
	$suspectData['c1'] = _getContact( $c1 );
	$suspectData['c1']['contact_id'] = $c1;
	/*
	 * ophalen details van contact 2
	 */
	$suspectData['c2'] = _getContact( $c2 );
	$suspectData['c2']['contact_id'] = $c2;
	$suspectData['weight'] = $w;
	/*
	 * gevonden dupes wegschrijven naar tabel
	 */
	if ( !empty( $suspectData ) ) {
		_writeSuspect( $suspectData );
	}	 
}
echo "<p>Dubbele contacten zijn gevonden, check het rapport</p>";
/*-----------------------------------------------------+
 | Functie om contactgegevens op te halen met id als   |
 | sleutel. Return = array met data                    |
 *-----------------------------------------------------+
 */
function _getContact( $contactID ) {
	$data = array();
	/*
	 * fout als contactID leeg
	 */
	if ( empty( $contactID ) ) {
		$data['is_error'] = 1;
		$data['error_message'] = "ContactID is leeg!";
		return $data;
	}
	$data['is_error'] = 0;
	
	/*
	 * haal contact op met API
	 */
	require_once( 'api/v2/Contact.php');
	$contactParams = array( 'contact_id' => $contactID ); 
	$contactData = civicrm_contact_get( $contactParams );
	/*
	 * als CiviCRM error, message teruggeven
	 */
	if ( civicrm_error( $contactData ) ) {
		$data['is_error'] = 1;
		$data['error_message'] = "Fout van API contact_get met id 
			$contactID en boodschap ". $contactData['error_message'];
		return $data;
	}
	/*
	 * gegevens die aanwezig zijn overzetten naar resultaatarray
	 */
	if ( isset( $contactData[$contactID]['display_name'] ) ) {
		$data['display_name'] = $contactData[$contactID]['display_name'];
	} else {
		$data['display_name'] = null;
	}
	if ( isset( $contactData[$contactID]['first_name'] ) ) {
		$data['first_name'] = $contactData[$contactID]['first_name'];
	} else {
		$data['first_name'] = null;
	}
	if ( isset( $contactData[$contactID]['last_name'] ) ) {
		$data['last_name'] = $contactData[$contactID]['last_name'];
	} else {
		$data['last_name'] = null;
	}
	if ( isset( $contactData[$contactID]['middle_name'] ) ) {
		$data['middle_name'] = $contactData[$contactID]['middle_name'];
	} else {
		$data['middle_name'] = null;
	}
	if ( isset( $contactData[$contactID]['birth_date'] ) ) {
		$data['birth_date'] = $contactData[$contactID]['birth_date'];
	} else {
		$data['birth_date'] = null;
	}
	if ( isset( $contactData[$contactID]['street_address'] ) ) {
		$data['display_address'] = $contactData[$contactID]['street_address'];
	} else {
		$data['display_address'] = null;
	}
	if ( isset( $contactData[$contactID]['postal_code'] ) ) {
		$data['display_address'] .= ", ".$contactData[$contactID]['postal_code'];
	} 
	if ( isset( $contactData[$contactID]['city'] ) ) {
		if ( isset( $contactData[$contactID]['postal_code'] ) ) {
			$data['display_address'] .= " ".$contactData[$contactID]['city'];
		} else {
			$data['display_address'] .= ", ".$contactData[$contactID]['city'];
		}	
	}
	if ( isset( $contactData[$contactID]['phone'] ) ) {
		$data['phone'] = $contactData[$contactID]['phone'];
	} else {
		$data['phone'] = null;
	}
	$data['phone_type'] = null;
	if ( isset( $contactData[$contactID]['phone_type_id'] ) ) {
		$phoneTypeID = $contactData[$contactID]['phone_type_id'];
		$phoneQry = 
"SELECT label FROM civicrm_option_value WHERE option_group_id = 34 AND value = $phoneTypeID";
		$phoneDAO = CRM_Core_DAO::executeQuery( $phoneQry );
		if ( $phoneDAO->fetch() ) {
			if ( isset( $phoneDAO->label ) ) {
				$data['phone_type'] = $phoneDAO->label;
			}
		}
	}
	if ( isset( $contactData[$contactID]['email'] ) ) {
		$data['email'] = $contactData[$contactID]['email'];
	} else {
		$data['email'] = null;
	}
	/*
	 * ophalen BSN uit custom data
	 */
	$data['bsn'] = null; 
	require_once('CRM/Core/BAO/CustomValueTable.php');
	$customParams = array(
		'entityID'	=>	$contactID,
		CFPERSBSN      =>  1);
	$customData = CRM_Core_BAO_CustomValueTable::getValues($customParams);
	if ( !civicrm_error( $customData ) ) {
		if ( isset( $customData[CFPERSBSN] ) ) {
			$data['bsn'] = $customData[CFPERSBSN];
		}
	}
	return $data;
}
/*-----------------------------------------------------+
 | Functie om verdachten in tabel op te slaan          |
 *-----------------------------------------------------+
 */
function _writeSuspect( $data ) {
	
	$id1 = $data['c1']['contact_id'];
	$id2 = $data['c2']['contact_id'];
	$dis1 = CRM_Core_DAO::escapeString( $data['c1']['display_name'] );
	$dis2 = CRM_Core_DAO::escapeString( $data['c2']['display_name'] );
	$fir1 = CRM_Core_DAO::escapeString( $data['c1']['first_name'] );
	$fir2 = CRM_Core_DAO::escapeString( $data['c2']['first_name'] );
	$lst1 = CRM_Core_DAO::escapeString( $data['c1']['last_name'] );
	$lst2 = CRM_Core_DAO::escapeString( $data['c2']['last_name'] );
	$mid1 = CRM_Core_DAO::escapeString( $data['c1']['middle_name'] );
	$mid2 = CRM_Core_DAO::escapeString( $data['c2']['middle_name'] );
	$gbd1 = date('Ymd', strtotime( $data['c1']['birth_date'] ) );
	$gbd2 = date('Ymd', strtotime( $data['c2']['birth_date'] ) );
	$adr1 = CRM_Core_DAO::escapeString( $data['c1']['display_address'] );
	$adr2 = CRM_Core_DAO::escapeString( $data['c2']['display_address'] );
	$phn1 = CRM_Core_DAO::escapeString( $data['c1']['phone'] );
	$phn2 = CRM_Core_DAO::escapeString( $data['c2']['phone'] );
	$ptp1 = CRM_Core_DAO::escapeString( $data['c1']['phone_type'] );
	$ptp2 = CRM_Core_DAO::escapeString( $data['c2']['phone_type'] );
	$ema1 = CRM_Core_DAO::escapeString( $data['c1']['email'] );
	$ema2 = CRM_Core_DAO::escapeString( $data['c2']['email'] );
	$bsn1 = $data['c1']['bsn'];
	$bsn2 = $data['c2']['bsn'];
	$wgh = $data['weight'];

	$suspectIns = 
"INSERT INTO dgw_suspects1 SET contact_id_1 = $id1, contact_id_2 = $id2, display_name_1 = '$dis1', display_name_2 = '$dis2', first_name_1 = '$fir1', first_name_2 = '$fir2', last_name_1 = '$lst1', last_name_2 = '$lst2', middle_name_1 = '$mid1', middle_name_2 = '$mid2', birth_date_1 = '$gbd1', birth_date_2 = '$gbd2', display_address_1 = '$adr1', display_address_2 = '$adr2', email_1 = '$ema1', email_2 = '$ema2', bsn_1 = '$bsn1', bsn_2 = '$bsn2', phone_1 = '$phn1', phone_2 = '$phn2', phone_type_1 = '$ptp1', phone_type_2 = '$ptp2', weight = $wgh";
	CRM_Core_DAO::executeQuery( $suspectIns );
	return "ok";
}
	
	
    
