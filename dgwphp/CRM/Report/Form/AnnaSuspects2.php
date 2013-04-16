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
 | De Goede Woning - report AnnaSuspects2 - 27 juni 2012              |
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

class CRM_Report_Form_AnnaSuspects2 extends CRM_Report_Form {

    
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

		$bsnQry = "
SELECT * FROM dgw_suspects2 ORDER BY last_name_1, middle_name_1, first_name_1";

        $this->_columnHeaders = array(
            'display_name_1'	=> array( 'title' => 'Naam 1e contact' ),
			'contact_id_1'		=> array( 'title' => 'ID' ),
            'bsn_1'				=> array( 'title' => 'BSN' ),
            'display_address_1'	=> array( 'title' => 'Adres' ),
            'phone_1'			=> array( 'title' => 'Telefoon' ),
            'email_1'			=> array( 'title' => 'Emailadres' ),
            'birth_date_1'		=> array( 'title' => 'Geb. dat.' ),
            'display_name_2'	=> array( 'title' => 'Naam 2e contact'),
			'contact_id_2'		=> array( 'title' => 'ID' ),
            'bsn_2'				=> array( 'title' => 'BSN' ),
            'display_address_2'	=> array( 'title' => 'Adres' ),
            'phone_2'			=> array( 'title' => 'Telefoon' ),
            'email_2'			=> array( 'title' => 'Emailadres' ),
            'birth_date_2'		=> array( 'title' => 'Geb. dat.' )
            );
                       
        $this->buildRows ( $bsnQry, $rows );
		$this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );    
	}
    function alterDisplay( &$rows ) {

		$entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            // convert name to links
            if ( array_key_exists( 'display_name_1', $row ) ) {
				$rows[$rowNum]['display_name_1_link' ] = "http://insite/civicrm/contact/view?reset=1&cid={$row['contact_id_1']}";
                $rows[$rowNum]['display_name_1_hover'] = ts("Gegevens contact 1");
                $entryFound = true;
            }
            if ( array_key_exists( 'display_name_2', $row ) ) {
				$rows[$rowNum]['display_name_2_link' ] = "http://insite/civicrm/contact/view?reset=1&cid={$row['contact_id_2']}";
                $rows[$rowNum]['display_name_2_hover'] = ts("Gegevens contact 1");
                $entryFound = true;
            }
            if ( array_key_exists( 'birth_date_1', $row ) ) {
				if ( $row['birth_date_1'] == "1970-01-01" ) {
					$rows[$rowNum]['birth_date_1'] = "";
				} else {
					$rows[$rowNum]['birth_date_1'] = date( "d-m-Y", strtotime( $row['birth_date_1'] ) );
				}
			}  
            if ( array_key_exists( 'birth_date_2', $row ) ) {
				if ( $row['birth_date_2'] == "1970-01-01" ) {
					$rows[$rowNum]['birth_date_2'] = "";
				} else {
					$rows[$rowNum]['birth_date_2'] = date( "d-m-Y", strtotime( $row['birth_date_2'] ) );
				}
			}  
            
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
