<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\HorizontalBar $bar */
$bar = $factory->create($factory::HORIZONTAL_BAR);

// Set labels
$bar->labels()->exchangeArray(["M", "T", "W", "T", "F", "S", "S"]);

// Add Datasets
$apples = $bar->createDataSet();
$apples->setLabel('apples')
       ->setBackgroundColor('rgba( 0, 150, 0, .5 )')
       ->data()->exchangeArray([12, 19, 3, 17, 28, 24, 7]);
$bar->addDataSet($apples);

$oranges = $bar->createDataSet();
$oranges->setLabel('oranges')
        ->setBackgroundColor('rgba( 255, 153, 0, .5 )')
        ->data()->exchangeArray([30, 29, 5, 5, 20, 3, 10]);
$bar->addDataSet($oranges);

$scales = $bar->options()->getScales();
$scales->getXAxes()->append($scales->createXAxis()->setStacked(true));
$scales->getYAxes()->append($scales->createYAxis()->setStacked(true));

?>
<!doctype html>
<html lang="en">
<head>
    <title>Horizontal bar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $bar->render();
?>
</body>
</html>
