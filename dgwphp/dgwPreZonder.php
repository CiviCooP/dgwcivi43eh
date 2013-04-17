<?php
ini_set('display_errors', '1');
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranet/sites/all/modules/dgwphp/dgwConvConfig.php' );

$convHost = CONVHOST;
$convUser = CONVUSER;
$convPass = CONVPASS;
$convDb = CONVDB;
/*
 * maak verbinding met de database
 */
$db = new mysqli($convHost, $convUser, $convPass, $convDb);
if ( mysqli_connect_errno( ) ) {
	die("Conversie afgebroken!");	
}
/*
 * haal adressen uit brontabel
 */
$adresSelQry = "SELECT persoonsnummer, straat, huisnummer, huisletter, 
	toevoeging, postcode, plaats, adres_startdatum, adres_einddatum 
	FROM leegpers ORDER BY persoonsnummer, adres_startdatum DESC";
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
	$checkAantalQry = "SELECT COUNT(*) AS aantal FROM leegadres WHERE 
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
			$adresInsQry = "INSERT INTO leegadres SET pers_nr = $persoonsnummer, 
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
$adresStartEindQry = "DELETE FROM leegadres WHERE startdatum = einddatum";
$db->query( $adresStartEindQry ); 	
/*
 * haal emailadres uit brontabel
 */
$emailSelQry = "SELECT persoonsnummer, contacttype, contactgegeven, 
	startdatum, einddatum FROM leegpers WHERE contacttype = 'E-MAIL' OR 
	contactgegeven LIKE '%@%'";
$emailRes = $db->query( $emailSelQry );
while ( $emailData = $emailRes->fetch_array( MYSQLI_ASSOC ) ) {
	$persoonsnummer = (int)  $emailData['persoonsnummer'];
	$type = $db->real_escape_string( $emailData['contacttype'] );
	$emailadres = $db->real_escape_string( trim( $emailData['contactgegeven'] ) );
	/*
	 * check of record al bestaat, voeg alleen toe als dat niet zo is
	 */
	$checkAantalQry = "SELECT COUNT(*) AS aantal FROM leegemail WHERE 
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
			$emailInsQry = "INSERT INTO leegemail SET pers_nr = $persoonsnummer, 
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
	startdatum, einddatum FROM leegpers WHERE (contacttype <> 'E-MAIL' 
	OR contactgegeven NOT LIKE '%@%')";
$telRes = $db->query( $telSelQry );
while ( $telData = $telRes->fetch_array( MYSQLI_ASSOC ) ) {
	$persoonsnummer = (int)  $telData['persoonsnummer'];
	$type = $db->real_escape_string( $telData['contacttype'] );
	$nummer = $db->real_escape_string( trim( $telData['contactgegeven'] ) );
	/*
	 * check of record al bestaat, voeg alleen toe als dat niet zo is
	 */
	$checkAantalQry = "SELECT COUNT(*) AS aantal FROM leegtel WHERE 
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
			$telInsQry = "INSERT INTO leegtel SET pers_nr = $persoonsnummer, 
				type = '$type', nummer = '$nummer', startdatum = '$startdatum'"; 
			if ( isset ( $einddatum ) && !empty ( $einddatum ) ) {	 
				$telInsQry .= ", einddatum = '$einddatum'";
			}
			$db->query( $telInsQry );
		}
	}
}

?>
