<?php
/**
 * @package Include/help/es
 */
?>

<h1>Propagación de ACL's</h1>

La propagación de ACL está pensada para no tener que incluir en la definición de permisos de un usuario cada subgrupo que cuelga de un grupo. Veamos un ejemplo:
<br><br>
Supongamos que tenemos la siguiente jerarquía de grupo y subgrupos:
<br><br>
<pre>
   Clientes
     + Cliente A
     + Cliente B
     + Cliente C
     + Cliente D
</pre>
<br><br>
Queremos que un operador tenga acceso a todos los clientes (A,B,C y D), y a todos los que puedan estar incluidos en el grupo "Clientes" en el futuro. Al marcar la casilla "Propagar ACL's" al grupo Clientes, significa que cualquier usuario que tenga acceso al grupo Clientes podrá tener el mismo nivel de acceso a los subgrupos que lo contienen.

<h3>Propagación y ACL con tags</h3>

La propagación es compatible con el sistema de tags. Esto implica que la propagación de ACL afecta a todos los grupos propagando también sus tags, es decir, dará acceso a todos los subgrupos que cuelgan debajo del grupo padre verificando sus tags.

