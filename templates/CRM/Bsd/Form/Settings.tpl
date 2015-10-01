<div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
</div>

{foreach from=$elementNames item=elementName}
    <div class="crm-section">
        <div class="label">{$form.$elementName.label}</div>
        <div class="content">{$form.$elementName.html}</div>
        <div class="clear"></div>
    </div>
{/foreach}

<div class="crm-section">
    <div class="label">{$country_lang_mapping_title}</div>
    <div class="content">
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
