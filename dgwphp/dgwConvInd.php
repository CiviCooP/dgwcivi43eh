<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvInd.php                                    |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       27 May 2011                                  |
| Description   :       Conversie van organisaties (wordt included   |
|                       vanuit dgwConvContr.php (conv. controller))  |
+--------------------------------------------------------------------+
*/
/*----------------------------------------------------------------
 * Functie om personen te verwerken
 *----------------------------------------------------------------*/
function verwerkInd($indTabel) {
	global $db, $indAdd, $indUpd, $indProcessed;	
	$indProcessed = 0;
	$indAdd = 0;
	$indUpd = 0;
	/*
	 * lees alle records uit de brontabel (alleen individuen)
	 */
	$selectBronQry = "SELECT * FROM $indTabel ORDER BY persoonsnummer";
	$bronResult = $db->query( $selectBronQry );
	if ( !$bronResult ) {
		$fout = array( );
		$fout['onderwerp'] = "Geen personen geconverteerd";
		$fout['bericht'] = "Er zijn geen personen overgezet naar CiviCRM, de query $selectQry kon niet uitgevoerd worden.";
		logFout( $fout, true );
		return $aantalRec;
	}
	/*
	 * voor iedere persoon uit brontabel
	 * 
	 */
	while ( $indData = $bronResult->fetch_array( MYSQLI_ASSOC ) ) {
		$aantalRec++;
		/*
		 * haal CiviCRM ID op met persoonsnummer first
		 */
		$civiparms = array(
			'contact_type' 	=> 'Individual',
			CFPERSNR		=>	$indData['persoonsnummer']);
		$indRes = civicrm_contact_get( $civiparms );
		if ( !empty( $indRes ) ) {
			foreach ($indRes as $individual) {
				$contact_id = $individual['contact_id'];
			}
		} else {
			$contact_id = null;
		}
		$contact_id = verwerkPersoon( $indData, $contact_id );
		/*
		 * adressen uit First zetten
		 */
		verwerkAdres( $indData['persoonsnummer'], $contact_id );
		/*
		 * telefoonnnummers uit First zetten
		 */
		verwerkTel ( $indData['persoonsnummer'], $contact_id );
		/*
		 * updaten emailadres als nodig
		 */
		 verwerkEmail( $indData['persoonsnummer'], $contact_id );
		 /*
		  * voeg toe aan speciale groep ter controle
		  */
		 if ( !empty( $contact_id ) ) {
			 plaatsGroep( 15, "DefConvInd", $contact_id ); 
		 }
	}
	return $aantalRec;
}
/*----------------------------------------------------------------
 * Functie om persoon bij te werken of toe te voegen
 *----------------------------------------------------------------*/
