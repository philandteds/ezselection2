{let content=$attribute.content
     selected_array=array()
     classContent=$attribute.class_content
     available_options=$classContent.options
     delimiter=cond($classContent.delimiter|ne(""),$classContent.delimiter,", ")}

{foreach $classContent.options as $option}
    {if $content|contains($option.identifier)}
        {set $selected_array=$selected_array|append($option.name|wash(xhtml))}
    {/if}
{/foreach}
{$selected_array|implode($delimiter)}

{/let}