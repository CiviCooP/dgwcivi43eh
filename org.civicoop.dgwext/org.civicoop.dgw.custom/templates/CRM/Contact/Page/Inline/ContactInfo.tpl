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
 | Date         :   12 Jan 2011 (V3.3.0)                              |
 | Marker       :   CoreCorp8                                         |
 | Description  :   Add SIC code (KvK nummer) for organizations       |
 | Marker       :   DGW3                                              |
 | Description  :   Disable edit or delete for household if huur- or  |
 |                  koopovereenkomst present                          |
 | Date         :   31 Jan 2011                                       | 
 | Marker       :   DGW3                                              |
 | Description  :   Disable edit and delete for household if          |
 |                  huur- of koopovereenkomst present                 |
 | Date         :   10 Feb 2011                                       |
 | Marker       :   DGW6                                              |
 | Description  :   Add button to access First if persoonsnummer      |
 |                  First not empty                                   | 
 | Date			:   10 Feb 2011                                       |
 | Marker		:   DGW7                                              |
 | Description  :   Add button to access payment details First        |
 | Marker		:	DGW20                                             |
 | Date			:	6 Nov 2011                                        |
 | Description	:	Aanpassing naar D:/ voor eWorX Active X 		  |
 |                                                                    |
 | Marker       :   CoreCorp6                                         |
 | Description  :   Remove nick name                                  |
 | Date         :   21 Jan 2011 (V3.3.0)                              |
 | Marker       :   CoreCorp7                                         |
 | Description  :   Remove legal name and nick name                   |
 | Marker       :   CoreCorp8                                         |
 | Description  :   Label for sic code is KvK nummer                  |
 | Date         :   12 Jan 2011 (v3.3.0)                              |
 | Marker       :   CoreCorp4                                         |
 | Description  :   Remove nick name for household                    |
 +--------------------------------------------------------------------+
*}
{* CoreCorp7 *}
{assign var="showNickname" value="1"}
{assign var="showLegalName" value="1"}
{if $contact_type eq 'Organization'}
	{assign var="showNickname" value="0"}
	{assign var="showLegalName" value="0"}
{/if}
{* end CoreCorp7 *}
{* CoreCorp6 *}
{if $contact_type eq 'Individual'}
	{assign var="showNickname" value="0"}
{/if}
{* end CoreCorp6 *}
{* CoreCorp4 *}
{if $contact_type eq 'Household'}
	{assign var="showNickname" value="0"}
{/if}
{* end CoreCorp4 *}
<div id="crm-contactinfo-content" {if $permission EQ 'edit'} class="crm-inline-edit" data-edit-params='{ldelim}"cid": "{$contactId}", "class_name": "CRM_Contact_Form_Inline_ContactInfo"{rdelim}'{/if}>
  <div class="crm-clear crm-inline-block-content" {if $permission EQ 'edit'}title="{ts}Edit info{/ts}"{/if}>
    {if $permission EQ 'edit'}
    <div class="crm-edit-help">
      <span class="batch-edit"></span>{ts}Edit info{/ts}
    </div>
    {/if}

      {if $contact_type eq 'Individual'}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Employer{/ts}</div>
        <div class="crm-content crm-contact-current_employer">
          {if !empty($current_employer_id)} 
          <a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$current_employer_id`"}" title="{ts}view current employer{/ts}">{$current_employer}</a>
          {/if}
        </div>
      </div>
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Job Title{/ts}</div>
        <div class="crm-content crm-contact-job_title">{$job_title}</div>
      </div>
      {/if}
      
      {* CoreCorp7 *}
      {if $showNickname}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Nickname{/ts}</div>
        <div class="crm-content crm-contact-nick_name">{$nick_name}</div>
      </div>
	  {/if}
	  
      {if $contact_type eq 'Organization'}
      {if $showLegalName}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Legal Name{/ts}</div>
        <div class="crm-content crm-contact-legal_name">{$legal_name}</div>
      </div>
      {/if}
      <div class="crm-summary-row">
      	{* Customization CoreCorp8 sic code as KvK *}
        {* <div class="crm-label">{ts}SIC Code{/ts}</div> *}
        <div class="crm-label">{ts}KvK Nummer{/ts}</div>
        {* end CoreCorp8 *}
        <div class="crm-content crm-contact-sic_code">{$sic_code}</div>
      </div>
      {/if}
      <div class="crm-summary-row">
        <div class="crm-label">{ts}Source{/ts}</div>
        <div class="crm-content crm-contact_source">{$source}</div>
      </div>

    </div>
</div>
