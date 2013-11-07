<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.2                                                |
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
 | Customization for De Goede Woning (www.degoedewoning.nl)           |
 | Date     :   16 February 2011                                      |
 | Author   :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl)          |
 | Marj     :   dgwZoek                                               |
 | Descr.   :   Customized search for contacts                        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class dgwZoek
   extends    CRM_Contact_Form_Search_Custom_Base
   implements CRM_Contact_Form_Search_Interface {

    function __construct( &$formValues ) {
        parent::__construct( $formValues );
        
        $this->_columns = array(
		ts('')		=>	'contact_type',
		ts('Naam')	=>	'sort_name',
		ts('Adres')	=>	'street_address',
		ts('Postcode')	=>	'postal_code',
		ts('Plaats')	=>	'city',
		ts('Telefoon')	=>	'phone');	
    }

    function buildForm( &$form ) {
	$contactTypes = array( '' => ts('- alle contact types -') ) + CRM_Contact_BAO_ContactType::getSelectElements( );
        $form->add(	'select', 
			'contact_type', 
			ts('Contact type'), 
			$contactTypes );

        $form->add( 'text',
                    'last_name',
                    ts( 'Achternaam' ),
                    true );

        $form->add( 'text',
                    'middle_name',
                    ts( 'Tussenvoegsel' ),
                    true );

        $form->add( 'text',
                    'initials',
                    ts( 'Voorletters' ),
                    true );

        $form->add( 'text',
                    'street_name',
                    ts( 'Straat' ),
                    true );
                    
        $form->add( 'text',
                    'street_number',
                    ts( 'Huisnr' ),
                    true );
                    
        $form->add( 'text',
                    'postal_code_from',
                    ts( 'Postcode van' ),
                    true );
                    
        $form->add( 'text',
                    'postal_code_to',
                    ts( 'Postcode tot' ),
                    true );
                    
        $form->add( 'text',
                    'city',
                    ts( 'Plaats' ),
                    true );
                    
        $form->add('text',
		'phone',
		ts( 'Telefoon' ),
		true );

        /**
         * You can define a custom title for the search form
         */
         $this->setTitle('Zoekvenster De Goede Woning');
         
         /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
         $form->assign( 'elements', array( 'contact_type', 'last_name', 
			'middle_name', 'initials', 'street_name', 'street_number', 
			'postal_code_from', 'postal_code_to', 'city', 'phone' ) );
    }

    function all( $offset = 0, $rowcount = 0, $sort = null,$includeContactIDs = false, $justIDs = false ) {
        $selectClause = "contact_a.id as contact_id,
			contact_a.contact_type as contact_type,
			contact_a.sort_name as sort_name,
			address.street_address as street_address,
			address.postal_code as postal_code,
			address.city as city, 		
			phone.phone as phone";
		$sort = "sort_name";				

		return $this->sql( $selectClause, $offset, $rowcount, $sort,
                           $includeContactIDs, null );

    }
    
    function from( ) {
        return "FROM civicrm_contact contact_a LEFT JOIN civicrm_address 
		address ON ( address.contact_id = contact_a.id) 
		LEFT JOIN civicrm_phone phone ON ( phone.contact_id = 
		contact_a.id)";
    }

    function where( $includeContactIDs = false ) {
        $params = array( );
        $count  = 1;
        $clause = array( );
		
        $types = CRM_Utils_Array::value( 'contact_type', 
		$this->_formValues );
        $last   = CRM_Utils_Array::value( 'last_name',
		$this->_formValues );
        $middle = CRM_Utils_Array::value( 'middle_name',
            $this->_formValues );
        $first = CRM_Utils_Array::value( 'initials',
		$this->_formValues );
	$street = CRM_Utils_Array::value( 'street_name',
		$this->_formValues );	
	$number = CRM_Utils_Array::value( 'street_number',
		$this->_formValues );
	$pcfrom = CRM_Utils_Array::value( 'postal_code_from',
		$this->_formValues );
	$pcto = CRM_Utils_Array::value( 'postal_code_to',
		$this->_formValues );
	$city = CRM_Utils_Array::value( 'city', $this->_formValues );
	$phone = CRM_Utils_Array::value( 'phone', $this->_formValues );	
		
	$type = null;
	$subtype = null;
	if ( $types != null ) {
		$typeparts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $types);
		if ($typeparts[0]) {
			$type = $typeparts[0];
		}
		if ($typeparts[1]) {
			$subtype = $typeparts[1];
		}
	}
                                          
	if ( $type != null ) {
		$params[$count] = array( $type, 'String');
		$clause[] = "(contact_a.contact_type = %{$count})";
		$count++;
	}

	if ( $subtype != null ) {
		$params[$count] = array( $subtype, 'String');
		$clause[] = "(contact_a.contact_sub_type = %{$count})";
		$count++;
	}
			
        if ( $last != null ) {
            if ( strpos( $last, '%' ) === false ) {
                $last = "%{$last}%";
            }
            $params[$count] = array( $last, 'String' );
            $clause[] = "(contact_a.sort_name LIKE %{$count})";
            $count++;
        }
        
        if ( $middle != null ) {
		if ( strpos( $middle, '%' ) === false ) {
			$middle = "%{$middle}%";
		}
		$params[$count] = array( $middle, 'String' );
		$clause[] = "(contact_a.middle_name LIKE %{$count})";
		$count++;
	}
		
	if ( $first != null ) {
		if ( strpos( $first, '%' ) === false ) {
			$first = "%{$first}%";
		}
		$params[$count] = array( $first, 'String' );
		$clause[] = "(contact_a.first_name LIKE %{$count})";
		$count++;
	}
			
	if ( $street != null ) {
		if ( strpos( $street, '%' ) === false ) {
			$first = "%{$first}%";
		}
		$params[$count] = array( $street, 'String' );
		$clause[] = "(address.street_name LIKE %{$count})";
		$count++;
	}
		
	if ( $number != null ) {
		/*
		 * if number is not numeric, error
		 */
		$params[$count] = array( $number, 'String' );
		$clause[] = "(address.street_number LIKE %{$count})";
		$count++;
	}
		
	if ( $pcfrom != null ) {
		if ($pcto != null ) {
		
			$params[$count] = array( $pcfrom, 'String' );
			$countfrom = $count;
			$count++;
			$params[$count] = array( $pcto, 'String' );
			$clause[] = "(address.postal_code BETWEEN %{$countfrom} and 
				%{$count})";
		} else {
			$params[$count] = array( $pcfrom, 'String' );
			$clause[] = "(address.postal_code >= %{$count})";
		}
		$count++;
	}
		
	if ( $city != null ) {
		$params[$count] = array( $city, 'String' );
		$clause[] = "(address.city LIKE %{$count})";
		$count++;
	}
		
	if ( $city != null ) {
		if ( strpos( $city, '%' ) === false ) {
			$city = "%{$city}%";
		}
		
		$params[$count] = array( $city, 'String' );
		$clause[] = "(address.city LIKE %{$count})";
		$count++;
	}
		
	if ( $phone != null ) {
		if ( strpos( $phone, '%' ) === false ) {
			$phone = "%{$phone}%";
		}
		
		$params[$count] = array( $phone, 'String' );
		$clause[] = "(phone.phone LIKE %{$count})";
		$count++;
	}
		
        if ( ! empty( $clause ) ) {
            $where = implode( ' AND ', $clause );
        }
        return $this->whereClause( $where, $params );
    }

    function setDefaultValues( ) {
        return array( );
    }

    function templateFile( ) {
		$file = "dgwZoek.tpl";
        return $file;
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Zoek'));
        }
    }
	 	 
    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
        $sql = $this->all( );
           
        $dao = CRM_Core_DAO::executeQuery( $sql );
        return $dao->N;
    }
    function sql( $selectClause,
                  $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false,
                  $groupBy = null ) {

        $sql = "SELECT $selectClause ".$this->from ( );
        $where = $this->where();
        if (!empty($where)) {
			$sql .= " WHERE is_deleted = 0 AND ".$where;
		}	

        if ( $includeContactIDs ) {
            $this->includeContactIDs( $sql,
                                      $this->_formValues );
        }

        if ( $groupBy ) {
            $sql .= " $groupBy ";
        }
        
        $this->addSortOffset( $sql, $offset, $rowcount, $sort );
        return $sql;
    }
}


