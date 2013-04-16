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
    $newValue['dwaarde'] = $daoComplex->dwaarde;
    $newValue['ewaarde'] = $daoComplex->ewaarde;
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
"UPDATE vst_complex SET strategie = '$label', datum_berekend = $calcDate
 WHERE id = {$newValue['id']}";
    CRM_Core_DAO::executeQuery( $updComplex );
}
echo "<p>Correcties 31 jan 2013 klaar";