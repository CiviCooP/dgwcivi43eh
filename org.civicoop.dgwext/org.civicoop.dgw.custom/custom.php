<?php
require_once 'custom.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function custom_civicrm_config(&$config) {
  _custom_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function custom_civicrm_xmlMenu(&$files) {
  _custom_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function custom_civicrm_install() {
  return _custom_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function custom_civicrm_uninstall() {
  return _custom_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function custom_civicrm_enable() {
  return _custom_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function custom_civicrm_disable() {
  return _custom_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function custom_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _custom_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function custom_civicrm_managed(&$entities) {
  return _custom_civix_civicrm_managed($entities);
}
/**
 * Implementation of hook_civicrm_validateForm
 * 
 * @author Erik Hommel (erik.hommel@civicoop.org)
 */
function custom_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
    /*
     * validation address fields on Contact Edit form
     */
    if ( $formName == "CRM_Contact_Form_Contact" || $formName == "CRM_Contact_Form_Inline_Address" ) {
        foreach ( $fields['address'] as $addressKey => $address ) {
            /*
             * if street_address entered and street_name empty, split address before validation
             */
            if ( !empty( $address['street_address'] ) && empty( $address['street_name'] ) ) {
                require_once 'CRM/Utils/DgwUtils.php';
                $splitAddress = CRM_Utils_DgwUtils::splitStreetAddressNl( $address['street_address'] );
                if ( $splitAddress['is_error'] == 0 ) {
                    $address['street_name'] = $splitAddress['street_name'];
                    $address['street_number'] = $splitAddress['street_number'];
                    $address['street_unit'] = $splitAddress['street_unit'];
                }
            }
            /*
             * if streetname is entered, street number can not be empty and vice versa
             */
            if ( !empty( $address['street_name'] ) ) {
                if ( empty( $address['street_number'] ) ) {
                   $errors['address[' . $addressKey . '][street_number]'] = 'Huisnummer mag niet leeg zijn als straat gevuld is';
                }
            }
            if ( !empty( $address['street_number'] ) ) {
                if ( empty( $address['street_name'] ) ) {
                   $errors['address[' . $addressKey . '][street_name]'] = 'Straat mag niet leeg zijn als huisnummer gevuld is';
                }
            }
            /*
             * street number has to be numeric
             */
            if ( !empty( $address['street_number'] ) ) {
                if ( !ctype_digit( $address['street_number'] ) ) {
                   $errors['address[' . $addressKey . '][street_number]'] = 'Huisnummer mag alleen cijfers bevatten';                    
                }
            }
            /*
             * if city is entered, postal code can not be empty and vice versa
             */
            if ( !empty( $address['city'] ) ) {
                if ( empty( $address['postal_code'] ) ) {
                   $errors['address[' . $addressKey . '][postal_code]'] = 'Postcode mag niet leeg zijn als plaats gevuld is';
                }
            }
            if ( !empty( $address['postal_code'] ) ) {
                if ( empty( $address['city'] ) ) {
                   $errors['address[' . $addressKey . '][city]'] = 'Plaats mag niet leeg zijn als postcode gevuld is';
                }
            }
            /*
             * supplemental_address_2 can only be used if 1 and street_name is not empty
             */
            if ( !empty( $address['supplemental_address_2'] ) ) {
                if ( empty( $address['supplemental_address_1'] ) || empty( $address['street_name'] ) ) {
                   $errors['address[' . $addressKey . '][supplemental_address_2]'] = 'Adres toevoeging (2) kan alleen gevuld worden als adres toevoeging (1) en straatnaam ook gevuld zijn';
                }            
            }
            /*
             * supplemental_address_1 can only be used if street_name is not empty
             */
            if ( !empty( $address['supplemental_address_1'] ) ) {
                if ( empty( $address['street_name'] ) ) {
                    $errors['address['. $addressKey . '][supplemental_address_1'] = 'Adres toevoeging (1) kan alleen gevuld worden als straatnaam ook gevuld is';
                }
            }
            /*
             * postal_code and/or city can only be used if street_name or street_address is not empty
             */
            if ( !empty( $address['postal_code'] ) ) {
                if ( empty( $address['street_name'] ) ) {
                    $errors['address['. $addressKey . '][postal_code]'] = 'Postcode kan alleen gevuld worden als straatnaam ook gevuld is';
                    
                }
            }
            if ( !empty( $address['city'] ) ) {
                if ( empty( $address['street_name'] ) ) {
                    $errors['address['. $addressKey . '][city]'] = 'Plaats kan alleen gevuld worden als straatnaam ook gevuld is';
                    
                }
            }
            /*
             * pattern postal code has to be correct (is required in First Noa)
             */
            if ( !empty( $address['postal_code'] ) && !empty( $address['city'] ) ) {
                if ( $address['country_id'] == 1152  || empty( $address['country_id'] ) ) {
                    if ( strlen( $address['postal_code'] ) != 7 ) {
                        $errors['address['. $addressKey . '][postal_code]'] = 'Postcode moet formaat "1234 AA" hebben (incl. spatie). Het is nu te lang of te kort';
                        
                    }
                    $digitPart = substr( $address['postal_code'], 0, 4);
                    $stringPart = substr( $address['postal_code'], -2 );
                    if ( !ctype_digit ( $digitPart ) ) {
                        $errors['address['. $addressKey . '][postal_code]'] = 'Postcode moet formaat "1234 AA" hebben (incl. spatie). Eerste 4 tekens zijn nu niet alleen cijfers';
                    }
                    if ( !ctype_alpha( $stringPart ) ) {
                        $errors['address['. $addressKey . '][postal_code]'] = 'Postcode moet formaat "1234 AA" hebben (incl. spatie). Laatste 2 tekens zijn nu niet alleen letters';                   
                    }
                    if ( substr( $address['postal_code'] , 4, 1 ) != " " ) {
                        $errors['address['. $addressKey . '][postal_code]'] = 'Postcode moet formaat "1234 AA" hebben (incl. spatie). Er staat nu geen spatie tussen cijfers en letters';
                    }
                }
            }
        }
    }
    return;
}
/**
 * Implementation of hook_civicrm_post
 * 
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * 
 * - change sequence of address fields for street parsing in Dutch format
 *   
 */
function custom_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
}
/**
 * Implementation of hook_civicrm_pre
 * 
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 * Object Address: 
 * - change sequence of address fields for street parsing in Dutch format
 *   
 */
function custom_civicrm_pre( $op, $objectName, $objectId, &$objectRef ) {
    
    if ( $objectName == "Address" ) {
        if ( isset( $objectRef['street_address'] ) ) {
            if ( !empty( $objectRef['street_address'] ) ) {
                require_once 'CRM/Utils/DgwUtils.php';
                $splitAddress = CRM_Utils_DgwUtils::splitStreetAddressNl( $objectRef['street_address'] );
                if ( $splitAddress['is_error'] == 0 ) {
                    $objectRef['street_name'] = $splitAddress['street_name'];
                    $objectRef['street_number'] = $splitAddress['street_number'];
                    $objectRef['street_unit'] = $splitAddress['street_unit'];
                } 
            }
        }
        
        $parsedStreetAddress = "";
        if ( isset( $objectRef['street_name'] ) && !empty( $objectRef['street_name'] ) ) {
            $parsedStreetAddress = $objectRef['street_name'];
        }
        if ( isset( $objectRef['street_number'] ) && !empty( $objectRef['street_number'] ) ) {
            $parsedStreetAddress .= " ".$objectRef['street_number'];
        }
        if ( isset( $objectRef['street_unit'] ) && !empty( $objectRef['street_unit'] ) ) {
            $parsedStreetAddress .= " ".$objectRef['street_unit'];
        }
        $objectRef['street_address'] = $parsedStreetAddress;
    }
    return;   
}
/**
 * Implementation of hook_civicrm_buildForm
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 * Contact_Form_Contact: 
 * - change sequence of address fields to show Dutch format
 *   
 */
function custom_civicrm_buildForm( $formName, &$form ) {
    if ( $formName == "CRM_Contact_Form_Contact" ) {
        $values = $form->getVar('_values' );
        if ( isset( $values['address'] ) ) {
            foreach ( $values['address'] as $addressKey => $address ) {
                if ( isset( $values['address'][$addressKey]['street_name'] ) ) {
                    $parseParams['street_name'] = $values['address'][$addressKey]['street_name'];
                }
                if ( isset( $values['address'][$addressKey]['street_number'] ) ) {
                    $parseParams['street_number'] = $values['address'][$addressKey]['street_number'];
                }
                if ( isset( $values['address'][$addressKey]['street_unit'] ) ) {
                    $parseParams['street_unit'] = $values['address'][$addressKey]['street_unit'];
                }
                require_once 'CRM/Utils/DgwUtils.php';
                $parseResult = CRM_Utils_DgwUtils::glueStreetAddressNl( $parseParams );
                if ( $parseResult['is_error'] == 0 ) {
                    if ( isset( $parseResult['parsed_street_address'] ) ) {
                        $defaults['address'][$addressKey]['street_address'] = $parseResult['parsed_street_address'];
                        $form->setDefaults( $defaults );
                    }
                }
            }
        }
    }
}
