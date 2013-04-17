{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 | Customer     :   De Goede Woning                                   |
 | Date         :   19 April 2011                                     |
 | Marker       :   DGW13                                             |
 | Description  :   Can not remove household from actions             |
 |                                                                    |
 | Date         :   7 maart 2013                                      |
 | Marker       :   incident 20 06 12 004                             |
 | Description  :   Niet laten zien als - copy sent                   |
 +--------------------------------------------------------------------+

*}
{* Displays recently viewed objects (contacts and other objects like groups, notes, etc. *}
<div id="recently-viewed">
    <ul>
        {foreach from=$recentlyViewed item=item}

            {assign var='showItem' value=1}
            {* incident 20 06 12 004 - act met - copy sent niet laten zien *}
            {if $item.type eq 'Activity'}
                {if $item.title|mb_substr:0:12 eq ' - copy sent'}
                    {assign var='showItem' value=0}
                {/if}    
            {/if}

            {if $showItem eq 1}

                 <li class="crm-recently-viewed" ><a  href="{$item.url}" title="{$item.title}">
                 {if $item.image_url}
                    <span class="icon crm-icon {if $item.subtype}{$item.subtype}{else}{$item.type}{/if}-icon" style="background: url('{$item.image_url}')"></span>
                 {else}
                    <span class="icon crm-icon {$item.type}{if $item.subtype}-subtype{/if}-icon"></span>
                 {/if}
                 {if $item.isDeleted}<del>{/if}{$item.title|mb_truncate:25:"..":true}{if $item.isDeleted}</del>{/if}</a>
                 <ul class="crm-recentview-wrapper" style="display:none;">

                 <li><a href='{$item.url}' class="crm-actions-view">{ts}View{/ts}</a>
                                {* DGW13 can not remove household from action list *}
                                {if $item.type ne 'Household'}
                                        {if $item.edit_url}<a href='{$item.edit_url}' class="crm-actions-edit">{ts}Edit{/ts}</a>{/if}    
                                        {if $item.delete_url}<a href='{$item.delete_url}' class="crm-actions-delete">{ts}Delete{/ts}</a>{/if}

                            {/if}
                            {* end DGW13 *}

                     </li>
                 </ul>
                 </li>
            {/if}

        {/foreach}
   </ul>
</div>
{literal}
<script type="text/javascript">
    cj( function( ) {
       	cj('li.crm-recently-viewed')
	.addClass('crm-processed')
	.hover(
		function(e)  {
		    cj(this).addClass('crm-recentview-active');
		
		      var pos       = cj(this).parent().offset();
		      var eleWidth  = cj(this).width( );
		      var eleHeight = cj(this).height( );
		    
		      var addStyle  = 'display:block;';
		      if ( eleWidth == 'undefined' ) {
		      	 eleWidth   = 180;
		      }
		      if ( eleHeight == 'undefined' ) {
		         eleHeight  = 24;    
		      }
		      		      
		      var linkCount = cj('ul li',this).children().size();
		      if ( linkCount ) {
		      	addStyle += 'width:'+ ( linkCount*30) + 'px;'; 
		      }		      

 		      if ( pos.left >= 150 ) {
		      	// hack for IE6 and IE7  
		        if ( cj.browser.msie && ( cj.browser.version.substr( 0,1 ) == '7' ||  cj.browser.version.substr( 0,1 ) == '6' ) ) {
			    gethtml =   '<ul class="crm-recentview-wrapper" style="display:none;">'+  cj(this).children('ul').html( ) +'</ul>' ;
			    cj(this).children('ul').remove();			
			    cj(this).prepend(gethtml );
			    addStyle += 'margin-top:'+ (eleHeight+2) + 'px;';
			} else {
		      	    addStyle += 'margin-left:-'+ (linkCount*30+2) + 'px;';
			    addStyle += 'margin-top:-'+ (eleHeight+2) + 'px;';
			}
		        if (cj(this).children('ul.crm-recentview-wrapper-right').length == '' ) {
		            cj(this).children('ul').addClass('crm-recentview-wrapper-right');
		        }
		        cj(this).children('ul').attr('style', addStyle);
		      } else {
		        // hack for IE6 and IE7  
			if ( cj.browser.msie && ( cj.browser.version.substr( 0,1 ) == '7' ||  cj.browser.version.substr( 0,1 ) == '6' ) ) {
			    addStyle += 'margin-top:-3px;';
			} else {   
		            addStyle += 'margin-left:'+ (eleWidth-1) + 'px;';
		      	    addStyle += 'margin-top:-'+ (eleHeight+2) + 'px;';
			}   
		        if (cj(this).children('ul.crm-recentview-wrapper-left').length == '' ) {
		      	    cj(this).children('ul').addClass('crm-recentview-wrapper-left');
		        }
		        cj(this).children('ul').attr('style', addStyle);
		      }	 
		      
		},
		function(){
		 cj(this).children('ul.crm-recentview-wrapper').attr('style','display:none' );
		 cj(this).removeClass('crm-recentview-active');
		}
		)
	.click(function() {return true;});	
	
    });
 
{/literal}
</script>
