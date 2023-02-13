<?php

require_once '../../vendor/autoload.php';

use Artica\PHPChartJS\Factory;

$factory = new Factory();
/** @var \Artica\PHPChartJS\Chart\Line $line */
$line = $factory->create($factory::LINE);

// Set labels
$line->labels()->exchangeArray(["January", "February", "March", "April", "May", "June", "July"]);

// Add Datasets
$dataSet = $line->createDataSet();
$dataSet->setLabel('My First dataset')
        ->setFill(false)
        ->setLineTension(0.1)
        ->setBackgroundColor('rgba(75,192,192,0.4)')
        ->setBorderColor('rgba(75,192,192,1)')
        ->setBorderCapStyle('butt')
        ->setBorderDash([])
        ->setBorderDashOffset(0.0)
        ->setBorderJoinStyle('miter')
        ->setPointBorderColor('rgba(75,192,192,1)')
        ->setPointBackgroundColor('#fff')
        ->setPointBorderWidth(1)
        ->setPointHoverRadius(5)
        ->setPointHoverBackgroundColor('rgba(75,192,192,1)')
        ->setPointHoverBorderColor('rgba(220,220,220,1)')
        ->setPointHoverBorderWidth(2)
        ->setPointRadius(1)
        ->setPointHitRadius(10)
        ->setSpanGaps(false)
        ->data()->exchangeArray([65, 59, 80, 81, 56, 55, 40]);
$line->addDataSet($dataSet);

// Set mode to stacked
$scales = $line->options()->getScales();
$scales->getYAxes()->append($scales->createYAxis()->setStacked(true))
       ->append($scales->createYAxis()->setPosition('right')->setId('y2'));

// Add even more data
$apples = $line->createDataSet();
$apples->setLabel('apples')
       ->setYAxisID('y2')
       ->setBackgroundColor('rgba( 0, 150, 0, .5 )')
       ->data()->exchangeArray([12, 19, 3, 17, 28, 24, 7]);
$line->addDataSet($apples);

$oranges = $line->createDataSet();
$oranges->setLabel('oranges')
        ->setYAxisID('y2')
        ->setBackgroundColor('rgba( 255, 153, 0, .5 )')
        ->data()->exchangeArray([30, 29, 5, 5, 20, 3, 10]);
$line->addDataSet($oranges);

?>
<!doctype html>
<html lang="en">
<head>
    <title>Line</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>
</head>
<body>
<?php
// Render the chart
echo $line->render();
?>
</body>
</html>
