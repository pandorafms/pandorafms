<?php
/**
 * @package Include/help/es
 */
?>
<h1>Peso de estado del mapa asociado</h1>

<p>
Solo los elementos de tipo "imagen estática" pueden llevar esta opción. Mediante ella establecemos un porcentaje de elementos en un estado diferente al normal para que nuestro elemento enlazado a ese mapa recoja su estado.
</p>
<p>
Por ejemplo:
</p>
<p>
Si tenemos un mapa 1 con un elemento de tipo "imagen estática" que apunta a un mapa 2 y tiene su peso establecido en un 50%, solo verá modificado su estado si al menos la mitad de los elementos (elementos que deben devolver un estado tanto de agente, de módulo o de otra consola enlazada) del mapa 2 no están en un estado normal.
</p>
<p>
También podemos tener un elemento enlazado a una consola con 10 elementos que devuelvan un estado, que a su vez pueden tener elementos enlazados a otras consolas. Si nuestro elemento de la primera consola tiene establecido un peso del 20% es porque queremos que nos muestre un estado crítico (o de advertencia) si al menos 2 de los 10 elementos de la segunda consola tienen un estado crítico (o de advertencia). Del mismo modo si el peso fuese del 80%, se tendrán que tener 8 elementos de 10 en un estado no normal.
</p>