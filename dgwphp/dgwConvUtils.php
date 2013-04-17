<?php
/*+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
 |   script dgwConUtils.php                                   |
 |   autuer		:	Erik Hommel (hommel@ee-atwork.nl)         |
 |   datum		:	27 mei 2011                               |
 |	 beschr		:	De Goede Woning conversue helpfuncties    |
 +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++*/
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
/*------------------------------------------------------------------
 * functie om te checken of telefoon mobiel is
 *-----------------------------------------------------------------*/
function bepaalMobiel( $telefoon ) {
	$mobiel = false;
	if ( substr ( $telefoon, 0, 2 ) == "06" )  {
		$mobiel = true;
	}
	return $mobiel;
}
/*--------------------------------------------------------------
 * functie om geslacht vanuit First te vertalen naar CiviCRM geslacht
 *--------------------------------------------------------------*/
function civiGeslacht( $input ) {
	switch(strtolower( $input ) ) {
		case "vrouw":
			$geslacht = 1;
            break;
		case "man":
			$geslacht = 2;
                break;
		default:
			$geslacht = 3;
			break;
	}
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
/*--------------------------------------------------------------------
 * Functie om bronbestand naar MySQL te laden en de quotes om de 
 * tekstvelden te verwijderen
 *------------------------------------------------------------------*/
 function laadBronTabel( $bron, $doel ) {
	 global $db;
	 /*
	  * fout als bron of doel leeg
	  */
	 if ( empty($bron ) or empty( $doel ) ) {
		 return false;
	 }
	 /*
	  * doeltabel leegmaken
	  */
	 if ( $db->query( "TRUNCATE TABLE $doel" ) === false ) {
		 return false;
	 } 
	 /*
	  * brongegevens laden in doeltabel
	  */
	 $laadBronQry = "LOAD DATA LOCAL INFILE '".$bron."' INTO TABLE $doel 
		FIELDS TERMINATED BY ';'";
	 if ( $db->query( $laadBronQry ) === false ) {
		 return false;
	 }
	 /*
	  * quotes van de voorkant van de tekstvelden verwijderen
	  */
		$correctFields = array(
			'aanhef', 
			'voorletters', 
			'tussenvoegsel', 
			'achternaam', 
			'geslacht', 
			'gebdat', 
			'contacttype', 
			'contactgegeven',
			'startdatum',
			'einddatum',
			'adres_startdatum',
			'adres_einddatum',
			'straat',
			'huisnummer',
			'huisletter',
			'toevoeging',
			'postcode',
			'plaats',
			'hoofdhuurder',
			'huurder',
			'relatie',
			'sofinummer',
			'burg');
		foreach( $correctFields as $naam ) {
			$correctQry = "UPDATE $doel SET ".$naam." = SUBSTR(".$naam.
				",2,(LENGTH(".$naam.")-1))";
			if ( $db->query( $correctQry ) === false ) {
				return false;
			}
			$correctQry = "UPDATE $doel SET ".$naam." = SUBSTR(".$naam.
				", 1, LENGTH(".$naam.")-1) WHERE RIGHT(".$naam.",1) 
				= '\"'";
			if ( $db->query( $correctQry ) === false ) {
				return false;
			}
		}
		return true;
}
/*--------------------------------------------------------------------
 * Functie om brontabel MySQL te splitsen in een deel met organisaties
 * en een deel met personen
 *------------------------------------------------------------------*/
function splitBronTabel( $bron, $org, $ind ) {
	global $db;
	$orgInd = "Niet van toepassing";
	/*
	 * fout als bron, org of ind leeg zijn
	 */
	if ( empty( $bron ) || empty( $org ) || empty( $ind ) ) {
		return false;
	}
	/*
	 * tabellen voor organisaties en personen leegmaken
	 */
	if ( $db->query( "TRUNCATE TABLE $org" ) === false ) {
		return false;
	} 
	if ( $db->query( "TRUNCATE TABLE $ind" ) === false ) {
		return false;
	}
	/*
	 * selecteer organisaties uit bronbestand en breng die onder in de
	 * organisatietabel
	 */
	$orgQry = "INSERT INTO $org (SELECT distinct(persoonsnummer), aanhef, 
		voorletters, tussenvoegsel, achternaam, geslacht FROM $bron WHERE 
		geslacht = '$orgInd')"; 
	$db->query( $orgQry );
	/*
	 * selecteer personen uit bronbestand en breng die onder in de
	 * persoontabel
	 */
	$indQry = "INSERT INTO $ind (SELECT distinct(persoonsnummer), aanhef, 
		voorletters, tussenvoegsel, achternaam, geslacht, gebdat, sofinummer, 
		burg FROM $bron WHERE geslacht <> '$orgInd')"; 
	$db->query( $indQry );
	return true; 
}
function splitsContactGegevens ( $bron ) {
	/*
	 * maak doeltabellen leeg
	 */
	global $db; 
	$truncQry = "TRUNCATE TABLE brontel";
	$db->query( $truncQry );
	$truncQry = "TRUNCATE TABLE bronemail";
	$db->query( $truncQry );
	$truncQry = "TRUNCATE TABLE bronadres";
	$db->query( $truncQry );
	/*
	 * haal adressen uit brontabel
	 */
	$adresSelQry = "SELECT persoonsnummer, straat, huisnummer, huisletter, 
		toevoeging, postcode, plaats, adres_startdatum, adres_einddatum 
		FROM $bron ORDER BY persoonsnummer, adres_startdatum DESC";
	$adresRes = $db->query( $adresSelQry );
	while ( $adresData = $adresRes->fetch_array( MYSQLI_ASSOC ) ) {
		$persoonsnummer = (int)  $adresData['persoonsnummer'];
		$huisnummer = (int) $adresData['huisnummer'];
		$toevoeging = $db->real_escape_string( trim( $adresData['toevoeging'] ) );
		$straat = $db->real_escape_string( $adresData['straat'] );
		$postcode = $db->real_escape_string( $adresData['postcode'] );
		$plaats = $db->real_escape_string( $adresData['plaats'] );
		/*
		 * check of record al bestaat, voeg alleen toe als dat niet zo is
		 */
		$checkAantalQry = "SELECT COUNT(*) AS aantal FROM bronadres WHERE 
			pers_nr = $persoonsnummer AND straat = '$straat' AND huisnummer 
			= $huisnummer AND toevoeging = '$toevoeging' AND postcode = 
			'$postcode' AND plaats = '$plaats'";
		$checkAantalRes = $db->query( $checkAantalQry );
		if ($checkAantalData = $checkAantalRes->fetch_array( MYSQLI_ASSOC ) ) {
		if ( $checkAantalData['aantal'] == 0 ) {
				$startdatum = date( "Ymd", strtotime( $adresData['adres_startdatum'] ) );
				if ( !empty( $adresData['adres_einddatum'] ) ) {
					$einddatum = date( "Ymd", strtotime( $adresData['adres_einddatum'] ) );
				} else {
					$einddatum = null;
				}
				if ( !empty ( $adresData['toevoeging'] ) ) {
					$toevoeging = $adresData['huisletter']." ".$adresData['toevoeging'];
				} else {
					$toevoeging = $adresData['huisletter'];
				}
				$adresInsQry = "INSERT INTO bronadres SET pers_nr = $persoonsnummer, 
					straat = '$straat', huisnummer = $huisnummer, toevoeging = '$toevoeging', 
					postcode = '$postcode', plaats = '$plaats', startdatum = '$startdatum'";
				if ( isset ( $einddatum ) && !empty ( $einddatum ) ) {	 
					$adresInsQry .= ", einddatum = '$einddatum'";
				}
				$db->query( $adresInsQry );
			}
		}
	}
	/*
	 * verwijder alle record waarbij startdatum gelijk is aan einddatum (invoerfouten)
	 */
	$adresStartEindQry = "DELETE FROM bronadres WHERE startdatum = einddatum";
	$db->query( $adresStartEindQry ); 	
	/*
	 * haal emailadres uit brontabel
	 */
	$emailSelQry = "SELECT persoonsnummer, contacttype, contactgegeven, 
		startdatum, einddatum FROM $bron WHERE contacttype = 'E-MAIL' OR 
		contactgegeven LIKE '%@%'";
	$emailRes = $db->query( $emailSelQry );
	while ( $emailData = $emailRes->fetch_array( MYSQLI_ASSOC ) ) {
		$persoonsnummer = (int)  $emailData['persoonsnummer'];
		$type = $db->real_escape_string( $emailData['contacttype'] );
		$emailadres = $db->real_escape_string( trim( $emailData['contactgegeven'] ) );
		/*
		 * check of record al bestaat, voeg alleen toe als dat niet zo is
		 */
		$checkAantalQry = "SELECT COUNT(*) AS aantal FROM bronemail WHERE 
			pers_nr = $persoonsnummer AND type = '$type' AND emailadres = 
			'$emailadres'";
		$checkAantalRes = $db->query( $checkAantalQry );
		if ($checkAantalData = $checkAantalRes->fetch_array( MYSQLI_ASSOC ) ) {
			if ( $checkAantalData['aantal'] == 0 ) {
				$startdatum = date( "Ymd", strtotime( $emailData['startdatum'] ) );
				if ( !empty( $emailData['einddatum'] ) ) {
					$einddatum = date( "Ymd", strtotime( $emailData['einddatum'] ) );
				} else {
					$einddatum = null;
				}
				$emailInsQry = "INSERT INTO bronemail SET pers_nr = $persoonsnummer, 
					type = '$type', emailadres = '$emailadres', startdatum = '$startdatum'"; 
				if ( isset ( $einddatum ) && !empty ( $einddatum ) ) {	 
					$emailInsQry .= ", einddatum = '$einddatum'";
				}
				$db->query( $emailInsQry );
			}
		}
	}	
	/*
	 * haal telefoon uit brontabel
	 */
	$telSelQry = "SELECT persoonsnummer, contacttype, contactgegeven, 
		startdatum, einddatum FROM $bron WHERE (contacttype <> 'E-MAIL' 
		OR contactgegeven NOT LIKE '%@%')";
	$telRes = $db->query( $telSelQry );
	while ( $telData = $telRes->fetch_array( MYSQLI_ASSOC ) ) {
		$persoonsnummer = (int)  $telData['persoonsnummer'];
		$type = $db->real_escape_string( $telData['contacttype'] );
		$nummer = $db->real_escape_string( trim( $telData['contactgegeven'] ) );
		/*
		 * check of record al bestaat, voeg alleen toe als dat niet zo is
		 */
		$checkAantalQry = "SELECT COUNT(*) AS aantal FROM brontel WHERE 
			pers_nr = $persoonsnummer AND type = '$type' AND nummer = 
			'$nummer'";
		$checkAantalRes = $db->query( $checkAantalQry );
		if ($checkAantalData = $checkAantalRes->fetch_array( MYSQLI_ASSOC ) ) {
			if ( $checkAantalData['aantal'] == 0 ) {
				$startdatum = date( "Ymd", strtotime( $telData['startdatum'] ) );
				if ( !empty( $telData['einddatum'] ) ) {
					$einddatum = date( "Ymd", strtotime( $telData['einddatum'] ) );
				} else {
					$einddatum = null;
				}
				$telInsQry = "INSERT INTO brontel SET pers_nr = $persoonsnummer, 
					type = '$type', nummer = '$nummer', startdatum = '$startdatum'"; 
				if ( isset ( $einddatum ) && !empty ( $einddatum ) ) {	 
					$telInsQry .= ", einddatum = '$einddatum'";
				}
				$db->query( $telInsQry );
			}
		}
	}	
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
	$telSelQry = "SELECT * FROM brontel WHERE pers_nr = $persoonsnummer 
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
				$log['brontabel'] = "brontel";
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
			$log['brontabel'] = "brontel";
			logFout( $log, false );
		}

		if ( substr( $telData['nummer'], 0, 2 )  == "06" && strtolower( $telData['type'] ) != "mobiele" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer is mobiel";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." heeft geen mobiel als type maar begint wel met 06, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "brontel";
			logFout( $log, false );
		}
		
		if ( substr( $telData['nummer'], 0, 3 )  == "(06" && strtolower( $telData['type'] ) != "mobiele" ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer is mobiel";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." heeft geen mobiel als type maar begint wel met (06, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "brontel";
			logFout( $log, false );
		}

		if ( strpos( $telData['nummer'], " " ) != false ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer bavat spaties haakjes";
			$log['bericht'] = "Een telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." bevat spaties, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "brontel";
			logFout( $log, false );
		}

		if ( strlen( $telData['nummer'] )  > 15 ) {
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Telefoonnummer verdacht lang";
			$log['bericht'] = "De lengte van het telefoonnummer ".$telData['nummer']." van CiviCRM contact ID ".$contactID." is langer dan 15 tekens, nakijken!";
			$log['persoonsnummer'] = $persoonsnummer;
			$log['contact_id'] = $contactID;
			$log['brontabel'] = "brontel";
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
					$log['brontabel'] = "brontel";
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
	$emailSelQry = "SELECT * FROM bronemail WHERE pers_nr = $persoonsnummer 
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
			$log['brontabel'] = "bronemail";
			logFout( $log, false );
		} else {
			if ( strstr( $emailData['emailadres'], "@" ) == false ) {
				$log = array ( );
				$log['type'] = "controle";
				$log['onderwerp'] = "Emailadres heeft geen @";
				$log['bericht'] = "Een emailadres van First persoonsnummer ".$persoonsnummer." heeft geen @ in het emailadres uit First, nakijken!";
				$log['persoonsnummer'] = $persoonsnummer;
				$log['contact_id'] = $contactID;
				$log['brontabel'] = "bronemail";
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
	$adresSelQry = "SELECT * FROM bronadres WHERE pers_nr = $persoonsnummer 
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
		
		if ( !empty( $adresData['toevoeging'] ) ) {
			$streetnumbersuffix = CRM_Core_DAO::escapeString( $adresData['toevoeging'] );
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
 * functie om contact toe te voegen aan een groep. 
/*----------------------------------------------------------------*/
function plaatsGroep( $groupID, $groupTitle, $contactID ) {
	/*
	 * alleen als alle velden gevuld
	 */
	if ( empty( $groupID ) || empty( $groupTitle ) || empty( $contactID ) ) {
		return false;
	}
	/*
	 * alleen verder als groep bestaat
	 */
	 $civiparms = array( 'id' => $groupID );
	 $civires = civicrm_group_get ( $civiparms );
	 if ( civicrm_error( $civires ) ) {
		 return false;
	 }
	/*
	 * contact aan groep toevoegen
	 */
	$civiparms = array(
		'contact_id.1' => $contactID,
		'group_id'     => $groupID);
	$civires = civicrm_group_contact_add( $civiparms );
	return true;
}


		 
