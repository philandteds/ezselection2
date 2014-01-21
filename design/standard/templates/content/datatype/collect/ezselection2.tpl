{let $content=$attribute.content
     $classContent=$attribute.class_content
     $available_options=$classContent.options
     $id=$attribute.id}

{if $classContent.is_checkbox}
{foreach $available_options as $option}
<div>
<input type="{if $classContent.is_multiselect}checkbox{else}radio{/if}" 
 name="ContentObjectAttribute_ezselection2_{$id}[]"
 {if $content|contains($option.identifier)}checked="checked"{/if}  
 value="{$option.name|wash(xhtml)}" /> {$option.name|wash(xhtml)}
</div>
{/foreach}
{else}
<select name="ContentObjectAttribute_ezselection2_{$id}[]" {if $classContent.is_multiselect} multiple="multiple"{/if}>
        
{foreach $available_options as $option}
    <option value="{$option.identifier|wash}" {if $content|contains($option.identifier)}selected="selected"{/if}>
        {$option.name|wash}
    </option>
{/foreach}      
        
</select>  
{/if}

{/let}
