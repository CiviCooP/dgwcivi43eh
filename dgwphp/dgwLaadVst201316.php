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
 * alle complex records ophalen
 */
$newValues = array( );
$qryComplex = "SELECT * FROM vst_complex";
$daoComplex = CRM_Core_DAO::executeQuery( $qryComplex );
while ( $daoComplex->fetch() ) {
    /*
     * dwaarde ophalen uit vst_complex12dec12
     */
    $newValue = array( );
    $newValue['id'] = $daoComplex->id;
    $newValue['awaarde'] = $daoComplex->awaarde;
    $newValue['bwaarde'] = $daoComplex->bwaarde;
    $newValue['cwaarde'] = $daoComplex->cwaarde;
    $newValue['ewaarde'] = $daoComplex->ewaarde;
    $qryDwaarde = 
"SELECT dwaarde FROM vst_complex12dec12 WHERE complex = '{$daoComplex->complex}'";
    $qryDwaarde .= 
" AND subcomplex = '{$daoComplex->subcomplex}' AND stadsdeel = '{$daoComplex->stadsdeel}'";
    $qryDwaarde .= 
" AND buurt = '{$daoComplex->buurt}' AND woningtype = '{$daoComplex->woningtype}'";
    $daoDwaarde = CRM_Core_DAO::executeQuery( $qryDwaarde );
    if ( $daoDwaarde->fetch() ) {
        $newValue['dwaarde'] = $daoDwaarde->dwaarde;
    } else {
        $newValue['dwaarde'] = 0;
    }
    $newValues[] = $newValue;
}
/*
 * complexgegevens bijwerken met nieuwe waarden
 */
$calcDate = date("Ymd");
foreach ( $newValues as $newValue ) {
    $wens = $newValue['awaarde'] + $newValue['bwaarde'] + $newValue['cwaarde'] 
        + $newValue['dwaarde'] + $newValue['ewaarde'];
    $markt = $newValue['awaarde'] + $newValue['bwaarde'];
    $kwa = $newValue['cwaarde'] + $newValue['dwaarde'] + $newValue['ewaarde'];
    $labelParams = array(
        'wens'  =>  $wens,
        'markt' =>  $markt,
        'kwa'   =>  $kwa
        );
    $label = CRM_Utils_VastStrat::bepaalStrategie( $labelParams );
    $updComplex = 
"UPDATE vst_complex SET dwaarde = {$newValue['dwaarde']}, wenswaarde = $wens, 
marktwaarde = $markt, kwawaarde = $kwa, strategie = '$label', datum_berekend = $calcDate
 WHERE id = {$newValue['id']}";
    CRM_Core_DAO::executeQuery( $updComplex );
}
echo "<p>Correcties 16 jan 2013 klaar";