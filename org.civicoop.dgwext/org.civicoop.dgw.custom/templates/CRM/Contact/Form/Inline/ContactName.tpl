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
 | Customization EE-atWork                                            |
 | Author       :   Erik Hommel (hommel@ee-atwork.nl)                 |
 | Project      :   Implementatie CiviCRM                             |
 | Customer     :   De Goede Woning Apeldoorn                         |
 |                                                                    |
 | Date         :   12 Jan 2011 (V3.3.0)                              |
 | Marker       :   CoreCorp5                                         |
 | Description  :   Remove suffix                                     |
 | Date		:   27 Dec 2011                                       |
 | Marker       :   DGW22                                             |
 | Description  :   Wijzigen voornaam in voorletters                  |
 | Author       :   Erik Hommel (erik.hommel@civicoop.org)            |
 | Date         :   2 April 2013                                      |
 | Marker       :   BOS1303566                                        |
 | Description  :   Prefix based on gender, no longer available in    |
 |                  edit or create                                    |
 +--------------------------------------------------------------------+
*}

{* CoreCorp5 *}
{assign var="showSuffix" value="1"}
{if $contactType eq 'Individual'}
	{assign var="showSuffix" value="0"}
{/if}
{* end CoreCorp5 *}

{* This file builds html for Contact Display Name inline edit *}
{$form.oplock_ts.html}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl"}
  </div>
  {if $contactType eq 'Individual'}
    {* BOS1303566 *}
        
        {* if $form.prefix_id *}       
        {if $form.prefix_id and $action ne 1}
	    <div class="crm-inline-edit-field">
                {*$form.prefix_id.label}<br/>*}
                {*$form.prefix_id.html*}
                {* retrieve prefix with API *}
                {crmAPI var="contactData" entity="contact" action="get" contact_id=$contactId}
                {foreach from=$contactData.values item=contactField}
                    {if $contactField.individual_prefix != ''}
                        <input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value={$contactField.individual_prefix} >
                    {else}
                        {if $contactField.gender_id == 1}
                            <input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='mevrouw'>
                        {elseif $contactField.gender_id == 2}
                            <input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='heer'>
                        {elseif $contactField.gender_id == 3}
                            <input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='mevrouw/heer'>
                        {else}    
                            <input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value=''>
                        {/if}    
                    {/if}
                {/foreach}
            </div>    
        {/if}
        {* end BOS1303566 *}
    <div class="crm-inline-edit-field">
      {* DGW22 wijzigen voornaam in voorletters *}
	  Voorletters (zonder puntjes)<br />
      {*{$form.first_name.label}<br />*}
      {* end DGW22 *} 
      {$form.first_name.html}
    </div>
    <div class="crm-inline-edit-field">
      {$form.middle_name.label}<br />
      {$form.middle_name.html}
    </div>
    <div class="crm-inline-edit-field">
      {$form.last_name.label}<br />
      {$form.last_name.html}
    </div>
    {* CoreCorp5 *}
    {if $showSuffix}
    {if $form.suffix_id}
      <div class="crm-inline-edit-field">
        {$form.suffix_id.label}<br/>
        {$form.suffix_id.html}
      </div>
    {/if}
    {/if}
    {* end CoreCorp5 *}
  {elseif $contactType eq 'Organization'}
    <div class="crm-inline-edit-field">{$form.organization_name.label}&nbsp;
    {$form.organization_name.html}</div>
  {elseif $contactType eq 'Household'}
    <div class="crm-inline-edit-field">{$form.household_name.label}&nbsp;
    {$form.household_name.html}</div>
  {/if}
</div>
<div class="clear"></div>
