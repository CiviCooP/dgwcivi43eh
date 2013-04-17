<?php
/*
+--------------------------------------------------------------------+
| Added PHP script dgwImportHelma.php                                |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       22 Nov 2011                                  |
| Description   :       Import klanttevredenheidsonderzoek Helma     |
+--------------------------------------------------------------------+
*/
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
$groupID = 19;
/*
 * CiviCRM settings laden
 */
require_once($_SERVER['DOCUMENT_ROOT'].
	'/sites/default/civicrm.settings.php');
/*
 * CiviCRM Initialiseren als nodig
 */
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton( );
require_once 'api/v2/GroupContact.php';
/*
 * lees alle huurders uit onderzoeksbestand
 */
$huurderData = CRM_Core_DAO::executeQuery("SELECT * FROM helma1");
while ( $huurderData->fetch() ) {
	/*
	 * ophalen alle bijbehorende huurovereenkomsten
	 */
	$vgeID = trim( $huurderData->vgenummer );
	$naam = trim( $huurderData->correspondentienaam );
	$begindatum = date("Ymd", strtotime( $huurderData->startdatum ) );
	
	$hovQry = "SELECT * FROM civicrm_value_huurovereenkomst_2 WHERE 
		vge_nummer_first_6 = '".$vgeID."' ORDER BY begindatum_hov_9 DESC";
	$hovData = CRM_Core_DAO::executeQuery( $hovQry );
	while ( $hovData->fetch() ) {
		/*
		 * check of correspondentienaam en begindatum overeenkomen
		 */
		$hovNaam = trim( $hovData->correspondentienaam_first_8 ); 
		$hovBegindatum = date("Ymd", strtotime( $hovData->begindatum_hov_9 ) );
		$contactID = $hovData->entity_id;
		
		if ( $hovNaam == $naam and $hovBegindatum == $begindatum ) {
			/*
			 * zo ja, huishouden (entity van hov) aan groep benaderd maar passieve
			 * huurders/kopers
			 */
			$groupParms = array(
                'contact_id.1' 	=> 	$contactID,
                'group_id'     	=> 	$groupID);
            $groupResult = &civicrm_group_contact_add($groupParms);
         }
	 }
}
echo "<p>Klaar met verwerken huurders Helma!</p>";
unset($hovData);
unset($hovQry);

/*
 * lees alle kopers uit onderzoeksbestand
 */
$koperData = CRM_Core_DAO::executeQuery("SELECT distinct(kov), vge, naam FROM helma2");
while ( $koperData->fetch() ) {
	/*
	 * ophalen alle bijbehorende koopovereenkomsten
	 */
	$kovID = trim( $koperData->kov );
	$vgeID = trim( $koperData->vge );
	$naam = trim( $koperData->naam );
	
	$kovQry = "SELECT entity_id FROM civicrm_value_koopovereenkomst_3 WHERE 
		kov_nummer_first_11 = '".$kovID."'";
	$kovData = CRM_Core_DAO::executeQuery( $kovQry );
	while ( $kovData->fetch() ) {
		$contactID = $kovData->entity_id;
		/*
		 * zo ja, huishouden (entity van kov) aan groep benaderd maar passieve
		 * huurders/kopers
		 */
		$groupParms = array(
			'contact_id.1' 	=> 	$contactID,
			'group_id'     	=> 	$groupID);
		$groupResult = &civicrm_group_contact_add($groupParms);
	 }
}
echo "<p>Klaar met verwerken kopers Helma!</p>";
