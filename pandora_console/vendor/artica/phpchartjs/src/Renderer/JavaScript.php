<?php

namespace Artica\PHPChartJS\Renderer;

/**
 * Class JavaScript
 *
 * @package Artica\PHPChartJS\Renderer
 */
class JavaScript extends Renderer
{


    /**
     * Renders the necessary JavaScript for the chart to function in the frontend.
     *
     * @param integer|null $flags
     *
     * @return string
     */
    public function render($flags=null)
    {
        $script = [];
        // First, setup the canvas context
        $script[] = "var ctx = document.getElementById( \"{$this->chart->getId()}\" ).getContext( \"2d\" );";

        // Now, setup the chart instance
        $jsonRenderer = new Json($this->chart);
        $json = $jsonRenderer->render($flags);

        // Watermark.
        if (empty($this->chart->defaults()->getWatermark()) === false) {
          $script[] = 'const chart_watermark_'.$this->chart->getId().' = {
            id: "chart_watermark_'.$this->chart->getId().'",
            afterDraw: (chart) => {
              const image = new Image();
                image.src = "'.$this->chart->defaults()->getWatermark()->getSrc().'";
                if (image.complete) {
                  const image_height = '.($this->chart->defaults()->getWatermark()->getHeight() ?? 20).';
                  const image_width = '.($this->chart->defaults()->getWatermark()->getWidth() ?? 100).';
                  const ctx = chart.ctx;
                  let x = 0;
                  let y = 0;

                  switch ("'.$this->chart->defaults()->getWatermark()->getPosition().'") {
                    case "start":
                      x = 0;
                      break;
            
                    case "center":
                      x = (chart.width / 2) - image_width;
                      break;
            
                    default:
                    case "end":
                      x = chart.width - image_width;
                      break;
                  }

                  switch ("'.$this->chart->defaults()->getWatermark()->getAlign().'") {
                    default:
                    case "top":
                      y = 0;
                      break;
            
                    case "center":
                      y = (chart.height / 2) + image_height;
                      break;
            
                    case "bottom":
                      y = chart.height + image_height;
                      break;
                  }
                  
                  ctx.globalAlpha = 1;
                  ctx.drawImage(image, x, y, image_width, image_height);
                  ctx.globalAlpha = 1;
                } else {
                  image.onload = () => chart.draw();
                }
              }
            };';
          $script[] = 'Chart.register(chart_watermark_'.$this->chart->getId().');';
        }

        

        // Create chart.
        $script[] = 'try {';
        $script[] = "  var chart = new Chart( ctx, {$json} );";

        // Defaults values.
        $script[] = '  Chart.defaults.font.size = '.($this->chart->defaults()->getFonts()->getSize() ?? 8).';';
        $script[] = '  Chart.defaults.font.family = "'.($this->chart->defaults()->getFonts()->getFamily() ?? 'Lato, sans-serif').'";';
        $script[] = '  Chart.defaults.font.style = "'.($this->chart->defaults()->getFonts()->getStyle() ?? 'normal').'";';
        $script[] = '  Chart.defaults.font.weight = "'.($this->chart->defaults()->getFonts()->getWeight() ?? '').'";';

        $script[] = '} catch (error) {';
        $script[] = '  console.error(error);';
        $script[] = '}';

        $scriptString = implode("\n", $script);

        return $scriptString;

        // Return the script
        return <<<JS
          window.onload=(function(oldLoad){return function(){
            if (oldLoad) {
              oldLoad();
            }
            
            {$scriptString};
            
            if (! window.hasOwnProperty('chartInstances')) {
              window.chartInstances = {};
            }
            
            window.chartInstances['{$this->chart->getId()}'] = chart;
          }})(window.onload);
        JS;
    }


}
