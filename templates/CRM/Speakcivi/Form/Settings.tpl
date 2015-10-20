<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
</div>

{foreach from=$elementNames item=elementName}
    <div class="crm-section">
        <div>{$form.$elementName.label}</div>
        <div>{$form.$elementName.html}</div>
        <div class="clear"></div>
    </div>
{/foreach}

<div class="crm-section">
    <div>{$country_lang_mapping_title}</div>
    <div>
        <ul>
            {foreach from=$country_lang_mapping item=lang key=country}
                <li>{$country}: {$lang}</li>
            {/foreach}
        </ul>
    </div>
    <div class="clear"></div>
</div>

<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
