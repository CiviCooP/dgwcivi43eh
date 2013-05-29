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
 | Date		:   7 Nov 2011                                        |
 | Marker	:   DGW19                                             |
 | Description  :   Gevoelige informatie act. type alleen beschikbaar |
 |                  voor leden groep 18 (Consulenten Wijk & Buurt)    |
 |                  en admin                                          |
 |                                                                    |
 | Date         :   18 Feb 2013                                       |
 | Marker       :   incident 14 01 13 003                             |
 | Description  :   Details en bijlage niet zien voor activiteitstype |
 |                  'Gespreksverslag dir/best' tenzij in speciale     |
 |                  groep Dir/Best of Admin                           |
 +--------------------------------------------------------------------+
*}
{* Links for scheduling/logging meetings and calls and Sending Email *}
{if $cdType eq false }
{if $contact_id }
{assign var = "contactId" value= $contact_id }
	{* DGW19 - Act type 108 alleen laten zien als user in groep 18 *}
    {* incident 14 01 13 003 - Act type 118 alleen laten zien als user in groep 28 *}
    {assign var='userDirBest' value=0}
    {if $config->userFrameworkBaseURL eq "http://insitetest2/"}
        {assign var='groupDirBest' value=28}
    {else}
        {assign var='groupDirBest' value=24}
    {/if}
    {assign var='typeDirBest' value=118}
    {assign var='userWijk' value=0}
    {assign var='typeWijk' value=109}
    {assign var='groupWijk' value=18}
    {assign var='userAdmin' value=0}
    {crmAPI var="userGroups" entity="GroupContact" action="get" contact_id=$session->get('userID')}
    {* als één van de groepen groep Wijk, dan userWijk=1 (tonen) *}
    {* als één van de groepen groep DirBest dan userDirBest=1 (tonen) *}
    {* als één van de groepen groep 1 dan userAdmin=1 (tonen) *}
    {foreach from=$userGroups.values item=userGroup}
        {if $userGroup.group_id eq 1}
            {assign var='userAdmin' value=1}
        {/if}    
        {if $userGroup.group_id eq $groupWijk}
            {assign var='userWijk' value=1}
        {/if}
        {if $userGroup.group_id eq $groupDirBest}
            {assign var='userDirBest' value=1}
        {/if}    
    {/foreach}	
    {* end DGW19 en incident 14 01 13 003 1e deel *}
{/if}

{if $as_select} {* on 3.2, the activities can be either a drop down select (on the activity tab) or a list (on the action menu) *}
<select onchange="if (this.value) window.location=''+ this.value; else return false" name="other_activity" id="other_activity" class="form-select">
  <option value="">{ts}- new activity -{/ts}</option>
{foreach from=$activityTypes key=k item=link}
 	{* DGW19 / incident 14 01 13 003 *}
	{* alleen act type typeWijk als user in groep Consulenten Wijk en Ontwikkeling of Admin *}
	{* alleen act type typeDirBest als user in groep Dir/Best of Admin *}
	{if $k ne $typeWijk and $k ne $typeDirBest}
	    <option value="{$k}">{$link}</option>
	{else}
	    {if $userAdmin eq 1}
	        <option value="{$k}">{$link}</option>
	    {else}
	        {if $k eq $typeWijk and $userWijk eq 1}
	            <option value="{$k}">{$link}</option>
	        {/if}
	        {if $k eq $typeDirBest and $userDirBest eq 1}
	            <option value="{$k}">{$link}</option>
	        {/if}
	    {/if}
	{/if}
	{* end DGW19 / incident 14 01 13 003 deel 2 *}
{/foreach}
</select>

{else}
<ul>
{foreach from=$activityTypes key=k item=link}
	{* DGW19 / incident 14 01 13 003 *}
	{* alleen act type 109 als user in groep Consulenten Wijk en Ontwikkeling of Admin *}
	{* alleen act type 118 als user in groep Dir/Best of Admin *}
	{if $k ne $typeWijk and $k ne $typeDirBest}
	    <li class="crm-activity-type_{$k}"><a href="{$url}{$k}">{$link}</a></li>
	{else}
	    {if $userAdmin eq 1}
	        <li class="crm-activity-type_{$k}"><a href="{$url}{$k}">{$link}</a></li>
	    {else}
	        {if $k eq $typeWijk and $userWijk eq 1}
	            <li class="crm-activity-type_{$k}"><a href="{$url}{$k}">{$link}</a></li>
	        {/if}
	        {if $k eq $typeDirBest and $userDirBest eq 1}
	            <li class="crm-activity-type_{$k}"><a href="{$url}{$k}">{$link}</a></li>
	        {/if}
	    {/if}
	{/if}
	{* end DGW19 / incident 14 01 13 003 deel 3 *}
{/foreach}

{* add hook links if any *}
{if $hookLinks}
   {foreach from=$hookLinks item=link}
    <li>
        <a href="{$link.url}">
          {if $link.img}
                <img src="{$link.img}" alt="{$link.title}" />&nbsp;
          {/if}
          {$link.title}
        </a>
    </li>
   {/foreach}
{/if}

</ul>

{/if}

{/if}
