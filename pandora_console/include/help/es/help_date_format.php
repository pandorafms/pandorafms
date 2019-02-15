<?php
/**
 * @package Include/help/es
 */
?>
      <p class="para">
      El formato de la salida de la cadena de fecha. Consulte las opciones de formato debajo.
      </p>
      <p class="para">

       </p><table border="5">
        <caption><b>Los siguientes caracteres se reconocen en el parámetro de cadena <i><tt class="parameter">formato</tt></i></b></caption>
        <colgroup>

         </colgroup><thead valign="middle">
          <tr valign="middle">
           <th colspan="1"><i><tt class="parameter">formato</tt></i> carácter</th>
           <th colspan="1">Descripción</th>
           <th colspan="1">Ejemplo de los valores devueltos</th>
          </tr>

         </thead>

         <tbody class="tbody" valign="middle">

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Día</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>d</i></td>

           <td colspan="1" rowspan="1" align="left">Día del mes, 2 dígitos con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> a <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>D</i></td>

           <td colspan="1" rowspan="1" align="left">Representación textual de un día, con tres letras</td>
           <td colspan="1" rowspan="1" align="left"><i>Mon</i> a <i>Sun</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>j</i></td>

           <td colspan="1" rowspan="1" align="left">Día del mes sin ceros precedentes</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> a <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>l</i> ('L' minúscula)</td>

           <td colspan="1" rowspan="1" align="left">Representación completa del día de la semana</td>
           <td colspan="1" rowspan="1" align="left"><i>Sunday</i> a <i>Saturday</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>N</i></td>

           <td colspan="1" rowspan="1" align="left">Representación numérica en ISO-8601 del día de la semana</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> (para lunes) a <i>7</i> (para domingo)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>S</i></td>

           <td colspan="1" rowspan="1" align="left">Sufijo ordinal inglés para el día del mes, 2 caracteres</td>
           <td colspan="1" rowspan="1" align="left">
            <i>st</i>, <i>nd</i>, <i>rd</i> o
            <i>th</i>.  Funciona correctamente con <i>j</i>

           </td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>w</i></td>
           <td colspan="1" rowspan="1" align="left">Representación numérica del día de la semana</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> (para domingo) a <i>6</i> (para sábado)</td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>z</i></td>
           <td colspan="1" rowspan="1" align="left">El día del año (comenzando desde 0)</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> a <i>365</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Semana</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>W</i></td>
           <td colspan="1" rowspan="1" align="left">Número de la semana del año en ISO-8601, las semanas empiezan el lunes</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>42</i> (la semana 42 del año)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Mes</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>F</i></td>

           <td colspan="1" rowspan="1" align="left">Una representación textual completa del mes, como January o March</td>
           <td colspan="1" rowspan="1" align="left"><i>January</i> a <i>December</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>m</i></td>

           <td colspan="1" rowspan="1" align="left">Representación numérica del mes, con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> a <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>M</i></td>

           <td colspan="1" rowspan="1" align="left">Representación corta del mes, tres letras</td>
           <td colspan="1" rowspan="1" align="left"><i>Jan</i> a <i>Dec</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>n</i></td>

           <td colspan="1" rowspan="1" align="left">Representación numérica del mes, sin ceros precedentes</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> a <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>t</i></td>

           <td colspan="1" rowspan="1" align="left">Número de días en el mes dado</td>
           <td colspan="1" rowspan="1" align="left"><i>28</i> a <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Año</em></td>

           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>L</i></td>
           <td colspan="1" rowspan="1" align="left">Indica si es un año bisiesto</td>

           <td colspan="1" rowspan="1" align="left"><i>1</i> si es año bisiesto <i>0</i> de otra forma.</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>o</i></td>
           <td colspan="1" rowspan="1" align="left">Número de año en ISO-8601. Esto tiene el mismo valor que 
            <i>Y</i>, excepto que si el número ISO de semana (<i>W</i>) pertenece al año anterior o siguiente, se usa ese año
            en su lugar</td>

           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>1999</i> o <i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Y</i></td>
           <td colspan="1" rowspan="1" align="left">Representación numérica del año, 4 dígitos</td>

           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>1999</i> or <i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>y</i></td>
           <td colspan="1" rowspan="1" align="left">Representación del año con dos dígitos</td>

           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>99</i> o <i>03</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Hora</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>

           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>a</i></td>
           <td colspan="1" rowspan="1" align="left">En minúsula: AM y PM</td>
           <td colspan="1" rowspan="1" align="left"><i>am</i> y <i>pm</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>A</i></td>
           <td colspan="1" rowspan="1" align="left">En mayúscula: AM y PM</td>
           <td colspan="1" rowspan="1" align="left"><i>AM</i> y <i>PM</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>B</i></td>
           <td colspan="1" rowspan="1" align="left">Hora de internet</td>
           <td colspan="1" rowspan="1" align="left"><i>000</i> a <i>999</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>g</i></td>
           <td colspan="1" rowspan="1" align="left">Formato de 12 horas, sin ceros precedentes</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> a <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>G</i></td>
           <td colspan="1" rowspan="1" align="left">Formato de 24 horas, sin ceros precedentes</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> a <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>h</i></td>
           <td colspan="1" rowspan="1" align="left">Formato de 12 horas, con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> a <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>H</i></td>
           <td colspan="1" rowspan="1" align="left">Formato de 24 horas, con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> a <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>i</i></td>
           <td colspan="1" rowspan="1" align="left">Minutos con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> a <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>s</i></td>
           <td colspan="1" rowspan="1" align="left">Segundos, con ceros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> a <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>u</i></td>
           <td colspan="1" rowspan="1" align="left">Milisegundos</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>54321</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Zona horaria</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>e</i></td>
           <td colspan="1" rowspan="1" align="left">Identificador de zona horaria</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>UTC</i>, <i>GMT</i>, <i>Atlántico/Azores</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>I</i> (i mayúscula)</td>
           <td colspan="1" rowspan="1" align="left">Indica si se está en horario de verano</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> si se está en horario de verano, <i>0</i> de otra forma.</td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>O</i></td>
           <td colspan="1" rowspan="1" align="left">Diferencia con el horario de Greenwich (GMT) en horas</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>+0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>P</i></td>
           <td colspan="1" rowspan="1" align="left">Diferencia con el horario de Greenwich (GMT) con dos puntos entre horas y minutos</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>+02:00</i></td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>T</i></td>
           <td colspan="1" rowspan="1" align="left">Abreviación de la zona horaria</td>
           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>EST</i>, <i>MDT</i> ...</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Z</i></td>
           <td colspan="1" rowspan="1" align="left">Desplazamiento de la zona horaria en segundos. El desplazamiento para las zonas horarias del
            oeste de UTC es siempre negativo, y para las del este de UTC es siempre positivo.</td>
           <td colspan="1" rowspan="1" align="left"><i>-43200</i> a <i>50400</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Fecha completa/Hora</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>c</i></td>

           <td colspan="1" rowspan="1" align="left">Fecha en ISO 8601</td>
           <td colspan="1" rowspan="1" align="left">2004-02-12T15:19:21+00:00</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>r</i></td>
           <td colspan="1" rowspan="1" align="left">Fecha formateada en <a href="http://www.faqs.org/rfcs/rfc2822" class="link external">» RFC 2822</a></td>

           <td colspan="1" rowspan="1" align="left">Ejemplo: <i>Thu, 21 Dec 2000 16:01:07 +0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>U</i></td>
           <td colspan="1" rowspan="1" align="left">Segundos desde la Época Unix (January 1 1970 00:00:00 GMT)</td>
           <td colspan="1" rowspan="1" align="left">Consulte también <a href="http://es.php.net/manual/en/function.time.php" class="function">time()</a></td>

          </tr>

         </tbody>
        

       </table>
  
  <h3 class="title">Ejemplos</h3>

<table cellpadding=4 cellspacing=4 class=datos>
<tr>
<th>Cadena de formato</th> <th>Ejemplo de salida</th>
</tr>
<tr>
<td>F j, Y, g:i a</td><td>March 10, 2001, 5:16 pm</td>
</tr>
<tr>
<td>m.d.y</td><td>03.10.01</td>
</tr>
<tr>
<td>j, n, Y</td><td>10, 3, 2001</td>
</tr>
<tr>
<td>Ymd</td><td>20010310</td>
</tr>
<tr>
<td>h-i-s, j-m-y, it is w Day z</td><td>05-16-17, 10-03-01, 1631 1618 6 Fripm01</td>
</tr>
<tr>
<td>\e\s \e\l \d\í\a j.</td><td>Es el día 10.</td>
</tr>
<tr>
<td>D M j G:i:s T Y</td><td>Sat Mar 10 15:16:08 MST 2001</td>
</tr>
<tr>
<td>H:i:s</td><td>17:16:17</td>
</table>
