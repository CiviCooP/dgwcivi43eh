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
 | De Goede Woning - report Synchronisatiefouten - 28 juli 2011       |
 | (Erik Hommel)                                                      |
 |                                                                    |
 | Toont alle personen waarbij synchronisatiefouten voorkomen         |
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

class CRM_Report_Form_SyncFouten extends CRM_Report_Form {

    
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
		
		$syncTableTitle = CRM_Utils_DgwUtils::getDgwConfigValue( 'sync first error tabel' );
		$syncTable = CRM_Utils_DgwUtils::getCustomGroupTableName( $syncTableTitle );
		
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error datum veld', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errDateFldName = $retrieveSyncField['sync_field'];
		}
		
		
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error action veld', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errActFldName = $retrieveSyncField['sync_field'];
		}
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error entity veld', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errEntFldName = $retrieveSyncField['sync_field'];
		}
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error entity_id veld', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errEntIdFldName = $retrieveSyncField['sync_field'];
		}
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error key_first veld', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errKeyFldName = $retrieveSyncField['sync_field'];
		}
		$retrieveSyncField = CRM_Utils_SyncUtils::retrieveSyncField('sync first error foutboodschap', $syncTableTitle);
		if ( $retrieveSyncField['is_error'] == 0 ) {
			$errMsgFldName = $retrieveSyncField['sync_field'];
		}

		$SyncQry = "
SELECT b.display_name, b.contact_type, a.* FROM ".$syncTable." a 
LEFT JOIN civicrm_contact b ON a.entity_id = b.id
ORDER BY ".$errDateFldName;

        $this->_columnHeaders = array(
			'display_name' 		=> array( 'title' => 'Naam contact' ),
            'contact_type'  	=> array( 'title' => 'Type' ),
            'entity_id' 		=> array( 'title' => 'ContactID' ),
            $errActFldName			=> array( 'title' => 'Actie' ),
            $errEntFldName			=> array( 'title' => 'Entiteit' ),
            $errEntIdFldName			=> array( 'title' => 'EntiteitID' ),
            $errKeyFldName			=> array( 'title' => 'FirstID'),
            $errDateFldName			=> array( 'title' => 'Datum fout'),
            $errMsgFldName			=> array( 'title' => 'Foutboodschap uit First' )	
                       );

        $this->buildRows ( $SyncQry, $rows );
		$this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );    
	}
    function alterDisplay( &$rows ) {

		$entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            // convert display name to links
            if ( array_key_exists('display_name', $row) && 
                 array_key_exists('entity_id', $row) ) {
                $url = CRM_Utils_System::url( "civicrm/contact/view",  
                                              'reset=1&cid=' . $row['entity_id'],
                                              $this->_absoluteUrl );
                $rows[$rowNum]['display_name_link' ] = $url;
                $rows[$rowNum]['display_name_hover'] = ts("View Contact details for this contact.");
                $entryFound = true;
            }

            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }

}