function verwerkPersoon( $data, $contactID ) {
	/*
	 * samenstellen sorteernaam
	 */
	global $indAdd, $indUpd;
	$arraySort = array( );
	$sortName = null;
	if (isset( $data['achternaam'] ) && !empty( $data['achternaam'] ) ) {
		$arraySort[] = $data['achternaam'];
	}
	if ( isset( $data['voorletters'] ) && !empty( $data['voorletters'] ) ) {
		$arraySort[] = $data['voorletters'];
	}
	if ( isset( $data['tussenvoegsel'] ) && !empty( $data['tussenvoegsel'] ) ) {
		$arraySort[] = $data['tussenvoegsel'];
	}
	if (isset( $arraySort[1] ) ) {
		$arraySort[0] .= ",";
	}
	$sortName = implode( " ", $arraySort );
	$sortName = CRM_Core_DAO::escapeString( $sortName );
	/*
	 * samenstellen display naam
	 */
	$arrayDisplay = array( );
	$displayName = null;
	if (isset( $data['voorletters'] ) && !empty( $data['voorletters'] ) ) {
		$arrayDisplay[] = $data['voorletters'];
	}
	if ( isset( $data['tussenvoegsel'] ) && !empty( $data['tussenvoegsel'] ) ) {
		$arrayDisplay[] = $data['tussenvoegsel'];
	}
	if ( isset( $data['achternaam'] ) && !empty( $data['achternaam'] ) ) {
		$arrayDisplay[] = $data['achternaam'];
	}
	$displayName = implode( " ", $arrayDisplay );
	$displayName = CRM_Core_DAO::escapeString( $displayName );
	/*
	 * klaarzetten overige persoonsgegevens voor verwerking
	 */
	$firstName = CRM_Core_DAO::escapeString( $data['voorletters'] );
	$middleName = CRM_Core_DAO::escapeString( $data['tussenvoegsel'] );
	/*
	 * als tussenvoegsel met hoofdletters begint, loggen
	 */
	if ( ctype_upper( substr ($middleName, 0, 1 ) ) ) {
		$log = array ( );
		$log['type'] = "controle";
		$log['onderwerp'] = "Tussenvoegsel hoofdletters";
		$log['bericht'] = "Het tussenvoegsel $middleName uit First voor CiviCRM contact ID ".$contactID." is met hoofdletters, nakijken! ";
		$log['contact_id'] = $contactID;
		$log['brontabel'] = "bronind";
		logFout( $log, false );
	}

	$lastName = CRM_Core_DAO::escapeString( $data['achternaam'] );
	switch ( strtolower( $data['geslacht'] ) ) {
		case "man":
			$genderID = 2;
			break;
		case "vrouw": 
			$genderID = 1;
			break;
		default: 
			$genderID = 3;
			break;
	}
	$birthDate = null;
	if ( !empty( $data['gebdat'] ) ) {
		/*
		 * als geboortedatum #MEER WAARDEN bevat, loggen
		 */
		if ( $data['gebdat'] == "#MEER WAARDEN" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Geboortedatum uit First was niet herkenbaar!";
			$log['bericht'] = "Een geboortedatum uit First voor CiviCRM contact ID ".$contactID." heeft een onherkenbare geboortedatum, nakijken! ";
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "bronind";
			logFout( $log, false );
		} else {
			$birthDate = date( "Y-m-d", strtotime( $data['gebdat'] ) );
		}
	}
	/*
	 * aanvullende gegevens voor persoon verwerken
	 */
	$persoonsNummer = $data['persoonsnummer'];
	$bsn = $data['sofinummer'];
	$burgID = bepaalBurgID( $data['burg'] );
	/*
	 * als persoon al bestaat (contactID gevuld) dan record in 
	 * civicrm_contact bijwerken
	 */
	if ( !empty ( $contactID ) ) {
		$updIndQry = "UPDATE civicrm_contact SET sort_name = '$sortName', 
			display_name = '$displayName', first_name = '$firstName', 
			middle_name = '$middleName', last_name = '$lastName', gender_id = 
			$genderID, email_greeting_custom = null, email_greeting_display =
			null, postal_greeting_custom = null, postal_greeting_display = null,
			addressee_custom = null, addressee_display = null";
		if ( !empty( $birthDate ) ) {
			$updIndQry .= ", birth_date = '$birthDate'";
		}
		$updIndQry .= " WHERE id = $contactID";
		$indUpd++;
		/*
		 * bijwerken aanvullende gegevens
		 */
		$updAanvQry = "UPDATE ".TABFIRSTPERS." SET ".FLDPERSBSN." = '$bsn', ".
			FLDPERSBURG." = $burgID WHERE entity_id = $contactID";
		CRM_Core_DAO::executeQuery( $updAanvQry );	
		
	} else {
		/*
		 * anders nieuw contact aanmaken
		 */
		$civiparms = array( 
			'contact_type'	=>	'Individual',
			'first_name'	=>	$firstName,
			'middle_name'	=>	$middleName,
			'last_name'		=>	$lastName,
			'display_name'	=>	$displayName,
			'sort_name'		=>	$sortName,
			'gender_id'		=>	$genderID );
		if ( !empty( $birthDate ) ) {
			$civiparms['birth_date'] = $birthDate;
		}
		$indRes = civicrm_contact_create( $civiparms );
		if ( !civicrm_error( $indRes ) ) {
			$contactID = $indRes['contact_id'];
			$indAdd++;
		}
		/*
		 * toevoegen aanvullende gegevens
		 */
		$civiparms = array( );
		$civiparms['entityID'] = $contactID;
		$civiparms[CFPERSNR] = $persoonsNummer;
		if ( !empty( $bsn ) ) {
			$civiparms[CFPERSBSN] = $bsn;
		}
		if ( !empty( $burgID ) ) {
			$civiparms[CFPERSBURG] = $burgID;
		}
		CRM_Core_BAO_CustomValueTable::setValues( $civiparms ); 
	}
	return $contactID;
	 			
}

		
