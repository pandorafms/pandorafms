<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Chart\Bar;

$bar = new Bar();
$bar->setId('myChart');

// Set labels
$bar->labels()->exchangeArray(["M", "T", "W", "T", "F", "S", "S"]);

// Add Datasets
$apples = $bar->createDataSet();

$apples->setLabel("apples")
       ->setBackgroundColor("rgba( 0, 150, 0, .5 )")
       ->data()->exchangeArray([12, 19, 3, 17, 28, 24, 7]);
$bar->addDataSet($apples);

$oranges = $bar->createDataSet();
$oranges->setLabel("oranges")
        ->setBackgroundColor('rgba( 255, 153, 0, .5 )')
        ->data()->exchangeArray([30, 29, 5, 5, 20, 3, 10]);
$bar->addDataSet($oranges);
$bar->options()->setOnClick('myClickEvent');
?>
<!doctype html>
<html lang="en">
<head>
    <title>onClick</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $bar->render();
?>
<script>
  window.myClickEvent = function (event, dataSets) {
    var dataSet = dataSets[0],
      label = this.data.labels[dataSet._index],
      value1 = this.data.datasets[0].data[dataSet._index],
      value2 = this.data.datasets[1].data[dataSet._index];
    alert(label + ': ' + value1 + ', ' + value2);
  };
</script>
</body>
</html>
