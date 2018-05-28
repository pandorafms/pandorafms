<?php
/**
 * @package Include/help/en
 */
?>
<h1>Umbral Flip Flop del Módulo</h1>

<br> 
<br>

El umbral del parámetro FF (FF=FlipFLoP) se utiliza para "filtrar" los continuos cambios de estado en la creación de eventos/estados, para que pueda indicar a <?php echo get_product_name();?> que hasta que un elemento no esté al menos x veces en el mismo estado después de cambiar desde su estado original, no considere que haya cambiado.

<br><br>

Tomemos como ejemplo clásico: un ping para un host donde hay pérdida de paquetes. En un entorno como este, podría resultar como:

<pre>
 1  
 1  
 0  
 1  
 1  
 0  
 1  
 1  
 1 
</pre>
<br>

Sin embargo, el host está vivo en todos los casos. Lo que queremos realmente es decirle a <?php echo get_product_name();?> que hasta que es host no lo diga usted está al menos tres veces caído, no lo marque así, con lo que en el caso anterior no estaría caído, y sólo en este caso sería:

<pre>
 1  
 1  
 0  
 1  
 0  
 0  
 0  
 </pre>
<br>
Desde este punto lo vería como caído, pero no antes.

<br>

La protección anti FLip-flop se usa para evitar estas fluctuaciones tan molestas, todos los módulos la implementan y la utilizan para evitar el cambio de estado (definido por sus limites definidos o sus sistemas diferenciales, como por ejemplo ocurre con los módulos *proc).

<br><br>

