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
require_once( 'dgwConvUtils.php' );
require_once( 'api/v2/Dgwcontact.php' );
/*
 * initialiseer vaste variabelen
 */
$convHost = CONVHOST;
$convUser = CONVUSER;
$convPass = CONVPASS;
$convDb = CONVDB;
$aantHov = null;
$aantHovOrg = null;
$aantHovPers = null;
/*
 * maak verbinding met de database
 */
$db = new mysqli($convHost, $convUser, $convPass, $convDb);
if ( mysqli_connect_errno( ) ) {
	$fout = array( );
	$fout['onderwerp'] = "Conversie huurovereenkomst afgebroken";
	$fout['bericht'] = "Kan geen verbinding krijgen met database $convDb (host $convHost - user $convUser - pass $convPass), conversie huurovereenkomst is afgebroken!";
	logFout( $fout, true );
	die("Conversie huurovereenkomst afgebroken!");	
}
/*
 * huurovereenkomsten lezen
 */
$selectBronQry = "SELECT * FROM singlehov WHERE hovnummer > 43387 ORDER BY hovnummer";
$bronResult = $db->query( $selectBronQry );
if ( !$bronResult ) {
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
while ( $nummerData = $bronResult->fetch_array( MYSQLI_ASSOC ) ) {
	$aantHov++;
	/*
	 * bijbehorende gegevens ophalen uit firsthov
	 */
	$hovNummer = $nummerData['hovnummer']; 
	$selHovDetQry = "SELECT * FROM firsthov WHERE hovnummer = $hovNummer";
	$hovDetResult = $db->query( $selHovDetQry );
	if ( $hovData = $hovDetResult->fetch_array( MYSQLI_ASSOC ) ) {
		/*
		 * check of persoonsnummer bij organisatie of persoon is in CiviCRM
		 */
		$hovPersOrg = checkPersOrg( $hovData['persoonsnummer'] );
		/*
		 * als Org, HOV toevoegen aan tabel met organisaties
		 */
		if ( !$hovPersOrg['is_error'] ) {
			$hovData['contact_id'] = $hovPersOrg['contact_id'];
			if ( $hovPersOrg['org'] ) {
				$okOrg = createHovOrg( $hovData );
				if ( $okOrg ) {
					$aantHovOrg++;
				}
			} 
			if ( $hovPersOrg['pers'] ) { 
				$okPers = createHovPers( $hovData );
				if ( $okPers ) {
					$aantHovPers++;
				}
			}
		}
	}
}
$fout = array( );
$fout['onderwerp'] = "Conversie huurovereenkomsten succesvol afgerond";
$fout['bericht'] = "De conversie van huurovereenkomsten naar CiviCRM is succesvol afgerond. Er zijn $aantHov huurovereenkomsten gelezen, $aantHovOrg toegevoegd voor organisaties en $aantHovPers huurovereenkomsten voor personen toegevoegd. Bij personen $indAdd toegevoegd.";
logFout( $fout , true );
/*---------------------------------------------------------------
 * functie om te checken of de HOV voor een org of persoon is
 *-------------------------------------------------------------*/
function checkPersOrg( $persoonsnummer ) {
	$outParms = array ( 
		'is_error'	=>	false,
		'org'		=>	false,
		'pers'		=>	false);
	/*
	 * fout als persoonsnummer leeg
	 */
	if ( empty( $persoonsnummer ) ) {
		$outParms['is_error'] = true;
		return $outParms;
	}
	/*
	 * als het om een organisatie gaat dan is er een record in de tabel
	 * TABFIRSTORG
	 */
	$checkOrgQry = "SELECT COUNT(*) AS aantOrg, entity_id FROM ".TABFIRSTORG." WHERE ".
		FLDORGPERSNR." = '$persoonsnummer'"; 
	$daoOrg = CRM_Core_DAO::executeQuery( $checkOrgQry );
	if ( $daoOrg->fetch( ) ) {
		if ( $daoOrg->aantOrg > 0 ) {
			$outParms['org'] = true;
			$outParms['contact_id'] = $daoOrg->entity_id;
		}
	}
	/*
	 * als het om een persoon gaat dan is er een record in de tabel
	 * TABFIRST
	 */
	$checkPersQry = "SELECT COUNT(*) AS aantPers, entity_id FROM ".TABFIRSTPERS." WHERE ".
		FLDPERSNR." = '$persoonsnummer'";
	$daoPers = CRM_Core_DAO::executeQuery( $checkPersQry );
	if ( $daoPers->fetch( ) ) {
		if ( $daoPers->aantPers > 0 ) {
			$outParms['pers'] = true;
			$outParms['contact_id'] = $daoPers->entity_id;
			
		}
	}
	/*
	 * Fout loggen als hov bij beiden voorkomt!
	 */
	if ( $org == true && $pers == true ) {
		$log = array ( );
		$log['type'] = "fout";
		$log['onderwerp'] = "Persoonsnummer bij persoon EN organisatie";
		$log['bericht'] = "Het persoonsnummer $persoonsnummer komt zowel bij persoon als organisatie voor, nakijken! Huurovereenkomst wordt NIET overgezet.";
		$log['persoonsnummer'] = $persoonsnummer;
		logFout( $log, false );
		$outParms['is_error'] = true;
	}
	return $outParms;
}	
/*---------------------------------------------------------------
 * functie om huurovereenkomst bij organisatie bij te werken of toe
 * te voegen
 *-------------------------------------------------------------*/
function createHovOrg( $data ) {
	/*
	 * fout als leeg of geen array
	 */
	if ( empty ( $data ) || !is_array( $data ) ) {
		return false;
	}
	/*
	 * klaarzetten gegevens voor create
	 */
	$persoonsNummer = $data['persoonsnummer'];
	$hovNummer = $data['hovnummer']; 
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
	$startDate = date("Y-m-d", strtotime( $data['hovstart'] ) ); 
	$eindDate = date("Y-m-d", strtotime( $data['hoveind'] ) ); 
	$startHoofdDate = date("Y-m-d", strtotime( $data['huurderstart'] ) ); 
	$eindHoofdDate = date("Y-m-d", strtotime( $data['huurdereind'] ) );
	if ( empty( $contactID ) ) {
		$log = array ( );
		$log['type'] = "fout";
		$log['onderwerp'] = "Geen contact voor overeenkomst $hovNummer";
		$log['bericht'] = "Er is geen contact gevonden voor huurovereenkomst $hovNummer met persoon $persoonsNummer ! Huurovereenkomst wordt NIET overgezet.";
		$log['persoonsnummer'] = $persoonsNummer;
		logFout( $log, false );
	} else {
		$hovInsQry = "INSERT INTO civicrm_value_huurovereenkomst__org__11 SET 
			entity_id = $contactID , hov_nummer_58 = '$hovNummer', 
			vge_nummer_59 = '$vgeNummer', vge_adres_60 = '$vgeAdres', begindatum_overeenkomst_61 = '$startDate',
			einddatum_overeenkomst_62 = '$eindDate', naam_op_overeenkomst_63 = '$corrNaam'";
		$daoHovOrg = CRM_Core_DAO::executeQuery( $hovInsQry );
	}
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
?>
