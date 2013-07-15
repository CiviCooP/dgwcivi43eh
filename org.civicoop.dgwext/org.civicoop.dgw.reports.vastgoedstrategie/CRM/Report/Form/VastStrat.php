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
 | De Goede Woning - report Vastgoedstrategie - 28 december 2011      |
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

require_once 'CRM/Report/Form.php';

class CRM_Report_Form_VastStrat extends CRM_Report_Form {

    
    function __construct( ) {
		$this->_columns = array( );
        parent::__construct( );    }
    
    function preProcess( ) {
        self::preProcessCommon( );
        if ( !$this->_id ) {
            self::addBreadCrumb();
        }

        foreach ( $this->_columns as $tableName => $table ) {
            // set alias
            if ( ! isset( $table['alias'] ) ) {
                $this->_columns[$tableName]['alias'] = substr( $tableName, 8 ) . '_civireport';
            } else {
                $this->_columns[$tableName]['alias'] = $table['alias'] . '_civireport';
            }

            $this->_aliases[$tableName] = $this->_columns[$tableName]['alias'];

            // higher preference to bao object
            if ( array_key_exists('bao', $table) ) {
                require_once str_replace( '_', DIRECTORY_SEPARATOR, $table['bao'] . '.php' );
                eval( "\$expFields = {$table['bao']}::exportableFields( );");
            } else {
                require_once str_replace( '_', DIRECTORY_SEPARATOR, $table['dao'] . '.php' );
                eval( "\$expFields = {$table['dao']}::export( );");
            }

            $doNotCopy   = array('required');
        }

        if ( $this->_force ) {
            $this->setDefaultValues( false );
        }
        
        if ( $this->_force ) {
            $this->_formValues = $this->_defaults;
            $this->postProcess( );
        }

    }
    
    function buildQuickForm( ) {
        $this->addColumns( );
        $this->buildInstanceAndButtons( );

        //add form rule for report
        if ( is_callable( array( $this, 'formRule' ) ) ) {
            $this->addFormRule( array( get_class($this), 'formRule' ), $this );
        }
    }
    
    static function formRule( $fields, $files, $self ) {  
        $errors = $grouping = array( );
        return $errors;
    }

