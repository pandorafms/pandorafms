<?php
global $config;
check_login ();
ui_require_css_file ('firts_task');
?>

<div class="Table">
    <div class="Title">
        <p>This is a Table</p>
    </div>
    <div class="Heading">
        <div class="Cell">
            <p>Heading 1</p>
        </div>
        <div class="Cell">
            <p>Heading 2</p>
        </div>
        <div class="Cell">
            <p>Heading 3</p>
        </div>
    </div>
    <div class="Row">
        <div class="Cell">
            <a href="index.php?sec=snmpconsole&sec2=godmode/snmpconsole/snmp_filters&edit_filter=-1">Crear un nuevo filtro snmp</a>
        </div>
        <div class="Cell">
            <p>Row 1 Column 2</p>
        </div>
        <div class="Cell">
            <p>Row 1 Column 3</p>
        </div>
    </div>
    <div class="Row">
        <div class="Cell">
            <p>Row 2 Column 1</p>
        </div>
        <div class="Cell">
            <p>Row 2 Column 2</p>
        </div>
        <div class="Cell">
            <p>Row 2 Column 3</p>
        </div>
    </div>
</div>
