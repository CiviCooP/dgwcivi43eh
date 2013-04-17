<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       1 June 2011                                  |
| Description   :       Conversion huurovereenkomst                  |
+--------------------------------------------------------------------+
*/
ini_set('display_errors', '1');
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranet/sites/all/modules/dgwphp/dgwConvConfig.php' );
require_once( 'api/v2/Dgwcontact.php' );
require_once( 'CRM/Utils/Mail.php' );
require_once( 'CRM/Utils/String.php' );


$convHost = CONVHOST;
$convUser = CONVUSER;
$convPass = CONVPASS;
$convDb = CONVDB;
$db = new mysqli($convHost, $convUser, $convPass, $convDb);

$selectBronQry = "SELECT * FROM leegind ORDER BY persoonsnummer";
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
 * huurovereenkomsten lezen
 */
$selectHovQry = "SELECT * FROM leeghov ORDER BY hovnummer";
$hovResult = $db->query( $selectHovQry );
if ( !$hovResult ) {
	$fout = array( );
	$fout['onderwerp'] = "Geen huurovereenkomsten geconverteerd";
	$fout['bericht'] = "Er zijn geen huurovereenkomsten overgezet naar CiviCRM, de query $selectQry kon niet uitgevoerd worden.";
	logFout( $fout, true );
	return $aantalRec;
}
/*
 * voor ieder record uit brontabel
 * 
 */
while ( $nummerData = $hovResult->fetch_array( MYSQLI_ASSOC ) ) {
	/*
	 * bijbehorende gegevens ophalen uit firsthov
	 */
	$hovNummer = $nummerData['hovnummer']; 
	$selHovDetQry = "SELECT * FROM firsthov WHERE hovnummer = $hovNummer";
	$hovDetResult = $db->query( $selHovDetQry );
	if ( $hovData = $hovDetResult->fetch_array( MYSQLI_ASSOC ) ) {
		$okPers = createHovPers( $hovData );
	}
}
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
	$data['voorletters'] = "QQ";
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
/*----------------------------------------------------------------
 * Functie om addressen over te zetten
 *----------------------------------------------------------------*/
function verwerkAdres( $persoonsnummer, $contactID ) {
	global $db;
	/*
	 * alleen als contactID gevuld
	 */
	if ( empty( $contactID ) ) {
		return false;
	}
	/*
	 * eerst alle bestaande adressen uit CiviCRM verwijderen
	 */
	$adresDelQry = "DELETE FROM civicrm_address WHERE contact_id = $contactID";
	CRM_Core_DAO::executeQuery( $adresDelQry );
	/*
	 * daarna laatste 2 adressen uit bronbestand selecteren en
	 * in CiviCRM plaatsen
	 */
	$adresSelQry = "SELECT * FROM leegadres WHERE pers_nr = $persoonsnummer 
		ORDER BY startdatum DESC LIMIT 2";
	$adresRes = $db->query( $adresSelQry );
	$locationTypeID = null;
	$address = null;
	while ( $adresData = $adresRes->fetch_array( MYSQLI_ASSOC ) ) {
		$adresInsQry = "INSERT INTO civicrm_address SET contact_id = $contactID, 
			country_id = 1152";
		if ( empty ( $locationTypeID ) ) {
			$locationTypeID = LOCTYPETHUIS;
			$primary = 1;
		} else {
			$primary = 0;
			if ( empty( $adresData['einddatum'] ) ) {
				$locationTypeID = LOCTYPEOVERIG;
			} else {
				$locationTypeID = LOCTYPEOUD;
				$supp1 = "(Tot ".date("d-m-Y", strtotime( $adresData['einddatum'] ) ).
					" )";
				$adresInsQry .= ", supplemental_address_1 = '$supp1'";	
			}
		}
		$adresInsQry .= ", location_type_id = $locationTypeID, is_primary = $primary";
	
		if ( !empty( $adresData['straat'] ) ) {
			$streetname = CRM_Core_DAO::escapeString( $adresData['straat'] );
			$adresInsQry .= ", street_name = '$streetname'";
			$address = $streetname;
		}
		
		if ( !empty( $adresData['huisnummer'] ) ) {
			$streetnumber = $adresData['huisnummer'];
			$adresInsQry .= ", street_number = $streetnumber";
			$address .= " ".$streetnumber;
		}
		
		if ( !empty( $adresData['huisletter'] ) ) {
			$streetnumbersuffix = CRM_Core_DAO::escapeString( $adresData['huisletter'] );
		}
		
		if ( !empty( $adresData['toevoeging'] ) ) {
			if ( isset( $streetnumbersuffix ) &&  !empty( $streetnumbersuffix ) ) {
				$streetnumbersuffix .= " ".CRM_Core_DAO::escapeString( $adresData['toevoeging'] );
			} else {
				$streetnumbersuffix = CRM_Core_DAO::escapeString( $adresData['toevoeging'] );
			}	
			$adresInsQry .= ", street_number_suffix = '$streetnumbersuffix'";
			$address .= " ".$streetnumbersuffix;
		}
		
		if ( !empty( $address ) ) {
			$adresInsQry .= ", street_address = '$address'";
		}
		
		if ( !empty( $adresData['plaats'] ) ) {
			$city = CRM_Core_DAO::escapeString( $adresData['plaats'] );
			$adresInsQry .= ", city = '$city'";
		}
		
		if ( !empty( $adresData['postcode'] ) ) {
			$postalcode = CRM_Core_DAO::escapeString( $adresData['postcode']);
			$adresInsQry .= ", postal_code = '$postalcode'";
		}
		CRM_Core_DAO::executeQuery( $adresInsQry );
	} 
			
	return true;
}
/*----------------------------------------------------------------
 * Functie om emailadressen over te zetten
 *----------------------------------------------------------------*/
