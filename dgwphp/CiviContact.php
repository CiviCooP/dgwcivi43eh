<?php
/*
+--------------------------------------------------------------------+
| Added PHP script in CiviCRM CiviPersoon.php (Class)                |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       13 August 2010                               |
| Description   :       Class is used in initial data import from    |
|                       MySQL table pers, tel, email and adres.      |
|                       Class is extended from CiviContact           |
+--------------------------------------------------------------------+
 */
class CiviContact {

    public $contact_id = 0;
    public $first_name = null;
    public $middle_name = null;
    public $last_name = null;

    /*
     * Constructor. Hiermee worden de waarden gevalideerd uit de
     * inkomende array input en in de desbetreffende attributen
     */
    function __construct($input) {

        /*
         * als contact_id gevuld is, toekennen aan attribuut als
         * alleen numeriek. Als het veld andere tekens bevat,
         * error message genereren
         */
        if (isset($input['contact_id'])) {
            if (!empty($input['contact_id'])) {
                if (is_numeric($input['contact_id'])) {
                    $this->contact_id = $input['contact_id'];
                } else {
                    trigger_error("CiviContact; Array
                        input, element contact_id bevat andere tekens dan
                        cijfers : ".$input['contact_id'], E_USER_ERROR);
                }
            }
        }

        /*
         * als first_name (voorletters) gevuld is, toekennen aan attribuut.
         */
        if (isset($input['first_name'])) {
            if (!empty($input['first_name'])) {
                $this->first_name = trim($input['first_name']);
            }
        }

        /*
         * als middle_name gevuld is (tussenvoegsel), toekennen aan
         * attribuut (alleen kleine letters)
         */
         if (isset($input['middle_name'])) {
             if (!empty($input['middle_name'])) {
                 $this->middle_name = trim($input['middle_name']);
             }
         }

         /*
          * als last_name (achternaam) gevuld is, toekennen aan attribuut
          */
         if (isset($input['last_name'])) {
             if (!empty($input['last_name'])) {
                 $this->last_name = trim($input['last_name']);
             }
         }
    }
    /*
     * functie om contact toe te voegen aan een groep. Als de groep
     * nog niet bestaat wordt deze aangemaakt
     */
    function addContactGroup($input) {

        $fout = false;
        $description = null;
        $civipar = array("");
        $civires = array("");

        /*
         * valideren dat group_id in de inkomende array staat, anders fout
         */
        if (!isset($input['group_id'])) {
            trigger_error("CiviContact; Geen group_id in parameter array ".
                    $input." voor functie addContactGroup", E_USER_ERROR);
            $fout = true;
        }

        /*
         * valideren dat contact_id in de inkomende array staat, anders fout
         */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviContact; Geen contact_id in parameter array ".
                    $input." voor functie addContactGroup", E_USER_ERROR);
            $fout = true;
        }

