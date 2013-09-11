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
 |                                                                    |
 | Date		:   27 Dec 2011                                       |
 | Marker       :   DGW22                                             |
 | Description  :   Wijzigen voornaam in voorletters                  |
 |                                                                    |
 | Author       :   Erik Hommel (erik.hommel@civicoop.org)            |
 | Date         :   2 April 2013                                      |
 | Marker       :   BOS1303566                                        |
 | Description  :   Prefix based on gender, no longer available in    |
 |                  edit or create                                    |
 +--------------------------------------------------------------------+
*}
{* tpl for building Individual related fields *}
<script type="text/javascript">
{literal}
cj(function($) {
{/literal}
  var cid=parseFloat("{$contactId}");//parseInt is octal by default
  var contactIndividual = "{crmURL p='civicrm/ajax/rest' q='entity=contact&action=get&json=1&contact_type=Individual&return=display_name,sort_name,email&rowCount=50' h=0}";
  var viewIndividual = "{crmURL p='civicrm/contact/view' q='reset=1&cid=' h=0}";
  var editIndividual = "{crmURL p='civicrm/contact/add' q='reset=1&action=update&cid=' h=0}";
  var checkSimilar = {$checkSimilar};
  var lastnameMsg;
{literal}
  $(document).ready(function() {
    if (cj('#contact_sub_type *').length == 0) {//if they aren't any subtype we don't offer the option
      cj('#contact_sub_type').parent().hide();
    }
    if (!isNaN(cid) || ! checkSimilar) {
     return;//no dupe check if this is a modif or if checkSimilar is disabled (contact_ajax_check_similar in civicrm_setting table)
    }
    cj('#last_name').blur(function () {
      // Close msg if it exists
      lastnameMsg && lastnameMsg.close && lastnameMsg.close();
      if (this.value == '') return;
      cj.getJSON(contactIndividual,{sort_name:cj('#last_name').val()},
        function(data){
          if (data.is_error == 1 || data.count == 0) {
            return;
          }
          var msg = "<em>{/literal}{ts escape='js'}If the person you were trying to add is listed below, click their name to view or edit their record{/ts}{literal}:</em>";
          if ( data.count == 1 ) {
            var title = "{/literal}{ts escape='js'}Similar Contact Found{/ts}{literal}";
          } else {
            var title = "{/literal}{ts escape='js'}Similar Contacts Found{/ts}{literal}";
          }
          msg += '<ul class="matching-contacts-actions">';
          cj.each(data.values, function(i,contact){
            if ( !(contact.email) ) {
              contact.email = '';
            }
          msg += '<li><a href="'+viewIndividual+contact.id+'">'+ contact.display_name +'</a> '+contact.email+'</li>';
        });
        msg += '</ul>';
        lastnameMsg = CRM.alert(msg, title);
        cj('.matching-contacts-actions a').click(function(){
          // No confirmation dialog on click
          global_formNavigate = true;
          return true;
        });
      });
    });
  });
});
</script>
{/literal}

<table class="form-layout-compressed">
  <tr>
    {* BOS1303566 *}

        {* if $form.prefix_id *}
        {if $form.prefix_id and $action ne 1}
	    <td>
                {*$form.prefix_id.label}<br/>*}
                {*$form.prefix_id.html*}
                {* retrieve prefix with API *}
                {crmAPI var="contactData" entity="contact" action="get" contact_id=$contactId}
                {foreach from=$contactData.values item=contactField}
                    {if $contactField.individual_prefix != ''}
                        <br /><input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value={$contactField.individual_prefix} ><br />
                    {else}
                        {if $contactField.gender_id == 1}
                            <br /><input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='mevrouw'><br />
                        {elseif $contactField.gender_id == 2}
                            <br /><input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='heer'><br />
                        {elseif $contactField.gender_id == 3}
                            <br /><input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value='mevrouw/heer'><br />
                        {else}
                            <br /><input readonly=readonly size=6 style='background-color:#E6E6E6' type=text name=prefixName value=''><br />
                        {/if}
                    {/if}
                {/foreach}
            </td>
        {/if}
        {* end BOS1303566 *}
    <td>
    	{* DGW22 wijzigen voornaam in voorletters *}
		Voorletters (zonder puntjes)<br />
        {* {$form.first_name.label}<br />*}
        {* end DGW22 *}
      {$form.first_name.html}
    </td>
    <td>
      {$form.middle_name.label}<br />
      {$form.middle_name.html}
    </td>
    <td>
      {$form.last_name.label}<br />
      {$form.last_name.html}
    </td>
    {* Customization CoreCorp5 Remove suffix *}
    {*{if $form.suffix_id}
    {*<td>
    {*  {$form.suffix_id.label}<br/>
    {*  {$form.suffix_id.html}
    {*</td>
    {* {/if}
    {* end CoreCorp5 *}
  </tr>

  <tr>
    <td colspan="2">
      {$form.current_employer.label}&nbsp;{help id="id-current-employer" file="CRM/Contact/Form/Contact.hlp"}<br />
      {$form.current_employer.html|crmAddClass:twenty}
      <div id="employer_address" style="display:none;"></div>
    </td>
    <td>
      {$form.job_title.label}<br />
      {$form.job_title.html}
    </td>
    <td>
    {* DGW22 roepnaam voor nick_name *}
    {*{$form.nick_name.label}<br />*}
    Roepnaam<br />

    {$form.nick_name.html}
    </td>
    
    <td>
      {if $buildContactSubType}
      {$form.contact_sub_type.label}<br />
      {$form.contact_sub_type.html}
      {/if}
    </td>
  </tr>
</table>

{include file="CRM/Contact/Form/CurrentEmployer.tpl"}
