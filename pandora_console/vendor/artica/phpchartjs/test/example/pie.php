<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$colors = [
    'rgb(73,10,61)',
    'rgb(189,21,80)',
    'rgb(233,127,2)',
    'rgb(248,202,0)',
    'rgb(138,155,15)',
    'rgb(89,79,79)',
    'rgb(84,121,128)',
];

$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\Pie $pie */
$pie = $factory->create($factory::PIE);

// Set labels
$pie->labels()->exchangeArray([
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
]);

// Add Datasets
$apples = $pie->createDataSet();
$apples->setLabel('My First dataset')
       ->setBackgroundColor($colors)
       ->data()->exchangeArray([165, 59, 80, 81, 56, 55, 40]);
$pie->addDataSet($apples);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Pie</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $pie->render();
?>
</body>
</html>