        /*
         * valideren dat name in de inkomende array staat, anders waarschuwing
         */
        if (!isset($input['name'])) {
            trigger_error("CiviContact; geen naam voor groep in parameter
                array ".$input." voor functie addContactGroup. Geen groep
                aangemaakt als groep nog niet bestaat.",E_USER_WARNING);
        }

        /*
         * alleen verder als geen fout geconstateerd in parameter array
         */
        if (!$fout) {

            /*
             * kijken of de beoogde groep bestaat met nummer als gevuld, anders
             * met naam
             */
            if (empty($input['group_id'])) {
                $civipar = array("name"    =>  $input['name']);
            } else {
                $civipar = array("id"    =>  $input['group_id']);
            }

            $civires = &civicrm_group_get($civipar);

            /*
             * als groep nog niet bestaat en titel doorgegeven, groep aanmaken
             */
            if (civicrm_error($civires)) {
                if ($civires['error_message'] == "No such group exists") {
                    if (isset($input['name'])) {
                        if (!empty($input['name'])) {

                            $description = $input['name'].", groep aangemaakt
                                in conversie";

                            $civipar = array(
                                "name"          =>  trim($input['name']),
                                "title"         =>  trim($input['name']),
                                "description"   =>  $description,
                                "is_active"     =>  1,
                                "visibility"    =>  "Public User Pages and
                                                        Listings"
                            );

                            $civires = &civicrm_group_add($civipar);
                            if (!civicrm_error($civires)) {
                                $input['group_id'] = $civires['result'];
                            }
                        }
                    }
                }

            }

            /*
             * contact aan groep toevoegen
             */

            $civipar = array(
                "contact_id.1" => $input['contact_id'],
                "group_id"     => $input['group_id']);
            $civires = &civicrm_group_contact_add($civipar);

        }
        return $civires;
    }
    /*
     * functie om een locatie aan het contact toe te voegen
     */
    function addLocation($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");
        $locphone = array("");
        $locemail = array("");
        $locaddress = array("");
        $primary = 0;
        $location_type_id = 0;
        $i = 0;

        /*
         * valideren dat contact_id in de inkomende array staat, anders fout
         */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviContact; Geen contact_id in parameter array ".
                    $input." voor functie addLocation", E_USER_ERROR);
            $fout = true;
        }

        if (!$fout) {

            /*
             * bepalen location_type_id
             */
            if (isset($input['location_type'])) {
                $location_type_id =
                    $this->getLocationTypeId($input['location_type']);
            } else {
                $location_type_id = 4;
            }

            /*
             * samenstellen array telefoonnummers
             */
            if (isset($input['phone'])) {
                $i = 0;
                foreach($input['phone'] as $phone) {
                    /*
                     * alleen als telefoonnummer aanwezig en niet oud
                     */
                    $phone['location_type'] = strtolower($phone['location_type']);
                    if (isset($phone['phone']) && $phone['location_type'] != "oud") {
                        $locphone[$i]['phone'] = $phone['phone'];

                        /*
                         * zetten location_type_id als ingevuld bij telefoon,
                         * anders location_type van de hele locatie
                         */
                        if (isset($phone['location_type'])) {
                            $locphone[$i]['location_type_id'] =
                                $this->getLocationTypeId($phone['location_type']);
                        } else {
                            $locphone[$i]['location_type_id'] = $location_type_id;
                        }

                        /*
                         * zetten phone_type_id als ingevuld bij telefoon, anders
                         * standaardwaarde 1 (telefoon)
                         */
                        if (isset($phone['phone_type'])) {
                            $locphone[$i]['phone_type_id'] =
                                $this->getPhoneTypeId($phone['phone_type']);
                        } else {
                            $locphone[$i]['phone_type_id'] = 1;
                        }

                        /*
                         * als primary met waarde 1 is doorgegeven bij telefoon,
                         * dit overnemen. In alle andere gevallen primary = 0
                         */
                        if (isset($phone['primary'])) {
                            if ($phone['primary'] == 1) {
                                $locphone[$i]['is_primary'] = "1";
                            } else {
                                $locphone[$i]['is_primary'] = "0";
                            }
                        } else {
                            $locphone[$i]['is_primary'] = "0";
                        }
                        $i++;
                    }
                }
            }

            /*
             * samenstellen array emails
             */
            $i = 0;
            if (isset($input['email'])) {
                foreach ($input['email'] as $email) {
                    /*
                     * alleen als email ingevuld en niet oud
                     */
                    $email['location_type'] = strtolower($email['location_type']);
                    if (isset($email['email']) && $email['location_type'] != "oud") {
                        $locemail[$i]['email'] = trim($email['email']);

                        /*
                         *  zetten location_type_id als ingevuld bij email,
                         * anders location_type van de hele locatie
                         */
                        if (isset($email['location_type'])) {
                            $locemail[$i]['location_type_id'] =
                                $this->getLocationTypeId($email['location_type']);
                        } else {
                            $locemail[$i]['location_type_id'] = $location_type_id;
                        }

                        /*
                         * als primary met waarde 1 is doorgegeven bij email,
                         * dit overnemen. In alle andere gevallen primary = 0
                         */
                        if (isset($email['primary'])) {
                            if ($email['primary'] == 1) {
                                $locemail[$i]['is_primary'] = "1";
                            } else {
                                $locemail[$i]['is_primary'] = "0";
                            }
                        } else {
                            $locemail[$i]['is_primary'] = "0";
                        }
                        $i++;
                    }
                }
            }

            /* samenstellen array address. Dit moet hardcoded met element 1
             * vanwege een foutje in de standaard API. Tegelijkertijd
             * street_address samenstellen als straat+nummer+toevoeging
             */
            if (isset($input['street_name'])) {
                $locaddress[1]['street_name'] = trim($input['street_name']);
                $locaddress[1]['street_address'] = trim($input['street_name']);
            }

            if (isset($input['street_number'])) {
                $locaddress[1]['street_number'] = trim($input['street_number']);

                if (empty($locaddress[1]['street_address'])) {
                    $locaddress[1]['street_address'] = trim(
                            $input['street_number']);
                } else {
                    $locaddress[1]['street_address'] =
                        $locaddress[1]['street_address']." ".
                        trim($input['street_number']);
                }
            }

            if (isset($input['street_suffix']) && !empty($input['street_suffix'])) {
                $locaddress[1]['street_number_suffix'] = trim(
                        $input['street_suffix']);

                if (empty($locaddress[1]['street_address'])) {
                    $locaddress[1]['street_address'] = trim(
                            $input['street_suffix']);
                } else {
                    $locaddress[1]['street_address'] =
                        $locaddress[1]['street_address']." ".
                        trim($input['street_suffix']);
                }
            }

            if (isset($input['postcode'])) {
                $locaddress[1]['postal_code'] = trim($input['postcode']);
            }

            if (isset($input['city'])) {
                $locaddress[1]['city'] = trim($input['city']);
            }
            /*
             * issue 49: standaard country om google maps te kunnen gebruiken
             */
            $locaddress[1]['country_id'] = (int) $config->defaultContactCountry;
            if ($locaddress[1]['country_id'] == 0) {
                $locaddress[1]['country_id'] = 1152;
            }
            $locaddress[1]['country'] = "Nederland";

            /*
             * primary overnemen (meest recente zal op primary staan)
             */
            $locaddress[1]['is_primary'] = $input['primary'];

            $locaddress[1]['location_type_id'] = $location_type_id;
            /*
             * als location_type adres toekomst, startdatum opnemen in 2e
             * adresregel
             */
            if (strtolower($input['location_type']) == "toekomst") {
                $locaddress[1]['supplemental_address_2'] = "(vanaf : ".
                    date("d-m-Y", strtotime($input['start_date'])).")";
            }

            /*
             * samenstellen parameter voor call civicrm API
             */
            $civipar = array(
                "contact_id"    =>  $input['contact_id'],
                "version"       =>  "3.0",
                "phone"         =>  $locphone,
                "email"         =>  $locemail,
                "address"       =>  $locaddress);

            $civires = &civicrm_location_add($civipar);

        }
        return $civires;

    }
    /*
     * functie om een relatie tussen twee contacten toe te voegen
     */
    function addRelationship($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");
        $rdate = array("");
        $startdate = array("");
        $enddate = array("");
        $active = "1";

        /*
         * valideren of beide contact_id's doorgeven zijn, anders fout
         */
        if (!isset($input['contact_id_a'])) {
            trigger_error("CiviContact; contact_id_a niet doorgegeven in
                parameter ".$input." voor functie addRelationship.",
                    E_USER_ERROR);
            $fout = true;
        }

        if (!isset($input['contact_id_b'])) {
            trigger_error("CiviContact; contact_id_b niet doorgegeven in
                paramter ".$input." voor functie addRelationship.",
                E_USER_ERROR);
            $fout = true;
        }

        /*
         * valideren of relatietype doorgegeven is, anders fout
         */
        if (!isset($input['relationship_type_id'])) {
            trigger_error("CiviContact; relationship_type_id niet doorgegeven in
                paramter ".$input." voor functie addRelationship.",
                E_USER_ERROR);
            $fout = true;
        }

        /*
         * begindatum relatie overnemen of standaard op vandaag zetten
         */
        if (isset($input['startdate']) && !empty($input['startdate'])) {
            $startdate = date("Ymd", strtotime($input['startdate']));
        }

        /*
         * einddatum overnemen als gevuld
         */
        if (isset($input['einddate']) && !empty($input['einddate'])) {
            $enddate = date("Ymd", strtotime($input['einddate']));
            /*
             * als einddatum voor datum vandaag, dan niet actief
             */
            if ($enddate < date("Ymd")) {
                $active = "0";
            }
        }

        /*
         * CiviCRM API parameter samenstellen aanroepen
         */
        $civipar = array(
            "contact_id_a"          =>  $input['contact_id_a'],
            "contact_id_b"          =>  $input['contact_id_b'],
            "relationship_type_id"  =>  $input['relationship_type_id'],
            "start_date"            =>  $startdate,
            "end_date"              =>  $enddate,
            "is_active"             =>  $active);

        $civires = &civicrm_relationship_create($civipar);
        return $civires;

    }

    /*
     * functie om een notitie voor een contact toe te voegen
     */
    function addNote($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");
        $subject = null;

        /*
         * alleen als contact_id ingevuld, anders fout
         */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviContact; contact_id niet doorgegeven in
                parameter ".$input." voor functie addNote.", E_USER_ERROR);
            $fout = true;
        } else {
            /*
             * alleen als contact_id numeriek, anders fout
             */
            if (!is_numeric($input['contact_id'])) {
                trigger_error("CiviContact; Contact_id ".$input['contact_id'].
                    " bevat niet-numerieke tekens in functie addNote.",
                    E_USER_ERROR);
                $fout = true;
            }
        }

        /*
         * alleen als note is doorgegeven, anders fout
         */
        if (!isset($input['note'])) {
            trigger_error("CiviContact; note niet doorgegeven in parameter".
                    $input." voor functie addNote.", E_USER_ERROR);
            $fout = true;
        }

        /*
         * alleen verder als geen fout
         */
        if (!$fout) {

            /*
             * subject overzetten als gevuld, anders standaardwaarde
             */
            if (isset($input['subject'])) {
                if (!empty($input['subject'])) {
                    $subject = trim($input['subject']);
                } else {
                    $subject = "Notitie vanuit conversie";
                }
            } else {
                $subject = "Notitie vanuit conversie";
            }

            /*
             * CiviCRM API om notitie te maken
             */
            $civipar = array(
                "contact_id"    =>  1,
                "entity_id"     =>  $input['contact_id'],
                "entity_table"  =>  "civicrm_contact",
                "note"          =>  $input['note'],
                "subject"       =>  $subject,
                "modified_date" =>  date("Ymd"));

            $civires = &civicrm_note_create($civipar);

        }
        return $civires;

    }
    /*
     * functie om een tag toe te voegen voor een contact
     */
    function addTag($input) {

    }
    /*
     * functie om contact te updaten met inkomende gegevens
     */
    function updateContact($input) {

        $fout = false;
        $civires = array("");
        $valid_types = array("Household", "Individual", "Organization");

        /*
         * contact_id moet gevuld en geldig zijn in inkomende array
         */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviContact; geen contact_id in parameters in
                functie updateContact.", E_USER_ERROR);
            $fout = true;
        } else {
            if (!is_numeric($input['contact_id'])) {
                trigger_error("CiviContact; ongeldige gegevens in contact_id ".
                    $input['contact_id']." in functie updateContact",
                    E_USER_ERROR);
                $fout = true;
            }
        }

        /*
         * contact_type moet gevuld en geldig zijn in inkomende array
         */
        if (!isset($input['contact_type'])) {
            trigger_error("CiviContact; geen contact_type in parameters in
                functie updateContact.", E_USER_ERROR);
            $fout = true;
        } else {
            if (!in_array($input['contact_type'], $valid_types)) {
                trigger_error("CiviContact, contact_type ".
                    $input['contact_type']." niet geldig in functie
                    updateContact", E_USER_ERROR);
                $fout = true;
            }
        }
        /*
         * alleen verder als geen fouten
         */
        if (!$fout) {

            /*
             * CiviCRM API aanroepen
             */
            $civipar = $input;
            $civires = &civicrm_contact_update($input);

        }
        return $civires;
    }
    /*
     * Functie om phone type id op te halen met omschrijving
     */
    function getPhoneTypeId($phonetypetxt) {

        switch(strtolower($phonetypetxt)) {
            case "fax":
                $phonetypeid = 3;
                break;
            case "mobiel":
                $phonetypeid = 2;
                break;
            case "geheim":
                $phonetypeid = 4;
                break;
            default:
                $phonetypeid = 1;
                break;
        }
        return $phonetypeid;
    }
    /*
     * Functie om phone type omschrijving op te halen met id
     */
    function getPhoneTypeTxt($phonetypeid) {

        switch($phonetypeid) {
            case 2:
                $phonetypetxt = "mobiel";
                break;
            case 3:
                $phonetypetxt = "fax";
                break;
            case 4:
                $phonetypetxt = "geheim";
                break;
            default:
                $phonetypetxt = "telefoon";
                break;
        }
        return $phonetypetxt;
    }
    /*
     * Functie om location type id op te halen met text
     */
    function getLocationTypeId($locationtypetxt) {

        switch(strtolower($locationtypetxt)) {
            case "thuis":
                $locationtypeid = 1;
                break;
            case "werk":
                $locationtypeid = 2;
                break;
            case "oud":
                $locationtypeid = 6;
                break;
            case "toekomst":
                $locationtypeid = 8;
                break;
            case "postbusadres":
                $locationtypeid = 7;
                break;
            default:
                $locationtypeid = 4;
                break;
        }
        return $locationtypeid;
    }
    /*
     * Functie om location type omschrijving op te halen met id
     */
    function getLocationTypeTxt($locationtypeid) {

        switch($locationtypeid) {
            case 1:
                $locationtypetxt = "Thuis";
                break;
            case 2:
                $locationtypettxt = "Werk";
                break;
            case 6:
                $locationtypetxt = "Oud";
                break;
            case 8:
                $locationtypetxt = "Toekomst";
                break;
            case 7:
                $locationtypetxt = "Postbusadres";
                break;
            default:
                $locationtypetxt = "Overig";
                break;
        }
        return $locationtypetxt;
    }
    /*
     * Functie om te beoordelen of locatietype oud of toekomst is, gebaseerd
     * op inkomende start- en einddatum (formaat jjjjmmdd). Return is oud,
     * toekomst of geen
     */
    function checkOudToekomst($input) {

        /*
         * als geen start en geen einddatum in array, geen retour
         */
        if (!isset($input['startdatum']) && !isset($input['einddatum'])) {
            return "geen";
        }

        /*
         * als beiden leeg, geen retour
         */
        if (empty($input['startdatum']) && empty($input['einddatum'])) {
                return "geen";
        }
        /*
         * als gevuld en geen 8 posities, geen retour
         */
        if (!empty($input['startdatum'])) {
            if (strlen($input['startdatum']) != 8) {
                return "geen";
            }
        }
        if (!empty($input['einddatum'])) {
            if (strlen($input['einddatum']) != 8) {
                return "geen";
            }
        }
        /*
         * als startdatum gevuld en geen geldige datum, geen retour
         */
        if (!empty($input['startdatum'])) {
            $jaar = (int) substr($input['startdatum'], 0, 4);
            $maand = (int) substr($input['startdatum'], 4, 2);
            $dag = (int) substr($input['startdatum'], 6, 2);
            if (!checkdate($maand, $dag, $jaar )) {
                return "geen";
            }
        }

        /*
         * als einddatum gevuld en geen geldige datum, geen retour
         */
        if (!empty($input['einddatum'])) {
            $jaar = (int) substr($input['einddatum'], 0, 4);
            $maand = (int) substr($input['einddatum'], 4, 2);
            $dag = (int) substr($input['einddatum'], 6, 2);
            if (!checkdate($maand, $dag, $jaar )) {
                return "geen";
            }
        }

        /*
         * als einddatum gevuld en kleiner dan vandaag, return oud
         */
        if (!empty($input['einddatum'])) {
            if ($input['einddatum'] < date("Ymd")) {
                return "oud";
            }
        }

        /*
         * als startdatum gevuld en groter dan vandaag, return toekomst
         */
        if (!empty($input['startdatum'])) {
            if ($input['startdatum'] > date("Ymd")) {
                return "toekomst";
            }
        }
        /*
         * in alle andere gevallen, geen
         */
        return "geen";

    }
}
?>
