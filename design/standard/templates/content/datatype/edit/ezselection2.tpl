{let content=$attribute.content
     classContent=$attribute.class_content
     id=$attribute.id
     i18n_context="extension/ezselection2/object/edit"}

{if $classContent.is_checkbox}
<table>
{foreach $classContent.options as $option}
<tr>
<td>
<input type="{if $classContent.is_multiselect}checkbox{else}radio{/if}" name="ContentObjectAttribute_ezselection2_selection_{$id}[]" {if $content|contains($option.identifier)}checked="checked"{/if} value="{$option.identifier|wash(xhtml)}" />{$option.name|wash(xhtml)|i18n( $context1 )}
</td>
</tr>
{/foreach}
</table>
{else}

<select name="ContentObjectAttribute_ezselection2_selection_{$id}[]" {if $classContent.is_multiselect}multiple="multiple"{/if}>
        
{foreach $classContent.options as $option}
    <option value="{$option.identifier|wash}"
            {section show=$content|contains($option.identifier)}selected="selected"{/section}>
        {$option.name|wash}
    </option>
{/foreach}      
        
</select>
{/if}    
{/let}     
