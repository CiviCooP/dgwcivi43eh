<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvOrg.php                                    |
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
 * Functie om organisaties te verwerken
 *----------------------------------------------------------------*/
function verwerkOrg($orgTabel) {
	global $db, $orgAdd, $orgUpd;
	$orgProcessed = 0;
	$orgAdd = 0;
	$orgUpd = 0;
	/*
	 * lees alle records uit de brontabel (alleen organisaties)
	 */
	$selectBronQry = "SELECT * FROM $orgTabel ORDER BY persoonsnummer";
	$bronResult = $db->query( $selectBronQry );
	if ( !$bronResult ) {
		$fout = array( );
		$fout['onderwerp'] = "Geen organisaties geconverteerd";
		$fout['bericht'] = "Er zijn geen organisaties overgezet naar CiviCRM, de query $selectQry kon niet uitgevoerd worden.";
		logFout( $fout, true );
		return $aantalRec;
	}
	/*
	 * voor iedere organisatie uit brontabel
	 * 
	 */
	while ( $orgData = $bronResult->fetch_array( MYSQLI_ASSOC ) ) {
		
		$orgProcessed++;
		/*
		 * naam en sorteernaam samenstellen uit delen
		 */
		$arrayNaam = array( );
		$orgData['orgNaam'] = null;
		if ( isset( $orgData['aanhef'] ) && !empty( $orgData['aanhef'] ) ) {
			$arrayNaam[] = $orgData['aanhef'];
		}
		if ( isset( $orgData['voorletters'] ) && !empty( $orgData['voorletters'] ) ) {
			$arrayNaam[] = $orgData['voorletters'];
		}
		if ( isset( $orgData['tussenvoegsel'] ) && !empty( $orgData['tussenvoegsel'] ) ) {
			$arrayNaam[] = $orgData['tussenvoegsel'];
		}
		if (isset( $orgData['achternaam'] ) && !empty( $orgData['achternaam'] ) ) {
			$arrayNaam[] = $orgData['achternaam'];
		}
		$orgData['orgNaam'] = implode( " ", $arrayNaam );
		/*
		 * haal CiviCRM ID op met persoonsnummer first
		 */
		$civiparms = array(
			'contact_type' 	=> 'Individual',
			CFPERSNR		=>	$orgData['persoonsnummer']);
		$indRes = civicrm_contact_get( $civiparms );
		if (!civicrm_error( $indRes )) {
			foreach ($indRes as $individual) {
				$contact_id = $individual['contact_id'];
			}
		}
		/*
		 * check of de organisatie al als persoon voorkomt in CiviCRM
		 */
		$orgExistInd = checkOrgPers($orgData['persoonsnummer']);
		 /*
		  * als de organisatie al als persoon voorkomt, vastleggen in
		  * foutenbestand
		  */
		if ( $orgExistInd ) {
			 
			$log = array ( );
			$log['type'] = "controle";
			$log['onderwerp'] = "Organisatie komt al als persoon voor in CiviCRM";
			$log['bericht'] = "De organisatie ".$orgData['orgNaam']." met persoonsnummer ".$orgData['persoonsnummer']." komt al als persoon voor in CiviCRM (hetzelfde persoonsnummer First).";
			$log['bericht'] .= "De persoon is omgezet naar organisatie in CiviCRM. Deze gevallen moeten na conversie nagekeken worden!";
			$log['persoonsnummer'] = $orgData['persoonsnummer'];
			$log['brontabel'] = $orgTabel;
			logFout( $log, false );
			/*
			 * zet de persoon om naar organisatie
			 */
			switchIndOrg( $orgData, $contact_id );
			$orgUpd++; 
		
		} else {
			/*
			 * als organisatie nog niet voorkomt als persoon, nieuwe
			 * organisatie  toevoegen
			 */
			$contact_id = createOrg( $orgData );
			$orgAdd++;
		}
		/*
		 * adressen uit First zetten
		 */
		verwerkAdres( $orgData['persoonsnummer'], $contact_id );
		/*
		 * telefoonnnummers uit First zetten
		 */
		verwerkTel ( $orgData['persoonsnummer'], $contact_id );
		/*
		 * updaten emailadres als nodig
		 */
		 verwerkEmail( $orgData['persoonsnummer'], $contact_id );
		 /*
		  * voeg toe aan speciale groep ter controle
		  */
		 plaatsGroep( 16, "DefConvOrg", $contact_id ); 
	}
	return $orgProcessed;
}
/*----------------------------------------------------------------
 * Functie om te checken of de organisatie al bestaat als persoon
 *----------------------------------------------------------------*/
