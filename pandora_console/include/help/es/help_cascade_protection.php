<h1>Protección en Cascada</h1>


<img src='../images/help/cascade_protection_ilustration.png'>
<br>
<p>
Esta opción se designa para evitar una "tormenta" de alertas que entren porque un grupo de agentes son inalcanzables. Este tipo de comportamiento ocurre cuando un dispositivo intermedio, como por ejemplo un router, está caido, y todos los dispositivos que están tras él no se pueden alcanzar. Probablemente estos dispositivos no estén caídos e incluso estos dispositivos estén trabajando junto con otro router, en modo HA. Pero si no hace nada, probablemente Pandora FMS piense que estén caídos porque no los pueden testar con un Remote ICMP Proc Test (un ping).

<br><br>

Cuando habilite  <i>cascade protection</i> en un agente, esto significa que si cualquiera de sus padres tiene una alerta CRÍTICA disparada, entonces las alertas del agente NO SERÁN disparadas. Si el padre del agente tiene un módulo en CRITICAL o varias alertas con menor criticidad que CRITICAL, las alertas del agente serán disparadas si deben hacerlo. La protección en cascada comprueba las alertas padre con criticidad CRITICAL, incluyendo las alertas de correlación asignadas al padre.

<br><br>

Si quiere usar un sistema avanzado de protección en cascada, sólo tiene que usar correlación entre padres sucesivos, y que sólo habilite la Protección en Cascada en los hijos.

</p>
