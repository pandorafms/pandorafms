<?php
/**
 * @package Include/help/es
 */
?>
<h1>Dynamic threshold</h1>

<h2>Dynamic threshold Interval</h2>

<p>
	Dynamic threshold interval permite introducir un intervalo de tiempo, mediante el cual durante el intervalo definido comprobara los datos que ha tenido el módulo según los resultados obtenidos establecerá los mínimos para los umbrales de critical y warning el intervalo de ejecución depende de la configuración del servidor.
	<br><br>
	Por defecto <b>Solo</b> dará los minimos con lo cual mientras el máximo es = 0 implica que va desde el minimo mostrado hasta el infinito.
	<br><br>
	<b>Ejemplo:</b><br>
	warning status min = 5 y max = 0<br>
	critical status min = 10 y max = 0<br>
	Con estos datos nuestro módulo recogerá los siguientes estados:<br> 
	  - Estado normal de -infinito a 4.<br>
	  - Estado warning de 5 a 9.<br>
	  - Estado critico de 10 a infinito.
	<br><br>
	<b>Ejemplo 2:</b><br>
	warning status min = 5 y max = 0 con inverse interval seleccionado<br>
	critical status min = 10 y max = 0 con inverse interval seleccionado<br>
	Con estos datos nuestro módulo recogerá los siguientes estados:<br> 
	  - Estado normal de 10 a infinito.<br>
	  - Estado warning no estaria nunca en warning.<br>
	  - Estado critico de 10 a -infinito.
	<br><br>
		En estos ejemplos hay que tener en cuenta que en caso de que el <b>umbral critical coincida con el umbral warning prevalece critical.</b>
</p>

<h2>Advanced options dynamic threshold</h2>
<b>Dynamic threshold Min. / Max.</b>
<p>
	Con estos campos podemos ajustar con porcentajes si queremos ampliar o disminuir los rangos dados dinamicamente.
	Si lo que queremos es disminuir los rangos dados dinamicamente introduciriamos porcentajes negativo si lo que queremos es ampliar ese rango estableceremos rangos positivos con lo cual podemos afinar mas aun nuestros umbrales
</p>

<b>Dynamic threshold Two Tailed:</b>
<p>
	Con este campo podremos tambien definir si queremos ambos rangos tanto el minimo como el maximo ya que por defecto solo dara los minimos.
</p>
<b>Ejemplo:</b><br>
warning status min = 5 y max = 10<br>
critical status min = 10 y max = 15<br>
Con estos datos nuestro módulo recogerá los siguientes estados:<br> 
	- Estado normal de -infinito a 4 y de 16 a infinito.<br>
	- Estado warning de 5 a 9.<br>
	- Estado critico de 10 a 15.
<br><br>
<b>Ejemplo 2:</b><br>
warning status min = 40 y max = 80 con inverse interval seleccionado<br>
critical status min = 20 y max = 100 con inverse interval seleccionado<br>
Con estos datos nuestro módulo recogerá los siguientes estados:<br> 
	- Estado normal de 41 a 79.<br>
	- Estado warning de 21 a 40 y de 80 a 99.<br>
	- Estado critico de -infinito a 20 y de 100 a infinito.
<br><br>
<b>Para enterder estos ejemplos mejor utilizar la gráfica que te indicará como estaràn tus estados dependiendo los valores que introduzcas. </b>

