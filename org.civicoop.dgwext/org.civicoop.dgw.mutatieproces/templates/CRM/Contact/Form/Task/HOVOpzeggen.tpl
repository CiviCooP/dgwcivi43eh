{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
*}
{* Confirmation of contact deletes  *}
<div class="crm-block crm-form-block crm-contact-task-hovopzeggen-form-block">
<div class="messages status no-popup">
  <div class="icon inform-icon"></div>&nbsp;
	{ts}Weet u zeker dat u de huurovereenkomst wil opzeggen voor het contact?{/ts}
  </div>

  <div class="form-item">
        <span class="label">
			{$form.hov_id.label}
		</span>
		<span class="value">
			{$form.hov_id.html}
		</span>
  </div>
  
  <div class="form-item">
        <span class="label">{$form.verwachte_einddatum.label}</span>
        <span class="value">{include file="CRM/common/jcalendar.tpl" elementName=verwachte_einddatum}</span>
  </div>
  
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl"}</div>
</div>
