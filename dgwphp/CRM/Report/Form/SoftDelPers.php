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
 | De Goede Woning - report Soft Delete Personen - 7 november 2012    |
 | (Erik Hommel)                                                      |
 |                                                                    |
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

class CRM_Report_Form_SoftDelPers extends CRM_Report_Form {
       
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
        $qrySoftDelPers = 
"SELECT persNr, naam, type, hov, relatie, act, dossier, groep, civi_id FROM dgw_del_pers";

        $this->_columnHeaders = array(
            'persNr'    => array( 'title' => 'Pers. nr. First'),
            'naam'	=> array( 'title' => 'Naam in First' ),
            'type'      => array( 'title' => 'Type in CiviCRM' ),
            'hov'	=> array( 'title' => 'Huurovereenkomsten?' ),
            'relatie'   => array( 'title' => 'Relaties?' ),
            'act'       => array( 'title' => 'Activiteiten?'),
            'dossier'   => array( 'title' => 'Dossiers?'),
            'groep'     => array( 'title' => 'Lid van groepen?'),
            'civi_id'   => array( 'title' => 'CiviCRM ID')
                       );
        $this->buildRows ( $qrySoftDelPers, $rows );
	$this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );    
    }
    function alterDisplay( &$rows ) {
        $entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            // convert name to links
            if ( array_key_exists('naam', $row) && 
                 array_key_exists('civi_id', $row) ) {
                if ( !empty( $row['civi_id'] ) ) {
                    $url = CRM_Utils_System::url( "civicrm/contact/view",  
                            'reset=1&cid=' . $row['civi_id'], $this->_absoluteUrl );
                    $rows[$rowNum]['naam_link' ] = $url;
                    $rows[$rowNum]['naam_hover'] = "Bekijk contact in CiviCRM";
                    $entryFound = true;
                }
            }
            if ( array_key_exists('persNr', $row) ) {
                $url = "http://fhpxyp1.a004.woonsuite.nl:7777/portal/page/portal/NCCW/EFP_WOOND_PER?p_par_refno={$row['persNr']}";
                $rows[$rowNum]['persNr_link' ] = $url;
                $rows[$rowNum]['persNr_hover'] = "Bekijk persoon in First";
                $entryFound = true;
            }
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
