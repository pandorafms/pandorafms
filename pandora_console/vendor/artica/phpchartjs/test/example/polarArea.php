<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$colors  = ["#FF6384", "#4BC0C0", "#FFCE56", "#E7E9ED", "#36A2EB"];
$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\PolarArea $polarArea */
$polarArea = $factory->create($factory::POLAR_AREA);

// Set labels
$polarArea->labels()->exchangeArray(["Red", "Green", "Yellow", "Grey", "Blue"]);

// Add Datasets
$dataSet = $polarArea->createDataSet();
$dataSet->setLabel('My dataset')
        ->setBackgroundColor($colors)
        ->data()->exchangeArray([11, 16, 7, 3, 14]);
$polarArea->addDataSet($dataSet);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Polar area</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $polarArea->render();
?>
</body>
</html>
