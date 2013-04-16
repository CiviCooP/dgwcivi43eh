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
 * alle records verwerken uit bestaande strategie
 */
$newValues = array( );
$qryProcess = "SELECT * FROM vst_complex";
$daoProcess = CRM_Core_DAO::executeQuery( $qryProcess );
while ( $daoProcess->fetch() ) {
    /*
     * gegevens ophalen uit correctietabel
     */
    $newValue = array( );
    $qryCorrect = 
"SELECT awaarde, bwaarde, cwaarde, ewaarde FROM vst_20130101 WHERE complex = '{$daoProcess->complex}'";
    $qryCorrect .= 
" AND sub = '{$daoProcess->subcomplex}' AND wijk = '{$daoProcess->stadsdeel}'";
    $qryCorrect .= 
" AND buurt = '{$daoProcess->buurt}' AND vgetype = '{$daoProcess->woningtype}'";
    $daoCorrect = CRM_Core_DAO::executeQuery( $qryCorrect );
    if ( $daoCorrect->fetch() ) {
        $newValue['id'] = $daoProcess->id;
        $newValue['awaarde'] = $daoCorrect->awaarde;
        $newValue['bwaarde'] = $daoCorrect->bwaarde;
        $newValue['cwaarde'] = $daoCorrect->cwaarde;
        $newValue['ewaarde'] = $daoCorrect->ewaarde;
        $newValue['wenswaarde'] = $daoCorrect->awaarde + $daoCorrect->ewaarde + 
            $daoCorrect->bwaarde + $daoCorrect->cwaarde + $daoProcess->dwaarde;
        $newValue['marktwaarde'] = $daoCorrect->awaarde + $daoCorrect->bwaarde;
        $newValue['kwawaarde'] = $daoCorrect->cwaarde + $daoProcess->dwaarde + 
            $daoCorrect->ewaarde;
        $labelParams = array(
            'wens'  =>  $newValue['wenswaarde'],
            'markt' =>  $newValue['marktwaarde'],
            'kwa'   =>  $newValue['kwawaarde']
        );
        $newValue['label'] = CRM_Utils_VastStrat::bepaalStrategie( $labelParams );
        
        $newValues[] = $newValue;
    }
}
/*
 * complexgegevens bijwerken met nieuwe waarden
 */
$calcDate = date("Ymd");
foreach ( $newValues as $newValue ) {
    $updComplex = 
"UPDATE vst_complex SET awaarde = {$newValue['awaarde']}, bwaarde = {$newValue['bwaarde']}";
    $updComplex .=
", cwaarde = {$newValue['cwaarde']}, ewaarde = {$newValue['ewaarde']}, wenswaarde = ";
    $updComplex .=
"{$newValue['wenswaarde']}, marktwaarde = {$newValue['marktwaarde']}, kwawaarde = ";
    $updComplex .=
"{$newValue['kwawaarde']}, strategie = '{$newValue['label']}', datum_berekend = $calcDate";
    $updComplex .=
" WHERE id = {$newValue['id']}";
    CRM_Core_DAO::executeQuery( $updComplex );
}
echo "<p>Correcties klaar";