function verwerkEmail( $persoonsnummer, $contactID ) {
	global $db;
	/*
	 * alleen als contactID gevuld
	 */
	if ( empty( $contactID ) ) {
		return false;
	}
	/*
	 * eerst alle bestaande emailadressen uit CiviCRM verwijderen
	 */
	$emailDelQry = "DELETE FROM civicrm_email WHERE contact_id = $contactID";
	CRM_Core_DAO::executeQuery( $emailDelQry );
	/*
	 * daarna emailadressen uit bronbestand selecteren en
	 * in CiviCRM plaatsen (alleen actieven)
	 */
	$emailSelQry = "SELECT * FROM leegemail WHERE pers_nr = $persoonsnummer 
		AND (ISNULL(einddatum) OR einddatum > '20110601') ORDER BY 
		startdatum DESC";
	$emailRes = $db->query( $emailSelQry );
	$locationTypeID = null;
	while ( $emailData = $emailRes->fetch_array( MYSQLI_ASSOC ) ) {
		if ( $emailData['emailadres'] == "#MEER WAARDEN" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Emailadres heeft onherkenbare waarde";
			$log['bericht'] = "Een emailadres van First persoonsnummer ".$persoonsnummer." heeft een onherkenbare waarde uit First, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegemail";
			logFout( $log, false );
		} else {
			if ( strstr( $emailData['emailadres'], "@" ) == false ) {
				$log = array ( );
				$log['type'] = "controle";
				$log['onderwerp'] = "Emailadres heeft geen @";
				$log['bericht'] = "Een emailadres van First persoonsnummer ".$persoonsnummer." heeft geen @ in het emailadres uit First, nakijken!";
				$log['persoonsnummer'] = $persoonsnummer;
				$log['contact_id'] = $contactID;
				$log['brontabel'] = "leegemail";
				logFout( $log, false );

			} else {
			
				$emailInsQry = "INSERT INTO civicrm_email SET contact_id = $contactID"; 
				if ( empty ( $locationTypeID ) ) {
					$locationTypeID = LOCTYPETHUIS;
					$primary = 1;
				} else {
					$primary = 0;
					$locationTypeID = LOCTYPETHUIS;
				}
				$emailInsQry .= ", location_type_id = $locationTypeID, is_primary = $primary";
			
				if ( !empty( $emailData['emailadres'] ) ) {
					$email = CRM_Core_DAO::escapeString( $emailData['emailadres'] );
					$emailInsQry .= ", email = '$email'";
				}
				CRM_Core_DAO::executeQuery( $emailInsQry );
			}
		}
	} 
			
	return true;
}
/*----------------------------------------------------------------
 * Functie om telefoons over te zetten
 *----------------------------------------------------------------*/
