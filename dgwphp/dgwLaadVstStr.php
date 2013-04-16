<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwLaadVstStr.php |
+--------------------------------------------------------------------+
| Project : Vastgoedstrategie / De Goede Woning |
| Author : Erik Hommel (EE-atWork, hommel@ee-atwork.nl |
| http://www.ee-atwork.nl) |
| Date : 29 december 2011 |
| Description : Script laad het vastgoedstrategie bestand |
| uit First in CivCRM custom data. Verwacht |
| bronbestand is vastgoedstrategie.csv op |
| /home/kov/ op de webserver. |
+--------------------------------------------------------------------+
* */
ini_set('display_errors', '1');
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);

$mode = null;
require_once 'dgwConfig.php';
require_once 'CRM/Utils/Mail.php';
require_once 'CRM/Utils/String.php';
require_once 'CRM/Utils/VastStrat.php';


$fout = false;
/*
* controleer of het bestand voor vastgoedstrategie op de server staat.
* De bestandsnaam komt uit dgwConfig (VSTFILE). De map waar het bestand
* op staat (KOVPATH) komt uit dgwConfig. Als het bestand niet bestaat
* wordt er een mail verstuurd naar de beheerder waarvan het mailadres
* in dgwConfig staat (KOVMAIL). Bestaat het bestand wel, dan wordt het
* geimporteerd naar de MySQL tabel uit dgwConfig (VSTTABLE) in de
* database KOVDB.
*/
//$vst_csv = KOVPATH."/vastgoedstrategie.csv";
/*
* als bestand niet bestaat, mail aan beheerder
*/
//if (!file_exists($vst_csv)) {
//	$fout = true;
//	$foutBericht = "Het laden van de vastgoedstrategie is mislukt omdat het bestand ".$vst_csv." niet bestaat. Zorg ervoor dat het bestand alsnog op de juiste plek op de server komt en probeer opnieuw!";
//	$foutSubject = "Laden vastgoedstrategie mislukt, bestand bestaat niet";
//	stuurFout($mode, $foutSubject, $foutBericht);
//}
if (!$fout) {
	//TIJDELIJK UIT OMDAT LOCAL INFILE NIET WERKT OP NIEUWSTE VERSIE MYSQL
	//CRM_Core_DAO::executeQuery("TRUNCATE TABLE vaststrat");
        /*
	 * gegevens uit csv bestand importeren in MySQL tabel
	 */
	//$qryVst = "LOAD DATA LOCAL INFILE '".$vst_csv. "' INTO TABLE vaststrat FIELDS TERMINATED BY ';'";
	//CRM_Core_DAO::executeQuery($qryVst);
	/*
	 * uitzondering voor complex 161, daar alleen type appartement met lift toepassen.
	 * Dus updaten in bronbestand
	 */
	$updCpl161 = "UPDATE vaststrat SET vgetype = 'Appartement Met Lift' WHERE cpl = 'CPL0161'";
	CRM_Core_DAO::executeQuery( $updCpl161 ); 
	/*
	 * lezen vastgoedstrategie
	 */
	$qryVst = "SELECT * FROM vaststrat WHERE cpl <> 'CPL0501' AND cpl <> 'CPL0567' AND vgetype<> 'Kantoor'";
	$daoVst = CRM_Core_DAO::executeQuery( $qryVst);
	if ( !$daoVst->fetch() ) {
		$fout = true;
		$foutSubject = "Laden vastgoedstrategie mislukt, geen gegevens in importbestand?";
		$foutBericht = "Hetladen van de vastgoedstrategie is mislukt omdat de query $qryVst niet uitgevoerd kon worden. Foutboodschap van MySQL : ".$vstdb->error;
		stuurFout($mode, $foutSubject, $foutBericht);
	} else {
		/*
		 * leegmaken doeltabel eenheden en complexen
		 */
		$truncEenheid = "TRUNCATE TABLE vst_eenheid";
		CRM_Core_DAO::executeQuery( $truncEenheid );
		$truncComplex = "TRUNCATE TABLE vst_complex";
		CRM_Core_DAO::executeQuery( $truncComplex );
		/*
		 * alle records verwerken
		 */
		while ( $daoVst->fetch() ) {
			/*
			 * waarden velden uit bron overzetten
			 */
			$cpl = (string) trim( CRM_Core_DAO::escapeString( $daoVst->cpl ) );
			$subcpl = (string) trim( CRM_Core_DAO::escapeString ( $daoVst->subcpl ) );
			$wijk = (string) trim( CRM_Core_DAO::escapeString( $daoVst->wijk ) );
			$buurt = (string) trim( CRM_Core_DAO::escapeString( $daoVst->buurt ) );
			$vge = (string) trim ( CRM_Core_DAO::escapeString( $daoVst->vge ) );
			$vgetype = (string) trim ( CRM_Core_DAO::escapeString( $daoVst->vgetype ) );
			$strat = (string) trim ( CRM_Core_DAO::escapeString( $daoVst->strat ) );
			$oppvlk = (int) trim ( CRM_Core_DAO::escapeString( $daoVst->oppvlk ) );
			$vertrek = (int) trim ( CRM_Core_DAO::escapeString( $daoVst->vertrek ) );
                        $buitencode = (string) trim( CRM_Core_DAO::escapeString( $daoVst->buiten ) );
                        if ( trim( $daoVst->trap ) == "Ja") {
                            $trap = "J";
                        } else {
                            $trap = "N";
                        }
			/*
			 * ophalen epa label (apart bestand Eugene)
			 */
			$qryEpa = "SELECT * FROM vst_epa WHERE vgeid = '$vge'";
			$daoEpa = CRM_Core_DAO::executeQuery( $qryEpa );
			$epa = "";
			if ( $daoEpa->fetch() ) {
				$epa = $daoEpa->epa;
			}
			/*
			 * punten voor b-waarde bepalen
			 */
			$b_params = array(
				'type' => $vgetype,
				'trap' => $trap,
				'vertrek' => $vertrek,
				'meters' => $oppvlk,
				'buiten' => $buitencode);
			$b_punten = CRM_Utils_VastStrat::bepaalBVge( $b_params );
			/*
			 * punten voor c-waarde bepalen
			 */
			$c_params = array('label' => $epa);
			$c_punten = CRM_Utils_VastStrat::bepaalCVge( $c_params );
			/*
			 * velden toevoegen aan eenheidtabel
			 */
			$insEenheid = "INSERT INTO vst_eenheid SET vgefirst = '$vge',
				complex = '$cpl', subcomplex = '$subcpl', stadsdeel =
				'$wijk', buurt = '$buurt', type_first = '$vgetype',
				epa_first = '$epa', meters = $oppvlk, vertrek = $vertrek,
				b_punt = $b_punten, c_punt = $c_punten, strategie =
				'$strat', buitencode = '$buitencode', trap = '$trap'";
			$daoEenheid = CRM_Core_DAO::executeQuery( $insEenheid );
		}
		/*
		 * als alle eenheden overgezet zijn, complexen in basis aanmaken
		 * op complex/subcomplex/woningtype niveau
		 */
		$qryComplex = "SELECT DISTINCT complex, subcomplex AS sub, type_first AS type FROM vst_eenheid";
		$daoComplex = CRM_Core_DAO::executeQuery( $qryComplex );
		while ( $daoComplex->fetch() ) {
			$complex = $daoComplex->complex;
			$sub = $daoComplex->sub;
			$type = $daoComplex->type;
			$params = array(
				'complex' => $complex,
				'sub' => $sub,
				'type' => $type);
			/*
			 * bepaal aantal eenheden in (sub)complex/type
			 */
			$aantal = CRM_Utils_VastStrat::aantalVGE( $params );
			/*
			 * ophalen a_waarde gegevens op complex/subcomplex/type niveau
			 */
			$a_waarde = 0;
			$qryMarkt = "SELECT * FROM vst_markt WHERE complex = '$complex'
				AND subcomplex = '$sub' AND type = '$type'";
			$daoMarkt = CRM_Core_DAO::executeQuery( $qryMarkt );
			if ( $daoMarkt->fetch() ) {
				$locatie = $daoMarkt->locatie;
				$verhuurbaar = $daoMarkt->verhuurbaar;
				$doelgroep = $daoMarkt->doelgroep;
				$a_waarde = $locatie + $verhuurbaar + $doelgroep;
			}
			/*
			 * bepalen b_waarde op complex/subcomplex/type niveau
			 */
			$b_waarde = CRM_Utils_VastStrat::bepaalBCpl( $params );
                        /*
			 * bepalen c_waarde op complex/subcomplex/type niveau
			 */
                        $params['buurt'] = $buurt;
			$c_waarde = CRM_Utils_VastStrat::bepaalCCpl( $params );
			/*
			 * ophalen d_waarde uit financiele waarde
			 */
			$d_waarde = 0;
			$qryFin = "SELECT * FROM vst_fin WHERE complex = '$complex'
				AND subcomplex = '$sub'";
			$daoFin = CRM_Core_DAO::executeQuery( $qryFin );
			if ( $daoFin->fetch() ) {
				$d_waarde = $daoFin->finwaarde;
			}
			/*
			 * ophalen e_waarde uit bouwtechnische waarde
			 */
			$e_waarde = 0;
			/*
			 * uitzondering: technische waarde voor 124 en 125 3 punten,
			 * complex 164 en hoger 5 punten
			 */
			if ( $complex == "CPL0124" || $complex == "CPL0125" ) {
				$e_waarde = 3;
			} elseif ( $complex >= "CPL0164" ) {
				$e_waarde = 5;
			} else {
				$qryBouw = "SELECT * FROM vst_bouw WHERE complex = '$complex'
					AND subcomplex = '$sub'";
				$daoBouw = CRM_Core_DAO::executeQuery( $qryBouw );
				if ( $daoBouw->fetch() ) {
					$e_waarde = $daoBouw->bouwwaarde;
				}
			}
			/*
			 * type cast om af te ronden op gehele getallen
			 */
			$a_waarde = (int) round($a_waarde);
			$b_waarde = (int) round($b_waarde);
			$c_waarde = (int) round($c_waarde);
			$d_waarde = (int) round($d_waarde);
			$e_waarde = (int) round($e_waarde);
			/*
			 * bepalen wens_waarde
			 */
			$wens_waarde = $a_waarde + $b_waarde + $c_waarde + $d_waarde +
				$e_waarde;
			/*
			 * bepalen markt_waarde
			 */
			$markt_waarde = $a_waarde + $b_waarde;
			/*
			 * bepalen kwa_waarde
			 */
			$kwa_waarde = $c_waarde + $d_waarde + $e_waarde;
			/*
			 * bepalen strategie uit First
			 */
			$stratFirst = CRM_Utils_VastStrat::bepaalStratFirst( $params );
			/*
			 * bepalen strategie aan de hand van wens, markt en kwa
			 */
			$stratLabel = "";
			$params = array(
				'wens' => $wens_waarde,
				'markt' => $markt_waarde,
				'kwa' => $kwa_waarde);
			$stratLabel = CRM_Utils_VastStrat::bepaalStrategie( $params );

			$nu = date("Y-m-d");
			/*
			 * haal stadsdeel en buurt uit eenheden
			 */
			$qryBuurt = "SELECT buurt, stadsdeel FROM vst_eenheid WHERE 
				complex = '$complex' AND subcomplex = '$sub' AND 
				type_first = '$type'";
			$daoBuurt = CRM_Core_DAO::executeQuery( $qryBuurt );
			if ( $daoBuurt->fetch() ) {
				if ( isset( $daoBuurt->buurt ) ) {
					$buurt = $daoBuurt->buurt;
				} else {
					$buurt = "";
				}
				if ( isset( $daoBuurt->stadsdeel ) ) {
					$stadsdeel = $daoBuurt->stadsdeel;
				} else {
					$stadsdeel = "";
				}
			}

			$insComplex = "INSERT INTO vst_complex SET complex = '$complex',
				subcomplex = '$sub', stadsdeel = '$stadsdeel',
				buurt = '$buurt', aantal = $aantal, woningtype =
				'$type', awaarde = $a_waarde, bwaarde = $b_waarde, cwaarde =
				$c_waarde, dwaarde = $d_waarde, ewaarde = $e_waarde, wenswaarde =
				$wens_waarde, marktwaarde = $markt_waarde, kwawaarde =
				$kwa_waarde, datum_berekend = '$nu', strategie_first =
				'$stratFirst', strategie = '$stratLabel', locatie = $locatie,
				verhuurbaar = $verhuurbaar, doelgroep = $doelgroep";
			CRM_Core_DAO::executeQuery( $insComplex );
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
