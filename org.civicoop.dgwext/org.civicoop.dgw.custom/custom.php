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
    /**
     * change all existing street_number_suffix to street_unit as the
     * street parsing rules have changed in the upgrade to 4.3.x
     */
    $changeAddressQry =
"UPDATE civicrm_address SET street_unit = street_number_suffix, street_number_suffix = NULL where street_number_suffix IS NOT NULL";
    CRM_Core_DAO::executeQuery( $changeAddressQry );

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
 * Implementation of hook_civicrm_tabs
 *
 * @author Jaap Jansma (jaap.jansma@civicoop.org)
 */
function custom_civicrm_tabs( &$tabs, $contactID ) {
    foreach($tabs as $key => $tab) {
        /**
         * BOS1308567 insite - volgorde huurovereenkomsten
         */
        if ($tab['id'] == 'custom_2') {
            $tabs[$key]['url'] = str_replace('/civicrm/contact/view/cd', '/civicrm/contact/view/cd_hov_tab', $tabs[$key]['url']);
            break;
        }
    }
}

/**
 * Implementation of hook_civicrm_validateForm
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 */
function custom_civicrm_validateForm( $formName, &$fields, &$files, &$form, &$errors ) {
    /**
     * Incident BOS1303715 
     * verplicht maken van toewijzen aan bij terugbel verzoek
     */
    if ( $formName == "CRM_Activity_Form_Activity" ) {
        $terugbel_verzoek_type_id = false;
        $gid = false;
        $apiParams = array(
            'version'   =>  3,
            'name'     =>  'activity_type'
        );
        $apiGroup = civicrm_api('OptionGroup', 'Getsingle', $apiParams);
        if (!isset($apiGroup['is_error']) || $apiGroup['is_error'] == 0) {
            if (isset($apiGroup['id'])) {
                $gid = $apiGroup['id'];
            }
        }
        if ($gid) {
            $apiParams = array(
                'version'   =>  3,
                'option_group_id'     =>  $gid,
                'label' => 'Terugbellen',
            );
            $apiValue = civicrm_api('OptionValue', 'Getsingle', $apiParams);
            if (!isset($apiValue['is_error']) || $apiValue['is_error'] == 0) {
                if (isset($apiValue['value'])) {
                    $terugbel_verzoek_type_id = $apiValue['value'];
                }
            }
        }

        if ($terugbel_verzoek_type_id && $form->_activityTypeId == $terugbel_verzoek_type_id) {
            $assigned_to = CRM_Utils_Array::value( 'assignee_contact_id', $fields );
            if (empty($assigned_to)) {
                $form->setElementError('assignee_contact_id' , ts('Deze activiteit moet toegewezen worden aan een medewerker'));
            }
        }
    }
    /**
     * validation address fields on Contact Edit form
     */
    if ( $formName == "CRM_Contact_Form_Contact" || $formName == "CRM_Contact_Form_Inline_Address" ) {
        foreach ( $fields['address'] as $addressKey => $address ) {
            /**
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
            /**
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
            /**
             * street number has to be numeric
             */
            if ( !empty( $address['street_number'] ) ) {
                if ( !ctype_digit( $address['street_number'] ) ) {
                   $errors['address[' . $addressKey . '][street_number]'] = 'Huisnummer mag alleen cijfers bevatten';
                }
            }
            /**
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
            /**
             * supplemental_address_2 can only be used if 1 and street_name is not empty
             */
            if ( !empty( $address['supplemental_address_2'] ) ) {
                if ( empty( $address['supplemental_address_1'] ) || empty( $address['street_name'] ) ) {
                   $errors['address[' . $addressKey . '][supplemental_address_2]'] = 'Adres toevoeging (2) kan alleen gevuld worden als adres toevoeging (1) en straatnaam ook gevuld zijn';
                }
            }
            /**
             * supplemental_address_1 can only be used if street_name is not empty
             */
            if ( !empty( $address['supplemental_address_1'] ) ) {
                if ( empty( $address['street_name'] ) ) {
                    $errors['address['. $addressKey . '][supplemental_address_1'] = 'Adres toevoeging (1) kan alleen gevuld worden als straatnaam ook gevuld is';
                }
            }
            /**
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
            /**
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
 * Object Adress, Email or Phone
 * - if contact is hoofdhuurder, remove complete set of objects from
 *   huishouden and medehuurder and copy latest set
 */
function custom_civicrm_post( $op, $objectName, $objectId, &$objectRef ) {
    /*
     * Synchronization First Noa
     */
    if ( $objectName == "Address" || $objectName == "Email" || $objectName == "Phone" ) {
        /**
         * remove and then copy new set to huishouden and medehuurder if
         * contact hoofdhuurder for create and edit
         */
        if ( $op == "create" || $op == "edit" ) {
            $contactId = 0;
            /**
             * check if objectRef is array or object
             */
            if ( is_object( $objectRef ) ) {
                if ( isset( $objectRef->contact_id ) ) {
                    $contactId = $objectRef->contact_id;
                }
            }
            if ( is_array( $objectRef ) ) {
                if ( isset( $objectRef['contact_id'] ) ) {
                    $contactId = $objectRef['contact_id'];
                }
            }
        }
        if ( $op == "delete" ) {
            if (isset($_GLOBALS['delcontactid'])) {
                $contactId = $_GLOBALS['delcontactid'];
                unset($GLOBALS['delcontactid']);
            } else {
                $contactId = 0;
            }
        }
        require_once 'CRM/Utils/DgwUtils.php';
        $contactHoofdHuurder = CRM_Utils_DgwUtils::checkContactHoofdhuurder( $contactId );
        if ( $contactHoofdHuurder ) {
            switch( $objectName ) {
                case "Address":
                    CRM_Utils_DgwUtils::processAddressesHoofdHuurder( $contactId );
                    break;
                case "Email":
                    CRM_Utils_DgwUtils::processEmailsHoofdHuurder( $contactId );
                    break;
                case "Phone":
                    CRM_Utils_DgwUtils::processPhonesHoofdHuurder( $contactId );
                    break;
            }
        }
    }
    /**
     * BOS1303566
     */
    if ( $objectName == "Individual" ) {
        $prefix_id = 0;
        if ( $objectRef->gender_id == 1 ) {
            $prefix_id = 1;
        }
        if ( $objectRef->gender_id == 2 ) {
            $prefix_id = 2;
        }
        require_once 'CRM/Utils/DgwUtils.php';
        $displayGreetings = CRM_Utils_DgwUtils::setDisplayGreetings( $objectRef->gender_id,
                $objectRef->middle_name, $objectRef->last_name );
        $greetings = "";
        if ( isset( $displayGreetings['is_error'] ) ) {
            if ( $displayGreetings['is_error'] == 0 ) {
               if ( isset( $displayGreetings['greetings'] ) ) {
                   $greetings = $displayGreetings['greetings'];
               }
            }
        }
        $updContact = "UPDATE civicrm_contact set prefix_id = $prefix_id, ";
        $updContact .= "email_greeting_display = '$greetings', addressee_display = '$greetings', ";
        $updContact .= "postal_greeting_display = '$greetings' WHERE id = $objectId";
        CRM_Core_DAO::executeQuery( $updContact );
    }
    /**
     * incident 01 10 12 002 - add username and date to details activity,
     * remove 'old' username and date if required to avoid doubles
     */
    if ( $objectName == 'Activity') {
        if ( $op == "create" || $op == "edit" ) {
            $activityTypeId = $objectRef->activity_type_id;
            if ( $activityTypeId == 32 ) {
                $details = $objectRef->details;
                $activityId = $objectRef->id;
                /**
                 * explode details in parts between <p> to decide
                 * if there is already a username and date and no other
                 * text. If that is the case, the 'old' username and
                 * date can be removed
                 */
                $arrayDetails = explode("<p>", $details );
                if ( !empty( $arrayDetails ) ) {
                    $aantalElementen = count( $arrayDetails );
                    $lastElement = end( $arrayDetails );
                    $lastParts = explode( "</p>", $lastElement );
                    $lastTekst = trim( $lastParts[0] );
                    $arrayAchter = explode( ":", $lastTekst );
                    $lastAchter = end( $arrayAchter);
                    if ( empty( $lastAchter ) ) {
                        $lastTekst = substr( $lastTekst, 0, -8 );
                        $dateNu = date('d-m-Y', strtotime( 'now' ) );
                        $session = CRM_Core_Session::singleton( );
                        $contactId  = $session->get( 'userID' );
                        require_once 'api/v2/Contact.php';
                        $apiParams = array(
                            'id'                    =>  $contactId,
                            'return.display_name'   =>  1
                        );
                        $contactApi = civicrm_contact_get( $apiParams );
                        $displayName = "";
                        if ( !civicrm_error( $contactApi ) ) {
                            if ( isset( $contactApi[$contactId]['display_name'] ) ) {
                                $displayName = $contactApi[$contactId]['display_name'];
                            }
                        }
                        if ( !empty( $displayName ) ) {
                            $tekstUserDate = $displayName.", ".$dateNu;
                        } else {
                            $tekstUserDate = $dateNu;
                        }
                        if ( trim( $lastTekst ) == $tekstUserDate ) {
                            array_pop( $arrayDetails );
                            $details = implode( "<p>", $arrayDetails );
                            $updAct =
    "UPDATE civicrm_activity SET details = '$details' WHERE id = $activityId";
                            CRM_Core_DAO::executeQuery( $updAct );
                        }
                    }
                }
            }
        }
    }
    /**
     * incident 20 06 12 004 depending on the CiviCRM settings, a mail is sent
     * to the assignee contact for an activity. That is fine.
     * What also happens is that an activity is automatically created for
     * the sending of this assignment email. De Goede Woning does not want that!
     * Assignment is only internal, so all these - copy sent to activities are
     * simply in the way. These will be removed here.
     */
    if ( $objectName == 'Activity' ) {
        $apiParams = array(
            'version'           =>  3,
            'option_group_id'   =>  2,
            'label'             =>  'E-mail'
        );
        $actType = civicrm_api( 'OptionValue', 'Getsingle', $apiParams );
        if ( !isset( $actType['is_error'] ) || $actType['is_error'] == 0 ) {
            if ( $objectRef->activity_type_id == $actType['value'] ) {
                if ( !empty( $objectRef->source_record_id ) ) {
                    $actPrevious = $objectId - 1;
                    if ( $objectRef->source_record_id === $actPrevious ) {
                        if ( isset( $objectRef->subject ) ) {
                            $subjectParts = explode( "- copy sent to", $objectRef->subject );
                            if ( isset( $subjectParts[1] ) ) {
                                $actUpdate = "UPDATE civicrm_activity SET is_deleted = 1 WHERE id = $objectId ";
                                CRM_Core_DAO::executeQuery( $actUpdate );
                            }
                        }
                    }
                }
            }
       }
    }
}
/**
 * Implementation of hook_civicrm_buildForm
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 */
function custom_civicrm_buildForm( $formName, &$form ) {
    /**
     * DGW incident 14 01 13 003
     */
    if ( $formName == "CRM_Contact_Form_GroupContact") {

        global $user;
        $userBeheerder = false;
        if ( in_array( "klantinformatie admin", $user->roles ) ) {
            $userBeheerder = true;
        }

        if ( !$userBeheerder ) {

            $elements = & $form->getVar('_elements');
            $element = & $elements[1];
            $opties = & $element->_options;
            /**
             * remove elements that are only available for administrator
             */
            if ( $waarden['text'] == "Complex 37 en 46B voor Elke" ) {
                unset( $opties[$optie]);
            }
            if ( $waarden['text'] == "SyncGebruikers" ) {
                unset( $opties[$optie]);
            }
            if ( $waarden['text'] == "FirstSync" ) {
                unset( $opties[$optie]);
            }
            /**
             * only show groups that user is authorised for
             */
            if ( !$session ) {
                $session =& CRM_Core_Session::singleton();
            }
            $userID  = $session->get( 'userID' );
            require_once 'CRM/Utils/DgwUtils.php';
            $checkUserParams = array(
                'user_id'       =>  $userID,
                'is_wijk'       =>  1,
                'is_dirbest'    =>  1
            );
            $checkUser = CRM_Utils_DgwUtils::getGroupsCurrentUser( $checkUserParams );
            if ( $checkUser['is_error'] == 0 ) {
                $userWijk = $checkUser['wijk'];
                $userDirBest = $checkUser['dirbest'];
            } else {
                $userWijk = false;
                $userDirBest = false;
            }
            if ( $waarden['text'] == "Consulenten Wijk en Ontwikkeling" ) {
                if ( !$userWijk ) {
                    unset( $opties[$optie]);
                }
            }
            if ( $waarden['text'] == "Dir/Best" ) {
                if ( !$userDirBest ) {
                    unset ( $opties[$optie] );
                }
            }
        }
    }
    /**
     * default 'track url' to off
     */
    if ( $formName == "CRM_Mailing_Form_Settings" ) {
        $defaults = array('url_tracking' => 0);
        $form->setDefaults( $defaults );
    }
    /**
     * DGW incident 06 10 11 005
     */
    if ( $formName == "CRM_Case_Form_CaseView" ) {
        global $user;
        $userBeheerder = false;
        if ( in_array( "klantinformatie admin", $user->roles ) ) {
            $userBeheerder = true;
        }

        if ( !$userBeheerder ) {
            /**
             * only show details if user in special group
             */
            if ( !isset( $session ) ) {
                $session =& CRM_Core_Session::singleton();
            }
            $userID  = $session->get( 'userID' );
            require_once 'CRM/Utils/DgwUtils.php';
            $checkUserParams = array(
                'user_id'       =>  $userID,
                'is_wijk'       =>  1
            );
            $checkUser = CRM_Utils_DgwUtils::getGroupsCurrentUser( $checkUserParams );
            if ( $checkUser['is_error'] == 0 ) {
                $userWijk = $checkUser['wijk'];
            } else {
                $userWijk = false;
            }
            $elements = & $form->getElement('activity_type_id');
            $options = & $elements->_options;
            foreach ($options as $sleutel=>$optie) {
                if ( $optie['attr']['value'] == 110) {
                    if ( $userWijk == false ) {
                        unset($options[$sleutel]);
                    }
                }
            }
        }
    }
    /**
     * DGW incident 14 01 13 003
     */
    if ( $formName == "CRM_Activity_Form_Activity" ) {
        global $user;
        $userBeheerder = false;
        if ( in_array( "klantinformatie admin", $user->roles ) ) {
          $userBeheerder = true;
        }
        if ( !$userBeheerder ) {
            /**
             * only show details if user in special group
             */
            if ( !isset( $session ) ) {
                $session =& CRM_Core_Session::singleton();
            }
            $userID  = $session->get( 'userID' );
            require_once 'CRM/Utils/DgwUtils.php';
            $checkUserParams = array(
                'user_id'       =>  $userID,
                'is_wijk'       =>  1,
                'is_dirbest'    =>  1,
                'is_admin'      =>  1
            );
            require_once 'CRM/Utils/DgwUtils.php';
            $checkUser = CRM_Utils_DgwUtils::getGroupsCurrentUser( $checkUserParams );
            if ( $checkUser['is_error'] == 0 ) {
                $userWijk = $checkUser['wijk'];
                $userDirBest = $checkUser['dirbest'];
                $userAdmin = $checkUser['admin'];
            } else {
                $userWijk = false;
                $userDirBest = false;
                $userAdmin = false;
            }
            $formElements = & $form->getVar('_elements');
            $action = $form->getVar('_action');
            foreach ( $formElements as $keyFormElement => & $formElement ) {
                if ( $formElement->_attributes['name'] == 'activity_type_id' ||
                        $formElement->_attributes['name'] == 'followup_activity_type_id') {
                    if ( isset( $formElement->_options ) ) {
                        $typeOptions = & $formElement->_options;
                        if ( !empty( $typeOptions ) ) {
                            foreach ( $typeOptions as $keyOption => $typeOption ) {
                                if ( isset( $typeOption['attr']['value'] ) ) {
                                    if ( $typeOption['attr']['value'] == 109 ) {
                                        if ( !$userWijk && !$userAdmin ) {
                                            unset( $typeOptions[$keyOption] );
                                        }
                                    }
                                    if ( $typeOption['attr']['value'] == 118 ) {
                                        if ( !$userDirBest && !$userAdmin ) {
                                            unset( $typeOptions[$keyOption] );
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        /**
         * BOS1303566
         */
        $action = $form->getVar('_action');
        /**
         * only for create or edit
         */
        if ( $action == 1 || $action == 2 ) {
            $type = $form->getVar('_activityTypeId');
            if ( $type == 32 ) {
                $dateNu = date('d-m-Y H:i', strtotime( 'now' ) );
                $currentUserId = $form->getVar('_currentUserId');
                require_once 'api/v2/Contact.php';
                $apiParams = array(
                    'id'                    =>  $currentUserId,
                    'return.display_name'   =>  1
                    );
                $contactApi = civicrm_contact_get( $apiParams );
                $displayName = "";
                if ( !civicrm_error( $contactApi ) ) {
                    if ( isset( $contactApi[$currentUserId]['display_name'] ) ) {
                        $displayName = $contactApi[$currentUserId]['display_name'];
                    }
                }
                if ( !empty( $displayName ) ) {
                    $tekstUserDate = $displayName.", ".$dateNu." :";
                } else {
                    $tekstUserDate = $dateNu." :";
                }
                $defaults = $form->getVar('_defaultValues');
                if ( isset( $defaults['details'] ) ) {
                    if ( empty( $defaults['details'] ) ) {
                        $details = $tekstUserDate;
                    } else {
                        $details = $defaults['details'].$tekstUserDate;
                    }
                } else {
                    $details = $tekstUserDate;
                }
                $defaults['details'] = $details;
                $form->setDefaults( $defaults );
            }
        }
    }
}
/**
 * Implementation of hook_civicrm_contactListQuery
 * @author Erik Hommel (erik.hommel@civicoop.org)
 *
 */
function custom_civicrm_contactListQuery( &$query, $name, $context, $id ) {
    if ($context == "activity_assignee") {
        /**
         * retrieve group_id for group Toewijzen activiteit
         */
        $assigneeGroupId = 0;
        require_once 'CRM/Utils/DgwUtils.php';
        $groupTitle = CRM_Utils_DgwUtils::getDgwConfigValue("groep toewijzen activiteit");
        $apiParams = array(
            'version'   =>  3,
            'title'     =>  $groupTitle
        );
        $apiGroup = civicrm_api('Group', 'Getsingle', $apiParams);
        if (!isset($apiGroup['is_error']) || $apiGroup['is_error'] == 0) {
            if (isset($apiGroup['id'])) {
                $assigneeGroupId = $apiGroup['id'];
            }
        }
        /**
         * retrieve all members of the group
         */
        $query =
"SELECT cc.sort_name AS name, cc.id
FROM civicrm_group_contact
JOIN civicrm_contact cc ON(contact_id = cc.id)
WHERE group_id = $assigneeGroupId AND cc.sort_name LIKE '%{$name}%'";
    }
}