    function postProcess( ) {

		$this->beginPostProcess( );

		$VastStratQry = "
SELECT * FROM vst_complex";

        $this->_columnHeaders = array(
			'complex' 			=> array( 'title' => 'Complex' ),
            'subcomplex'		=> array( 'title' => 'Subcomplex' ),
            'stadsdeel'			=> array( 'title' => 'Stadsdeel' ),
            'buurt'                     => array( 'title' => 'Buurt' ),
            'woningtype'		=> array( 'title' => 'Woningtype' ),
            'aantal'			=> array( 'title' => 'Aantal VGE' ),
            'awaarde'			=> array( 'title' => 'A. Volkshuisvestelijke waarde' ),
            'bwaarde'			=> array( 'title' => 'B. Woning waarde' ),
            'cwaarde'			=> array( 'title' => 'C. Energetische waarde' ), 
            'dwaarde'			=> array( 'title' => 'D. Financiële waarde' ),
            'ewaarde'			=> array( 'title' => 'E. Bouwtechnische waarde' ),
            'wenswaarde'		=> array( 'title' => 'WENS' ),
            'marktwaarde'		=> array( 'title' => 'MARKT' ),
            'kwawaarde'			=> array( 'title' => 'KWA' ),
            'strategie'			=> array( 'title' => 'Strategie 2013' ),
            'opmerkingen'		=> array( 'title' => 'Opmerkingen' )
                       );
        $this->buildRows ( $VastStratQry, $rows );
		$this->alterDisplay( $rows);
        $this->doTemplateAssignment( $rows );
        $this->endPostProcess( $rows );    
	}
    function alterDisplay( &$rows ) {

		$entryFound = false;
		
        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            // convert display name to links
            if ( array_key_exists('awaarde', $row)) {
                $rows[$rowNum]['awaarde_link' ] = "#";
                /*
                 * verklaring volkshuisvestelijke waarde in hover
                 * awaarde = locatie + doelgroep + verhuurbaar
                 */
                $rows[$rowNum]['awaarde_hover'] = "Locatie {$row['locatie']} punten, verhuurbaarheid {$row['verhuurbaar']} punten en doelgroep {$row['doelgroep']} punten.";
                $entryFound = true;
            }
            if ( array_key_exists('bwaarde', $row)) {
                $rows[$rowNum]['bwaarde_link' ] = "#";
                $rows[$rowNum]['bwaarde_hover'] = "Bepaald a.d.h.v. woningtype, oppervlakte, aantal kamers, evt. lift.";
                $entryFound = true;
            }
            if ( array_key_exists('cwaarde', $row)) {
                $rows[$rowNum]['cwaarde_link' ] = "#";
                $rows[$rowNum]['cwaarde_hover'] = "Totaal punten EPA labels complex gedeeld door aantal eenheden.";
                $entryFound = true;
            }
            if ( array_key_exists('dwaarde', $row)) {
                $rows[$rowNum]['dwaarde_link' ] = "#";
                $rows[$rowNum]['dwaarde_hover'] = "Wordt bepaald op basis van gegevens uit Wals.";
                $entryFound = true;
            }
            if ( array_key_exists('ewaarde', $row)) {
                $rows[$rowNum]['ewaarde_link' ] = "#";
                $rows[$rowNum]['ewaarde_hover'] = "Wordt bepaald door team Vastgoed.";
                $entryFound = true;
            }
            if ( array_key_exists('wenswaarde', $row)) {
                $rows[$rowNum]['wenswaarde_link' ] = "#";
                $rows[$rowNum]['wenswaarde_hover'] = "Volkshuisvestelijke ({$row['awaarde']}) + woning ({$row['bwaarde']}) + energetische ({$row['cwaarde']}) + financiële ({$row['dwaarde']}) + bouwtechnische waarde ({$row['ewaarde']}).";
                $entryFound = true;
            }
            if ( array_key_exists('marktwaarde', $row)) {
                $rows[$rowNum]['marktwaarde_link' ] = "#";
                $rows[$rowNum]['marktwaarde_hover'] = "Volkshuisvestelijke ({$row['awaarde']}) + woning ({$row['bwaarde']}) waarde.";
                $entryFound = true;
            }
            if ( array_key_exists('kwawaarde', $row)) {
                $rows[$rowNum]['kwawaarde_link' ] = "#";
                $rows[$rowNum]['kwawaarde_hover'] = "Energetische ({$row['cwaarde']}) + financiële ({$row['dwaarde']}) + bouwtechnische ({$row['ewaarde']}) waarde.";
                $entryFound = true;
            }
            
            if ( !$entryFound ) {
                break;
            }
        }
    }
    function preProcessCommon( ) {
        $this->_force = CRM_Utils_Request::retrieve( 'force',
                                                     'Boolean',
                                                     CRM_Core_DAO::$_nullObject );

        $this->_section = CRM_Utils_Request::retrieve( 'section', 'Integer', CRM_Core_DAO::$_nullObject );
        
        $this->assign( 'section', $this->_section );
                                                 
        $this->_id = $this->get( 'instanceId' );
        if ( !$this->_id ) {
            $this->_id  = CRM_Report_Utils_VastStrat::getInstanceID( );
	     if ( !$this->_id ) {
	         $this->_id  = CRM_Report_Utils_VastStrat::getInstanceIDForPath( );
	     }
        }

        // set qfkey so that pager picks it up and use it in the "Next > Last >>" links, 
        $_GET['qfKey'] = $this->controller->_key;

        if ( $this->_id ) {
            $this->assign( 'instanceId', $this->_id );
            $params = array( 'id' => $this->_id );
            $this->_instanceValues = array( );
            CRM_Core_DAO::commonRetrieve( 'CRM_Report_DAO_Instance',
                                          $params,
                                          $this->_instanceValues );
            if ( empty($this->_instanceValues) ) {
                CRM_Core_Error::fatal("Report could not be loaded.");
            }

            if ( !empty($this->_instanceValues['permission']) && 
                 (!(CRM_Core_Permission::check( $this->_instanceValues['permission'] ) ||
                    CRM_Core_Permission::check( 'administer Reports' ))) ) {
                CRM_Utils_System::permissionDenied( );
                CRM_Utils_System::civiExit( );
            }
            $this->_formValues = unserialize( $this->_instanceValues['form_values'] );

            // lets always do a force if reset is found in the url.
            if ( CRM_Utils_Array::value( 'reset', $_GET ) ) {
                $this->_force = 1;
            }

            // set the mode
            $this->assign( 'mode', 'instance' );
        } else {
            list($optionValueID, $optionValue) = CRM_Report_Utils_VastStrat::getValueIDFromUrl( );
            $instanceCount = CRM_Report_Utils_VastStrat::getInstanceCount( $optionValue );
            if ( ($instanceCount > 0) && $optionValueID ) {
                $this->assign( 'instanceUrl', 
                               CRM_Utils_System::url( 'civicrm/report/list', 
                                                      "reset=1&ovid=$optionValueID" ) );
            }
            if ( $optionValueID ) {
                $this->_description = 
                    CRM_Core_DAO::getFieldValue( 'CRM_Core_DAO_OptionValue', $optionValueID, 'description' );
            }
            
            // set the mode
            $this->assign( 'mode', 'template' );
        }

        // lets display the 
        $this->_instanceForm       = $this->_force || $this->_id || ( ! empty( $_POST ) );

        // do not display instance form if administer Reports permission is absent
        if ( ! CRM_Core_Permission::check( 'administer Reports' ) ) {
            $this->_instanceForm   = false;
        }
    
        $this->assign( 'criteriaForm', false );
        if ( CRM_Core_Permission::check( 'administer Reports' ) ||
             CRM_Core_Permission::check( 'access Report Criteria' ) ) {
            $this->assign( 'criteriaForm', true );
        }

        $this->_instanceButtonName = $this->getButtonName( 'submit', 'save'  );
        $this->_csvButtonName      = $this->getButtonName( 'submit', 'csv'   );
        $this->_vgeButtonName      = $this->getButtonName( 'submit', 'vge'   );
        

    }
     function processReportMode( ) {
        $buttonName = $this->controller->getButtonName( );

        $output     = CRM_Utils_Request::retrieve( 'output',
                                                   'String', CRM_Core_DAO::$_nullObject );
        $this->_sendmail = CRM_Utils_Request::retrieve( 'sendmail', 
                                                        'Boolean', CRM_Core_DAO::$_nullObject );
        $this->_absoluteUrl = false;
        $printOnly = false;
        $this->assign( 'printOnly', false );
		
		if ( $this->_vgeButtonName == $buttonName ) {
			$this->assign( 'printOnly', true );
			$printOnly = true;
			$this->assign( 'outputMode', 'vge' );
			$this->_outputMode = 'vge';
			$this->_absoluteUrl = true;
        } else if ( $this->_csvButtonName   == $buttonName || $output == 'csv' ) {
            $this->assign( 'printOnly', true );
            $printOnly = true;
            $this->assign( 'outputMode', 'csv' );
            $this->_outputMode  = 'csv';
            $this->_absoluteUrl = true;
        } else {
            $this->assign( 'outputMode', 'html' );
            $this->_outputMode = 'html';
        }

        // Get today's date to include in printed reports
        if ( $printOnly ) {
            require_once 'CRM/Utils/Date.php';
            $reportDate = CRM_Utils_Date::customFormat( date('Y-m-d H:i') );
            $this->assign( 'reportDate', $reportDate );
        }
    }
    function buildInstanceAndButtons( ) {
        require_once 'CRM/Report/Form/Instance.php';
        CRM_Report_Form_Instance::buildForm( $this );
        
        $label = $this->_id ? ts( 'Update Report' ) : ts( 'Create Report' );
        
        $this->addElement( 'submit', $this->_instanceButtonName, $label );
        if ( $this->_instanceForm ){
            $this->assign( 'instanceForm', true );
        }

        $label = $this->_id ? ts( 'Export to CSV' ) : ts( 'Preview CSV' );

        if ( $this->_csvSupported ) {
			$this->addElement('submit', $this->_vgeButtonName, 'Exporteer eenheden naar CSV');
            $this->addElement('submit', $this->_csvButtonName, $label );
        }

        $this->addChartOptions( );
        $this->addButtons( array(
                                 array ( 'type'      => 'submit',
                                         'name'      => ts('Preview Report'),
                                         'isDefault' => true   ),
                                 )
                           );
                           
    }
    function buildRows( $sql, &$rows ) {
        $dao  = CRM_Core_DAO::executeQuery( $sql );
		
        if ( ! is_array($rows) ) {
            $rows = array( );
        }

        // use this method to modify $this->_columnHeaders
        $this->modifyColumnHeaders( );

        while ( $dao->fetch( ) ) {
            $row = array( );
            foreach ( $this->_columnHeaders as $key => $value ) {
                if ( property_exists( $dao, $key ) ) {
                    $row[$key] = $dao->$key;
                }
                if ( isset( $dao->locatie ) ) {
					$row['locatie'] = $dao->locatie;
				}
                if ( isset( $dao->verhuurbaar ) ) {
					$row['verhuurbaar'] = $dao->verhuurbaar;
				}
                if ( isset( $dao->doelgroep ) ) {
					$row['doelgroep'] = $dao->doelgroep;
				}
            }
            $rows[] = $row;
        }

    }
    function endPostProcess( &$rows = null ) {
		
        if ( $this->_outputMode == 'print' || 
             $this->_outputMode == 'pdf'   ||
             $this->_sendmail              ) {
            $templateFile = parent::getTemplateFileName( );
            
            $content = $this->_formValues['report_header'] .
                CRM_Core_Form::$_template->fetch( $templateFile ) .      
                $this->_formValues['report_footer'] ;

            if ( $this->_sendmail ) {
                if ( CRM_Report_Utils_VastStrat::mailReport( $content, $this->_id,
                                                          $this->_outputMode  ) ) {
                    CRM_Core_Session::setStatus( ts("Report mail has been sent.") );
                } else {
                    CRM_Core_Session::setStatus( ts("Report mail could not be sent.") );
                }
                if ( $this->get( 'instanceId' ) ) {
                    CRM_Utils_System::civiExit( );
                } 

                CRM_Utils_System::redirect( CRM_Utils_System::url( CRM_Utils_System::currentPath(), 
                                                                   'reset=1' ) );
         
            } 
            CRM_Utils_System::civiExit( );
        } else if ( $this->_outputMode == 'csv' ) {
            CRM_Report_Utils_VastStrat::export2csv( $this, $rows );
        /*
         * uitbreiding voor specifiek rapport: eenheden (vge) naar csv
         * in functie worden $rows opnieuw opgebouwd en $this 
         * doorgegeven
         */
        } else if ( $this->_outputMode == 'vge' ) {
			require_once('CRM/Utils/VastStrat.php');
			CRM_Utils_VastStrat::exportVgeCsv( $this );
            CRM_Utils_System::civiExit( );
		/*
		 * end uitbreiding specifiek rapport
		 */	
			     
        } else if ( $this->_outputMode == 'group' ) {
            $group = $this->_params['groups'];
            CRM_Report_Utils_VastStrat::add2group( $this, $group );
        } else if ( $this->_instanceButtonName == $this->controller->getButtonName( ) ) {
            require_once 'CRM/Report/Form/Instance.php';
            CRM_Report_Form_Instance::postProcess( $this );
        }      
    }

    /*function getTemplateFileName(){
    	return 'CRM/Report/VastStrat.tpl';
    }*/
    
    function getTemplateFileName(){
    	$defaultTpl = parent::getTemplateFileName();
    	$template   = CRM_Core_Smarty::singleton();
    	if (!$template->template_exists($defaultTpl)) {
    		$defaultTpl = 'CRM/Report/VastStrat.tpl';
    	}
    	return $defaultTpl;
    }
   
}
