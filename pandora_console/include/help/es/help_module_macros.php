<?php
/**
 * @package Include/help/es
 */
?>
<h1>Macros de módulo</h1>
<p>    
    Se puede definir cualquier número de macros de módulo. El formato recomendado para los nombres de macros es el siguiente:
</p>
<pre>
    _macroname_
</pre>

<p>
    Por ejemplo:
</p>

<ol>
    <li class="lato_bolder font_12pt">
        _technology_
    </li>
    <li class="lato_bolder font_12pt">
        _modulepriority_
    </li>
    <li class="lato_bolder font_12pt">
        _contactperson_
    </li>
</ol>

<p>
    Estas macros se pueden utilizar en las alertas de módulos.
</p>

<h2>Si el módulo es de tipo analisis de módulo web:</h2>

<p>
    Las macros dinámicas tendrán un formato especial que empieza por @ y tendrán estas posibles sustituciones:
</p>
<ol>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT (fecha/hora actual con formato definido por el usuario)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_nh (horas)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_nm (minutos)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_nd (días)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_ns (segundos)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_nM (mes)
    </li>
    <li class="lato_bolder font_12pt">
        @DATE_FORMAT_nY (años)
    </li>
</ol>
<p>
    Donde “n” puede ser un numero sin signo (positivo) o negativo.
</p>
<p>
    Y FORMAT sigue el standard de strftime de perl:
    http://search.cpan.org/~dexter/POSIX-strftime-GNU-0.02/lib/POSIX/strftime/GNU.pm
</p>
<p>
    Ejemplos:
</p>
<pre>
    @DATE_%Y-%m-%d %H:%M:%S
    @DATE_%H:%M:%S_300s
    @DATE_%H:%M:%S_-1h
</pre>