<?php
/**
 * @package Include/FusionCharts
 */

// Page: FusionCharts.php
// Author: InfoSoft Global (P) Ltd.
// This page contains functions that can be used to render FusionCharts.


/**
 *  encodeDataURL function encodes the dataURL before it's served to FusionCharts.
 *If you've parameters in your dataURL, you necessarily need to encode it.
 * Param: $strDataURL - dataURL to be fed to chart
 * Param: $addNoCacheStr - Whether to add aditional string to URL to disable caching of data
 */
function encodeDataURL($strDataURL, $addNoCacheStr=false) {
    //Add the no-cache string if required
    if ($addNoCacheStr==true) {
        // We add ?FCCurrTime=xxyyzz
        // If the dataURL already contains a ?, we add &FCCurrTime=xxyyzz
        // We replace : with _, as FusionCharts cannot handle : in URLs
		if (strpos(strDataURL,"?")<>0)
			$strDataURL .= "&FCCurrTime=" . Date("H_i_s");
		else
			$strDataURL .= "?FCCurrTime=" . Date("H_i_s");
    }
	// URL Encode it
	return urlencode($strDataURL);
}


// datePart function converts MySQL database based on requested mask
// Param: $mask - what part of the date to return "m' for month,"d" for day, and "y" for year
// Param: $dateTimeStr - MySQL date/time format (yyyy-mm-dd HH:ii:ss)
function datePart($mask, $dateTimeStr) {
    @list($datePt, $timePt) = explode(" ", $dateTimeStr);
    $arDatePt = explode("-", $datePt);
    $dataStr = "";
    // Ensure we have 3 parameters for the date
    if (count($arDatePt) == 3) {
        list($year, $month, $day) = $arDatePt;
        // determine the request
        switch ($mask) {
        case "m": return (int)$month;
        case "d": return (int)$day;
        case "y": return (int)$year;
        }
        // default to mm/dd/yyyy
        return (trim($month . "/" . $day . "/" . $year));
    }
    return $dataStr;
}


// renderChart renders the JavaScript + HTML code required to embed a chart.
// This function assumes that you've already included the FusionCharts JavaScript class
// in your page.

// $chartSWF - SWF File Name (and Path) of the chart which you intend to plot
// $strURL - If you intend to use dataURL method for this chart, pass the URL as this parameter. Else, set it to "" (in case of dataXML method)
// $strXML - If you intend to use dataXML method for this chart, pass the XML data as this parameter. Else, set it to "" (in case of dataURL method)
// $chartId - Id for the chart, using which it will be recognized in the HTML page. Each chart on the page needs to have a unique Id.
// $chartWidth - Intended width for the chart (in pixels)
// $chartHeight - Intended height for the chart (in pixels)
function renderChart($chartSWF, $strURL, $strXML, $chartId, $chartWidth, $chartHeight) {
	//First we create a new DIV for each chart. We specify the name of DIV as "chartId"Div.			
	//DIV names are case-sensitive.

    // The Steps in the script block below are:
    //
    //  1)In the DIV the text "Chart" is shown to users before the chart has started loading
    //    (if there is a lag in relaying SWF from server). This text is also shown to users
    //    who do not have Flash Player installed. You can configure it as per your needs.
    //
    //  2) The chart is rendered using FusionCharts Class. Each chart's instance (JavaScript) Id 
    //     is named as chart_"chartId".		
    //
    //  3) Check whether we've to provide data using dataXML method or dataURL method
    //     save the data for usage below 
	if ($strXML=="")
        $tempData = "//Set the dataURL of the chart\n\t\tchart_$chartId.setDataURL(\"$strURL\")";
    else
        $tempData = "//Provide entire XML data using dataXML method\n\t\tchart_$chartId.setDataXML(\"$strXML\")";

    // Set up necessary variables for the RENDERCAHRT
    $chartIdDiv = $chartId . "Div";

    // create a string for outputting by the caller
	$render_chart = <<<RENDERCHART
	<!-- START Script Block for Chart $chartId -->
	<div id="$chartIdDiv" align="center">
		Chart.
	</div>
	<script type="text/javascript">	
		//Instantiate the Chart	
		var chart_$chartId = new FusionCharts("$chartSWF", "$chartId", "$chartWidth", "$chartHeight");
		$tempData
		//Finally, render the chart.
		chart_$chartId.render("$chartIdDiv");
	</script>	
	<!-- END Script Block for Chart $chartId -->
RENDERCHART;

  return $render_chart;
}


//renderChartHTML function renders the HTML code for the JavaScript. This
//method does NOT embed the chart using JavaScript class. Instead, it uses
//direct HTML embedding. So, if you see the charts on IE 6 (or above), you'll
//see the "Click to activate..." message on the chart.
// $chartSWF - SWF File Name (and Path) of the chart which you intend to plot
// $strURL - If you intend to use dataURL method for this chart, pass the URL as this parameter. Else, set it to "" (in case of dataXML method)
// $strXML - If you intend to use dataXML method for this chart, pass the XML data as this parameter. Else, set it to "" (in case of dataURL method)
// $chartId - Id for the chart, using which it will be recognized in the HTML page. Each chart on the page needs to have a unique Id.
// $chartWidth - Intended width for the chart (in pixels)
// $chartHeight - Intended height for the chart (in pixels)
function renderChartHTML($chartSWF, $strURL, $strXML, $chartId, $chartWidth, $chartHeight) {
    // Generate the FlashVars string based on whether dataURL has been provided
    // or dataXML.
    $strFlashVars = "&chartWidth=" . $chartWidth . "&chartHeight=" . $chartHeight ;
    if ($strXML=="")
        // DataURL Mode
        $strFlashVars .= "&dataURL=" . $strURL;
    else
        //DataXML Mode
        $strFlashVars .= "&dataXML=" . $strXML;

$HTML_chart = <<<HTMLCHART
	<!-- START Code Block for Chart $chartId -->
	<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase=http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"  width="$chartWidth" height="$chartHeight" id="$chartId">
		<param name="allowScriptAccess" value="always" />
		<param name="movie" value="$chartSWF"/>		
		<param name="FlashVars" value="$strFlashVars" />
		<param name="quality" value="high" />
		<embed src="$chartSWF" FlashVars="$strFlashVars" quality="high" width="$chartWidth" height="$chartHeight" name="$chartId" allowScriptAccess="always" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />
	</object>
	<!-- END Code Block for Chart $chartId -->
HTMLCHART;

  return $HTML_chart;
}

// boolToNum function converts boolean values to numeric (1/0)
function boolToNum($bVal) {
    return (($bVal==true) ? 1 : 0);
}

?>