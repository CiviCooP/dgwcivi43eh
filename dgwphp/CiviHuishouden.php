<?php
/*
+--------------------------------------------------------------------+
| Added PHP script in CiviCRM CiviHuishouden.php (Class)             |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       17 August 2010                               |
| Description   :       Class is used in initial data import from    |
|                       MySQL table pers, tel, email and adres.      |
|                       Class is extended from CiviContact           |
+--------------------------------------------------------------------+
 */
class CiviHuishouden extends CiviContact {

    /*
     * functie om organisatie aan te maken
     */
    function addHuishouden($input) {

        $civipar = array(
            "household_name"    =>  $input['household_name'],
            "contact_type"      =>  "Household");

        /*
         * API functie om organisatie toe te voegen aanroepen
         */
        $civires = &civicrm_contact_add($civipar);

        return $civires;
    }
function setHuurovereenkomst($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");

        /*
        * Check of contact_id gevuld is, anders fout
        */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviHuishouden; Geen contact_id in parameter ".
                $input." voor functie setHuurovereenkomst.", E_USER_ERROR);
            $fout = true;
        } else {

            /*
             * Check of contact_id numeriek is, anders fout
             */
            if (!is_numeric($input['contact_id'])) {
                trigger_error("CiviHuishouden; Contact_id ".$input['contact_id']
                    ." bevat niet-numerieke tekens in functie
                    setHuurovereenkomst.", E_USER_ERROR);
                $fout = true;
            }
        }

        /*
         * alleen verder als geen fout
         */
        if (!$fout) {

            $civipar['entityID'] = $input['contact_id'];

            /*
             * nummer huurovereenkomst overzetten
             */
            if (isset($input['hov_nummer_first'])) {
                $civipar[CFHOVNR] = trim($input['hov_nummer_first']);
            }

            /*
             * begindatum huurovereenkomst overzetten
             */
            if (isset($input['begindatum'])) {
                $civipar[CFHOVBEG] = date("Ymd",
                        strtotime($input['begindatum']));
            }

            /*
             * einddatum huurovereenkomst overzetten
             */
            if (isset($input['einddatum'])) {
                $civipar[CFHOVEND] = date("Ymd",
                        strtotime($input['einddatum']));
            }

            /*
             * nummer VGE overzetten
             */
            if (isset($input['vge_nummer_first'])) {
                $civipar[CFHOVVGE] = trim($input['vge_nummer_first']);
            }

            /*
             * adres vge overzetten
             */
            if (isset($input['adres_vge_first'])) {
                $civipar[CFHOVADRES] = trim($input['adres_vge_first']);
            }

            /*
             * correspondentienaam overzetten
             */
            if (isset($input['correspondentienaam'])) {
                $civipar[CFHOVCOR] = trim($input['correspondentienaam']);
            }

            /*
             * aanroepen CiviCRM functie om Custom Data te vullen
             */
            require_once("CRM/Core/BAO/CustomValueTable.php");
            $civires = CRM_Core_BAO_CustomValueTable::setValues($civipar);
        }
        return $civires;
    }
        function setKoopovereenkomst($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");
        /*
        * Check of contact_id gevuld is, anders fout
        */
        if (!isset($input['contact_id'])) {
            $error = array(
                "is_error"      => 1,
                "error_message" => "CiviHuishouden; Geen contact_id in parameter "
                    .$input." voor functie setKoopovereenkomst." );
            return $error;
        } else {

            /*
             * Check of contact_id numeriek is, anders fout
             */
            if (!is_numeric($input['contact_id'])) {
            $error = array(
                "is_error"      => 1,
                "error_message" => "CiviHuishouden; Contact_id ".
                    $input['contact_id']." bevat niet-numerieke tekens in
                    functie setKoopovereenkomst." );
            return $error;

            }
        }

        $civipar['entityID'] = $input['contact_id'];

        /*
         * Nummer koopovereenkomst als ingevuld
         */
        if (isset($input['kov_nummer_first'])) {
            $civipar[CFKOVNR] = trim($input['kov_nummer_first']);
        }

        /*
         * Nummer vge als ingevuld
         */
        if (isset($input['vge_nummer_first'])) {
            $civipar[CFKOVVGE] = $input['vge_nummer_first'];
        }

        /*
         * Adres vge als ingevuld
         */
        if (isset($input['vge_adres_first'])) {
            $civipar[CFKOVADRES] = $input['vge_adres_first'];
        }

        /*
         * Correspondentienaam als ingevuld
         */
        if (isset($input['correspondentienaam'])) {
            $civipar[CFKOVCOR] = $input['correspondentienaam'];
        }

        /*
         * Datum overdracht als ingevuld
         */
        if (isset($input['datum_overdracht'])) {
            $civipar[CFKOVOV] = date("Ymd", strtotime(
                $input['datum_overdracht']));
        }

        /*
         * Definitief
         */
        if (isset($input['definitief'])) {
            $civipar[CFKOVDEF] = $input['definitief'];
        } else {
            $civipar[CFKOVDEF] = 0;
        }

        /*
         * Type overeenkomst als ingevuld
         */
        if (isset($input['type'])) {
            $civipar[CFKOVTYPE] = $input['type'];
        }

        /*
         * Verkoopprijs als ingevuld
         */
        if (isset($input['verkoopprijs'])) {
            $civipar[CFKOVPRIJS] = $input['verkoopprijs'];
        }

        /*
         * Notaris als ingevuld
         */
        if (isset($input['notaris'])) {
            $civipar[CFKOVNOTARIS] = $input['notaris'];
        }

        /*
         * Taxatiewaarde als ingevuld
         */
        if (isset($input['taxatiewaarde'])) {
            $civipar[CFKOVTAXW] = $input['taxatiewaarde'];
        }

        /*
         * Taxateur als ingevuld
         */
        if (isset($input['taxateur'])) {
            $civipar[CFKOVTAX] = $input['taxateur'];
        }

        /*
         * Datum taxatie als ingevuld
         */
        if (isset($input['datum_taxatie'])) {
            $civipar[CFKOVTAXD] = date("Ymd", strtotime(
                $input['datum_taxatie']));
        }

        /*
         * Bouwkundige als ingevuld
         */
        if (isset($input['bouwkundige'])) {
            $civipar[CFKOVBOUW] = $input['bouwkundige'];
        }

        /*
         * Datum bouwkeuring als ingevuld
         */
        if (isset($input['datum_bouw'])) {
            $civipar[CFKOVBOUWDAT] = date("Ymd", strtotime(
                $input['datum_bouw']));
        }

        /*
         * aanroepen CiviCRM functie om Custom Data te vullen
         */
        require_once("CRM/Core/BAO/CustomValueTable.php");
        $civires = CRM_Core_BAO_CustomValueTable::setValues($civipar);
        return $civires;
    }

}
?>
