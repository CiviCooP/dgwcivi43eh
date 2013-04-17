<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwLaadKovMnup                         |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       30 maart 2011                                |
| Description   :       Script zet koopovereenkomsten uit First over |
|						naar CiviCRM. Het laden kan via een cronjob  |
|						gebeuren of via een menuoptie. Dat wordt via |
|                       een GET variabele doorgegeven (mode)         | 
+--------------------------------------------------------------------+
* */
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);

$mode = null;
require_once("dgwConfig.php");
require_once("CRM/Utils/Mail.php");
require_once("CRM/Utils/String.php");

$fout = false; 

if (isset($_GET['mode'])) {
	if ($_GET['mode'] == "CiviCRM" || $_GET['mode'] == "cron") {
		$mode = trim($_GET['mode']);
	} else {
		$fout = true;
		$foutBericht = "Het laden van koopovereenkomsten in CiviCRM is niet mogelijk zonder geldige parameters!";
		$foutSubject = "Laden KOV naar CiviCRM is mislukt, geen geldige parameters!";
		stuurFout($mode, $foutSubject, $foutBericht);
	}

} else {
	$fout = true;
	$foutBericht = "Het laden van koopovereenkomsten in CiviCRM is niet mogelijk zonder geldige parameters!";
	$foutSubject = "Laden KOV naar CiviCRM is mislukt, geen geldige parameters!";
	stuurFout($mode, $foutSubject, $foutBericht);
}
if (!$fout) {	
	if ($mode == "CiviCRM") {
		require_once("CRM/Core/Session.php");
		CRM_Core_Session::setStatus("Start!");
	}

	/*
	 * controleer of het bestand voor kov's op de server staat. De bestandsnaam
	 * komt uit dgwConfig (KOVFILENAME), daarachter wordt de datum in 
	 * formaat jjjjmmdd geplakt. De map waar het bestand op staat 
	 * (KOVPATH) komt uit dgwConfig. Als het bestand niet bestaat wordt er 
	 * een mail verstuurd naar de beheerder waarvan het mailadres in 
	 * dgwConfig staat (KOVMAIL). Bestaat het bestand wel, dan wordt het 
	 * geimporteerd naar de MySQL tabel uit dgwConfig (KOVTABLE) in de
	 * database KOVDB.
	 */
	$bestand = KOVPATH.KOVFILENAME.date("Ymd").".csv"; 
	/*
	 * als bestand niet bestaat, afhankelijk van de mode een mail aan de 
	 * beheerder sturen of een error message in CiviCRM 
	 */
	if (!file_exists($bestand)) {
		$fout = true;
		$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat het bestand ".$bestand." niet bestaat. Zorg ervoor dat het bestand alsnog op de juiste plek op de server komt en laad dan de koopovereenkomsten in CiviCRM middels de menuoptie Beheer/Laden koopovereenkomsten.";
		$foutSubject = "Laden KOV naar CiviCRM is mislukt, bestand bestaat 
			niet";
		stuurFout($mode, $foutSubject, $foutBericht);
	}
	 
	/*
	 * verbinding maken met database voor kov
	 */
	if (!$fout) { 
		$kovdb = new mysqli(DGWLOGHOST, DGWLOGUSER, DGWLOGWW, KOVDB);
		if (mysqli_connect_errno()) {
			$fout = true;	
			$foutSubject = "Laden KOV naar CiviCRM mislukt, geen 
				verbinding met de database";
			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat er geen verbinding gemaakt kon worden met de database ".KOVDB." met de gebruiker ".DGWLOGUSER." op host ".DGWLOGHOST;
			stuurFout($mode, $foutSubject, $foutBericht);
		}
	}
	if (!$fout) { 
		$kovtable = KOVTABLE;
		$kovheader = KOVHEADER;
		/*
		 * tabel leegmaken, als niet bestaat fout melden
		 */
		if ($kovdb->query("TRUNCATE TABLE $kovtable") === false) {
			$fout = true;
			$foutSubject = "Laden KOV naar CiviCRM mislukt, doeltabel in MySQL 
				niet gevonden";
			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat tabel $kovtable niet leegemaakt kon worden (bestaat niet?). Foutboodschap van MySQL : ".$kovdb->error;
			stuurFout($mode, $foutSubject, $foutBericht);
		}
	}
	if (!$fout) {
		/*
		 * gegevens uit csv bestand importeren in MySQL tabel
		 */
		$kovQry = "LOAD DATA LOCAL INFILE '".$bestand. "' INTO TABLE ".$kovtable." FIELDS TERMINATED BY ';'"; 
		if ($kovdb->query($kovQry) === false) {
			$fout = true;
			$foutSubject = "Laden KOV naar CiviCRM mislukt, importeren gegevens 
				in MySQL tabel mislukt!";
			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM met query $kovQry is mislukt omdat de gegevens niet geimporteerd konden worden. Foutboodschap van MySQL : ".$kovdb->error;
			stuurFout($mode, $foutSubject, $foutBericht);
		}
	}
	if (!$fout) {
 		/*
		 * Quotes voor en achter de tekstvelden verwijderen (alleen voor lokaal ivm verschil Excel en LibreOffice Calc)
		 */
		//$correctFields = array(
		//	'corr_naam', 
		//	'vge_adres', 
		//	'vge_nr',
		//	'type', 
		//	'spec', 
		//	'notaris', 
		//	'taxateur',
		//	'tax_waarde', 
		//	'tax_datum',
		//	'bouwkundige',
		//	'bouw_datum',
		//	'ov_datum',
		//	'definitief');
		//foreach($correctFields as $naam) {
		//	if (!$fout) {
		//		$kovCorrectQry = "UPDATE ".$kovtable." SET ".$naam." = SUBSTR(".$naam.",2,(LENGTH(".$naam.")-1))";
		//		if ($kovdb->query($kovCorrectQry) === false) {
		//			$fout = true;
		//			$foutSubject = "Laden KOV naar CiviCRM mislukt, corrigeren quotes bij gegevens 
		//				in MySQL tabel mislukt!";
		//			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de query $kovCorrectQry niet uitgevoerd kon worden. Foutboodschap van MySQL : ".$kovdb->error;
		//			stuurFout($mode, $foutSubject, $foutBericht);
		//		}
		//		if (!$fout) {
		//			$kovCorrectQry = "UPDATE ".$kovtable." SET ".$naam." = SUBSTR(".$naam.", 1, LENGTH(".$naam.")-1) WHERE RIGHT(".$naam.",1) = '\"'";
		//			$kovdb->query($kovCorrectQry);
		//			if ($kovdb->query($kovCorrectQry) === false) {
		//				$fout = true;
		//				$foutSubject = "Laden KOV naar CiviCRM mislukt, corrigeren quotes bij gegevens 
		//					in MySQL tabel mislukt!";
		//				$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de query $kovCorrectQry niet uitgevoerd kon worden. Foutboodschap van MySQL : ".$kovdb->error;
		//				stuurFout($mode, $foutSubject, $foutBericht);
		//			}
		//		}
		//	}
		//}
		/*
		 * uitsplitsen header bestand door unieke kov nummers uit te halen
		 */
		if ($kovdb->query("TRUNCATE TABLE ".$kovheader) === false) {
			$fout = true;
			$foutSubject = "Laden KOV naar CiviCRM mislukt, headertabel kon niet leeggemaakt worden.";
			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de tabel $kovheader niet leeggemaakt kon worden. Foutboodschap van MySQL : ".$kovdb->error;
			stuurFout($mode, $foutSubject, $foutBericht);
		}
		if (!$fout) {
			$kovHdrInsert = "INSERT INTO $kovheader (SELECT DISTINCT(kov_nr),
				vge_nr, corr_naam, ov_datum, vge_adres, type, prijs, notaris, 
				tax_waarde, taxateur, tax_datum, bouwkundige, bouw_datum, definitief
				FROM $kovtable)";
			if ($kovdb->query($kovHdrInsert) === false) {
				$fout = true;
				$foutSubject = "Laden KOV naar CiviCRM mislukt, laden headertabel mislukt.";
				$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de tabel $kovheader niet geladen kon worden met de query $kovHdrInsert. Foutboodschap van MySQL : ".$kovdb->error;
				stuurFout($mode, $foutSubject, $foutBericht);
			}
		}
		/*
		 * lezen headerbestand koopovereenkomst
		 */
		 
		$kovHdrQry = "SELECT * FROM $kovheader";
		$kovHdrResult = $kovdb->query($kovHdrQry);
		if (!$kovHdrResult) {
			$fout = true;
			$foutSubject = "Laden KOV naar CiviCRM mislukt, geen gegevens in importbestand?";
			$foutBericht = "Het dagelijks laden van de koopovereenkomsten naar CiviCRM is mislukt omdat de query $kovHdrQry niet uitgevoerd kon worden. Foutboodschap van MySQL : ".$kovdb->error;
			stuurFout($mode, $foutSubject, $foutBericht);
		}
		/*
		 * alle records verwerken
		 */
		if (!$fout) {
			/*
			 * initialiseren klassen voor laden in CiviCRM
			 */
			require_once(CUSTOMDIR."/CiviContact.php");
			require_once(CUSTOMDIR."/CiviPersoon.php");
			require_once(CUSTOMDIR."/CiviHuishouden.php");
			require_once("api/v2/Contact.php");
			require_once("api/v2/Relationship.php");
			require_once("api/v2/Location.php");
			require_once("api/v2/Group.php");
			require_once("api/v2/GroupContact.php");
			$leeg = array();
			$huishouden = new CiviHuishouden($leeg);
			$persoon = new CiviPersoon($leeg);
			
			while ($kovHdr = $kovHdrResult->fetch_array(MYSQLI_ASSOC)) {
				/*
				 * waarden velden bewerken waar nodig voor load in CiviCRM
				 */
				if ( !empty( $kovHdr['kov_nr'] ) && $kovHdr['kov_nr'] != 0 ) { 
					$kov_nr = (int) $kovHdr['kov_nr'];
					$vge_nr = (int) $kovHdr['vge_nr'];
					$corr_naam = (string) $kovHdr['corr_naam'];
					if (!empty($kovHdr['ov_datum'])) {
						$kovHdr['ov_datum'] = correctDatum($kovHdr['ov_datum'] );
						$ov_datum = date("Y-m-d", strtotime($kovHdr['ov_datum']));
					} else {
						$ov_datum = null;
					}
					$vge_adres = (string) $kovHdr['vge_adres'];
					$type = bepaalType($kovHdr['type']);
					if (is_numeric($kovHdr['prijs'])) {
						$prijs = (int) $kovHdr['prijs'];
					} else {
						$prijs = 0;
					}
					$notaris = opmaakHoofdNamen( $kovHdr['notaris'] );
					if (is_numeric($kovHdr['tax_waarde'])) {
						$tax_waarde = (int) $kovHdr['tax_waarde'];
					} else {
						$tax_waarde = 0;
					}
					$taxateur = opmaakHoofdNamen( $kovHdr['taxateur'] );
					if (!empty($kovHdr['tax_datum'])) {
						$kovHdr['tax_datum'] = correctDatum($kovHdr['tax_datum'] );
						$tax_datum = date("Y-m-d", strtotime($kovHdr['tax_datum']));
					} else {
						$tax_datum = "";
					}
					$bouwkundige = opmaakHoofdNamen( $kovHdr['bouwkundige'] );
					if (!empty($kovHdr['bouw_datum'])) {
						$kovHdr['bouw_datum'] = correctDatum($kovHdr['bouw_datum'] );
						$bouw_datum = date("Y-m-d", strtotime($kovHdr['bouw_datum']));
						
					} else {
						$bouw_datum = "";
					}
					if ( trim( $kovHdr['definitief'] ) == "J") {
						$definitief = 1;
					} else {
						$definitief = 0;
					}
					/*
					 * initialiseer variabele om te bepalen of er een
					 * huishouden aangemaakt moet worden
					 */
					$maakHuisH = true;
					/*
					 * haal alle personen op voor de koopovereenkomst en plaats
					 * persoonnummers in array
					 */
					$kovPersQry = "SELECT DISTINCT(pers_nr) FROM $kovtable WHERE kov_nr = $kov_nr";
					$kovPersResult = $kovdb->query($kovPersQry);
					$i = 0;
					$personenKov = array( );
					while ($kovPers = $kovPersResult->fetch_array(MYSQLI_ASSOC)) {
						/*
						 * plaats persoon in array om later relaties mee te maken
						 */
						$dataPersoon = $persoon->retrievePersoonFirst($kovPers['pers_nr']);
						$personenKov[$i] = $dataPersoon['contact_id'];
						$i++;
						 /*
						  * check of de persoon ergens hoofdhuurder is en als
						  * dat het geval is, gebruik dat huishouden
						  */
						$hoofdHuurder = $persoon->checkPersHoofd($kovPers['pers_nr']);
						if ($hoofdHuurder != "geen") {
							$HuisHoudenID = (int) $hoofdHuurder;
							$maakHuisH = false;
						}
						/*
						 * als nog geen huishouden gevonden, kijk of er 
						 * een relatie is van het type koopovereenkomst partner
						 * met één van de personen. Zo ja, gebruik dat huishouden
						 */
						$kovPartner = $persoon->checkPersKovPartner($kovPers['pers_nr']);
						if ($kovPartner != "geen") {
							$HuisHoudenID = (int) $kovPartner;
							$maakHuisH = false;
						}
					}
					/*
					 * als na verwerking personen nog steeds maakHuisH, dan
					 * huishouden aanmaken en in correctiegroep plaatsen
					 */
					if ($maakHuisH) {
						$civiParms = array("household_name"    =>  $corr_naam);
						$civires = $huishouden->addHuishouden($civiParms);
						if (!civicrm_error($civires)) {
							$HuisHoudenID = (int) $civires['contact_id'];
						}
						$parms = array(
						"group_id"      =>  KOVFOUTGRP,
						"contact_id"    =>  $HuisHoudenID,
						"name"          =>  KOVFOUTNM);
						$huishouden->addContactGroup($parms);
						/*
						 * haal adres, telefoon en email op van 1e persoon en
						 * neem die over in het huishouden
						 */
						if ( isset ( $personenKov[0] ) ) {
							_civicrm_copy_persoon( $HuisHoudenID, $personenKov[0] );
						}
					}
					/*
					 * check of koopovereenkomst al bestaat en zo ja,
					 * updaten!
					 */
					$checkKov = checkKov( $kov_nr );
					if ( $checkKov ) {
						/*
						 * ophalen id van bestaande kov
						 */
						$selKovQry = "SELECT id FROM ".TABKOV." WHERE ".FLDKOVNR.
							" = '$kov_nr'";
						$daoKov = CRM_Core_DAO::executeQuery( $selKovQry );
						if ( $daoKov->fetch( ) ) {
							$kovID = $daoKov->id;
						}
						$vge_adres = CRM_Core_DAO::escapeString( $vge_adres );
						$corr_naam = CRM_Core_DAO::escapeString( $corr_naam );
						$notaris = CRM_Core_DAO::escapeString( $notaris );
						$taxateur = CRM_Core_DAO::escapeString( $taxateur );
						$bouwkundige = CRM_Core_DAO::escapeString( $bouwkundige );
						$updKovQry = "UPDATE ".TABKOV." SET ".FLDKOVVGE." = '$vge_nr', "
							.FLDKOVADRES." = '$vge_adres', ".FLDKOVCOR." = '$corr_naam', "
							.FLDKOVDEF." = $definitief, ".FLDKOVTYPE." = '$type', "
							.FLDKOVPRIJS." = $prijs, ".FLDKOVNOT." = '$notaris', "
							.FLDKOVTAXWA." = $tax_waarde, ".FLDKOVTAX." = '$taxateur', "
							.FLDKOVBOUW." = '$bouwkundige', ".FLDKOVOVDAT." = 
							'$ov_datum', ".FLDKOVTAXDAT." = '$tax_datum', "
							.FLDKOVBOUWDAT." = '$bouw_datum'";
						$updKovQry .= " WHERE id = $kovID";
						CRM_Core_DAO::executeQuery( $updKovQry );
						/*
						 * verwijder alle bestaande relaties koopovereenkomst
						 * partner voor het huishouden
						 */
						$delRelQry = "DELETE FROM civicrm_relationship WHERE 
							contact_id_b = $HuisHoudenID AND relationship_type_id 
							= ".RELKOV;
						CRM_Core_DAO::executeQuery( $delRelQry );
							
					} else {
						/*
						 * Koopovereenkomst toevoegen aan huishouden
						 */
						 $parms = array(
							 "contact_id"           =>  $HuisHoudenID,
							 "kov_nummer_first"     =>  $kov_nr,
							 "vge_nummer_first"     =>  $vge_nr,
							 "vge_adres_first"      =>  $vge_adres,
							 "correspondentienaam"  =>  $corr_naam,
							 "definitief"           =>  $definitief,
							 "type"                 =>  $type,
							 "verkoopprijs"         =>  $prijs,
							 "notaris"              =>  $notaris,
							 "taxatiewaarde"        =>  $tax_waarde,
							 "taxateur"             =>  $taxateur,
							 "bouwkundige"          =>  $bouwkundige,
							 "datum_overdracht"		=>	$ov_datum,
							 "datum_taxatie"		=>	$tax_datum,
							 "datum_bouw"			=>	$bouw_datum);
						 $huishouden->setKoopovereenkomst($parms);
					 }
					 /*
					  * relatie "koopovereenkomstpartner" tussen huishouden en alle personen op
					  * koopovereenkomst
					  */
					 foreach ($personenKov as $relPers) {
						/*
						 * relatie koopovereenkomstpartner toevoegen
						 */
						if ( !empty( $relPers ) && !empty( $HuisHoudenID ) ) {
							$parms = array(
								"contact_id_a"         =>  $relPers,
								"contact_id_b"         =>  $HuisHoudenID,
								"relationship_type_id" =>  RELKOV);
							if (!empty($ov_datum)) {
								 $parms['startdate'] = $ov_datum;
							} else {
								 $parms['startdate'] = date("Ymd");
							}
							$persoon->addRelationship($parms);
						}
					 }
				}
			}
			/*
			 * bronbestand verwijderen
			 */
			unlink($bestand); 
			if ($mode == "CiviCRM") {
				CRM_Core_Session::setStatus("Laden koopovereenkomsten is klaar!");
			} else {
				$mailParms['toEmail'] = KOVMAIL;
				$mailParms['toName'] = "Helpdesk";
				$mailParms['from'] = "CiviCRM";
				$mailParms['subject'] = "Laden koopovereenkomsten in CiviCRM succesvol afgerond.";
				$mailParms['text'] = "Het laden van het de koopovereenkomsten in bestand 
					$bestand is gelukt, het bestand is verwijderd!";
				CRM_Utils_Mail::send($mailParms);	
			}
		}
	 }
}
/*
 * functie voor afhandeling fouten
 */
