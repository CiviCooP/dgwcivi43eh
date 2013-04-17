<?php
/*
+--------------------------------------------------------------------+
| Added PHP script in CiviCRM dgwAddRefnoLoc.php    )                |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       12 November 2010                             |
| Description   :       Creates records in sync tabel for dgw        |
| Date			:		24 February 2011                             |
| Description	:		Change for prod environment                  |
+--------------------------------------------------------------------+
*/
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
@date_default_timezone_set( 'Europe/Amsterdam' );
set_time_limit(0);
/*
 * configuratiebestand laden
 */
require_once("/var/www/intranet/sites/all/modules/dgwphp/dgwConfig.php");

/*
 * alle records uit aanvullende persoonsgegevens lezen
 */
$tabel = TABFIRSTPERS;
$qry1 = "SELECT * FROM $tabel";
$daoPers = CRM_Core_DAO::executeQuery($qry1);
while ($daoPers->fetch()) {
    /*
     * create record in sync tabel voor persoon
     */
    $fldpersnr = FLDPERSNR;
    $persoonsnummer = $daoPers->$fldpersnr;
    $ins1 = "INSERT INTO ".TABSYNC." SET entity_id = ".$daoPers->entity_id.", "
        .FLDSYNCACT." = 'none', ".FLDSYNCENT." = 'contact', ".FLDSYNCID." = "
        .$daoPers->entity_id.", ".FLDSYNCKEY." = '$persoonsnummer'";
    $daoInsCont = CRM_Core_DAO::executeQuery($ins1);

    /*
     *  check if there are phone numbers for the person in CiviCRM
     */
    $qry2 = "SELECT * FROM civicrm_phone WHERE contact_id = ".
        $daoPers->entity_id;
    $daoPhone = CRM_Core_DAO::executeQuery($qry2);
    while ($daoPhone->fetch()) {
        /*
         * check if the number is present in the cderefno table, if so pick
         * up the key and write record to synctable
         */
        $qry3 = "SELECT * FROM cderefno WHERE persnr = '$persoonsnummer' AND
            waarde = '".$daoPhone->phone."'";
        $daoCdeP = CRM_Core_DAO::executeQuery($qry3);
        while ($daoCdeP->fetch()) {
            $ins2 = "INSERT INTO ".TABSYNC." SET entity_id = ".
                $daoPers->entity_id.", ".FLDSYNCACT." = 'none', ".FLDSYNCENT.
                " = 'phone', ".FLDSYNCID." = ".$daoPhone->id.", ".FLDSYNCKEY.
                " = '$daoCdeP->cde_refno'";
            $daoPIns = CRM_Core_DAO::executeQuery($ins2);
        }
    }
    /*
     * check if there are email addresses for the person in CiviCRM
     */
    $qry4 = "SELECT * FROM civicrm_email WHERE contact_id = ".
        $daoPers->entity_id;
    $daoEmail = CRM_Core_DAO::executeQuery($qry4);
    while ($daoEmail->fetch()) {
        /*
         * check if the emailaddress is present in the cde_refno table, if so
         * pick up the key and write record to synctable
         */
        $qry5 = "SELECT * FROM cderefno WHERE persnr = '$persoonsnummer' AND
            waarde = '".$daoEmail->email."'";
        $daoCdeE = CRM_Core_DAO::executeQuery($qry5);
        while ($daoCdeE->fetch()) {
            $ins3 = "INSERT INTO ".TABSYNC." SET entity_id = ".
                $daoPers->entity_id.", ".FLDSYNCACT." = 'none', ".FLDSYNCENT.
                " = 'email', ".FLDSYNCID." = ".$daoEmail->id.", ".FLDSYNCKEY.
                " = '$daoCdeE->cde_refno'";
            $daoEIns = CRM_Core_DAO::executeQuery($ins3);
        }
    }
    /*
     * check if there are addresses for the person in CiviCRM
     */
    $qry6 = "SELECT * FROM civicrm_address WHERE contact_id = ".
        $daoPers->entity_id." AND is_primary = 1";
    $daoAddress = CRM_Core_DAO::executeQuery($qry6);
    while ($daoAddress->fetch()) {
        /*
         * checik if the address is present in the adr_refno table with
         * persoonsnummer and end_date = empty. If so, pick up the key and write
         * record to synctable
         */
        $qry7 = "SELECT * FROM adrrefno WHERE par_refno = '$persoonsnummer' AND
            end_date = ''";
        $daoCdeA = CRM_Core_DAO::executeQuery($qry7);
        while ($daoCdeA->fetch()) {
            $ins4 = "INSERT INTO ".TABSYNC." SET entity_id = ".
                $daoPers->entity_id.", ".FLDSYNCACT." = 'none', ".FLDSYNCENT.
                " = 'address', ".FLDSYNCID." = ".$daoAddress->id.", ".FLDSYNCKEY.
                " = '$daoCdeA->adr_refno'";
            $daoAIns = CRM_Core_DAO::executeQuery($ins4);
        }
    }
}
echo "<p>Laden refno's is klaar!</p>";

?>
