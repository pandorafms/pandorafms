<?php
/**
 * @package Include/help/es
 */
?>
<h1>Formas de calcular el estado de la consola visual enlazada</h1>

<h3>Por defecto</h3>
<p>
    Calcula el estado a partir del estado de todos los elementos, como lo haría un agente.
</p>

<h3>Por peso</h3>
<p>
    Calcula el estado de los elementos que tienen asignados una consola visual, un módulo o un agente en relación a un porcentaje de elementos configurado por el usuario. Este porcentaje es el que tiene que superar el número de elementos de un estado no normal respecto al número de elementos tenidos en cuenta en el cálculo para que el ese estado cambie.
</p>

<p>
    Por ejemplo, dado un elemento con un porcentaje del 50% y una consola visual enlazada con 5 elementos:
</p>
<ul>
    <li>1 <i>critical</i>, 1 <i>warning</i> y 3 <i>normal</i> -> Estado <i>normal</i>.</li>
    <li>2 <i>critical</i>, 2 <i>warning</i> y 1 <i>normal</i> -> Estado <i>normal</i>.</li>
    <li>1 <i>critical</i>, 3 <i>warning</i> y 1 <i>normal</i> -> Estado <i>warning</i>.</li>
    <li>3 <i>critical</i>, 1 <i>warning</i> y 1 <i>normal</i> -> Estado <i>critical.</i></li>
    <li>1 <i>critical</i>, 1 <i>warning</i> y 3 <i>unknown</i> -> Estado <i>unknown</i>.</li>
</ul>

<p>
    Si varios estados superan el peso, la prioridad es igual que en el resto de cálculo de estados (<i>critical</i> > <i>warning</i> > <i>unknown</i>). Si no hay elementos para realizar el cálculo, el estado pasa a ser <i>unknown</i>.
</p>

<h3>Por elementos críticos</h3>
<p>
    Calcula el estado usando los elementos en estado <i>critical</i> y los porcentajes de los umbrales definidos por el usuario. Si el número de los elementos en estado <i>critical</i> respecto al número de elementos tenidos en cuenta en el cálculo supera el porcentaje asignado como <i>warning</i>, el estado pasa a ser <i>warning</i>. Lo mismo para el porcentaje asignado como <i>critical</i>, que además tiene preferencia.
</p>
