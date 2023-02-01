<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;
use Artica\PHPChartJS\Options\ScatterOptions;

$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\Scatter $scatter */
$scatter = $factory->create($factory::SCATTER);
$scatter->setTitle('Scatter chart');

/** @var ScatterOptions $options */
$options = $scatter->options();
$xAxis   = $options->getScales()->createXAxis();
$xAxis->ticks()->setStepSize(1);
$yAxis = $options->getScales()->createYAxis();

$options->getScales()->getXAxes()->append($xAxis);
$options->getScales()->getYAxes()->append($yAxis);

// Set labels
$scatter->labels()->exchangeArray(["M", "T", "W", "T", "F", "S", "S"]);

// Add Datasets
$apples = $scatter->createDataSet();
$apples->setLabel('My first dataset')
       ->setBackgroundColor('rgba( 0, 150, 0, .5 )')
       ->setPointStyle('rect')
       ->setPointRadius(10);
$apples->data()->exchangeArray([
    ['x' => 0, 'y' => 0],
    ['x' => 0, 'y' => 1],
    ['x' => 0, 'y' => 2],
    ['x' => 0, 'y' => 3],
    ['x' => 0, 'y' => 4],
    ['x' => 0, 'y' => 5],
    ['x' => 0, 'y' => 6],
]);
$scatter->addDataSet($apples);

$oranges = $scatter->createDataSet();
$oranges->setLabel('My second dataset')
        ->setBackgroundColor('rgba( 255, 153, 0, .5 )');
$oranges->data()->exchangeArray([
    ['x' => 1, 'y' => 0],
    ['x' => 1, 'y' => 1],
    ['x' => 1, 'y' => 2],
    ['x' => 1, 'y' => 3],
    ['x' => 1, 'y' => 4],
    ['x' => 1, 'y' => 5],
    ['x' => 1, 'y' => 6],
]);
$scatter->addDataSet($oranges);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Scatter</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $scatter->render();
?>
</body>
</html>