function checkOrgPers( $nummer ) {
	global $db;
	/*
	 * fout als nummer leeg
	 */
	if ( empty ( $nummer ) ) {
		return false;
	}
	/*
	 * check of nummer al voorkomt in tabel aanvullende personen
	 */
	$checkQry = "SELECT COUNT(*) AS aantal FROM ".TABFIRSTPERS." WHERE ".
		FLDPERSNR." = '$nummer'";
	$daoCheck = CRM_Core_DAO::executeQuery( $checkQry );
	if ( $daoCheck->fetch( ) ) {
		if ( $daoCheck->aantal > 0 ) {
			return true;
		}
	}
	return false;
}
/*----------------------------------------------------------------
 * Functie om bestaand persoon om te zetten naar organisatie
 *----------------------------------------------------------------*/
function switchIndOrg( $data, $contactID ) {
	/*
	 * samenstellen sorteernaam
	 */
	$orgNaam = CRM_Core_DAO::escapeString( $data['orgNaam'] );
	$arraySort = array( );
	$orgSort = null;
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
	$orgSort = implode( " ", $arraySort );
	$orgSort = CRM_Core_DAO::escapeString( $orgSort );
	/*
	 * wijzig contact record in civicrm_contact
	 */
	$updContactQry = "UPDATE civicrm_contact SET contact_type = 
		'Organization',  first_name = null, middle_name = null,
		last_name = null, prefix_id = null, suffix_id = null, email_greeting_id 
		= null, email_greeting_display = null, postal_greeting_id = null,
		postal_greeting_display = null, addressee_id = null, addressee_display 
		= null, job_title = null, gender_id = null, birth_date = null,
		is_deceased = 0, deceased_date = null, employer_id = null, 
		organization_name = '$orgNaam', display_name = '$orgNaam', sort_name 
		= '$orgSort' WHERE id = $contactID";
	CRM_Core_DAO::executeQuery( $updContactQry );
	/*
	 * persoonsnummer vastleggen in aanvullende tabel voor organisaties
	 */
	$insAanvQry = "INSERT INTO ".TABFIRSTORG." SET entity_id = $contactID, 
		".FLDORGPERSNR." = '".$data['persoonsnummer']."'";
	CRM_Core_DAO::executeQuery( $insAanvQry );
	/*
	 * verwijderen aanvullende gegevens persoon
	 */
	$delAanvQry = "DELETE FROM ".TABFIRSTPERS." WHERE entity_id = $contactID";
	CRM_Core_DAO::executeQuery( $delAanvQry );
	 
}
/*----------------------------------------------------------------
 * Functie om nieuwe organisatie te maken
 *----------------------------------------------------------------*/
function createOrg( $data ) {
	/*
	 * samenstellen sorteernaam
	 */
	$orgNaam = CRM_Core_DAO::escapeString( $data['orgNaam'] );
	$arraySort = array( );
	$orgSort = null;
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
	$orgSort = implode( " ", $arraySort );
	$orgSort = CRM_Core_DAO::escapeString( $orgSort );
	$civiparms = array(
		'contact_type'		=>	"Organization",
		'organization_name'	=>	$orgNaam);
	$civires = civicrm_contact_add($civiparms);
	if (! civicrm_error ( $civires ) ) {
		$contactID = $civires['contact_id'];
	}
	/*
	 * persoonsnummer vastleggen in aanvullende tabel voor organisaties
	 */
	$insAanvQry = "INSERT INTO ".TABFIRSTORG." SET entity_id = $contactID, 
		".FLDORGPERSNR." = '".$data['persoonsnummer']."'";
	CRM_Core_DAO::executeQuery( $insAanvQry );
	return $contactID;
}

		
