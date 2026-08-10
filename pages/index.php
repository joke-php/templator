{%layout main%}
<p>{{name}} - {%if extend%}test1{%else%}test2{%/if%}</p>
{%layout main%}
<p>{{status.named}}'</p>
{%/layout%}
<?php echo date('Y-m-d H:i:s'); ?>
{%/layout%}