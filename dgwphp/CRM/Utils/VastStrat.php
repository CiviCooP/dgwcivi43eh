<?php
/*
+--------------------------------------------------------------------+
| Added PHP class in CiviCRM Complex |
+--------------------------------------------------------------------+
| Project : Vastgoedstrategie / De Goede Woning |
| Author : Erik Hommel (EE-atWork, hommel@ee-atwork.nl |
| http://www.ee-atwork.nl) |
| Date : 29 december 2011 |
| Description : Class met complex berekeningen vastgoed |
| strategie |
+--------------------------------------------------------------------+
*/

/**
*
* @package CRM
* @copyright CiviCRM LLC (c) 2004-2010
* $Id$
*
*/
class CRM_Utils_VastStrat {
    /*
     * functie om aantal vge's in subcomplex te bepalen
     */
    static function aantalVGE ( $params ) {
        /*
         * return 0 als fouten
         */
        if ( self::_validateParams( $params ) == false ) {
            return 0;
        }
        $where = self::_processWhere ( $params );
        /*
         * bepaal aantal uit eenheden met count
         */
        $qryAantal = 
"SELECT COUNT(*) AS aantal FROM vst_eenheid WHERE ".$where;
        $daoAantal = CRM_Core_DAO::executeQuery( $qryAantal );
        /*
         * return 0 als er fouten zijn of niks gevonden is
         */
        if ( !$daoAantal->fetch() ) {
            $aantal = 0;
        } else {
            $aantal = $daoAantal->aantal;
        }
        return $aantal;
    }
    /*
     * bepaal b-waarde voor individuele VGE
     * @params type woningtype vanuit First
     * trap J/N, vaste trap naar zolder
     * vertrek aantal vertrekken
     * meters oppervlakte
     * buiten code buitenruimte
     * 
     * @return $punten aantal punten op basis van parms
     */
    static function bepaalBVge ( $params ) {
        /*
         * fout als alle inkomende parameters leeg
         */
        if ( empty( $params ) ) {
            return 0;
        }
        /*
         * type bepaalt welke verwerking er gevolgd wordt
         */
        $punten = 0;
        $type = strtolower( $params['type'] );
        if ( $type == "eengezinswoning" ) {
            $punten = self::_bepaalEGW ( $params );
        } elseif ( $type == "seniorenwoning" || $type == "laagbouwwoning" || $type == "beganegrondwoning" ) {
            $punten = self::_bepaalLaag( $params );
        } elseif ( substr( $type, 0, 11 ) == "appartement" ) {
            $punten = self::_bepaalApt( $params );
        } else {
            $punten = self::_bepaalOverig( $params );
        }
        return $punten;
    }
    /*
     * bepaal c-waarde voor individuele VGE
     * @params label EPA label vanuit first
     * 
     * @return $punten aantal punten op basis van parms
     */
    static function bepaalCVge ( $params ) {
        /*
         * fout als geen params
         */
        if ( empty( $params ) || !is_array( $params ) || !isset( $params['label'] ) ) {
            return 0;
        }
        $label = strtolower( $params['label'] );
        /*
         * return aantal punten op basis van label
         */
        $validLabels = array(
            'a' => 10,
            'b' => 9,
            'c' => 7,
            'd' => 5,
            'e'	=> 3);
        /*
         * als label in $validLabels, bijbehorende punten, anders 0 punten
         */
        if ( array_key_exists ( $label, $validLabels ) ) {
            return $validLabels[$label];
        } else {
            return 0;
        }
    }
    /*
     * bepaal b-waarde voor complex/subcomplex/type
     * @params complex complex
     * sub subcomplex
     * type type
     * 
     * @return $punten aantal punten op basis van parms
     */
    static function bepaalBCpl ( $params ) {
        /*
         * valideer params
         */
        if ( self::_validateParams( $params ) == false ) {
            return 0;
        }
        /*
         * stel where samen om eenheden op te halen
         */
        $where = self::_processWhere ( $params );
        /*
         * tel punten per where statement
         */
        $qryBCpl = 
"SELECT count(*) AS aantal, sum(b_punt) AS b_punten FROM vst_eenheid WHERE ".$where;
        $daoBCpl = CRM_Core_DAO::executeQuery( $qryBCpl );
        if ( $daoBCpl->fetch() ) {
            $punten = $daoBCpl->b_punten / $daoBCpl->aantal;
        }
        return $punten;
    }
    /*
     * bepaal b-waarde voor complex/subcomplex/type
     * @params complex complex
     * sub subcomplex
     * type type
     * 
     * @return $punten aantal punten op basis van parms
     */
    static function bepaalCCpl ( $params ) {
        /*
         * valideer params
         */
        if ( self::_validateParams( $params ) == false ) {
            return 0;
        }
        $ones = array( "CPL0006", "CPL0017", "CPL0018", "CPL0023", "CPL0046", "CPL0064" );
        $twos = array( "CPL0005", "CPL0019", "CPL0020", "CPL0021", "CPL0022", "CPL0037", 
            "CPL0047", "CPL0068");
        $threes = array( "CPL0026", "CPL0038", "CPL0051", "CPL0059", "CPL0061", "CPL0062", 
            "CPL0069", "CPL0076", "CPL0079" );
        $fours = array( "CPL0030", "CPL0055", "CPL0058" );
        $fives = array( "CPL0001", "CPL0010", "CPL0032", "CPL0043", "CPL0054", "CPL0060",
            "CPL0070", "CPL0075", "CPL0082", "CPL0087", "CPL0090", "CPL0092", "CPL0100",
            "CPL0102", "CPL0105", "CPL0109", "CPL0111", "CPL0143");
        $sixes = array( "CPL0035", "CPL0065", "CPL0074", "CPL0118", "CPL0140" );
        $sevens = array( "CPL0003", "CPL0008", "CPL0014", "CPL0025", "CPL0027", "CPL0028", 
            "CPL0033", "CPL0034", "CPL0050", "CPL0057", "CPL0063", "CPL0080", "CPL0083", 
            "CPL0084", "CPL0085", "CPL0089", "CPL0096", "CPL0103", "CPL0104", "CPL0110",
            "CPL0114", "CPL0115", "CPL0116", "CPL0117", "CPL0119", "CPL0121", "CPL0123",
            "CPL0124", "CPL0125", "CPL0129", "CPL0131", "CPL0135", "CPL0136", "CPL0137", 
            "CPL0139", "CPL0141", "CPL0142", "CPL0144", "CPL0147", "CPL0148", "CPL0149" );
        $eights = array( "CPL0040", "CPL0066", "CPL0097", "CPL0126", "CPL0128", "CPL0130", 
            "CPL0153", "CPL0154", "CPL0155" );
        $nines = array( "CPL0160", "CPL0165", "CPL0167", "CPL0170", "CPL0171" );
        $tens = array( "CPL0004", "CPL0156", "CPL0159", "CPL0161", "CPL0164", "CPL0166", 
            "CPL0169", "CPL0172", "CPL0173" );
        $cpl = trim( $params['complex'] );
        $sub = trim( $params['sub'] );
        $type = strtolower( trim( $params['type'] ) );
        $buurt = trim( $params['buurt'] );
        if ( in_array( $cpl, $ones ) ) {
            return 1;
        }
        if ( in_array( $cpl, $twos ) ) {
            return 2;
        }
        if ( in_array( $cpl, $threes ) ) {
            return 3;
        }
        if ( in_array( $cpl, $fours ) ) {
            return 4;
        }
        if ( in_array( $cpl, $fives ) ) {
            return 5;
        }
        if ( in_array( $cpl, $sixes ) ) {
            return 6;
        }
        if ( in_array( $cpl, $sevens ) ) {
            return 7;
        }
        if ( in_array( $cpl, $eights ) ) {
            return 8;
        }
        if ( in_array( $cpl, $nines ) ) {
            return 9;
        }
        if ( in_array( $cpl, $tens ) ) {
            return 10;
        }
        if ( $cpl == "CPL0150" ) {
            if ( $sub == "SCB15001" ) {
                return 7;
            } else {
                return 9;
            }
        }
        if ( $cpl == "CPL0007" ) {
            if ( $sub == "SCB00701" ) {
                return 8;
            } else {
                return 10;
            }
        }
        if ( $cpl == "CPL0045" ) {
            if ( $sub == "SCB04502" ) {
                return 2;
            } else {
                return 4;
            }
        }
        if ( $cpl == "CPL0053" ) {
            if ( $sub == "SCB05301" ) {
                return 1;
            } else {
                return 3;
            }
        }
        if ( $cpl == "CPL0029" ) {
            if ( $sub == "SCB0902" && $type == "bovenduplex" ) {
                return 2;
            } else {
                return 1;
            }
        }
        if ( $cpl == "CPL0024" ) {
            if ( $sub == "SCB02401" ) {
                return 4;
            } else {
                if ( $type == "bovenduplex" ) {
                    return 3;
                } else {
                    return 5;
                }
            }
        }
        if ( $cpl == "CPL0049" ) {
            if ( $sub == "SCB04901" ) {
                return 3;
            } else {
                if ( $type == "appartement zonder lift" ) {
                    return 6;
                } else {
                    return 7;
                }
            }
        }
        if ( $cpl == "CPL0031" ) {
            if ( $sub == "SCB03102" ) {
                return 4;
            } else {
                return 5;
            }
        }
        if ( $cpl == "CPL0056" ) {
            if ( $sub == "SCB05601" ) {
                return 4;
            } else {
               return 6;
            }
        }
        if ( $cpl == "CPL0086" ) {
            if ( $sub == "SCB08601" ) {
                return 4;
            } else {
                return 5;
            }
        }
        if ( $cpl == "CPL0101" ) {
            if ( $type == "laagbouwwoning" ) {
                return 6;
            } else {
                return 7;
            }
        }
        return 0;
    }
    /*
     * functie om strategie First uit eenheden op te halen
     * @params complex complex
     * sub subcomplex
     * type type
     * 
     * @return $strategie strategie uit First
     */
    static function bepaalStratFirst ( $params ) {
        /*
         * valideer params
         */
        if ( self::_validateParams( $params ) == false ) {
            return 0;
        }
        /*
         * stel where samen om eenheden op te halen
         */
        $where = self::_processWhere ( $params );
        /*
         * haal strategie First op uit eenheid
         */
        $qryStrat = "SELECT strategie FROM vst_eenheid WHERE ".$where;
        $daoStrat = CRM_Core_DAO::executeQuery( $qryStrat );
        if ( $daoStrat->fetch() ) {
            $strategie = $daoStrat->strategie;
        } else {
            $strategie = "";
        }
        return $strategie;
    }
    /*
     * functie om strategielabel te bepalen aan de hand van huidige data
     * @params wens wenswaarde
     * markt marktwaarde
     * kwa kwawaarde
     * 
     * @return $strategie strategie op basis van params
     */
    static function bepaalStrategie( $params ) {
        /*
         * label 'fout' als params leeg, geen array of ongeldige elementen
         */
        if ( empty( $params ) || !is_array( $params ) ) {
            return 'fout';
        }
        if ( !isset( $params['wens'] ) || !isset( $params['markt'] ) || !isset( $params['kwa'] ) ) {
            return 'fout';
        }
        /*
         * Bepalen strategielabel
         */
        $label = "";
        $wens = (int) $params['wens'];
        $markt = (int) $params['markt'];
        $kwa = (int) $params['kwa'];
        
        switch( $wens ) {
            case ( $wens >= 35 ):
                $label = 'Continueren 1';
                break;
            case ( $wens < 20 ):
                $label = 'Afstoten';
                break;
            default:
                switch( $markt ) {
                    case ( $markt >= 16 ):
                        if ( $kwa >= 13 ) {
                            $label = 'Continueren 2';
                        } else {
                            $label = 'Moderniseren 1';
                        }
                        break;
                    case ( $markt < 16 && $markt >= 13 ):
                        if ( $kwa >= 11 ) {
                            $label = 'Continueren 3';
                        } else {
                            $label = 'Moderniseren 2';
                        }
                        break;
                    case ( $markt < 13 && $markt >= 10 ):
                        if ( $kwa >= 9 ) {
                            $label = "Continueren 4";
                        } else {
                            $label = "Herpositioneren 1";
                        }
                        break;
                    default:
                        $label = "Herpositioneren 2";
                }
        }
        return $label;
    }
    /*
     * functie om punten voor eengezinswoning te bepalen
     */
    private function _bepaalEGW( $params ) {
        $punten = 0;
        /*
         * 2 punten voor vaste trap naar zolder
         */
        if ( isset( $params['trap'] ) && $params['trap'] == "J" ) {
            $punten = $punten + 2;
        }
        /*
         * 3 punten voor 3 of meer slaapkamers
         */
        if ( isset( $params['vertrek'] ) && $params['vertrek'] >= 3 ) {
            $punten = $punten + 3;
        }
        /*
         * punten voor oppervlakte
         */
        if (isset( $params['meters'] ) ) {
            if ( $params['meters'] >= 80 ) {
                $punten = $punten + 10;
            } elseif ( $params['meters'] >= 70 ) {
                $punten = $punten + 8;
            } elseif ( $params['meters'] >= 60 ) {
                $punten = $punten + 5;
            } else {
                $punten = $punten + 3;
            }
        }
        return $punten;
    }
    /*
     * functie om punten voor laagbouw/beganegron/seniorenwoning te bepalen
     */
    private function _bepaalLaag( $params ) {
        $punten = 0;
        /*
         * 3 punten voor 2 of meer slaapkamers
         */
        if ( isset( $params['vertrek'] ) && $params['vertrek'] >= 2 ) {
            $punten = $punten + 3;
        }
        /*
         * punten voor oppervlakte
         */
        if (isset( $params['meters'] ) ) {
            if ( $params['meters'] >= 65 ) {
                $punten = $punten + 10;
            } elseif ( $params['meters'] >= 55 ) {
                $punten = $punten + 8;
            } elseif ( $params['meters'] >= 45 ) {
                $punten = $punten + 5;
            } else {
                $punten = $punten + 3;
            }
        }
        return $punten;
    }
    /*
     * functie om punten voor appartementen te bepalen
     */
    private function _bepaalApt( $params ) {
        $punten = 0;
        /*
         * 2 punten als lift
         */
        $type = strtolower( $params['type']);
        if ( $type == "appartement met lift" ) {
            //$punten = $punten + 2;
            //temp 4
            $punten = $punten + 4;
        }
        /*
         * 2 punten minder als geen buitenruimte
         */
        $buiten = strtolower ( $params['buiten'] );
        if ( $buiten == "geen" ) {
            $punten = $punten - 2;
        }
        /*
         * 3 punten voor 2 of meer slaapkamers
         */
        if ( isset( $params['vertrek'] ) && $params['vertrek'] >= 2 ) {
            //$punten = $punten + 3;
            //temp 2
            $punten = $punten + 2;
        }
        /*
         * punten voor oppervlakte
         */
        if (isset( $params['meters'] ) ) {
            if ( $params['meters'] >= 70 ) {
                //$punten = $punten + 8;
                //temp 7
                $punten = $punten + 7;
            } elseif ( $params['meters'] >= 60 ) {
                //$punten = $punten + 6;
                //temp 5
                $punten = $punten + 5;
            } elseif ( $params['meters'] >= 50 ) {
                //$punten = $punten + 4;
                //temp 3
                $punten = $punten + 3;
            } else {
                $punten = $punten + 2;
            }
        }
        return $punten;
    }
    /*
     * functie om punten voor overige woningtypen te bepalen
     */
    private function _bepaalOverig( $params ) {
        $punten = 0;
        /*
         * 3 punten voor 2 of meer slaapkamers
         */
        if ( isset( $params['vertrek'] ) && $params['vertrek'] >= 2 ) {
            $punten = $punten + 3;
        }
        /*
         * punten gebaseerd op type
         */
        $type = strtolower( $params['type'] );
        switch ( $type ) {
            case "patiowoning":
                $punten = $punten + 10;
                break;
            case "onzelfstandige kamer":
                $punten = $punten + 6;
                break;
            case "bovenduplex":
                $punten = $punten + 2;
                break;
            case "benedenduplex":
                $punten = $punten + 3;
                break;
            case "maisonnette":
                $punten = $punten + 7;
                if ( isset( $params['buiten'] ) ) {
                    $buiten = strtolower( $params['buiten'] );
                    if ( $buiten != "geen" && $buiten != "enige" && !empty( $buiten ) ) {
                        $punten = $punten + 3;
                    }
                }
                break;
        }
        return $punten;
    }
    /*
     * functie om complex, subcomplex en type te valideren
     */
    private function _validateParams( $params ) {
        /*
         * fout als hele params leeg
         */
        if ( empty( $params ) ) {
            return false;
        }
        /*
         * alle 3 moeten in params zitten
         */
        if ( !isset( $params['complex'] ) || !isset( $params['sub'] ) || !isset( $params['type'] ) ) {
            return false;
        }
        /*
         * mogen niet alle 3 leeg zijn
         */
        if ( empty( $params['complex'] ) && empty( $params['sub'] ) && empty( $params['type'] ) ) {
            return false;
        }
        /*
         * als type gevuld moet sub of complex gevuld zijn
         */
        if ( !empty( $params['type'] ) ) {
            if ( empty( $params['complex']) && empty( $params['sub'] ) ) {
                return false;
            }
        }
        return true;
    }
    /*
     * functie om where statement samen te stellen uit complex, sub en type
     */
    private function _processWhere( $params ) {
        $where = null;
        if ( empty( $params['sub'] ) ) {
            $where = "complex = '{$params['complex']}'";
        } else {
            $where = "subcomplex = '{$params['sub']}'";
        }
        if ( !empty( $params['type'] ) ) {
            $where .= "AND type_first = '{$params['type']}'";
        }
        return $where;
    }
    static function exportVgeCsv( &$form ) {
        $complexRows = array ( );
        /*
         * rapport wordt alleen doorgegeven aan
         * functie om naar csv te exporteren
         * $rows worden hier opgebouwd
         */
        $query = 
"SELECT * FROM vst_eenheid ORDER By complex, subcomplex, type_first, vgefirst"; 
        $dao  = CRM_Core_DAO::executeQuery( $query );
        $verwerkComplex = null;
        $verwerkSubcomplex = null;
        $verwerkWoningtype = null;
        while ( $dao->fetch( ) ) {
            $row = array( );
            /*
             * check of er een nieuwe sleutel is (complex/subcomplex/woningtype)
             * zo ja, gegevens voor complex ophalen
             */
            if ( $dao->complex != $verwerkComplex || $dao->subcomplex != $verwerkSubcomplex || $dao->type_first != $verwerkWoningtype ) {
                $verwerkComplex = $dao->complex;
                $verwerkSubcomplex = $dao->subcomplex;
                $verwerkWoningtype = $dao->type_first;
                $complexQry = 
"SELECT locatie, verhuurbaar, doelgroep, cwaarde, dwaarde, ewaarde, strategie FROM vst_complex WHERE complex = '$verwerkComplex' AND subcomplex = '$verwerkSubcomplex' AND woningtype = '$verwerkWoningtype'";
                $daoComplex = CRM_Core_DAO::executeQuery( $complexQry );
                if ( $daoComplex->fetch() ) {
                    if ( isset( $daoComplex->locatie ) ) {
                        $locatie = $daoComplex->locatie;
                    } else {
                        $locatie = null;
                    }
                    if ( isset( $daoComplex->verhuurbaar ) ) {
                        $verhuurbaar = $daoComplex->verhuurbaar;
                    } else {
                        $verhuurbaar = null;
                    }
                    if ( isset( $daoComplex->doelgroep ) ) {
                        $doelgroep = $daoComplex->doelgroep;
                    } else {
                        $doelgroep = null;
                    }
                    if ( isset( $daoComplex->cwaarde ) ) {
                        $energiewaarde = $daoComplex->cwaarde;
                    } else {
                        $energiewaarde = null;
                    }
                    if ( isset( $daoComplex->dwaarde ) ) {
                        $financieel = $daoComplex->dwaarde;
                    } else {
                        $financieel = null;
                    }
                    if ( isset( $daoComplex->ewaarde ) ) {
                        $bouwtechnisch = $daoComplex->ewaarde;
                    } else {
                        $bouwtechnisch = null;
                    }
                    if ( isset( $daoComplex->strategie ) ) {
                        $strategie = $daoComplex->strategie;
                    } else {
                        $strategie = null;
                    }
                }
            }
            if ( isset( $dao->vgefirst ) ) {
                $row['vgenummer'] = $dao->vgefirst;
            } else {
                $row['vgenummer'] = null;
            }
            if ( isset( $dao->complex ) ) {
                $row['complex'] = $dao->complex;
            } else {
                $row['complex'] = null;
            }
            if ( isset( $dao->subcomplex ) ) {
                $row['subcomplex'] = $dao->subcomplex;
            } else {
                $row['subcomplex'] = null;
            }
            if ( isset( $dao->stadsdeel ) ) {
                $row['stadsdeel'] = $dao->stadsdeel;
            } else {
                $row['stadsdeel'] = null;
            }
            if ( isset( $dao->buurt ) ) {
                $row['buurt'] = $dao->buurt;
            } else {
                $row['buurt'] = null;
            }
            if ( isset( $dao->type_first ) ) {
                $row['woningtype'] = $dao->type_first;
            } else {
                $row['woningtype'] = null;
            }
            $row['locatie'] = $locatie;
            $row['verhuurbaar'] = $verhuurbaar;
            $row['doelgroep'] = $doelgroep;
            if ( isset( $dao->b_punt ) ) {
                $row['woningwaarde'] = $dao->b_punt;
            }
            if ( isset( $dao->meters ) ) {
                $row['oppervlakte'] = $dao->meters;
            } else {
                $row['oppervlakte'] = null;
            }
            if ( isset( $dao->vertrek ) ) {
                $row['slaapkamers'] = $dao->vertrek;
            } else {
                $row['slaapkamers'] = null;
            }
            if ( isset( $dao->buitencode ) ) {
                $row['buitenruimte'] = $dao->buitencode;
            } else {
                $row['buitenruimte'] = null;
            }
            if ( isset( $dao->trap ) ) {
                $row['lift'] = $dao->trap;
            } else {
                $row['lift'] = null;
            }
            $row['energiewaarde'] = $energiewaarde;
            $row['financieel'] = $financieel;
            $row['bouwtechnisch'] = $bouwtechnisch;
            $row['strategie'] = $strategie;
            $complexRows[] = $row;
        }
        /*
         * headers samenstellen
         */
        $complexHeaders['vgenummer'] = "VGE nummer"; 
        $complexHeaders['complex'] = "Complex";
        $complexHeaders['subcomplex'] = "Subcomplex";
        $complexHeaders['stadsdeel'] = "Stadsdeel";
        $complexHeaders['buurt'] = "Buurt";
        $complexHeaders['woningtype'] = "Woningtype";
        $complexHeaders['locatie'] = "Locatie";
        $complexHeaders['verhuurbaar'] = "Verhuurbaarheid";
        $complexHeaders['doelgroep'] = "Doelgroep";
        $complexHeaders['woningwaarde'] = "Woningwaarde";
        $complexHeaders['oppervlakte'] = "Oppervlakte";
        $complexHeaders['slaapkamers'] = "Slaapkamers";
        $complexHeaders['buitenruimte'] = "Buitenruimte";
        $complexHeaders['lift'] = "Lift (J/N)";
        $complexHeaders['energiewaarde'] = "Energetische waarde";
        $complexHeaders['financieel'] = "Financiële waarde";
        $complexHeaders['bouwtechnisch'] = "Bouwtechnische waarde";
        $complexHeaders['strategie'] = "Strategie";

        //Mark as a CSV file.
        header('Content-Type: text/csv');

        //Force a download and name the file using the current timestamp.
        header('Content-Disposition: attachment; filename=Report_' . $_SERVER['REQUEST_TIME'] . '.csv');
                  
        $config    = CRM_Core_Config::singleton( );
          
        // Replace internal header names with friendly ones, where available.
        foreach ( $complexHeaders as $header ) {
            $headers[] = '"'. html_entity_decode(strip_tags($header)) . '"';
        }
        //Output the headers.
        echo implode(';', $headers) . "\n";

        $displayRows = array();
        $value       = null;
        foreach ( $complexRows as $row ) {
            //Output the data row.
            echo implode(';', $row) . "\n";
        }
    }
}