function stuurFout($mode, $foutSubject, $foutBericht) {
	if ($mode == "CiviCRM") {
		CRM_Core_Error::createError($foutBericht);
	} else {
		$mail_params = array();
		$mail_params['subject'] = trim($foutSubject);
		$mail_params['text'] = trim($foutBericht)." Corrigeer het probleem en laad dan de koopovereenkomsten in CiviCRM middels de menuoptie Beheer/Laden koopovereenkomsten.";
		$mail_params['toEmail'] = KOVMAIL;
		$mail_params['toName'] = "Helpdesk";
		$mail_params['from'] = "CiviCRM";
		CRM_Utils_Mail::send($mail_params);
	}		
}
/*
 * functie voor het selecteren van type
 */
function bepaalType( $inputType ) {
	if (!empty($inputType)) {
		$inputType = strtolower($inputType);
		switch($inputType) {
			case "koopgarant extern":
				$outputType = 1;
				break;
			case "koopgarant zittende huurders":
				$outputType = 2;
				break;
			case "koopplus extern":
				$outputType = 3;
				break;
			case "koopplus zittende huurders":
				$outputType = 4;
				break;
			case "reguliere verkoop":
				$outputType = 5;
				break;
		}
		return $outputType;
	} else {
		return "fout";
	}
}
/*
 * functie voor het controleren of een koopovereenkomst al bestaat
 */
