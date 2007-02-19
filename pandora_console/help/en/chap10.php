<?php

// Pandora - the Free monitoring system
// ====================================
// Copyright (c) 2004-2006 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2005-2006 Artica Soluciones Tecnológicas S.L, info@artica.es
// Copyright (c) 2004-2006 Raul Mateos Martin, raulofpandora@gmail.com
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

?>
<html>
<head>
<title>Pandora - The Free Monitoring System Help - IX. Pandora Configuration</title>
<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css">
<style>
div.logo {float:left;}
div.toc {padding-left: 200px;}
div.rayah {clear:both; border-top: 1px solid #708090; width: 100%;}
</style>
</head>

<body>
<div class='logo'>
<img src="../../images/logo_menu.gif" alt='logo'><h1>Pandora Help v1.2</h1>
</div>
<div class="toc">
<h1><a href="chap8.php">9. Pandora Configuration</a> « <a href="toc.php">Table of Contents</a></h1>

</div>
<div class="rayah">
<p align='right'>Pandora is a GPL Software Project. &copy; Sancho Lerena 2003-2006, David villanueva 2004-2005, Alex Arnal 2005, Ra&uacute;l Mateos 2004-2006.</p>
</div>

<a name="10"><h1>10. Reporting</h1></a>

<p>The objective of the reporting module is to represent the information stored in the Pandora FMS's database in a human-readable and structured way. This module allows the user to design a report structure, and to execute it to obtain a PDF or HTML document.</p>

<p>The process of building a report consists of two steps:</p>

<p><b>Design the report structure:</b> every report is made of a serie of <b>report components</b> (RC). In this step, the user decides whichs RC's will be included and in which order. Every RC can be configured to suit the user needs. Exemples of RC's are a paragraph (in which the user can configure the text that will be written) or a Pandora FMS graph (where any module can be represented in the time range desired). The output of this step is a XML file that can be downloaded for further use.</p>

<p><b>Execution of a report:</b> in this step a XML file configured in the last step is used. The Pandora Web Console parses the database information with the structured desired by the user, and offers a human-readable document for download. Examples of report final formats include PDF and HTML.</p>

<p>All these steps can be performed through the Reporting option in the Pandora FMS's Operation Menu. In the next sections, the use of this web page is explained. This sections are:</p>

<ul>
<li>Report structure
<li>Modify report
<li>Manage report
</ul>


<h2><a name="101">10.1. Report structure</a></h2>

<p>The objectives of this section are (a) to visualize the structure of the working report, and (b) to modify the parameters of each component. The changes made must be applied with the <b>Submit changes</b> button before executing the report or downloading the XML structure file.</p>

(xx figura de la estructura cerrada)

<p>The report structure exactly matches the XML file structure. When first visualized, it consist of a serie of lines that begin with an arrow (xx imagen de la flecha). Each line represents a <b>Report Component</b> (RC), and can be extended clicking on them once.</p>

(xx figura con un componente extendido))

<p>Each RC has two groups of parameters:</p>

<p>- <b>the configuration of the RC</b> that determines how the data will be represented in the final report. Examples of RC parameters can be el title of a list or the size of the columns of a table.

<p>- the configuration of the <b>Data Component</b> The Data Component (DC) is the component that supplies to the RC the data to be represented. Examples of DC can be the <b>DC_listmoduledata</b> that queries the Pandora FMS's database for a list of module data in a range of time. DC parameters to be configured in this example includes agent name, module name and time range.</p>

<p>There is a special component that is represented the first in the Report structure section: the <b>default values</b> component. The objective of this component is to configure default values used for parameters of a RC or DC. For example, if we are going to include several Pandora Graphs components (DC_pandoragraph) in our report, we could configure here a common time range used for all of them. In this case, it will not be necessary to configure the time range in every individual DC_pandoragraph. The default value can be overridden by specifying the parameter value in a particular RC or DC.</p>

