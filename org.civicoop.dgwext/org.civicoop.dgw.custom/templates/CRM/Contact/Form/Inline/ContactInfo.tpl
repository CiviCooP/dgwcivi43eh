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
 | Date         :   21 Jan 2011 (V3.3.0)                              |
 | Marker       :   CoreCorp7                                         |
 | Description  :   Remove legal name and nick name                   |
 | Marker       :   CoreCorp8                                         |
 | Description  :   Label for sic code is KvK nummer                  |
 | Marker       :   CoreCorp6                                         |
 | Marker       :   CoreCorp4                                         |
 | Description  :   Remove nick name for household                    |
 +--------------------------------------------------------------------+
*}
{* CoreCorp7 *}
{assign var="showNickname" value="1"}
{assign var="showLegalName" value="1"}
{if $contactType eq 'Organization'}
	{assign var="showLegalName" value="0"}
{/if}
{* end CoreCorp7 *}
{* CoreCorp6 *}
{* end CoreCorp6 *}
{$form.oplock_ts.html}
<div class="crm-inline-edit-form">
  <div class="crm-inline-button">
    {include file="CRM/common/formButtons.tpl"}
  </div>

  <div class="crm-clear">
    {if $contactType eq 'Individual'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.current_employer.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}</div>
      <div class="crm-content">
        {$form.current_employer.html}
        <div id="employer_address" style="display:none;"></div>
      </div>
    </div>
    <div class="crm-summary-row">
      <div class="crm-label">{$form.job_title.label}</div>
      <div class="crm-content">{$form.job_title.html}</div>
    <div>
    {/if}
    {if $contactType eq 'Individual'}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.nick_name.label}</div>
      <div class="crm-content">{$form.nick_name.html}</div>
    </div>
    {/if}
    {if $contactType eq 'Organization'}
    {if $showLegalName}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.legal_name.label}</div>
      <div class="crm-content">{$form.legal_name.html}</div>
    </div>
    {/if}
    <div class="crm-summary-row">
      <div class="crm-label">KvK Nummer</div>
      <div class="crm-content">{$form.sic_code.html}</div>
    </div>
    {/if}
    {* end CoreCorp7 *}
    <div class="crm-summary-row">
      <div class="crm-label">{$form.contact_source.label}</div>
      <div class="crm-content">{$form.contact_source.html}</div>
    </div>
  </div> <!-- end of main -->
</div>

{if $contactType eq 'Individual'}
  {include file="CRM/Contact/Form/CurrentEmployer.tpl"}
{/if}