function verwerkTel( $persoonsnummer, $contactID ) {
	global $db;
	/*
	 * alleen als contactID gevuld
	 */
	if ( empty( $contactID ) ) {
		return false;
	}
	/*
	 * eerst alle bestaande telefoonnummers uit CiviCRM verwijderen
	 */
	$telDelQry = "DELETE FROM civicrm_phone WHERE contact_id = $contactID";
	CRM_Core_DAO::executeQuery( $telDelQry );
	/*
	 * daarna telefoonnummers uit bronbestand selecteren en
	 * in CiviCRM plaatsen (alleen actieven)
	 */
	$telSelQry = "SELECT * FROM leegtel WHERE pers_nr = $persoonsnummer 
		AND (ISNULL(einddatum) OR einddatum > '20110601') ORDER BY 
		startdatum DESC";
	$telRes = $db->query( $telSelQry );
	$locationTypeID = null;
	while ( $telData = $telRes->fetch_array( MYSQLI_ASSOC ) ) {
		$telInsQry = "INSERT INTO civicrm_phone SET contact_id = $contactID"; 
		
		/*
		 * negeer gevallen met #MEER WAARDEN en E-MAIL
		 */
		$verwerk = true; 
		if ( $telData['nummer'] == "#MEER WAARDEN" ) {
			if ( $telData['type'] != "E-MAIL" ) {
				$log = array ( );
				$log['type'] = "controle";
				$log['onderwerp'] = "Telefoonnummer heeft onherkenbare waarde";
				$log['bericht'] = "Een telefoonnummer van CiviCRM contact ID ".$contactID." heeft een onherkenbare waarde uit First, nakijken!";
				$log['persoonsnummer'] = $persoonsnummer;
				$log['contact_id'] = $contactID;
				$log['brontabel'] = "leegtel";
				logFout( $log, false );
			}
			$verwerk = false;
		}
		
		if ( substr( $telData['nummer'], 0, 1 )  == "(" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer tussen haakjes";
			$log['bericht'] = "Een (deel van het) telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." staat tussen haakjes, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegtel";
			logFout( $log, false );
		}

		if ( substr( $telData['nummer'], 0, 2 )  == "06" && strtolower( $telData['type'] ) != "mobiele" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer is mobiel";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." heeft geen mobiel als type maar begint wel met 06, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegtel";
			logFout( $log, false );
		}
		
		if ( substr( $telData['nummer'], 0, 3 )  == "(06" && strtolower( $telData['type'] ) != "mobiele" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer is mobiel";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." heeft geen mobiel als type maar begint wel met (06, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegtel";
			logFout( $log, false );
		}

		if ( strpos( $telData['nummer'], " " ) != false ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer bavat spaties haakjes";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." bevat spaties, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegtel";
			logFout( $log, false );
		}

		if ( strlen( $telData['nummer'] )  > 15 ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer verdacht lang";
			$log['bericht'] = "De lengte van het telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." is langer dan 15 tekens, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "leegtel";
			logFout( $log, false );
		}
		
		if ($verwerk) { 
			if ( empty ( $locationTypeID ) ) {
				$locationTypeID = LOCTYPETHUIS;
				$primary = 1;
			} else {
				$primary = 0;
				if ( $telData['type'] == "WERKTEL") {
					$locationTypeID = LOCTYPEWERK;
				} else {
					$locationTypeID = LOCTYPETHUIS;
				}
			}
			$telInsQry .= ", location_type_id = $locationTypeID, is_primary = $primary";
		
			if ( !empty( $telData['type'] ) ) {
				$phonetypeID = bepaalPhoneType( $telData['type'] );
				$telInsQry .= ", phone_type_id = $phonetypeID";
			}
			
			if ( !empty( $telData['nummer'] ) ) {
				/*
				 * in sommige gevallen staat er een notitie achter
				 * het telefoonnummer. Deze er af plukken en als notitie
				 * toevoegen, en geval loggen!
				 */
				$teldelen = splitsTelNote ( $telData['nummer'] );
				if ( isset ( $teldelen['notitie'] ) ) {
					$log = array ( );
					$log['type'] = "controle";
					$log['onderwerp'] = "Telefoonnummer heeft een notitie in First";
					$log['bericht'] = "Een telefoonnummer van CiviCRM contact ID ".$contactID." heeft een notitie bij het telefoonnummer in First, dit is in CiviCRM gezet als een notitie en moet nagekeken worden. ";
					$log['persoonsnummer'] = $persoonsnummer;
					$log['contact_id'] = $contactID;
					$log['brontabel'] = "leegtel";
					logFout( $log, false );
					
					if ( !empty( $teldelen['notitie'] ) ) {
						$civiparms = array(
							"contact_id"    =>  $contactID,
							"entity_id"     =>  $contactID,
							"entity_table"  =>  "civicrm_contact",
							"note"          =>  $teldelen['notitie'],
							"subject"       =>  "Notitie uit First",
							"modified_date" =>  date("Ymd"));

						$result = civicrm_note_create($civiparms);
					}
				}
				$phone = CRM_Core_DAO::escapeString( $teldelen['telefoon'] );
				$telInsQry .= ", phone = '$phone'";
			}
			CRM_Core_DAO::executeQuery( $telInsQry );
		}
	} 
			
	return true;
}
/*-------------------------------------------------------------
 * functie om telefoon en evt. opmerking te scheiden
 *------------------------------------------------------------*/
function splitsTelNote( $input ) {
	/*
	 * alleen verwerken als input niet leeg
	 */
	 $tel['telefoon'] = null;
	 $tel['notitie'] = null;
	 
	 if ( !empty ( $input ) ) {
		 /*
		  * splits nummer in delen, gebruik spatie als
		  * separator
		  */
		 $inArray = array( );
		 $noteArray = array( );
		 $telArray = array( );
		 $inArray = explode( ' ', $input );
		 /*
		  * als er tekst in staat, notitie en loggen 
		  */
		 $j = count( $inArray ) - 1; 
		 if ( isset( $inArray[0] ) ) {
			 $telArray[] = $inArray[0];
		 }
		  
		 if ( isset ( $inArray[1] ) ) {
			for ( $i = 1; $i <= $j; $i++ ) {
				if ( is_numeric( substr( $inArray[$i],0,1 ) ) ) {
					$telArray[] = $inArray[$i];
				} else {
					$noteArray[] = $inArray[$i];
				}
			 }
			 $tel['telefoon'] = implode(" ", $telArray);
			 $tel['notitie'] = implode(" ", $noteArray);
		 } else {
			 $tel['telefoon'] = $inArray[0];
		 }
		  
	 }
	 return $tel;
}

/*---------------------------------------------------------------
 * functie om huurovereenkomst bij persoon bij te werken of toe
 * te voegen
 *-------------------------------------------------------------*/
function createHovPers( $data ) {
	global $db;
	/*
	 * fout als leeg of geen array
	 */
	if ( empty ( $data ) || !is_array( $data ) ) {
		return false;
	}
	/*
	 * klaarzetten gegevens voor update of create
	 */
	$hovNummer = $data['hovnummer']; 
	$persoonsNummer = $data['persoonsnummer']; 
	$contactID = $data['contact_id']; 
	$vgeNummer = CRM_Core_DAO::escapeString( trim( $data['vgenummer'] ) );
	$adresArray = array ( );
	if ( isset( $data['vgestraat'] ) && !empty( $data['vgestraat'] ) ) {
		$adresArray[] = trim ( $data['vgestraat'] );
	}
	if ( isset( $data['vgehuisnummer'] ) && !empty( $data['vgehuisnummer'] ) ) {
		$adresArray[] = trim ( $data['vgehuisnummer'] );
	}
	if ( isset( $data['vgehuisletter'] ) && !empty( $data['vgehuisletter'] ) ) {
		$adresArray[] = trim ( $data['vgehuisletter'] );
	}
	if ( isset( $data['vgetoevoeging'] ) && !empty( $data['vgetoevoeging'] ) ) {
		$adresArray[] = trim ( $data['vgetoevoeging'] );
	}
	$vgeAdres = CRM_Core_DAO::escapeString( trim( implode(" ", $adresArray ) ) );
	$corrNaam = CRM_Core_DAO::escapeString( trim( $data['correspondentie'] ) );
	if ( !empty( $data['hovstart'] ) ) {
		$startDate = date("Y-m-d", strtotime( $data['hovstart'] ) ); 
	}
	if ( !empty( $data['hoveind'] ) ) {
		$eindDate = date("Y-m-d", strtotime( $data['hoveind'] ) ); 
	}	
	if ( !empty( $data['huurderstart'] ) ) {
		$startDateHrd = date("Y-m-d", strtotime( $data['huurderstart'] ) );
	}
	if ( !empty( $data['huurdereind'] ) ) {
		$eindDateHrd = date("Y-m-d", strtotime( $data['huurdereind'] ) );
	} 
	/*
	 * haal alle personen van overeenkomst uit firsthov en bewaar in array
	 */
	$selPersQry =  "SELECT * FROM pershov WHERE hovnummer = $hovNummer";
	$persResult = $db->query( $selPersQry );
	while ( $persData = $persResult->fetch_array( MYSQLI_ASSOC ) ) {
		/*
		 * check type persoon aan firstpers
		 */
		$type = "mede";
		$selCheckHMQry = "SELECT hoofdhuurder FROM firstpers WHERE persoonsnummer 
			= ".$persData['persoonsnummer']; 
		$checkHMResult = $db->query( $selCheckHMQry );
		if ( $checkHM = $checkHMResult->fetch_array( MYSQLI_ASSOC ) ) {
			if ( $checkHM['hoofdhuurder'] == "J" ) {
				$type = "hoofd";
			}
		}
		if ( $type == "hoofd" ) {
			$hoofdID = $persData['persoonsnummer'];
			$hoofdStart = $persData['huurderstart'];
			$hoofdEind = $persData['huurdereind'];
		} else {
			$medeID = $persData['persoonsnummer'];
			$medeStart = $persData['huurderstart'];
			$medeEind = $persData['huurdereind'];
		}
	}
	/*
	 * nieuwe huurovereenkomst maken via API
	 */ 
	$hovParms =  array(
		'hov_nummer'	=>	$hovNummer,
		'vge_nummer'	=>	$vgeNummer,
		'vge_adres'		=>	$vgeAdres,
		'corr_name'		=>	$corrNaam);
	if ( isset( $startDate ) && !empty( $startDate ) ) {
		$hovParms['start_date'] = $startDate;
	}	
	if ( isset( $eindDate ) && !empty( $eindDate ) ) {
		$hovParms['end_date'] = $eindDate;
	}
	if ( isset( $hoofdID ) && !empty( $hoofdID ) ) {
		$hovParms['hh_persoon'] = $hoofdID;
		if ( isset( $hoofdStart ) && !empty( $hoofdStart ) ) {
			$hovParms['hh_start_date'] = date("Y-m-d", strtotime( $hoofdStart ) );
		}
		if ( isset( $hoofdEind ) && !empty( $hoofdEind ) ) {
			$hovParms['hh_end_date'] = date("Y-m-d", strtotime( $hoofdEind ) );
		}
	}
	if ( isset( $medeID ) && !empty( $medeID ) ) {
		$hovParms['mh_persoon'] = $medeID;
		if ( isset( $medeStart ) && !empty( $medeStart ) ) {
			$hovParms['mh_start_date'] = date("Y-m-d", strtotime( $medeStart ) );
		}
		if ( isset( $medeEind ) && !empty( $medeEind ) ) {
			$hovParms['mh_end_date'] = date("Y-m-d", strtotime( $medeEind ) );
		}
	}
	$hovResult = civicrm_dgwcontact_hovcreate( $hovParms );
}
/*-----------------------------------------------------------------
 * functie om burg staat id voor Civi op te halen met tekst
 *----------------------------------------------------------------*/
function bepaalBurgID( $burg ) {
	switch ( strtolower( trim( $burg ) ) ) {
		case "gehuwd":
			$burgID = 1;
			break;
		case "alleenstaand":
			$burgID = 2;
			break;
		case "samenwonend":
			$burgID = 3;
			break;
		case "geregistreerd partnerschap":
			$burgID = 4;
			break;
		case "gescheiden":
			$burgID = 5;
			break;
		case "weduwe of weduwnaar":
			$burgID = 6;
			break;
		case "onvolledig gezin":
			$burgID = 8;
			break;
		case "gehuwd geweest":
			$burgID = 9;
			break;
		case "ongehuwd":
			$burgID = 10;
			break;
		default:
			$burgID = 7;
			break;
	}
	return $burgID;
} 
/*-----------------------------------------------------------------
 * functie om met contactgegeven phone type te bepalen
 *----------------------------------------------------------------*/
 function bepaalPhoneType( $type ) {
	switch( strtolower( $type ) ) {
		 case "mobiele":
			$phonetype = 2;
			break;
		 case "geheim":
			$phonetype = 4;
			break;
		 case "werktel":
			$phonetype = 5;
			break;
		 case "contacttel":
			$phonetype = 6;
			break;
		 case "hometel":
			$phonetype = 7;
			break;
		 default:
			$phonetype = 1;
			break;
	}
	return $phonetype;	
}
/*---------------------------------------------------------------
 * functie om fouten te loggen en foutmail te versturen
 *--------------------------------------------------------------*/
function logFout( $fout , $mail ) {
	global $db;
	/*
	 * als onderwerp en bericht niet gevuld dit als fout melden
	 */
	if ( !CRM_Utils_Array::value( 'onderwerp', $fout ) && 
		!CRM_Utils_Array::value( 'bericht', $fout ) ) {
			$fout['onderwerp'] = "Geen onderwerp in foutmelding";
			$fout['bericht'] = "De functie logFout is aangeroepen met een leeg onderwerp en een leeg bericht!";
			$mail = true;
	}
			
	if ( empty( $fout['onderwerp'] ) && empty( $fout['bericht'] ) ) {
		$fout['onderwerp'] = "Geen onderwerp in foutmelding";
		$fout['bericht'] = "De functie logFout is aangeroepen met een leeg onderwerp en een leeg bericht!";
		$mail = true;
	}
	/*
	 * log fout in foutentabel
	 */
	$onderwerp = CRM_Core_DAO::escapeString( $fout['onderwerp'] );
	$bericht = CRM_Core_DAO::escapeString( $fout['bericht'] ); 
	$foutQry = "INSERT INTO fouten SET onderwerp = '$onderwerp', melding = '$bericht'";
	if ( CRM_Utils_Array::value( 'persoonsnummer', $fout ) ) {
		$persoonsnummer = CRM_Core_DAO::escapeString( $fout['persoonsnummer'] );
		$foutQry .= ", persoonsnummer = '$persoonsnummer'";
	}
	if ( CRM_Utils_Array::value( 'hovnummer', $fout ) ) {
		$hovnummer = CRM_Core_DAO::escapeString( $fout['hovnummer'] );
		$foutQry .= ", hovnummer = '$hovnummer'";
	}
	if ( CRM_Utils_Array::value( 'kovnummer', $fout ) ) {
		$kovnummer = CRM_Core_DAO::escapeString( $fout['kovnummer'] );
		$foutQry .= ", kovnummer = '$kovnummer'";
	}
	if ( CRM_Utils_Array::value( 'brontabel', $fout) ) {
		$brontabel = CRM_Core_DAO::escapeString( $fout['brontabel'] );
		$foutQry .= ", brontabel = '$brontabel'";
	}
	if ( CRM_Utils_Array::value( 'doeltabel', $fout ) ) {
		$doeltabel = CRM_Core_DAO::escapeString( $fout['doeltabel'] );
		$foutQry .= ", doeltabel = '$doeltabel'";
	}
	if ( CRM_Utils_Array::value( 'contactid', $fout) ) {
		$contactid = (int) $fout['contactid'];
		$foutQry .= ", contactid = $contactid";
	}
	if ( CRM_Utils_Array::value( 'type', $fout ) ) {
		$type = CRM_Core_DAO::escapeString( $fout['type'] );
	} else {
		$type = "fout";
	}
	$foutQry .= ", type = '$type'";
	$db->query( $foutQry );
	/*
	 * verstuur mail naar beheerder om op fout te attenderen als mail
	 */
	if ( $mail == true ) { 
		$mailparms = array( );
		$mailparms['subject'] = $onderwerp;
		if ( $type == "fout" ) {
			$mailparms['text'] = $bericht.", er is een fout gelogd in de tabel fouten in database dgwconv.";
		} else {
			$mailparms['text'] = $bericht;
		}
		$mailparms['toEmail'] = "hommel@ee-atwork.nl";
		$mailparms['toName'] = "Conversiebeheerder";
		$mailparms['from'] = "CiviCRM";
		CRM_Utils_Mail::send( $mailparms );
	}
}
?>
