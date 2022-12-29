<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\Radar $radar */
$radar = $factory->create($factory::RADAR);

// Set labels
$radar->labels()->exchangeArray([
    "Eating",
    "Drinking",
    "Sleeping",
    "Designing",
    "Coding",
    "Cycling",
    "Running",
]);

// Add Datasets
$dataSet1 = $radar->createDataSet();
$dataSet1->setLabel('My first dataset')
         ->setBackgroundColor('rgba(179,181,198,0.2)')
         ->setBorderColor('rgba(179,181,198,1)')
         ->setPointBackgroundColor('rgba(179,181,198,1)')
         ->setPointBorderColor('#fff')
         ->setPointHoverBackgroundColor('#fff')
         ->setPointHoverBorderColor('rgba(179,181,198,1)')
         ->data()->exchangeArray([65, 59, 90, 81, 56, 55, 40]);
$radar->addDataSet($dataSet1);

$dataSet2 = $radar->createDataSet();
$dataSet2->setLabel('My second dataset')
         ->setBackgroundColor('rgba(255,99,132,0.2)')
         ->setBorderColor('rgba(255,99,132,1)')
         ->setPointBackgroundColor('rgba(255,99,132,1)')
         ->setPointBorderColor('#fff')
         ->setPointHoverBackgroundColor('#fff')
         ->setPointHoverBorderColor('rgba(255,99,132,1)')
         ->data()->exchangeArray([28, 48, 40, 19, 96, 27, 100]);
$radar->addDataSet($dataSet2);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Radar</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $radar->render();
?>
</body>
</html>