function checkKov( $overeenkomst ) {
	/*
	 * fout als overeenkomst leeg
	 */
	if ( empty( $overeenkomst ) ) {
		return false;
	}
	
	/*
	 * check of overeenkomst voorkomt in de tabel met koopovereenkomsten
	 */
	$selKovQry = "SELECT COUNT(*) AS aantKov FROM ".TABKOV." WHERE "
		.FLDKOVNR." = '$overeenkomst'";
	$daoKov = CRM_Core_DAO::executeQuery( $selKovQry );
	if ( $daoKov->fetch( ) ) {
		if ( $daoKov->aantKov == 0 ) {
			return false;
		} else {
			return true;
		}
	} else {
		return false;
	}
}
/*
 * functie om data te corrigeren voor M probleem (Okt naar Oct, Mei naar
 * May, Maa naar Mar)
 */
function correctDatum( $datum ) {
	$dates = explode( "-", $datum );
	
	if ( $dates[1] == "Okt" ) {
		$correctDatum = $dates[0]."-Oct-".$dates[2];
		return $correctDatum;
	}
	if ( $dates[1] == "Mei" ) {
		$correctDatum = $dates[0]."-May-".$dates[2];
		return $correctDatum;
	}
	if ( $dates[1] == "Maa" ) {
		$correctDatum = $dates[0]."-Mar-".$dates[2];
		return $correctDatum;
	}
	return $datum;
}
/*
 * functie om tekst in delen op te splitsen (spatie als separator) en
 * de delen met een hoofdletter te laten beginnen
 */
