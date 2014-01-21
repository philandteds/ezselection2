{let content=$attribute.content
     classContent=$attribute.class_content
     available_options=$classContent.options}

{set-block scope=root variable=pdf_text}{foreach $available_options as $option loop=$available_options}{$option.name|wash}{/foreach}{/set-block}
{pdf(text, $pdf_text|wash(pdf))}

{/let}