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
 | De Goede Woning - report AnnaZeepsop1 - 30 mei 2012                |
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
ini_set( 'display_startup_errors', '0' );
ini_set( 'display_errors', '1' );
ini_set( 'memory_limit', '256M' );

require_once 'CRM/Report/Form.php';

class CRM_Report_Form_DubbelBsn extends CRM_Report_Form {

    
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
SELECT * FROM dgw_bsn";

        $this->_columnHeaders = array(
			'contact_id_1'		=> array( 'title' => 'ID Contact 1' ),
            'first_name_1'		=> array( 'title' => 'Voorletters' ),
            'middle_name_1'		=> array( 'title' => 'Tussenvoegsel' ),
            'last_name_1'		=> array( 'title' => 'Achternaam' ),
            'bsn_1'				=> array( 'title' => 'BSN' ),
            'address_1'			=> array( 'title' => 'Adres' ),
            'post_1'			=> array( 'title' => 'Postcode' ),
            'city_1'			=> array( 'title' => 'Plaats' ),
            'phone_1'			=> array( 'title' => 'Telefoon' ),
            'email_1'			=> array( 'title' => 'Emailadres' ),
            'birth_date_1'		=> array( 'title' => 'Geb. dat.' ),
			'contact_id_2'		=> array( 'title' => 'ID Contact 2' ),
            'first_name_2'		=> array( 'title' => 'Voorletters' ),
            'middle_name_2'		=> array( 'title' => 'Tussenvoegsel' ),
            'last_name_2'		=> array( 'title' => 'Achternaam' ),
            'bsn_2'				=> array( 'title' => 'BSN' ),
            'address_2'			=> array( 'title' => 'Adres' ),
            'post_2'			=> array( 'title' => 'Postcode' ),
            'city_2'			=> array( 'title' => 'Plaats' ),
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
            if ( array_key_exists( 'contact_id_1', $row ) ) {
				$rows[$rowNum]['contact_id_1_link' ] = "http://insite/civicrm/contact/view?reset=1&cid={$row['contact_id_1']}";
                $rows[$rowNum]['contact_id_1_hover'] = ts("Gegevens contact 1");
                $entryFound = true;
            }
            if ( array_key_exists( 'contact_id_2', $row ) ) {
                $rows[$rowNum]['contact_id_2_link' ] = "http://insite/civicrm/contact/view?reset=1&cid={$row['contact_id_2']}";
                $rows[$rowNum]['contact_id_2_hover'] = ts("Gegevens contact 1");
                $entryFound = true;
            }
            if ( array_key_exists( 'birth_date_1', $row ) ) {
				$rows[$rowNum]['birth_date_1'] = date( "d-m-Y", strtotime( $row['birth_date_1'] ) );
			}  
            if ( array_key_exists( 'birth_date_2', $row ) ) {
				$rows[$rowNum]['birth_date_2'] = date( "d-m-Y", strtotime( $row['birth_date_2'] ) );
			}  
            
            if ( !$entryFound ) {
                break;
            }
        }
    }
}
