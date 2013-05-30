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
 | Date			:	17 Mar 2011 (V3.3.5)                              |
 | Marker		:	CoreCorp10									      |
 | Description	: 	Change var $title in Kenmerken en Groepen as long |
 |                  as Word replacement does not work (until 3.4?)    |
 +--------------------------------------------------------------------+
*}
{if $title}
<div class="crm-accordion-wrapper crm-tagGroup-accordion collapsed">
  <div class="crm-accordion-header">
  
  	{* CoreCorp10 translate $title into Groepen en Kenmerken *}
	{* {$title} *}
	Groepen en kenmerken
	{* end CoreCorp10 *}
  
  </div>
  <div class="crm-accordion-body" id="tagGroup">
{/if}
    <table class="form-layout-compressed{if $context EQ 'profile'} crm-profile-tagsandgroups{/if}">
      <tr>
      {foreach key=key item=item from=$tagGroup}
        {* $type assigned from dynamic.tpl *}
        {if !$type || $type eq $key }
          <td width={cycle name=tdWidth values="70%","30%"}><span class="label">{if $title}{$form.$key.label}{/if}</span>
            <div id="crm-tagListWrap">
              <table id="crm-tagGroupTable">
                {foreach key=k item=it from=$form.$key}
                  {if $k|is_numeric}
                    <tr class={cycle values="'odd-row','even-row'" name=$key} id="crm-tagRow{$k}">
                      <td>
                        <strong>{$it.html}</strong><br />
                        {if $item.$k.description}
                          <div class="description">
                            {$item.$k.description}
                          </div>
                        {/if}
                      </td>
                    </tr>
                  {/if}
                {/foreach}
              </table>
            </div>
          </td>
        {/if}
      {/foreach}
    </tr>
    {if !$type || $type eq 'tag'}
      <tr><td>{include file="CRM/common/Tag.tpl"}</td></tr>
    {/if}
  </table>
{if $title}
  </div>
</div><!-- /.crm-accordion-wrapper -->
{/if}
