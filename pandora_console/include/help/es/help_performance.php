<?php
/**
 * @package Include/help/es
 */
?>
<h1>Configuración de Rendimiento</h1>


<b>Max. days before delete events</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar los eventos.
<br><br>
<b>Max. days before delete traps</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar los traps. 
<br><br>
<b>Max. days before delete audit events</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar los eventos de auditoría. 
<br><br>
<b>Max. days before delete string data</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar las cadenas de datos. 
<br><br>
<b>Max. days before delete GIS data</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar los datos GIS. 
<br><br>
<b>Max. days before purge</b>
<br><br>
Campo donde se definen el número máximo de días antes de borrar datos. Esto tambien especifica el nº maximo de dias a mantener datos de histórico de inventario (a partir de la version 4.0.3). Si tiene instalada una base de datos de histórico, este número tiene que ser más alto que el de dias en los que se transfiere la información a la base de datos de histórico. Recuerda que en la base de datos de histórico nunca se eliminan los datos, por lo tanto este número no indica que se borren los datos en la base de histórico.
<br><br>
<b>Max. days before compact data</b>
<br><br>
Campo donde se define el número máximo de días antes de compactar datos. 
<br><br>
<b>Compact interpolation in hours (1 Fine-20 bad)</b>
<br><br>
Campo donde se define el grado de interpolación, donde 1 es el mejor ajuste, y 20 el peor. Se recomienda usar 1 o valores próximos a uno.
<br><br>
<b>SLA period (seconds)</b>
<br><br>
El tiempo por defecto para calcular la vista de SLA en la solapa SLA de la vista de agentes. Calcula automáticamente los SLA de los Monitores definidos en ese agente en el intervalo en segundos que se da, diferenciando entre los valores Critical y Normal.
<br><br>
<b>Default hours for event view</b>
<br><br>
Campo donde se define el campo horas del filtro por defecto en la vista de eventos. Si por defecto es 24, la vista de eventos mostrará únicamente los eventos sucedidos en las últimas 24 horas.
<br><br>
<b>Use realtime statistics</b>
<br><br>
Habilitar/Deshabilitar el uso de estadísticas en tiempo real. 
<br><br>
<b>Batch statistics period (secs)</b>
<br><br>
Si las estadísticas en tiempo real están deshabilitadas, se definirá aquí el tiempo de refresco para las estadísticas.
<br><br>
<b>Use agent access graph</b>
<br><br>
El gráfico de accesos del agente, renderiza el número de contactos por hora en un gráfico con una escala diaria (24h). Esto se usa para conocer la frecuencia de contacto de cada agente. Puede necesitar mucho tiempo de procesado, por lo que si dispone de bajos recursos se recomienda deshabilitarlo. 
<br><br>
<b>Max. days before delete unknown modules</b>
<br><br>
Campo donde se define el número máximo de días antes de borrar los módulos desconocidos. 
<br><br>
<i>**Con todos estos parámetros trabaja la herramienta DB Tool. </i>

