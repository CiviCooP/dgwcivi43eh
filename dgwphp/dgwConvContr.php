<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwConvContr.php                                  |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       27 May 2011                                  |
| Description   :       Conversion Controller                        |
+--------------------------------------------------------------------+
*/
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * laad configuratiebestand
 */
require_once( '/var/www/intranettest/sites/all/modules/dgwphp/dgwConvConfig.php' );
require_once( 'CRM/Utils/Mail.php' );
require_once( 'CRM/Utils/String.php' );
require_once( 'dgwConvUtils.php' );
/*
 * initialiseer vaste variabelen
 */
$bronBestand = CONVPAD.CONVBRON; 
$bronTabel = CONVTBL;
$bronOrgTbl = CONVORGTBL;
$bronIndTbl = CONVINDTBL;
$convHost = CONVHOST;
$convUser = CONVUSER;
$convPass = CONVPASS;
$convDb = CONVDB;
/*
 * maak verbinding met de database
 */
$db = new mysqli($convHost, $convUser, $convPass, $convDb);
if ( mysqli_connect_errno( ) ) {
	$fout = array( );
	$fout['onderwerp'] = "Conversie afgebroken";
	$fout['bericht'] = "Kan geen verbinding krijgen met database $convDb (host $convHost - user $convUser - pass $convPass), conversie is afgebroken!";
	logFout( $fout, true );
	die("Conversie afgebroken!");	
}
/*
 * initialiseer fouten conversie logbestand
 */   
$initFoutQry = "TRUNCATE TABLE fouten";
$db->query( $initFoutQry );
/*
 * check of het bronbestand personen in de map staat
 */
if ( !file_exists( $bronBestand ) ) {
	$fout = array( );
	$fout['onderwerp'] = "Conversie afgebroken";
	$fout['bericht'] = "Bronbestand $bronBestand is niet gevonden, conversie is afgebroken!";
	logFout( $fout, true );
	die( "Conversie afgebroken!" );	
}
/*
 * laad het bronbestand personen naar MySQL en schoon de gegevens op
 */
$laadBronTabel = laadBronTabel( $bronBestand, $bronTabel );
if ( $laadBronTabel == false ) {
	$fout = array( );
	$fout['onderwerp'] = "Conversie afgebroken";
	$fout['bericht'] = "Bronbestand $bronBestand kon niet geladen worden in tabel $bronTabel, conversie is afgebroken!";
	logFout( $fout, true );
	die( "Conversie afgebroken!" );	
} else {
	$fout = array( );
	$fout['type'] = "status";
	$fout['onderwerp'] = "Brontabel geladen";
	$fout['bericht'] = "Bronbestand $bronBestand is geladen in tabel $bronTabel";
	logFout( $fout, true );
}
/*
 * splits de MySQL bron personen in een organisatie en personen deel
 */
$splitBronTabel = splitBronTabel( $bronTabel, $bronOrgTbl, $bronIndTbl );
if ( $splitBronTabel == false ) {
	$fout = array( );
	$fout['onderwerp'] = "Conversie afgebroken";
	$fout['bericht'] = "De gegevens in tabel .$bronTabel. konden niet gesplitst worden in organisaties in $bronOrgTbl en personen in $bronIndTbl, conversie is afgebroken!";
	logFout( $fout, true );
	die( "Conversie afgebroken!" );	
} else {
	$fout = array( );
	$fout['type'] = "status";
	$fout['onderwerp'] = "Brontabel gesplitst";
	$fout['bericht'] = "Bronbestand $bronBestand is gesplits in tabel $bronOrgTbl en $bronIndTbl";
	logFout( $fout, true );
}
/*
 * haal contactgegevens uit de brontabel
 */
splitsContactGegevens( $bronTabel );
/*
 * organisaties verwerken in CiviCRM
 */
require_once( 'dgwConvOrg.php' ); 
$verwerkOrg = verwerkOrg($bronOrgTbl);
$fout = array( );
$fout['type'] = "status";
$fout['onderwerp'] = "Organisaties verwerkt";
$fout['bericht'] = "De organisaties zijn geconverteerd";
logFout( $fout, true );
/*
 * personen verwerken in CiviCRM
 */
require_once( 'dgwConvInd.php' );
$verwerkInd = verwerkInd($bronIndTbl);

$fout = array( );
$fout['onderwerp'] = "Conversie succesvol afgerond";
$fout['bericht'] = "De conversie van CiviCRM is succesvol afgerond. Er zijn $orgProcessed organisaties en $indProcessed personen verwerkt. Bij personen $indAdd toegevoegd en $indUpd bijgewerkt, bij organisaties $orgAdd toegevoegd en $orgUpd bijgewerkt";
logFout( $fout , true );
		
		
?>
