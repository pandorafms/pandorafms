<?php
/**
 * @package Include/help/es
 */
?>
<h1>Cron para módulos de servidor</h1>

Mediante los grupos de parámetros de configuración <b>Cron desde</b> y <b>Cron hasta</b> se
puede hacer que un módulo solo se ejecute durante ciertos periodos de tiempo. El 
modo en el que se configura es parecido a la sintaxis de 
<a class="font_14px" href="https://es.wikipedia.org/wiki/Cron_(Unix)">cron</a>. 
Tal y como aparecen en la consola de <?php echo get_product_name(); ?>, cada uno de los parámetros 
tiene tres opciones.

<h4>Cron desde: cualquiera</h4>

El módulo no tendrá restricciones en ese parámetro. Se ejecutará cualquiera que 
que sea el valor y equivale al asterisco (*) en la nomenclatura de cron. En este 
caso se ignora <b>Cron desde</b>.

<h4>Cron desde: distinto de cualquiera. Cron hasta: cualquiera</h4>

El módulo se ejecutará solamente el tiempo en el que la fecha coincida con ese 
parámetro. Equivale a escribir solamente un número en la nomenclatura de cron.

<h4>Cron desde: distinto de cualquiera. Cron hasta: distinto de cualquiera</h4>

El módulo se ejecutará entre el tiempo indicado en el <b>Cron desde</b> y el <b>Cron hasta</b>. 
Equivale a escribir el número guión número (n-n) en la nomenclatura de cron.

<h2>Intervalo del agente</h2>

Mientras que se cumplan las condiciones de cron, el agente se ejecutará siguiendo 
su intervalo de ejecución.

<h2>Ejemplos</h2>

<ul>
    <li><i>* * * * *</i>: No hay cron configurado.</li>
    <li><i>15 20 * * *</i>: Se ejecutará todos los días a las 20:15.</li>
    <li><i>* 20 * * *</i>: Se ejecutará todos los días durante las 20 horas, es decir, entre las 20:00 y las 20:59.</li>
    <li><i>* 8-19 * * *</i>: Se ejecutará todos los días entre las 8:00 y las 19:59.</li>
    <li><i>15-45 * 1-16 * *</i>: Se ejecutará todos los primeros 16 días del mes a todas horas entre y cuarto y menos cuarto.</li>
    <li><i>* * * 5 *</i>: Se ejecutará solamente en mayo.</li>
<ul>

