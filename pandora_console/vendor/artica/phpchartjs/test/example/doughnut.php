<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$colors  = [
    'rgb(73,10,61)',
    'rgb(189,21,80)',
    'rgb(233,127,2)',
    'rgb(248,202,0)',
    'rgb(138,155,15)',
    'rgb(89,79,79)',
    'rgb(84,121,128)',
];
$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\Doughnut $doughnut */
$doughnut = $factory->create($factory::DOUGHNUT);

// Set labels
$doughnut->labels()->exchangeArray([
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
]);

// Add Datasets
$apples = $doughnut->createDataSet();
$apples->setLabel('apples')
       ->setBackgroundColor($colors)
       ->data()->exchangeArray([12, 19, 3, 17, 28, 24, 7,]);
$doughnut->addDataSet($apples);

$oranges = $doughnut->createDataSet();
$oranges->setLabel('oranges')
        ->setBackgroundColor($colors)
        ->data()->exchangeArray([30, 29, 5, 5, 20, 3, 10,]);
$doughnut->addDataSet($oranges);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Doughnut</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $doughnut->render();
?>
</body>
</html>
