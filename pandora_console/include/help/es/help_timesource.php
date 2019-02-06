<?php
/**
 * @package Include/help/es
 */
?>
<h1>Origen de tiempo</h1>

<p>
Qué origen de tiempo usar. Esto puede ser (por el momento) el sistema local (&laquo;Sistema&raquo;) o la base de datos (&laquo;Base de datos&raquo;).
</p>
<p>
Esto es útil cuando su base de datos no está en el mismo sistema que su servidor Web o los servidores de su <?php echo get_product_name(); ?>.
En ese caso cualquier diferencia de tiempo calculará de forma errónea las diferencias de tiempo y marcas de tiempo.
Debería usar NTP para sincronizar todos sus servidores de <?php echo get_product_name(); ?> y su servidor de MySQL.
Usando estas preferencias no tendrá que sincronizar su servidor web, aún así se recomienda.
</p>
<p>
Implemente más orígenes si lo ve necesario (ej. ntp, ldap, $_SERVER...).
</p>
<p>
Nota: La consulta a la base de datos se cacheará la primera vez que se haga, de tal forma que la hora será siempre la misma en la carga de página, mientras que la hora del sistema se devuelve siempre que se llame a la función, lo que puede diferir (especialmene al final de un segundo).
</p>
<p>
Estos ejemplos devuevent todos el tiempo Unix:
<script type="text/javascript">
var date = new Date; // Objeto de fecha JS genérico
var unixtime_ms = date.getTime(); // Devuelve los milisegundos desde la época
var unixtime = parseInt(unixtime_ms / 1000);
</script>
</p>
<p>
<?php
$option = ['prominent' => 'timestamp'];
?>
<b>Hora actual del sistema:</b> <?php ui_print_timestamp(time(), false, $option); ?>
<br />
<b>Hora actual de la base de datos:</b>
<?php
global $config;

switch ($config['dbtype']) {
    case 'mysql':
        $timestamp = db_process_sql('SELECT UNIX_TIMESTAMP();');
        $timestamp = $timestamp[0]['UNIX_TIMESTAMP()'];
    break;

    case 'postgresql':
        $timestamp = db_get_value_sql("SELECT ceil(date_part('epoch', CURRENT_TIMESTAMP));");
    break;

    case 'oracle':
        $timestamp = db_process_sql("SELECT ceil((sysdate - to_date('19700101000000','YYYYMMDDHH24MISS')) * (86400)) as dt FROM dual");
        $timestamp = $timestamp[0]['dt'];
    break;
}

ui_print_timestamp($timestamp, false, $option);
?>
<br />
<b>Hora de su navegador:</b> <script type="text/javascript">document.write (date);</script>
</p>