(xx figura con el default values extendido con un DC_pandoragraph)

<p><b>Remember!</b> apply your changes with <b>Submit changes</b> button before executing or downloading a report. Several changes can be submited at once.</p>


<h2><a name="102">10.2. Modify report</a></h2>

<p>The objective of this section is to make major changes to the report structure. These major changes includes: Add/Delete default values for a component, Add a report component, and Delete a report component.</p>

<p>For making a major changes, first configure it with the form of this section, and, second, apply your changes by clicking in the <b>Submit changes</b> button.</p>

<h3>Add/Delete default values for a component</h3>

(xx figura)

<p>By selecting a Report Component (RC) or Data Component (DC), the user can toggle its appereance in the <b>default values</b> section of the report. For example, if there is not default values configured for the RC RC_paragraph, select it and submit the change so this component could be visible in the <b>default values</b> part of the Report Structure section of this web page. Select it and submit it again to delete it.</p>

<h3>Add a report component</h3>

(xx figura)

<p>Use this fields to add a new RC to the report structure.</p>

<p>The position can be configured with the Before/After and Position fields. The Position field makes reference to the number that determines the position of each RC in the Report Structure section. If let blank, the RC will be added at the end of the list</p>

<p>The RC and DC fields represent the pair RC-DC to be added. Please note that there RC that requires a DC (like the RC_list component), but there are ones that does not require it (f.ex. the RC_header component). Also note that there are several possible combitations of RC-DC, for example, RC_list with DC_listmoduledata or RC_listpie with DC_listmoduledata.</p>

<h3>Delete a report component</h3>

(xx figura)

<p>Guess what ...  Exactly! It deletes the specified RC from the report structure.</p>

<p><b>Remember!</b> apply your changes with <b>Submit changes</b> button before executing or downloading a report. Several changes can be submited at once.</p>


<h2><a name="103">10.3. Manage report</a></h2>

<p>The objective of this section is to provide the features required to:</p>

<ul>
<li>download the XML file that stores the structure and configuration of a report: the report template
<li>upload a previously downloaded report template
<li>execute the current XML report template
</ul>

<p>The execution of a report template will provide a final report in the output format chosen by the user, like PDF or HTML. This report will be offered for download compressed and packaged in ZIP format.</p>

<p>The XML files are regular text files that can be edited by the user with an external editor. This text file uses ISO-8859-1 (latin1) charset, so do not be confused if you see some 'UTF-8' in the XML internal configuration.</p>


<h2><a name="104">10.4. Important notes</a></h2>

<p>The following notes will easy the use of the Pandora FMS's Reporting module, so read them carefully.</p>

<h3>headers and footers</h3>

<p>These are not what they seem. The RC_header component configure the initial parameters of the final report, while the RC_footer makes the closing steps. These two components are optional or required depending of the output format used. For example, for a HTML report the RC_header writes the &lt;HTML&gt; and &lt;/HTML&gt; tags, that are not required by most browsers.</p>

<p>It is recommended that every report begins with a RC_header and ends with a RC_footer.</p>


<h3>defaults for RC_report</h3>

<p>The defaults for this component are <b>required</b> and must not be deleted. The <b>output format</b> parameter must be configured</p>


<h2><a name="105">Suggested steps in order to build a report</a></h2>

<ol>
<li>configure the <b>output format</b> of the RC_report default value in the Report Structure section
<li>add a RC_header as first RC
<li>add all the RC and DC you need for your report
<li>add a RC_footer as last RC
<li>configure all RC's and DC's
<li>download XML file and store it in your local machine
<li>execute the report and debug possible errors
</ol>


<h2><a name="106">10.5. Components explained</a></h2>

(xx figuras de cada uno)


<h3>RC_report</h3>

<p>Configures general properties of the report like its <b>output format</b>.</p>

<p>Should be included <b>only</b> in the defaults values section, not as a RC in the report structure.</p>


