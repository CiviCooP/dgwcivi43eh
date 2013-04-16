<?php
/*
+--------------------------------------------------------------------+
| Added PHP script IN CiviCRM dgwLaadVst201311.php                   |
+--------------------------------------------------------------------+
| Project       :   Onderhoud CiviCRM De Goede Woning                |
| Author        :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl      |
|                   http://www.ee-atwork.nl)                         |
| Date          :   9 januari 2013                                   |
| Description   :   Bijwerken vastgoedstrategie voor 1/1/2013 stand  |
+--------------------------------------------------------------------+
*/
ini_set('display_errors', '1');
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
require_once 'dgwConfig.php';
require_once 'CRM/Utils/Mail.php';
require_once 'CRM/Utils/String.php';
require_once 'CRM/Utils/VastStrat.php';
/*
 * alle records ophalen uit nieuwe lijst Elke en Monique
 */
$newValues = array( );
$qryComplex = "SELECT * FROM vst_complex";
$daoComplex = CRM_Core_DAO::executeQuery( $qryComplex );
while ( $daoComplex->fetch() ) {
    $newValue = array( );
    $newValue['id'] = $daoComplex->id;
    /*
     * gegevens ophalen uit nieuwe lijst Elke en Monique
     */
    $qryLijst = 
"SELECT * FROM vst_130206 WHERE complex = '{$daoComplex->complex}' AND ";
    $qryLijst .= 
"sub = '{$daoComplex->subcomplex}' AND wijk = '{$daoComplex->stadsdeel}' AND ";
    $qryLijst .= 
"buurt = '{$daoComplex->buurt}' AND vgetype = '{$daoComplex->woningtype}'";
    $daoLijst = CRM_Core_DAO::executeQuery( $qryLijst );
    if ( $daoLijst->fetch() ) {
        $newValue['awaarde'] = $daoComplex->awaarde;
        $newValue['bwaarde'] = $daoComplex->bwaarde;
        $newValue['cwaarde'] = $daoComplex->cwaarde;
        $newValue['dwaarde'] = $daoComplex->dwaarde;
        $newValue['ewaarde'] = $daoComplex->ewaarde;
        $wens = $daoComplex->awaarde + $daoComplex->bwaarde + $daoComplex->cwaarde 
            + $daoComplex->dwaarde + $daoComplex->ewaarde;
        $newValue['wens'] = $wens;
        $markt = $daoComplex->awaarde + $daoComplex->bwaarde;
        $newValue['markt'] = $markt;
        $kwa = $daoComplex->cwaarde + $daoComplex->dwaarde + $daoComplex->ewaarde;
        $newValue['kwa'] = $kwa;
        $labelParams = array(
            'wens'  =>  $wens,
            'markt' =>  $markt,
            'kwa'   =>  $kwa
            );
        $label = CRM_Utils_VastStrat::bepaalStrategie( $labelParams );
        $newValue['label'] = $label;
        $newValues[] = $newValue;
    }
}
/*
 * complexgegevens bijwerken met nieuwe waarden
 */
$calcDate = date("Ymd");
foreach ( $newValues as $newValue ) {
    $updComplex = 
"UPDATE vst_complex SET strategie = '{$newValue['label']}' , datum_berekend = $calcDate, ";
    $updComplex .=
"awaarde = {$newValue['awaarde']}, bwaarde = {$newValue['bwaarde']}, cwaarde = {$newValue['cwaarde']}, ";
    $updComplex .=
"dwaarde = {$newValue['dwaarde']}, ewaarde = {$newValue['ewaarde']}, marktwaarde = {$newValue['markt']}, ";
    $updComplex .=
"wenswaarde = {$newValue['wens']}, kwawaarde = {$newValue['kwa']} WHERE id = {$newValue['id']}";
    CRM_Core_DAO::executeQuery( $updComplex );
}
echo "<p>Correcties 7 feb 2013 klaar";