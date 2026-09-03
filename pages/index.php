
<h1>{%defer page.title raw%}</h1>
{%layout main%}
<p>{{name}} - {%if extend%}test1{%else%}test2{% endif%}</p>
{%layout main%}
<p>{{status.named}}'</p>
{%endlayout%}
<?php
echo date('Y-m-d H:i:s'); ?>
{%endlayout%}
<ul>
{%foreach index,value in randomRange%}
    <li>{{index}}: {{value}}</li>
{%endforeach%}
</ul>
<p>CSRF {%csrf%}</p>
{%component vasoft:day%}

{%component vasoft:random%}
{%component vasoft:random variant1%}
{%component vasoft:random variant2 randomRange %}

<pre id="log">

</pre>
<script>
    const logBox = document.getElementById('log');
    const log = function (message) {
        logBox.innerHTML += message + "\n";
    }
</script>

{# bold name #}
<p>Сегодня: {% format_date date 'd.m.Y' %}</p>