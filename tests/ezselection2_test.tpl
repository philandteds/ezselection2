{* Test template *}

{switch match=$type}

{case match="classview"}
{class_attribute_view_gui class_attribute=$attribute}
{/case}

{case match="classedit"}
{class_attribute_edit_gui class_attribute=$attribute}
{/case}

{* Handles content view and collect view. Based on class attribute info collection settings *}
{case match="contentview"}
{attribute_view_gui attribute=$attribute}
{/case}

{case match="contentedit"}
{attribute_edit_gui attribute=$attribute}
{/case}

{case match="resultview"}
{attribute_result_gui attribute=$attribute}
{/case}

{/switch}
