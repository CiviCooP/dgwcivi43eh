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
 | Date         :   12 Jan 2011 (v3.3.0)                              |
 | Marker       :   CoreCorp4                                         |
 | Description  :   Remove nick name for household                    |
 +--------------------------------------------------------------------+
*}
{* tpl for building Household related fields *}
<table class="form-layout-compressed">
    <tr>
       <td>{$form.household_name.label}<br/>
         {$form.household_name.html}
       </td>

	   {* Customization CoreCorp 4 remove nick name *}
       {* <td>{$form.nick_name.label}<br/>
       {* {$form.nick_name.html}</td>
       {* end CoreCorp4 *}

       <td>{if $action == 1 and $contactSubType}&nbsp;{else}
              {$form.contact_sub_type.label}<br />
              {$form.contact_sub_type.html}
           {/if}
       </td>
     </tr>
</table>