function opmaakHoofdNamen ( $tekst ) {
	$tekstOut = null;
	if ( !empty( $tekst ) ) {
		$delen = explode ( " ", $tekst );
		if ( isset ( $delen[1] ) ) {
			foreach ( $delen as $deel ) {
				$deel = ucfirst ( strtolower ( $deel ) );
			}
			$tekstOut = implode( " ", $delen );  
		} else {
			$tekstOut = ucfirst( strtolower ( $tekst ) );
		}
	}
	return $tekstOut;
		
}
/*
 * function to copy all addresses, phones and emails of a persoon
 * to the huishouden
 */
function _civicrm_copy_persoon($huishoudenID, $persoonID) {
	/*
	 * process only if both huishouden and hoofdhuurder are not empty
	 */
	if (empty($huishoudenID) || empty($persoonID)) {
		 return false;
	}
	/*
	 * select all addresses from persoon and copy those to
	 * huishouden
	 */
	require_once('CRM/Core/DAO/Address.php');
	$addrDAO = new CRM_Core_DAO_Address();
	$fields = $addrDAO->fields();
	$adrSelQry = "SELECT * FROM civicrm_address WHERE contact_id = $persoonID";
	$adrPers = CRM_Core_DAO::executeQuery($adrSelQry);
	while ($adrPers->fetch()) {
		 $adrInsQry = "INSERT INTO civicrm_address SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($adrPers->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $adrPers->$field['name'] );
						$adrInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$adrInsQry .= $field['name']. " = ".
							$adrPers->$field['name'].", ";
					}
				}
		 }
		 $adrInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($adrInsQry);
	}
	 /*
	  * select all phones from persoon and copy those to
	  * huishouden
	  */
	require_once('CRM/Core/DAO/Phone.php');
	$phoneDAO = new CRM_Core_DAO_Phone();
	$fields = $phoneDAO->fields();
	$phoneSelQry = "SELECT * FROM civicrm_phone WHERE contact_id = $persoonID";
	$phonePers = CRM_Core_DAO::executeQuery($phoneSelQry);
	while ($phonePers->fetch()) {
		 $phoneInsQry = "INSERT INTO civicrm_phone SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($phonePers->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $phonePers->$field['name'] );
						$phoneInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$phoneInsQry .= $field['name']. " = ".
							$phonePers->$field['name'].", ";
					}
				}
		 }
		 $phoneInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($phoneInsQry);
	}
	 /*
	  * select all emails from persoon and copy those to
	  * huishouden
	  */
	require_once('CRM/Core/DAO/Email.php');
	$emailDAO = new CRM_Core_DAO_Email();
	$fields = $emailDAO->fields();
	$emailSelQry = "SELECT * FROM civicrm_email WHERE contact_id = $persoonID";
	$emailPers = CRM_Core_DAO::executeQuery($emailSelQry);
	while ($emailPers->fetch()) {
		 $emailInsQry = "INSERT INTO civicrm_email SET ";
		 foreach ($fields as $field) {
			if ($field['name'] != 'id' && $field['name'] != 'contact_id' 
				&& !empty($emailHoofd->$field['name'])) {
					if ($field['type'] == 2) {
						$veld = CRM_Core_DAO::escapeString( $emailPers->$field['name'] );
						$emailInsQry .= $field['name']. " = '".
							$veld."', ";
					} else {
						$emailInsQry .= $field['name']. " = ".
							$emailPers->$field['name'].", ";
					}
				}
		 }
		 $emailInsQry .= "contact_id = $huishoudenID";
		 CRM_Core_DAO::executeQuery($emailInsQry);
	}
}	
    
?>
