<h1>Módulo origen</h1>

<p>
En estos momentos la predicción sólo soporta dos tipos de módulos. Una predicción de datos numéricos, basada en márgenes de tiempo (definido como un intervalo en el nuevo módulo de predicción), o una detección de una desviación del comportamiento típico entre dos márgenes de tiempo, definido como 1/2 del intervalo definido. Cuanto más grande sea el margen de tiempo, mayor error de predicción tendrá, y se habrán de considerar más valores. Estos dos módulos se implementann como <i>generic_data</i> y <i>generic_proc</i>, respectivamente.
</p>

<h2>Creación de módulo de predicción</h2>

<p>
<b>La predicción necesita datos de, al menos, una semana para poder trabajar correctamente</b>. La predicción se realiza calculando valores medios del módulo durante un intervalo, en cuatro instantes: t1, t2, t3, t4. Siendo t1 el valor de hace una semana, t2 el valor de hace dos semanas, t3 el valor de hace tres semanas, y t4 el valor de hace cuatro semanas.
</p>

<h2>Creación de módulo de anomalías</h2>

<p>
Para calcular anomalías, también se calcula la desviación típica desde esas muestras con valores diferentes de 0, y después se compara el valor real con el valor predicho +/- la desviacion típica.
</p>