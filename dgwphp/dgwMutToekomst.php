<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwMutToekomst.php                     |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       8 maart 2011                                 |
| Description   :       Controleert of er addressen of telefoon-     |
|			nummers met locatie 'Toekomst' met de datum  |
|			van vandaag verwerkt moeten worden. Wordt    |
|                       geactiveerd door een cron job.               |
+--------------------------------------------------------------------+
/*
 * laad configuratiebestand
 */
require_once('dgwConfig.php');
require_once('CRM/Utils/Mail.php');
require_once( 'CRM/Utils/String.php' );  

/*
 * verwerk addressen: lees alle addressen uit civicrm_address waarbij
 * locatie type Toekomst is
 */
$toekomstID = LOCTOEKOMST; 
$oudID = LOCOUD;
$thuisID = LOCTHUIS;
$adrQry = "SELECT * FROM civicrm_address WHERE location_type_id = $toekomstID";
$adrToekomst = CRM_Core_DAO::executeQuery($adrQry);
/*
 * alle toekomstadressen verwerken
 */
while ($adrToekomst->fetch()) {
	$contactID = $adrToekomst->contact_id;
	$addressID = $adrToekomst->id;
	/*
	 * pluk de datum van de mutatie (staat in supplemental address 1
	 * van het record, zoek naar formaat dd-mm-yy in een element)
	 */
	 $parts_sup = explode(" ", $adrToekomst->supplemental_address_1);
	 $datumToekomst = null;
	 if (isset($parts_sup[1])) {
		 $parts_date = explode(")", $parts_sup[1]);
		 if (isset($parts_date[0])) {
			$datumToekomst = date("Ymd", strtotime($parts_date[0]));
		 } 
	 }
	 /*
	  * alleen verwerken als $datumToekomst gevuld en kleiner dan of 
	  * gelijk aan vandaag
	  */
	 if (!empty($datumToekomst) && $datumToekomst <= date("Ymd")) {
		 /*
		  * verwijder alle bestaande adressen 'Oud' voor het contact
		  * omdat het huidige adres straks oud wordt en er slechts 1
		  * adres 'Oud' bewaard blijft.
		  */
		 $adrDelOud = "DELETE FROM civicrm_address WHERE contact_id = 
			$contactID AND location_type_id = $oudID";
		 CRM_Core_DAO::executeQuery($adrDelOud);
		 /*
		  * zet huidig 'Thuis' adres om naar 'Oud' adres met vermelding
		  * '(Tot <datum vandaag>)'
		  */
		 $sup1 = "(Tot ".date('d-m-Y').")"; 
		 $adrUpdToekomst = "UPDATE civicrm_address SET location_type_id 
			= $oudID, supplemental_address_1 = '$sup1' WHERE contact_id = 
			$contactID AND location_type_id = $thuisID";
		 CRM_Core_DAO::executeQuery($adrUpdToekomst);
		 /*
		  * zet alle bestaande addressen op is_primary = 0
		  */
		 $adrUpdPrim = "UPDATE civicrm_address SET is_primary = 0 WHERE
			contact_id = $contactID";
		 CRM_Core_DAO::executeQuery($adrUpdPrim);	
		 /*
		  * zet uiteindelijk het 'Toekomst' adres om naar het 'Thuis'
		  * adres, primary en poets de tekst weg uit supplemental_address_1
		  */
		 $adrUpdThuis = "UPDATE civicrm_address SET location_type_id
			= $thuisID, supplemental_address_1 = '', is_primary = 1 WHERE 
			id = $addressID";
		 CRM_Core_DAO::executeQuery($adrUpdThuis);	
	 }
 }
 /*
  * alle toekomst telefoonnummers verwerken
  */
$phoneQry = "SELECT * FROM civicrm_phone WHERE location_type_id = $toekomstID";
$phoneToekomst = CRM_Core_DAO::executeQuery($phoneQry);
while ($phoneToekomst->fetch()) {
	$contactID = $phoneToekomst->contact_id;
	$phoneID = $phoneToekomst->id;
	$phonetypeID = $phoneToekomst->phone_type_id;
	/*
	 * datum van achter het telefoonnummer vandaan halen
	 */
	$datumToekomst = null;
	$ruweDatum = null;
	$parts_phone = explode(" ", $phoneToekomst->phone);
	if (isset($parts_phone[0])) {
		foreach($parts_phone as $phonepart) {
			if (strpos($phonepart, "-")) {
				$ruweDatum = $phonepart;
			}
		}
	}
	if (substr($ruweDatum, -1, 1) == ")") {
		$datumToekomst = date("Ymd", strtotime(substr($ruweDatum, 0, 
			(strlen($ruweDatum) -1))));
	} else {
		$datumToekomst = date("Ymd", strtotime($ruweDatum));
	}
	/*
	 * verwerken als datumToekomst kleiner of gelijk aan vandaag
	 */
	if ($datumToekomst <= date("Ymd")) {
		/*
		 * verwijder tekst achter telefoon
		 */
		$parts_phone = explode("(vanaf", $phoneToekomst->phone);
		$phone = trim($parts_phone[0]);
		/*
		 * check of er al een telefoon is met de combinatie Thuis en
		 * telefoontype. Zo ja, zet de nieuwe op is_primary = 1
		 */
		$phoneCheckQry = "SELECT * FROM civicrm_phone WHERE contact_id = 
			$contactID AND location_type_id = $thuisID and phone_type_id = 
			$phonetypeID";
		$phoneCheck = CRM_Core_DAO::executeQuery($phoneCheckQry);	
		if ($phoneCheck->fetch()) {
			$primary = 1;
			$phoneUpdPrim = "UPDATE civicrm_phone SET is_primary = 0 
				WHERE contact_id = $contactID AND location_type_id = 
				$thuisID and phone_type_id = $phonetypeID";
			CRM_Core_DAO::executeQuery($phoneUpdPrim);	
		} else {
			$primary = 0;
		}	
		$phoneUpd = "UPDATE civicrm_phone SET location_type_id = $thuisID, 
			phone = '$phone', is_primary = $primary WHERE id = $phoneID";
		CRM_Core_DAO::executeQuery($phoneUpd);	
		
	}
}
$mail_params = array();
$mail_params['subject'] = "Laden toekomstmutaties compleet";
$mail_params['text'] = "Script dgwMutToekomst voor het laden van de toekomstmutaties van
	vandaag is afgerond";
$mail_params['toEmail'] = KOVMAIL;
$mail_params['toName'] = "Helpdesk";
$mail_params['from'] = "CiviCRM";
$mailresult = CRM_Utils_Mail::send($mail_params);
