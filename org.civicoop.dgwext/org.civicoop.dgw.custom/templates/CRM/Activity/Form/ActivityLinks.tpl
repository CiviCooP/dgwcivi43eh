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
{* DGW19 - Act type 108 alleen laten zien als user in groep 18 *}
{* incident 14 01 13 003 - Act type 118 alleen laten zien als user in groep 28 *}
{assign var='typeWijk' value=109}
{assign var='typeDirBest' value=118}
{assign var='groupWijk' value=18}
{assign var='userAdmin' value=0}
{assign var='userWijk' value=0}
{assign var='userDirBest' value=0}
{if $config->userFrameworkBaseURL eq "http://insitetest2/"}
    {assign var='groupDirBest' value=28}
{else}
    {assign var='groupDirBest' value=24}
{/if}
{* get all groups for user *}
{crmAPI var="userGroups" entity="GroupContact" action="get" contact_id=$session->get('userID')}
{assign var='showStuff' value=0}
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

{* Links for scheduling/logging meetings and calls and Sending Email *}
{if $cdType eq false }
{if $contact_id }
{assign var = "contactId" value= $contact_id }
{/if}

{if $as_select} {* on 3.2, the activities can be either a drop down select (on the activity tab) or a list (on the action menu) *}
<select onchange="if (this.value) window.location=''+ this.value; else return false" name="other_activity" id="other_activity" class="form-select">
  <option value="">{ts}- new activity -{/ts}</option>
{foreach from=$activityTypes key=k item=link}
    {*DGW19 tweede deel *}
    {if $k != $typeWijk and $k != $typeDirBest}
        <option value="{$urls.$k}">{$link}</option>
    {else}
        {if $userAdmin == 1}
            <option value="{$urls.$k}">{$link}</option>
        {else}
            {if $k == $typeWijk and $userWijk == 1}
                <option value="{$urls.$k}">{$link}</option>
            {/if}
            {if $k == $typeDirBest and $userDirBest == 1}
                <option value="{$urls.$k}">{$link}</option>
            {/if}
        {/if}
    {/if}
    {*DGW19 tweede deel end *}
{/foreach}
</select>

{else}
<ul>
{foreach from=$activityTypes key=k item=link}
    {*DGW19 derde deel *}
    {if $k != $typeWijk and $k != $typeDirBest}
        <li class="crm-activity-type_{$k}"><a href="{$urls.$k}">{$link}</a></li>
    {else}
        {if $userAdmin == 1}
            <li class="crm-activity-type_{$k}"><a href="{$urls.$k}">{$link}</a></li>
        {else}
            {if $k == $typeWijk and $userWijk == 1}
                <li class="crm-activity-type_{$k}"><a href="{$urls.$k}">{$link}</a></li>
            {/if}
            {if $k == $typeDirBest and $userDirBest == 1}
                <li class="crm-activity-type_{$k}"><a href="{$urls.$k}">{$link}</a></li>
            {/if}
        {/if}
    {/if}
    {*DGW19 derde deel end *}
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
