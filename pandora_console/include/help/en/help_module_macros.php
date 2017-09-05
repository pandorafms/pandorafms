<?php
/**
 * @package Include/help/en
 */
?>
<h1>Module macros</h1>
<p>
	Any number of custom module macros may be defined. The recommended format for macro names is:
</p>
<pre>
	_macroname_
</pre>

<p>
	For example:
</p>
<ol>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		_technology_
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		_modulepriority_
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		_contactperson_
	</li>
</ol>

<p>
	This macros can be used in module alerts.
</p>

<h2>Si el módulo es de tipo analisis de módulo web:</h2>

<p>
	Las macros dinámicas tendrán un formato especial que empieza por @ y tendrán estas posibles sustituciones:
</p>
<ol>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT (fecha/hora actual con formato definido por el usuario)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT_nh (horas)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT_nm (minutos)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT_nd (días)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT_ns (segundos)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
		@DATE_FORMAT_nM (mes)
	</li>
	<li style="font-family: 'lato-bolder'; font-size: 12pt;">
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