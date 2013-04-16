<?php
/*
+--------------------------------------------------------------------+
| Added PHP script in CiviCRM CiviPersoon.php (Class)                |
+--------------------------------------------------------------------+
| Project       :       Implementation at De Goede Woning            |
| Author        :       Erik Hommel (EE-atWork, hommel@ee-atwork.nl  |
|                                    http://www.ee-atwork.nl)        |
| Date          :       13 August 2010                               |
| Modified      :       26 August 2010                               |
| Description   :       Class is used in initial data import from    |
|                       MySQL table pers, tel, email and adres.      |
|                       Class is extended from CiviContact           |
+--------------------------------------------------------------------+
 */
class CiviPersoon extends CiviContact {

    /*
     * functie om persoon aan te maken
     */
    function addPersoon($input) {

        $civipar = array("");
        $civires = array("");
        /*
         * standaardwaarde voor type persoon
         */
        $civipar['contact_type'] = "Individual";

        /*
         * Naam (voorletters, tussenvoegsel en achternaam) overzetten
         */
        if (isset($input['first_name'])) {
            $civipar['first_name'] = trim($input['first_name']);
        }

        if (isset($input['middle_name'])) {
            $civipar['middle_name'] = strtolower(trim($input['middle_name']));
        }

        if (isset($input['last_name'])) {
            $civipar['last_name'] = trim($input['last_name']);
        }

        /*
         * Geslacht bepalen
         */
        if (isset($input['gender'])) {
            $civipar['gender_id'] = $this->getGeslachtId($input['gender']);
        }

        /*
         * overzetten geboortedatum in juiste formaat als gevuld
         */
        if (isset($input['birth_date'])) {
            $civipar['birth_date'] = date("Y-m-d",
                    strtotime($input['birth_date']));
        }

        /*
         * overzetten roepnaam als gevuld
         */
        if (isset($input['nickname'])) {
            $civipar['nick_name'] = trim($input['nickname']);
        }

        /*
         * overzetten functie als gevuld
         */
        if (isset($input['jobtitle'])) {
            $civipar['job_title'] = trim($input['jobtitle']);
        }

        /*
         * API functie om persoon toe te voegen aanroepen
         */
        $civires = &civicrm_contact_create($civipar);

        return $civires;
    }
    /*
     * Functie om de specifieke gegevens voor persoon te zetten
     */
    function setAanvullendePersoonsgegevens($input) {

        $fout = false;
        $civipar = array("");
        $civires = array("");

        /*
        * Check of contact_id gevuld is, anders fout
        */
        if (!isset($input['contact_id'])) {
            trigger_error("CiviPersoon; Geen contact_id in parameter ".$input.
                " voor functie setAanvullendePersoonsgegevens.", E_USER_ERROR);
            $fout = true;
        } else {

            /*
             * Check of contact_id numeriek is, anders fout
             */
            if (!is_numeric($input['contact_id'])) {
                trigger_error("CiviPersoon; Contact_id ".$input['contact_id'].
                    " bevat niet-numerieke tekens in functie
                    setAanvullendePersoonsgegevens.", E_USER_ERROR);
                $fout = true;
            }
        }

        /*
         * alleen verder als geen fout
         */
        if (!$fout) {

            $civipar['entityID'] = $input['contact_id'];

            /*
             * persoonsnummer first overzetten
             */
            if (isset($input['persnr_first'])) {
                $civipar[CFPERSNR] = $input['persnr_first'];
            }

            /*
             * overzetten sofinummer als gevuld
             */
            if (isset($input['bsn'])) {
                $civipar[CFPERSBSN] = $input['bsn'];
            }

            /*
             * overzetten burgerlijke staat als gevuld
             */
            if (isset($input['burg_staat'])) {

                $civipar[CFPERSBURG] = $this->getBurgStaatId(
                        strtolower($input['burg_staat']));
            } else {
                $civipar[CFPERSBURG] = "7";
            }

            /*
             * aanroepen CiviCRM functie om Custom Data te vullen
             */
            require_once("CRM/Core/BAO/CustomValueTable.php");
            $civires = CRM_Core_BAO_CustomValueTable::setValues($civipar);}
        return $civires;
    }
    /*
     * functie om gegevens van een persoon met persoonsnummer first op te
     * halen
     */
    function retrievePersoonFirst($pers_first) {

        $civipar = array("");
        $civires = array("");
        $retrieved = array("");

        /*
         * alleen verwerken als persoonsnummer first gevuld is
         */
        if (!empty($pers_first)) {
            /*
             * eerst de basisgegevens van de persoon ophalen en overzetten naar
             * de array retrieved
             */
            $civipar = array(CFPERSNR => $pers_first);
            $civires = &civicrm_contact_get($civipar);

            if (civicrm_error($civires)) {
                $retrieved['is_error'] = $civicres['is_error'];
                $retrieved['error_message'] = $civires['error_message'];
            } else {
                foreach ($civires as $civicontact) {
                    $retrieved['contact_id'] = $civicontact['contact_id'];
                    $retrieved['contact_type'] = $civicontact['contact_type'];
                    $retrieved['first_name'] = $civicontact['first_name'];
                    if ( isset( $civicontact['middle_name'] ) ) {
						$retrieved['middle_name'] = $civicontact['middle_name'];
					}
                    $retrieved['last_name'] = $civicontact['last_name'];
                    if ( isset( $civicontact['job_title'] ) ) {
						$retrieved['job_title'] = $civicontact['job_title'];
					}
                    if ( isset( $civicontact['birth_date'] ) )		{
						$retrieved['birth_date'] = $civicontact['birth_date'];
					}
					if ( isset( $civicontact['is_deceased'] ) ) {
						$retrieved['is_deceased'] = $civicontact['is_deceased'];
					}
					if ( isset ( $civicontact['gender_id'] ) ) {
						$retrieved['gender_id'] = $civicontact['gender_id'];
					}
					if ( isset ( $civicontact['gender'] ) ) {
						$retrieved['gender'] = $civicontact['gender'];
                    }
                    if ( isset ( $civicontact['individual_prefix_id'] ) ) {
						$retrieved['individual_prefix_id'] =
							$civicontact['individual_prefix_id'];
					}
					if ( isset ( $civicontact['individual_prefix'] ) ) {
						$retrieved['individual_prefix'] =
							$civicontact['individual_prefix'];
					}
                    $retrieved['persoonsnummer_first'] = $pers_first;
                }
				/*
				 * daarna de locatiegegevens ophalen
				 */
				$civipar = array("contact_id" => $civicontact['contact_id']);
				$civires = &civicrm_location_get($civipar);
				if (!civicrm_error($civires)) {
					/*
					 * alle locaties langslopen
					 */
					$i = 0;
					foreach ($civires as $location) {
						/*
						 * als er telefoonnummers zijn, deze overzetten in array
						 * phones
						 */
						if (isset($location['phone']) && is_array($location['phone'])) {
							$retrieved['phones'] = array("");
							$j = 0;
							/*
							 * voor elk gevonden telefoonnummer
							 */
							foreach($location['phone'] as $telefoon) {
								/*
								 * omzetten location_type_id naar location_type
								 */
								$retrieved['phones'][$j]['location_type'] =
									$this->getLocationTypeTxt(
											$telefoon['location_type_id']);
								/*
								 * omzetten phone_type_id naar phone_type
								 */
								if ( isset ( $telefoon['phone_type_id'] ) ) { 
									$retrieved['phones'][$j]['phone_type'] = $this->
										getPhoneTypeTxt($telefoon['phone_type_id']);
								}
								/*
								 * overnemen telefoonnummer en primary
								 */
								if ( isset ( $telefoon['phone'] ) ) {
									$retrieved['phones'][$j]['phone'] =
										$telefoon['phone'];
								}
								$retrieved['phones'][$j]['primary'] =
									$telefoon['is_primary'];
								$j++;
							}
						}
						/* als er emailadressen zijn, deze overzetten in array
						 * emails
						 */
						if (isset($location['email'])) {
							$retrieved['emails'] = array("");
							$j = 0;
							/*
							 * voor elk gevonden emailadres
							 */
							foreach($location['email'] as $email) {
								/*
								 * omzetten location_type_id naar location_type
								 */
								$retrieved['emails'][$j]['location_type'] =
									$this->getLocationTypeTxt(
											$email['location_type_id']);
								/*
								 * overnemen emailadres en primary
								 */
								$retrieved['emails'][$j]['email'] =
									$email['email'];
								$retrieved['emails'][$j]['primary'] =
									$email['is_primary'];
								$j++;
							}
						}
						/*
						 * als er een adres is, dit overnemen
						 */
						if (isset($location['address'])) {
							$retrieved['address'][$i]['street_address'] =
								$location['address']['street_address'];

							$retrieved['address'][$i]['street_name'] =
								$location['address']['street_name'];

							$retrieved['address'][$i]['street_number'] =
								$location['address']['street_number'];

							$retrieved['address'][$i]['city'] =
								$location['address']['city'];

							$retrieved['address'][$i]['postal_code'] =
								$location['address']['postal_code'];

							$retrieved['address'][$i]['display'] =
								$location['address']['display'];
							$i++;


						}
					}

				}
				/*
				 * en als laatste de aanvullende gegevens voor de persoon
				 * ophalen (custom data)
				 */
				 require_once("CRM/Core/BAO/CustomValueTable.php");
				 $civipar = array(
					 "entityID" =>  $retrieved['contact_id'],
					 CFPERSNR =>  1,
					 CFPERSBSN =>  1,
					 CFPERSBURG =>  1);
				 $civires = CRM_Core_BAO_CustomValueTable::getValues($civipar);
				 if (!civicrm_error($civires)) {

					 $retrieved['persoonsnummer_first'] = $civires[CFPERSNR];
					 $retrieved['bsn'] = $civires[CFPERSBSN];
					 /*
					  * burgerlijke staat overzetten aan de hand van code
					  * custom veld
					  */
					 $retrieved['burg_staat'] =
						$this->getBurgStaatTxt($civires[CFPERSBURG]);
				 }
			}
        }
        return $retrieved;
    }
    /*
     * functie om het ID van een burgerlijke staat op te halen
     */
    function getBurgStaatId($burgtxt) {

        switch (strtolower(trim($burgtxt))) {
            case "gehuwd":
                $burgid = 1;
                break;
            case "alleenstaand":
                $burgid = 2;
                break;
            case "samenwonend":
                $burgid = 3;
                break;
            case "geregistreerd partnerschap":
                $burgid = 4;
                break;
            case "gescheiden":
                $burgid = 5;
                break;
            case "weduwe of weduwnaar":
                $burgid = 6;
                break;
            case "onvolledig gezin":
                $burgid = 8;
                break;
            case "gehuwd geweest":
                $burgid = 9;
                break;
            case "ongehuwd":
                $burgid = 10;
                break;
            default:
                $burgid = 7;
                break;
        }
        return $burgid;
    }
    /*
     * functie om de omschrijving van een burgerlijke staat op te halen
     */
    function getBurgStaatTxt($burgid) {

        switch($burgid) {
            case 1:
                $burgtxt = "Gehuwd";
                break;
            case 2:
                $burgtxt = "Alleenstaand";
                break;
            case 3:
                $burgtxt = "Samenwonend";
                break;
            case 4:
                $burgtxt = "Geregistreerd partnerschap";
                break;
            case 5:
                $burgtxt = "Gescheiden";
                break;
            case 6:
                $burgtxt = "Weduwe of weduwnaar";
                break;
            case 8:
                $burgtxt = "Onvolledig gezin";
                break;
            case 9:
                $burgtxt = "Gehuwd geweest";
                break;
            case 10:
                $burgtxt = "Ongehuwd";
                break;
            default:
                $burgtxt = "Onbekend";
                break;
        }
        return $burgtxt;
    }
    /*
     * functie om het id van een geslacht op te halen
     */
    function getGeslachtId($geslachttxt) {

        switch(strtolower($geslachttxt)) {
            case "vrouw":
                $geslachtid = 1;
                break;
            case "man":
                $geslachtid = 2;
                break;
            default:
                $geslachtid = 3;
                break;
        }
        return $geslachtid;
    }
    /*
     * functie om de omschrijving van een geslacht op te halen
     */
    function getGeslachtTxt($geslachtid) {

        switch($geslachtid) {
            case 1:
                $geslachttxt = "Vrouw";
                break;
            case 2:
                $geslachttxt = "Man";
                break;
            default:
                $geslachttxt = "Onbekend";
                break;
        }
        return $geslachttxt;
    }
        /*
     * functie om te kijken of de persoon een hoofdhuurder is van een
     * huishouden. Inkomende parameter is persoonsnummer First,
     * uitgaand is een het contact id van het huishouden of een
     * foutboodschap
     */
    function checkPersHoofd($persoonsnummer) {

        /*
         * geen terug als het persoonsnummer leeg is
         */
        if (empty($persoonsnummer)) {
            return "geen";
        }

        /*
         * contact_id van persoon ophalen.
         */
        $data_pers = $this->retrievePersoonFirst($persoonsnummer);
        /*
         * als resultaat leeg of geen contact_id bevat, fout
         */
        if (empty($data_pers)) {
            return "geen";
        }
        if (!isset($data_pers['contact_id'])) {
            return "geen";
        }

        $contact_id = $data_pers['contact_id'];

        /*
         * check of contact_id voorkomt in relatie hoofdhuurder. Zo ja,
         * nummers gevonden huishoudens in array. Zo nee, "niet" teruggeven
         */
        require_once 'api/v2/Relationship.php';
        $huishouden = null;
        $civipar = array("contact_id" => $contact_id);
        $relaties = civicrm_get_relationships($civipar);
        foreach ($relaties as $rels) {
			if ( is_array( $rels ) ) {
				foreach ($rels as $relatie) {
					if ($relatie['civicrm_relationship_type_id'] == RELHFD) {
						$huishouden = $relatie['cid'];
					}
				}
            }
        }
        if (empty($huishouden)) {
            return "geen";
        } else {
            return $huishouden;
        }

        return $relaties;

    }
    /*
     * functie om te kijken of de persoon een koopovereenkomt partner is 
     * van een huishouden. Inkomende parameter is persoonsnummer First,
     * uitgaand is een het contact id van het huishouden of een
     * foutboodschap
     */
    function checkPersKovPartner($persoonsnummer) {
        /*
         * geen terug als het persoonsnummer leeg is
         */
        if (empty($persoonsnummer)) {
            return "geen";
        }

        /*
         * contact_id van persoon ophalen. 
         */
        $data_pers = $this->retrievePersoonFirst($persoonsnummer);
        /*
         * als resultaat leeg of geen contact_id bevat, fout
         */
        if (empty($data_pers)) {
            return "geen";
        }
        if (!isset($data_pers['contact_id'])) {
            return "geen";
        }

        $contact_id = $data_pers['contact_id'];

        /*
         * check of contact_id voorkomt in relatie kov partner. Zo ja,
         * nummers gevonden huishoudens in array. Zo nee, "niet" teruggeven
         */
        require_once 'api/v2/Relationship.php';
        $huishouden = null;
        $civipar = array("contact_id" => $contact_id);
        $relaties = civicrm_get_relationships($civipar);
        foreach ($relaties as $rels) {
			if ( is_array ($rels ) ) {
				foreach ($rels as $relatie) {
					if ($relatie['civicrm_relationship_type_id'] == RELKOV) {
						$huishouden = $relatie['cid'];
					}
				}
			}
        }
        if (empty($huishouden)) {
            return "geen";
        } else {
            return $huishouden;
        }

        return $relaties;

    }

}

?>
