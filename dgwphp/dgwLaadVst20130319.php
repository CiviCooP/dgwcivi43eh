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
    $wens = $daoComplex->awaarde + $daoComplex->bwaarde + $daoComplex->cwaarde 
            + $daoComplex->dwaarde + $daoComplex->ewaarde;
    $newValue['wens'] = $wens;
    $markt = $daoComplex->awaarde + $daoComplex->bwaarde;
    $newValue['markt'] = $markt;
    $kwa = $daoComplex->cwaarde + $daoComplex->dwaarde + $daoComplex->ewaarde;
    $newValue['kwa'] = $kwa;
    $newValue['id'] = $daoComplex->id;
    $labelParams = array(
        'wens'  =>  $wens,
        'markt' =>  $markt,
        'kwa'   =>  $kwa
        );
    $label = CRM_Utils_VastStrat::bepaalStrategie( $labelParams );
    $newValue['label'] = $label;
    $newValues[] = $newValue;
}
/*
 * complexgegevens bijwerken met nieuwe waarden
 */
$calcDate = date("Ymd");
foreach ( $newValues as $newValue ) {
    $updComplex = 
"UPDATE vst_complex SET strategie = '{$newValue['label']}' , datum_berekend = $calcDate, ";
    $updComplex .= 
"wenswaarde = {$newValue['wens']}, marktwaarde = {$newValue['markt']}, ";
    $updComplex .=    
"kwawaarde = {$newValue['kwa']} WHERE id = {$newValue['id']}";
    CRM_Core_DAO::executeQuery( $updComplex );
}
echo "<p>Correcties 19 maart 2013 klaar";