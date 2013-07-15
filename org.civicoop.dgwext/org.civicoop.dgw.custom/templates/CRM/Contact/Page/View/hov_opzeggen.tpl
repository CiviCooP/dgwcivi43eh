{assign var="extension_found" value="0"}
{assign var="extension_installed" value="0"}
{crmAPI var='result' entity='Extension' action='get' sequential=1}
{foreach from=$result.values item=ext}
  {if $ext.key == 'org.civicoop.dgw.mutatieproces'} 
		{assign var="extension_found" value="1"}
		{if $ext.status == 'installed'}
			{assign var="extension_installed" value="1"}
		{/if}
  {/if}
{/foreach}

{if $extension_found == '1' && $extension_installed == '1'}
{include file="CRM/Contact/Page/View/mutatieproces_hov_opzeggen.tpl"}
{/if}