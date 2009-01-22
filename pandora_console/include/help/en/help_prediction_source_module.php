<h1>Source type</h1>

<p>
Right now prediction just supports two types of modules. A numeric data predition, based on a time margin (defined as an interval in the new prediction module), or a detection of a deviation of the typical behaviour between a time margin, defined as 1/2 of the defined interval. The longer the time margin is, the bigger error the prediction has, and more possible values must be considered. These two modules are implemented as <i>generic_data</i> and <i>generic_proc</i>, respectively.
</p>

<h2>Prediction module creation</h2>

<p>
<b>The prediction needs data from, at least, a week to work properly</b>. Prediction is done calculating average values of the module on a given interval, at four times: t1, t2, t3, t4. Being t1 a week ago value, t2 two weeks ago value, t3 three weeks ago value, and t4 four weeks ago value.
</p>

<h2>Anomaly module creation</h2>

<p>
To calculate anomalies, the typical deviation is also computed for those samples with values different from 0, and then the real value is compared with the predicted value +/- the typical deviation.
</p>
