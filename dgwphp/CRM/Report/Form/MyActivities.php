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
 | Specifiek rapport Activiteiten voor De Goede Woning                |
 | Incident 20 02 12 001, Erik Hommel, 31 juli 2012                   |
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

class CRM_Report_Form_MyActivities extends CRM_Report_Form {

    function __construct( ) {
        if ( !isset( $session ) ) {
            $session = CRM_Core_Session::singleton( );
        }
        $this->_userID = $session->get( 'userID' );
        $this->_columns = array( );
        parent::__construct( );
    }

    function postProcess( ) {
        $this->beginPostProcess( );
        
        $activityIDs = array( );
        require_once 'CRM/Utils/Array.php';
        /*
         * ophalen alle activiteiten die toegewezen zijn aan huidige
         * gebruiker en in array activityIDs zetten
         */
        $qryAssigned =
"SELECT activity_id FROM civicrm_activity_assignment WHERE assignee_contact_id = {$this->_userID}";
	$daoAssigned = CRM_Core_DAO::executeQuery( $qryAssigned );
        while ( $daoAssigned->fetch() ) {
            $activityIDs[] = $daoAssigned->activity_id;
        }
        /*
         * ophalen alle activiteiten waarbij huidige gebruiker klant is
         * en in array activityIDs zetten als activiteit er nog niet in staat
         */
        $qryTarget =
"SELECT activity_id FROM civicrm_activity_target WHERE target_contact_id = {$this->_userID}";
        $daoTarget = CRM_Core_DAO::executeQuery( $qryTarget );
        while ( $daoTarget->fetch() ) {
            if ( !in_array( $daoTarget->activity_id, $activityIDs ) ) {
                $activityIDs[] = $daoTarget->activity_id;
            }
        }
        if ( !empty( $activityIDs ) ) {
            $activityIDs = implode( ',', $activityIDs );
            /*
             * eerst ophalen alle activiteiten voor contact waarin ingelogde user
             * als assignee of als target voorkomt. In functie buildRows
             * worden daar de namen van assignee(s) and target(s) aan toegevoegd
             */
            $actQry = 
"SELECT DISTINCT(a.id), activity_type_id, b.label AS type, subject, source_contact_id, 
d.sort_name AS source, activity_date_time, c.label AS status FROM civicrm_activity a 
LEFT JOIN civicrm_option_value b ON activity_type_id = b.value AND b.option_group_id = 2 
LEFT JOIN civicrm_option_value c ON status_id = c.value AND c.option_group_id = 25 
LEFT JOIN civicrm_contact d ON source_contact_id = d.id WHERE a.id IN ($activityIDs) 
AND status_id = 1 AND activity_type_id <> 3 ORDER BY activity_date_time";
        }
        $this->_columnHeaders = array(
            'type'              => array( 'title' => 'Type' ),
            'subject'           => array( 'title' => 'Onderwerp' ),
            'source'            => array( 'title' => 'Toegevoegd door' ),
            'activity_date_time'=> array( 'title' => 'Datum' ),
            'status'            => array( 'title' => 'Status' ),
            'target'            => array( 'title' => 'Met' ),
            'adres'             => array( 'title' => 'Adres' ),
            'phone'             => array( 'title' => 'Telefoon'),
            'assignee'          => array( 'title' => 'Toegewezen aan' ),
            );               
        $this->buildRows ( $actQry, $rows );
	$this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );
    }
    function alterDisplay( &$rows ) {
        require_once 'CRM/Utils/Date.php';
	require_once 'CRM/Utils/Array.php';

	$entryFound = false;
        foreach ( $rows as $rowNum => $row ) {
            /*
             * zorg voor klik naar contact en link bij source (toegevoegd door)
             */
            if ( array_key_exists( 'subject', $row ) ) {
                if ( !empty( $row['activity_id'] ) ) {
                    $section = $this->getVar( $section );
                    if ( $this->_section == 2 ) {
                        $url = CRM_Utils_System::url( "civicrm/contact/view/activity",  
                                'atype=' . $row['activity_type_id'] . '&action=view&reset=1
                                &id=' . $row['activity_id'] . '&cid=' . $row['target_contact_id'] . '&context=home',
                                $this->_absoluteUrl );
                    } else {
                        $url = CRM_Utils_System::url( "civicrm/contact/view/activity",  
                                'atype=' . $row['activity_type_id'] . '&action=view&reset=1
                                &id=' . $row['activity_id'] . '&cid=' . $row['target_contact_id'],
                                $this->_absoluteUrl );
                    }
                    $rows[$rowNum]['subject_link' ] = $url;
                    $rows[$rowNum]['subject_hover'] = "Details activiteit";
                    $entryFound = true;
                }
            }
            /*
             * zorg voor klik naar activiteit en link bij subject (onderwerp)
             */
            if ( array_key_exists( 'source', $row ) ) {
                if ( !empty( $row['source_contact_id'] ) ) {
                    $url = CRM_Utils_System::url( "civicrm/contact/view/activity",  
                            'reset=1&cid=' . $row['source_contact_id'], $this->_absoluteUrl );
                    $rows[$rowNum]['source_link' ] = $url;
                    $rows[$rowNum]['source_hover'] = "Contactdetails ".$row['source'];
                    $entryFound = true;
                }
            }
            /*
             * maak de hele rij rood als de datum van de activiteit later is dan
             * nu
             */
            if ( $row['activity_date_time'] < date("Y-m-d h:i:s") ) {
                $rows[$rowNum]['type'] = "<font color='red'>{$rows[$rowNum]['type']}</font>";
		$rows[$rowNum]['subject'] = "<font color='red'>{$rows[$rowNum]['subject']}</font>";
                $rows[$rowNum]['activity_date_time'] = "<font color='red'>{$rows[$rowNum]['activity_date_time']}</font>";
                $rows[$rowNum]['status'] = "<font color='red'>{$rows[$rowNum]['status']}</font>";
            }
            /*
             * omzetten formaat datum
             */
            if ( array_key_exists( 'activity_date_time', $row ) ) {
                $actDay = date('d', strtotime( $row['activity_date_time'] ) );
                $actMonth = (int) date('m', strtotime( $row['activity_date_time'] ) );
                $maanden = CRM_Utils_Date::getFullMonthNames();
                $actMonth = CRM_Utils_Array::value( $actMonth, $maanden );
                $actYear = date('Y', strtotime( $row['activity_date_time'] ) );
                $actTime = date('H:i:s', strtotime( $row['activity_date_time'] ) );
                $rows[$rowNum]['activity_date_time'] = $actDay." ".$actMonth." ".$actYear." ".$actTime;
            }
            if ( !$entryFound ) {
                break;
            }
        }
    }
    function buildRows( $sql, &$rows ) {
        if ( ! is_array($rows) ) {
            $rows = array( );
        }
        // use htis method to modify $this->_columnHeaders
        $this->modifyColumnHeaders( );
        if ( !empty( $sql ) ) {
            $dao  = CRM_Core_DAO::executeQuery( $sql );
            while ( $dao->fetch( ) ) {
                $row = array( );
                foreach ( $this->_columnHeaders as $key => $value ) {
                    if ( property_exists( $dao, $key ) ) {
                        $row[$key] = $dao->$key;
                    }
                    /*
                     * ophalen alle targets voor activiteit
                     */
                    require_once 'CRM/Activity/BAO/ActivityTarget.php';
                    $targets = CRM_Activity_BAO_ActivityTarget::getTargetNames( $dao->id );
                    $aantal = count( $targets );
                    /*
                     * samenstellen target om te tonen op venster, simpel as er maar 1 element is
                     */
                    if ( $aantal != 0 ) {
                        if ( $aantal == 1 ) {
                            $row['target_contact_id'] = key( $targets );
                            /*
                             * adres en telefoon ophalen en in rij zetten\
                             */
                            require_once 'api/v2/Dgwcontact.php';
                            $contactParams = array( 'contact_id' => $row['target_contact_id'] );
                            $contactData = civicrm_dgwcontact_get( $contactParams );
                            if ( isset( $contactData[0]['record_count'] ) ) {
                                if ( $contactData[0]['record_count'] == 1 ) {
                                    $contactAdres = array();
                                    if ( isset( $contactData[1]['street_address'] ) ) {
                                        $contactAdres[] = $contactData[1]['street_address'];
                                    }
                                    if ( isset( $contactData[1]['postcode'] ) ) {
                                        $contactAdres[] = $contactData[1]['postcode'];
                                    }
                                    if ( isset( $contactData[1]['city'] ) ) {
                                        $contactAdres[] = $contactData[1]['city'];
                                    }
                                }
                                if ( isset( $contactAdres ) && !empty( $contactAdres ) ) {
                                    $row['adres'] = implode( ", " , $contactAdres );
                                }
                                if ( isset( $contactData[1]['phone'] ) ) {
                                    $row['phone'] = $contactData[1]['phone'];
                                }
                            }
                            $sleutel = key( $targets );
                            $url = CRM_Utils_System::url( "civicrm/contact/view",  'reset=1
                                &cid=' . $row['target_contact_id'], $this->_absoluteUrl );
                            $row['target'] = "<a href='".$url."' title='Contactdetails {$targets[$sleutel]}'>{$targets[$sleutel]}</a>";
                        } else {
                            $row['adres'] = "---";
                            $row['phone'] = "---";
                            /*
                             * voor ieder geval een target om te tonen samenstellen
                             */
                            $displayTargetIDs = array( );
                            $displayTargetNames = array( );
                            foreach ( $targets as $sleutel => $waarde ) {
                                $displayTargetIDs[] = $sleutel;
                                $url = CRM_Utils_System::url( "civicrm/contact/view", 'reset=1
                                    &cid=' . $sleutel, $this->_absoluteUrl );
                                $displayTargetNames[] = "<a href='".$url."' title='Contactdetails $waarde'>$waarde</a>";
                            }
                            $row['target_contact_id'] = implode( "," , $displayTargetIDs );
                            $row['target'] = implode ( ", " , $displayTargetNames );
                        }
                    }
                    if ( isset( $dao->id ) ) {
                        $row['activity_id'] = $dao->id;
                    }
                    /*
                     * ophalen alle assignees voor activiteit
                     */
                    require_once 'CRM/Activity/BAO/ActivityAssignment.php';
                    $assignees = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames( $dao->id );
                    $aantal = count( $assignees );
                    if ( $aantal != 0 ) {
                        if ( $aantal == 1 ) {
                            $sleutel = key( $assignees );
                            $row['assignee_contact_id'] = key( $assignees );
                            $url = CRM_Utils_System::url( "civicrm/contact/view",  'reset=1
                                &cid=' . $row['assignee_contact_id'], $this->_absoluteUrl );
                            $row['assignee'] = "<a href='".$url."' title='Contactdetails {$assignees[$sleutel]}'>{$assignees[$sleutel]}</a>";
                        } else {
                            /*
                             * voor ieder geval een assignee om te tonen samenstellen
                             */
                            $displayAssigneeIDs = array( );
                            $displayAssigneeNames = array( );
                            foreach ( $assignees as $sleutel => $waarde ) {
                                $displayAssigneeIDs[] = $sleutel;
                                $url = CRM_Utils_System::url( "civicrm/contact/view", 'reset=1
                                    &cid=' . $sleutel, $this->_absoluteUrl );
                                $displayAssigneeNames[] = "<a href='".$url."' title='Contactdetails $waarde'>$waarde</a>";
                            }
                            $row['assignee_contact_id'] = implode( "," , $displayAssigneeIDs );
                            $row['assignee'] = implode ( ", " , $displayAssigneeNames );
                        }
                    }
                    if ( isset( $dao->source_contact_id ) ) {
                        $row['source_contact_id'] = $dao->source_contact_id;
                    }
                    if ( isset( $dao->activity_type_id ) ) {
                        $row['activity_type_id'] = $dao->activity_type_id;
                    }
                }
                $rows[] = $row;
            }
        }
    }
}