<h3>RC_header</h3>

<p>Configures the header of the document, parameters related to the output format configuration and some general parameters of the report like Author, Title, etc.</p>

<p>RC_header must be the first RC in the report structure</p>

<p>This RC does not requires a DC</p>


<h3>RC_footer</h3>

<p>Closes the report and perform tasks related with the output format, like writing the &lt;/HTML&gt; tags in a HTML document.</p>

<p>This RC must be the last RC in the report structure</p>

<p>This RC does not requires a DC</p>

<p>No configuration is required for this component</p>


<h3>RC_section</h3>

<p>Opens a section in the report with a title in a remarked format. Every section must be configured with a level, where level 1 is the main ordering of chapters, level 2 regards to the 1.x numbering, and level 3 regards to the 1.x.y.</p>

<p>This RC does not requires a DC</p>


<h3>RC_paragraph</h3>

<p>This RC writes a text paragraph in the final report.</p>

<p>A DC_paragraph is required to be used with RC_paragraph</p>

<p>No configuration is required for this component</p>


<h3>DC_paragraph</h3>

<p>This DC only takes a text to be written with RC_paragraph.</p>

<p> A RC_paragraph is required to be used with DC_paragraph</p>


<h3>RC_pandoragraph</h3>

<p>Plot the data offered by a DC_pandoragraph component as a Pandora Graph.</p>

<p>A DC_pandoragraph is required  to be used with RC_pandoragraph</p>

<p>No configuration is required for this component</p>


<h3>DC_pandoragraph</h3>

<p>Queries the Pandora FMS database for the data of a module in a determined time range. DC_pandoragraph processes this information and offers it to a RC_pandoragraph to be plotted and included in the report.</p>

<p>A RC_pandoragraph is required to be used with DC_pandoragraph</p>

<p>Start and End dates accept the following key words:  NOW, HOUR, DAY, WEEK and MONTH


<h3>DC_listmoduledata</h3>

<p>Queries the Pandora FMS database for the data of a module in a determined time range. DC_listmoduledata processes this information to produce a list to be processed by RC's like RC_list or RC_listpie.</p>

<p>When <b>include timestamp</b> is selected, the first column will be the timestamp</p>

<p>When <b>include count</b> is selected, a last column will be added that counts the occurence of every data.</p>

<p>It is <b>NOT</b> recommended to select both, <b>include timestamp</b> and <b>include count</b>.</p>

<p>The column between the timestamp and the count is, by default, the module data.</p>

<p>More columns can be produced by introducing titles and regular expressions in the <b>regular expression defined columns</b> fields. Please note that:</p>

<ul>
<li>these are perl regular expressions
<li>both, title and regular expressions are required, but it is not required to fill all five columns
<li>if none regular expression is defined, the list will only contain only one data column: the original data
<li>if at least one column is selected, the original data will not be included. To include it, use the &quot;/.*/&quot; regular expression.
<li>all regular expressions must begin with <b>/</b> and end with <b>/</b>
</ul>


<h3>RC_list</h3>

<p>Takes a RC_list data structure from, for example, DC_listmoduledata, and writes a table in the final report.</p>

<p>Relative column widths can be configured. Write a string with the column width weights separated by <b>:</b> . For example, the string <b>1:3</b> configures two columns, the first taking the 25% of the space and the second taking the 75%.</p> 

<p>A DC_listmoduledata is required to be used with RC_list.</p>


<h3>RC_listpie</h3>

<p>Takes a RC_list data structure from, for example, DC_listmoduledata with <b>include count</b>, and plot a pie chart of the data counts.</p>

<p>Note that the DC_listmoduledata MUST:</p>
<ul>
<li>has <b>include timestamp</b> deselected
<li>has <b>include count</b> activated
<li>no regular expression defined columns activated
</ul>

<p>A DC_listmoduledata is required to be used with RC_listpie.</p>


</body>
</html>