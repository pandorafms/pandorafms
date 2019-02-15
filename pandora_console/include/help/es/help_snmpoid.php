<?php
/**
 * @package Include/help/es
 */
?>
<h1>OID SNMP</h1>

La OID SNMP del módulo. Si existe una MIB capaz de resolver el nombre en el servidor de red de <?php echo get_product_name(); ?>, entonces puede usar OID alfanumércias (ej. SNMPv2-MIB::sysDescr.0). Siempre se pueden usar OID numéricas (ej. 3.1.3.1.3.5.12.4.0.1), incluso si no hay una MIB específica.