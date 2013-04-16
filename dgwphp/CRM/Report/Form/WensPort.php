<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 | De Goede Woning - report Wensportefeuille -1 maart 2012            |
 | (Erik Hommel)                                                      |
 |                                                                    |
 | Incident 24 01 13 001 - 24 jan 2013 Erik Hommel                    |
 +--------------------------------------------------------------------+
 
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';

class CRM_Report_Form_WensPort extends CRM_Report_Form {

    
    function __construct( ) {
        $this->_columns = array( );
        parent::__construct( );    }
    
    function preProcess( ) {
        parent::preProcess( );
    }

    static function formRule( $fields, $files, $self ) {  
        $errors = $grouping = array( );
        return $errors;
    }

    function postProcess( ) {
        $this->beginPostProcess( );

        $this->_columnHeaders = array(
            'tekst'     => array( 'title' => ''),
            'aantal'	=> array( 'title' => 'Aantal (peildatum 31-12-2011)' ),
            'percentage'=> array( 'title' => 'Percentage' ),
            '2014'	=> array( 'title' => 'Naar 2014' ),
            '2025'	=> array( 'title' => 'Richting 2025' )
        );
        $this->buildRows ( $rows );	
        $this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );    
    }
    
    function buildRows( &$rows ) {
        // use this method to modify $this->_columnHeaders (kan weg?)
        $this->modifyColumnHeaders( );
        
        /*
         * haal totaal aantal eenheden op
         */
        $aantalVGE = 0; 
        $qryCountVGE = "SELECT COUNT(*) AS aantal FROM vst_eenheid";
        $daoVGE = CRM_Core_DAO::executeQuery( $qryCountVGE );
        if ( $daoVGE->fetch() ) {
            $aantalVGE = $daoVGE->aantal;
	}
        
        for ($i = 0; $i <= 32; $i++) {
            /*
             * bouw rijen op
             */
            $rows[$i]['huidig'] = "";
            $rows[$i]['toekomst'] = "";
            switch( $i ) {
                case 0:
                    $rows[$i]['tekst'] = "<strong>Algemeen</strong>";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 1:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;omvang in aantallen";
                    $rows[$i]['percentage'] = "100%";
                    $rows[$i]['aantal'] = $aantalVGE;
                    $rows[$i]['2014'] = "+/- 8000-7000";
                    $rows[$i]['2025'] = "+/- 8000-7000";
                    break;
                case 3:
                    $rows[$i]['tekst'] = "<strong>Woning</strong>";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 4:
                    /*
                     * aantal eenheden met oppervlakte meer dan 70m2
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Woninggrootte (groter dan 70m2 VVO)";
                    $qryOpp = 
"SELECT COUNT(*) AS aantalOpp FROM vst_eenheid WHERE meters > 70";
                    $daoOpp = CRM_Core_DAO::executeQuery( $qryOpp);
                    $aantalOpp = 0;
                    if ( $daoOpp->fetch() ) {
                        $aantalOpp = $daoOpp->aantalOpp;
                    }
                    if ( $aantalOpp != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalOpp / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalOpp;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Verhogen";
                    $rows[$i]['2025'] = "65%";
                    break;
                case 5:
                    /*
                     * aantal eenheden met type Eengezinswoning
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Eengezinswoning";
                    $qryEGW = 
"SELECT COUNT(*) AS aantalEGW FROM vst_eenheid WHERE type_first = 'Eengezinswoning'";
                    $daoEGW = CRM_Core_DAO::executeQuery( $qryEGW);
                    $aantalEGW = 0;
                    if ( $daoEGW->fetch() ) {
                        $aantalEGW = $daoEGW->aantalEGW;
                    }
                    if ( $aantalEGW != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalEGW / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalEGW;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Uitbreiden";
                    $rows[$i]['2025'] = "60%";
                    break;
                case 6:
                    /*
                     * aantal eenheden met type Appartment met lift
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Appartement met lift";
                    $qryLift = 
"SELECT COUNT(*) AS aantalLift FROM vst_eenheid WHERE type_first = 'Appartement Met Lift'";
                    $daoLift = CRM_Core_DAO::executeQuery( $qryLift);
                    $aantalLift = 0;
                    if ( $daoLift->fetch() ) {
                        $aantalLift = $daoLift->aantalLift;
                    }
                    if ( $aantalLift != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalLift / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalLift;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Consolideren";
                    $rows[$i]['2025'] = "20%";
                    break;
                case 7:
                    /*
                     * aantal eenheden met type Laagbouwwoning, Seniorenwoning, Begane grond woning
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Laagbouwwoning (senioren, bg)";
                    $qryBG = 
"SELECT COUNT(*) AS aantalBG FROM vst_eenheid WHERE type_first IN ('Laagbouwwoning', 'Seniorenwoning', 'Beganegrondwoning')";
                    $daoBG = CRM_Core_DAO::executeQuery( $qryBG);
                    $aantalBG = 0;
                    if ( $daoBG->fetch() ) {
                        $aantalBG = $daoBG->aantalBG;
                    }
                    if ( $aantalBG != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalBG / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalBG;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Uitbreiden";
                    $rows[$i]['2025'] = "10%";
                    break;
                case 8:
                    /*
                     * aantal eenheden met type Appartment zonder lift
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Appartement zonder lift";
                    $qryZonder = 
"SELECT COUNT(*) AS aantalZonder FROM vst_eenheid WHERE type_first = 'Appartement Zonder Lift'";
                    $daoZonder = CRM_Core_DAO::executeQuery( $qryZonder);
                    $aantalZonder = 0;
                    if ( $daoZonder->fetch() ) {
                        $aantalZonder = $daoZonder->aantalZonder;
                    }
                    if ( $aantalZonder != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalZonder / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalZonder;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Verminderen";
                    $rows[$i]['2025'] = "5%";
                    break;
                case 9:
                    /*
                     * aantal eenheden met overige typen
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Overige woning (bijzondere woonvormen)";
                    $qryOverig = 
"SELECT COUNT(*) AS aantalOverig FROM vst_eenheid WHERE type_first NOT IN ('Eengezinswoning', 'Appartement Met Lift', 'Appartement Zonder Lift', 'Laagbouwwoning', 'Seniorenwoning', 'Beganegrondwoning')";
                    $daoOverig = CRM_Core_DAO::executeQuery( $qryOverig);
                    $aantalOverig = 0;
                    if ( $daoOverig->fetch() ) {
                        $aantalOverig = $daoOverig->aantalOverig;
                    }
                    if ( $aantalOverig != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalOverig / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalOverig;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Uitbreiden bijzondere woonvormen";
                    $rows[$i]['2025'] = "5%";
                    break;
                case 11:
                    $rows[$i]['tekst'] = "<strong>Energetisch</strong>";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 12:
                    /*
                     * aantal eenheden met EPA label A, B of C
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Energielabel ABC";
                    $qryEPA = 
"SELECT COUNT(*) AS aantalEPA FROM vst_eenheid WHERE epa_first IN ('A', 'B', 'C')";
                    $daoEPA = CRM_Core_DAO::executeQuery( $qryEPA);
                    $aantalEPA = 0;
                    if ( $daoEPA->fetch() ) {
                        $aantalEPA = $daoEPA->aantalEPA;
                    }
                    if ( $aantalEPA != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalEPA / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalEPA;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Verhogen";
                    $rows[$i]['2025'] = "70%";
                    break;
                case 14:
                    $rows[$i]['tekst'] = "<strong>Bouwtechnisch</strong>";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 15:
                    /*
                     * aantal eenheden met lage kwaliteit
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Lage kwaliteit (sleutel 1)";
                    $qryLaag = 
"SELECT SUM(aantal) AS aantalLaag FROM vst_complex WHERE ewaarde < 3";
                    $daoLaag = CRM_Core_DAO::executeQuery( $qryLaag);
                    $aantalLaag = 0;
                    if ( $daoLaag->fetch() ) {
                        $aantalLaag = $daoLaag->aantalLaag;
                    }
                    if ( $aantalLaag != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalLaag / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalLaag;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Verminderen";
                    $rows[$i]['2025'] = "15%";
                    break;
                case 16:
                    /*
                     * aantal eenheden met basis kwaliteit
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Basis kwaliteit (sleutel 2)";
                    $qryBasis = 
"SELECT SUM(aantal) AS aantalBasis FROM vst_complex WHERE ewaarde >= 3";
                    $daoBasis = CRM_Core_DAO::executeQuery( $qryBasis);
                    $aantalBasis = 0;
                    if ( $daoBasis->fetch() ) {
                        $aantalBasis = $daoBasis->aantalBasis;
                    }
                    if ( $aantalBasis != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalBasis / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalBasis;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Verhogen";
                    $rows[$i]['2025'] = "85%";
                    break;
                case 17:
                    /*
                     * aantal eenheden met hoge kwaliteit
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Hoge kwaliteit (sleutel 3)";
                    $qryHoog = 
"SELECT SUM(aantal) AS aantalHoog FROM vst_complex WHERE ewaarde > 3";
                    $daoHoog = CRM_Core_DAO::executeQuery( $qryHoog);
                    $aantalHoog = 0;
                    if ( $daoHoog->fetch() ) {
                        $aantalHoog = $daoHoog->aantalHoog;
                    }
                    if ( $aantalHoog != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalHoog / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalHoog;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "Consolideren";
                    $rows[$i]['2025'] = "30%";
                    break;
                case 19:
                    $rows[$i]['tekst'] = "<strong>Strategieën</strong>";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 20:
                    /*
                     * aantal eenheden met strategie Continureren
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Continueren";
                    $qryCont = 
"SELECT SUM(aantal) AS aantalCont FROM vst_complex WHERE strategie IN ('Continueren 1', 'Continueren 2', 'Continueren 3', 'Continueren 4')";
                    $daoCont = CRM_Core_DAO::executeQuery( $qryCont);
                    $aantalCont = 0;
                    if ( $daoCont->fetch() ) {
                        $aantalCont = $daoCont->aantalCont;
                    }
                    if ( $aantalCont != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalCont / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalCont;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 21:
                    /*
                     * aantal eenheden met strategie Moderniseren
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Moderniseren";
                    $qryMod = 
"SELECT SUM(aantal) AS aantalMod FROM vst_complex WHERE strategie IN ('Moderniseren 1', 'Moderniseren 2')";
                    $daoMod = CRM_Core_DAO::executeQuery( $qryMod);
                    $aantalMod = 0;
                    if ( $daoMod->fetch() ) {
                        $aantalMod = $daoMod->aantalMod;
                    }
                    if ( $aantalMod != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalMod / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalMod;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 22:
                    /*
                     * aantal eenheden met strategie Herpositioneren
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Herpositioneren";
                    $qryHer = 
"SELECT SUM(aantal) AS aantalHer FROM vst_complex WHERE strategie = 'Herpositioneren 1'";
                    $daoHer = CRM_Core_DAO::executeQuery( $qryHer);
                    $aantalHer = 0;
                    if ( $daoHer->fetch() ) {
                        $aantalHer = $daoHer->aantalHer;
                    }
                    if ( $aantalHer != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalHer / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalHer;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 23:
                    /*
                     * aantal eenheden met strategie Afstoten
                     */
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Afstoten";
                    $qryAf = 
"SELECT SUM(aantal) AS aantalAf FROM vst_complex WHERE strategie = 'Afstoten'";
                    $daoAf = CRM_Core_DAO::executeQuery( $qryAf);
                    $aantalAf = 0;
                    if ( $daoAf->fetch() ) {
                        $aantalAf = $daoAf->aantalAf;
                    }
                    if ( $aantalAf != 0 && $aantalVGE != 0 ) {
                        $percentage = ( $aantalAf / $aantalVGE ) * 100;
                        $rows[$i]['percentage'] = round($percentage, 1)." %";
                        $rows[$i]['aantal'] = (int) $aantalAf;
                    } else {
                        $rows[$i]['percentage'] = "";
                        $rows[$i]['aantal'] = "";
                    }
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 25:
                    $rows[$i]['tekst'] = "<strong>Prijs en exploitatie</strong> (bron: jaarverslag/jaarrekening 2011)";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 26:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Betaalbaar (tot &euro; 554,76)";
                    $rows[$i]['percentage'] = "84.6%";
                    $rows[$i]['aantal'] = "6823";
                    $rows[$i]['2014'] = "Verminderen";
                    $rows[$i]['2025'] = "67%";
                    break;
                case 27:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Bereikbaar (tot &euro; 652,52)";
                    $rows[$i]['percentage'] = "9.5%";
                    $rows[$i]['aantal'] = "769";
                    $rows[$i]['2014'] = "Uitbreiden";
                    $rows[$i]['2025'] = "24%";
                    break;
                case 28:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;DAEB";
                    $rows[$i]['percentage'] = "94.1%";
                    $rows[$i]['aantal'] = "7592";
                    $rows[$i]['2014'] = "Minimaal 90%";
                    $rows[$i]['2025'] = "Minimaal 90%";
                    break;
                case 29:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Niet-DAEB";
                    $rows[$i]['percentage'] = "5.8%";
                    $rows[$i]['aantal'] = "473";
                    $rows[$i]['2014'] = "Maximaal 10%";
                    $rows[$i]['2025'] = "Maximaal 10%";
                    break;
                case 30:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Koopgarant";
                    $rows[$i]['percentage'] = "5.2%";
                    $rows[$i]['aantal'] = "445";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 31:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Koopplus";
                    $rows[$i]['percentage'] = "0.3%";
                    $rows[$i]['aantal'] = "25";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
                case 32:
                    $rows[$i]['tekst'] = "&nbsp;&nbsp;Te Woon gelabeld";
                    $rows[$i]['percentage'] = "0%";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "Uitbreiden";
                    $rows[$i]['2025'] = "25%";
                    break;
                default:
                    $rows[$i]['tekst'] = "";
                    $rows[$i]['percentage'] = "";
                    $rows[$i]['aantal'] = "";
                    $rows[$i]['2014'] = "";
                    $rows[$i]['2025'] = "";
                    break;
            }
        }
    }
}
