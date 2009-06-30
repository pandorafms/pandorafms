<?php
/*
   FUSIONCHARTS FREE API PHP CLASS 
   Author  :  Infosoft Global Pvt. Ltd. 
   version :  FREE
   Company :  Infosoft Global Pvt. Ltd. 
   
   *  Version: 1.0.1 (11 December 2008) [ Fix PHP Short Tag, Function addDatasetsFromDatabase 
   *                                    Modifiaction for Transposed ,
   *                                    Fix Transparent setting, FusionCharts.php insight Code  
   *                                    setInitParam Function Add, addColors Function Add,
   *                                    encodeXMLChars Function Add ]
   
   FusionCharts Class easily handles All FusionCharts XML Structures like
   graph, categories, dataset, set, Trend Lines, [vline, styles (for Future)] etc.
   It’s easy to use, it binds data into FusionCharts XML Structures
   
 */

class FusionCharts{

	var $chartType;               # Chart Friendly Name
	var $chartID;				  # ID of the Chart for JS interactivity(optional)
	var $SWFFile; 				  # Name of the required FusionCharts SWF file 
	var $SWFPath;                 # relative path of FusionCharts SWF files
	var $width;                   # FusionCharts width
	var $height;                  # FusionCharts height

	# attribute Delimiter
	var $del;

    # Chart XML string 
    var $strXML; 
	
	# Chart Series Types : 1 => single series, 2=> multi-series, 5=>Gantt (
	# For Future Use : 3=> scatter and bubble, 4=> MSStacked
     var $seriesType;               

	# Charts Atribute array 
	var $chartParams = array();     #List of Chart Parameters
	var $chartParamsCounter;		#Number of Chart parameters
	
	var $categoriesParam;           # Categories Parameter Setting 
	var $categoryNames = array();   # Category array for storing Category set
	var $categoryNamesCounter;      # Category array counter
	
	var $dataset = array();         # dataset array
	var $datasetParam = array();    # dataset parameter setting array
	var $datasetCounter;            # dataset array counter
	var $setCounter;                # set array counter 


    # trendLines array
    var $trendLines = array();      # trendLines array
	var $tLineCounter;              # trendLines array counter

    #chart messages
	var $chartMSG;

	var $chartSWF = array();		# Charts SWF array
	var $arr_FCColors = array(); 	# Color Set to be applied to dataplots
	var $UserColorON;               # User define color define true or false
    var $userColorCounter;
	
	// Cache Control
	var $noCache;

    var $DataBaseType;             # DataBase Type
	
	var $encodeChars;               # XML for dataXML or dataURL
	
#############============ For Gantt Chart  ============================
    # Gantt categories
	var $GT_categories = array();
	var $GT_categories_Counter;
	var $GT_categoriesParam = array();
	 
	var $GT_subcategories_Counter; 
	
	# Gantt processes
	var $GT_processes = array();
	var $GT_processes_Counter;
	var $GT_processes_Param;
	
	# Gantt Tasks
	var $GT_Tasks = array();
	var $GT_Tasks_Counter;
	var $GT_Tasks_Param;
	
	# Gantt Connectors
	var $GT_Connectors = array();
	var $GT_Connectors_Counter;
	var $GT_Connectors_Param;

	# Gantt Milestones
	var $GT_Milestones = array();
	var $GT_Milestones_Counter;
	var $GT_Milestones_Param;
	
	# Gantt datatable
	var $GT_datatable = array();
	var $GT_datatable_Counter;
	var $GT_datatableParam;
	var $GT_dataColumnParam = array();
	 
	var $GT_subdatatable_Counter; 
	
	#------- For Futute Use (start)----------------	
	# Gantt legend
	var $GT_legend = array();
	var $GT_legend_Counter;
	var $GT_legend_Param;
	#------- For Futute Use (end)----------------
	 
	var $wMode;
	
	# Advanced Chart settings
	var $JSC = array();
	
#############============ For Future Use (start)============================
	# dataset for MSStackedColumn2D
	
	var $MSSDataset = array();       # dataset array for MSStackedColumn2D
	var $MSSDatasetParams = array();  # MSSDataset parameter setting
	
	var $MSSDatasetCounter;          # MSSDataset array counter
     var $MSSSubDatasetCounter;       # ms sub dataset array counter
	var $MSSSetCounter;              # msset array counter
	
	# lineset 
	var $lineSet = array();         # lineSet array
	var $lineSetParam = array();    # lineSet Parameter setting array
	  
	var $lineCounter;               # line array counter 
	var $lineSetCounter;            # lineset array counter
	var $lineIDCounter;             # lineID counter;
	
	# vtrendLines array
    var $vtrendLines = array();     # vtrendLines array
	var $vtLineCounter;             # vtrendLines array counter

    # style array
    var $styles = array();           # styles array
    var $styleDefCounter;                # define counter
	var $styleAppCounter;                # apply counter
	

#############============ For Future Use (end)============================

	 
	 # FusionCharts Constructor, its take 4  Parameters.
	 # when we create object of FusionCharts, then Constructor will auto run and initialize 
	 # chats array parameter like chartType, width, height, chartsID
	 function FusionCharts($chartType="column2d",$width="400",$height="300",$chartID="",$isTransparent=""){
		
		  $this->wMode=$isTransparent;
		  # Setting All Charts Array
		  $this->setChartArrays();
		  
		  #initialise colorList
		  $this->colorInit();
		  
		  # Setting Chart name
		  $this->chartType=strtolower($chartType);
		  # Getting Charts Series Type
		  $this->getSeriesType();		
		  
		  # Setting Charts Width and Height
		  $this->width=$width;
		  $this->height=$height;
		  
		  # Setting ChartID, Defult is Charts Name
		  if ($chartID==""){
		  	$chartCounter=@$_SESSION['chartcount'];
			if($chartCounter<=0 || $chartCounter==NULL){
				$chartCounter=1;
			}
			
		    $this->chartID=$chartType . $chartCounter;
			$_SESSION['chartcount']=++$chartCounter;
			
		  }else{
		    $this->chartID=$chartID;
		  }
	       	     
		  # Setting Defult Parameter Delimiter  to ';'
		  $this->del=";";
		
		  # Setting Default SWF Path
		  $this->SWFPath="";
		  $this->SWFFile=$this->SWFPath . "FCF_" . $this->chartSWF[$this->chartType][0]  . ".swf";
		  # Initialize categoriesParam 
		  $this->categoriesParam="";
		  $this->categoryNamesCounter=1;
		  
		  # Creating Category Array
		  $this->createCategory($this->categoryNamesCounter);
		
		  # Initialize Dataset Variables
	      $this->datasetCounter=0;
          $this->setCounter= 0;
		  if($this->seriesType>1){
		    $this->setCounter++;
		  }
		
		  # Initialize  MSSDataset Variables
		  if($this->seriesType==4){
		    $this->MSSDatasetCounter=0;
		    $this->MSSSubDatasetCounter=0;
	        $this->MSSSetCounter=0;
			
			$this->lineCounter=0;
            $this->lineSetCounter=0;
			$this->lineIDCounter=0;
          }
		  
		  # vTrendLines Array inisialize
		  if($this->seriesType==3){
		    $this->vtLineCounter=1;
		    $this->createvTrendLines($this->vtLineCounter);
	      }
		  
		  # TrendLines Array inisialize
		  $this->tLineCounter=1;
		  $this->createTrendLines($this->tLineCounter);
		
		  # Styles Array inisialize
		  $this->styleDefCounter=1;
		  $this->styleAppCounter=1;
		  $this->createStyles("definition");
		  $this->createSubStyles("definition","style");
		  $this->createSubStylesParam("definition","style",$this->styleDefCounter);
        
		  $this->GT_categories_Counter=0;
	 	  $this->GT_subcategories_Counter=0;
		  
		  $this->GT_processes_Counter=0;
		  $this->GT_processes_Param="";
		  
		  $this->GT_Tasks_Counter=0;
	      $this->GT_Tasks_Param="";
		 	
		  # Gantt Connectors
	      $this->GT_Connectors_Counter=0;
	      $this->GT_Connectors_Param="";
		  
		  # Gantt datatable
		  $this->GT_datatable_Counter=0;
	      $this->GT_datatableParam="";
	      $this->GT_subdatatable_Counter=0;
		  
		  # Gantt legend
		  $this->GT_legend_Counter=0;
	      $this->GT_legend_Param="";
				  
		  $this->chartMSG="";
		  # XML store Variables 
		  $this->strXML="";
		  
		  $this->UserColorON = false;
		  $this->userColorCounter=0;
		  
		  $this->noCache=false;
		  $this->DataBaseType="mysql";
		  
		  // JS Constructor
		  $this->JSC["debugmode"]=false;       # debugmode default is false
		  $this->JSC["registerwithjs"]=false;  # registerwithJS default is false
	      $this->JSC["bgcolor"]="";            # bgcolor default not set
	      $this->JSC["scalemode"]="noScale";   # scalemode default noScale
	      $this->JSC["lang"]="EN";             # Language default EN
		  
		  // dataXML type encode
		  $this->encodeChars=true;
		  
	 }
	 

##------------ PUBLIC FUNCTIONS ----------------------------------------------------------------
     # Special Character
     function encodeXMLChars($option=true){
	     $this->$encodeChars=$option;
	 } 
	 # Setting Parameter Delimiter, Defult Parameter Separator is ";"
	 function setParamDelimiter($strDel){
	   $this->del=$strDel;
	 }
	 
	 # Database type set like ORACLE and MYSQL
	 function setDataBaseType($dbType){
	    $this->DataBaseType=strtolower($dbType);
	 }
	 
	 # Setting path of SWF file. file name like FCF_Column3D.swf. where FCF_ is common for all SWF file
	 function setSWFPath($SWFPath){
	   $this->SWFPath=$SWFPath;
	   $this->SWFFile=$this->SWFPath . "FCF_" . $this->chartSWF[$this->chartType][0]  . ".swf";
	 }
	 
	 # We can add or change single Chart parameter by setChartParam function
	 # its take Parameter Name and its Value
	 function setChartParam($paramName, $paramValue){
	   $this->chartParams[$paramName]=$this->encodeSpecialChars($paramValue);
	 }
	 
	 # We can add or change Chart parameter sets by setChartParams function
	 # its take parameterset [ caption=xyz caption;subCaption=abcd abcd abcd;xAxisName=x axis;yAxisName=y's axis;bgColor=f2fec0;animation=1 ]
	 # Defult Parameter Separator is ";"
	 function setChartParams($strParam){
	   $listArray=explode($this->del,$strParam);
	   foreach ($listArray as $valueArray) {
	       $paramValue=explode("=",$valueArray,2);
		   if($this->validateParam($paramValue)==true){
		    $this->chartParams[$paramValue[0]]=$this->encodeSpecialChars($paramValue[1]);
		   }
	   }
	 }
	 
	 
	 # Setting Categories Parameter into categoriesParam variables
	 function setCategoriesParams($strParam){
	   
	   $this->categoriesParam .= $this->ConvertParamToXMLAttribute($strParam);
	 }
	 
	 
	 # Function addCategoryFromDatabase adding Category from dataset
	 function addCategoryFromDatabase($query_result, $categoryColumn){
		if($this->DataBaseType=="mysql"){	 
			 # fetching recordset till eof
			 while($row = mysql_fetch_array($query_result)){
				# add category
				$this->addCategory($row[$categoryColumn],"","" );
			 } 
		}elseif($this->DataBaseType=="oracle"){
		     # fetching recordset till eof
			 while(OCIFetchInto($query_result, $row, OCI_ASSOC)){
				# add category
				$this->addCategory($row[$categoryColumn],"","" );
			 } 
		
		}	 
	 }
	 
	 # Function addCategoryFromArray adding Category from Array
	 function addCategoryFromArray($categoryArray){
	         # convert array to category set
			 foreach ($categoryArray as $value) {
			  # adding category
			  $this->addCategory($value);
			 }
	 }
	 
	 # Function for create set and catagory, dataset , set from array
	 function addChartDataFromArray($dataArray, $dataCatArray=""){
		if(is_array($dataArray)){
			if ($this->seriesType==1){
			   # Single series Array
			   # aa[..][..]="name" aa[..][..]="value"
			   foreach($dataArray as $arrayvalue){
				 if(is_array($arrayvalue)){
				   $this->addChartData($arrayvalue[1],"name=" . $arrayvalue[0] );	   
				 }
			   } 		
			}else{
			   # Multi series Array
			   if(is_array($dataCatArray)){
				   foreach($dataCatArray as $value){
					   $this->addCategory($value);
				   }
			   }
			   foreach($dataArray as $arrayvalue){
			     if(is_array($arrayvalue)){
				   $i=0;
				   $aaa[0]="";$aaa[1]="";
				   foreach($arrayvalue as $value){
				     if($i>=2){
					   	$this->addChartData($value);
					 }else{
					    $aaa[$i]=$value;
					 }				 					
				     if($i==1){
					   $this->addDataset($aaa[0],$aaa[1]);
					 }
					 $i++;
					 
				   }
				 } 
			   }
			}
		}	
	 }
	 
	 # Function addCategory adding Category and vLine element
	 function addCategory($label="",$catParam="",$vlineParam = "" ){
	 	 $strCatXML="";
		 $strParam="";
		 $label=$this->encodeSpecialChars($label);
		 # cheking vlineParam equal blank
		 if($vlineParam==""){
		   # cheking catParam not blank
		   if($catParam!=""){

		       $strParam = $this->ConvertParamToXMLAttribute($catParam);

		    }
			# adding label and parameter set to category 
		   $strCatXML ="<category name='" . $label . "' " . $strParam . " />";
           
		 }else{
		   
		   $strParam = $this->ConvertParamToXMLAttribute($vlineParam);
		   
		   # adding parameter set to vLine
		   $strCatXML="<vLine " . $strParam . "  />"; 
		 }
		 # storing into categoryNames array
		 $this->categoryNames[$this->categoryNamesCounter]=$strCatXML;
		 # Increase Counter
		 $this->categoryNamesCounter++;
	 }
	
	 
	 # adding dataset array element
	 function addDataset($seriesName, $strParam=""){
	   $this->datasetCounter++;
       $this->createDataset($this->datasetCounter);
	   	   
	   $this->setCounter++;
	   $this->createDataValues($this->datasetCounter,"_" . $this->setCounter);	
	
	   $seriesName=$this->encodeSpecialChars($seriesName);	
	   # creating seriesName and dataset parameter set
	   $tempParam="";
	   $tempParam ="seriesName='" . $seriesName . "' ";
	   $tempParam .= $this->ConvertParamToXMLAttribute($strParam);
	   
	   $colorParam="";
	   $pos = strpos(strtolower($tempParam), " color");
	   if ($pos === false) {
	     $colorParam=" color='" . $this->getColor($this->datasetCounter-1) . "'";
	   }

		# setting  datasetParam array
		$this->datasetParam[$this->datasetCounter]=$tempParam . $colorParam;
		
	 }
	 
				   
	 # Function addChartData adding set data element
	 function addChartData($value="",$setParam="",$vlineParam = "" ){
	     $strSetXML="";
		 
		 # Choosing dataset depend on seriesType and getting XML set
		 if($this->seriesType>=1 and $this->seriesType<=2){
 		   
		   $strSetXML=$this->setSSMSDataArray($value,$setParam,$vlineParam);
		 
		 }elseif ($this->seriesType==3){
		 
		   $strSetXML=$this->setScatterBubbleDataArray($value,$setParam,$vlineParam); 
		 
		 }elseif (($this->seriesType==4)){
		 
		   $strSetXML=$this->setSSMSDataArray($value,$setParam,$vlineParam);
		 
		 }
		 
		 # Adding xml set to dataset array and Increase set counter
		 if ($this->seriesType==1){
		      $this->dataset[$this->setCounter]=$strSetXML;
		      $this->setCounter++;
		 }elseif($this->seriesType>1 and $this->seriesType<4){
		      $this->dataset[$this->datasetCounter]["_" . $this->setCounter]=$strSetXML;
		      $this->setCounter++;
		 }elseif($this->seriesType==4){
			  $this->MSSDataset[$this->MSSDatasetCounter][$this->MSSSubDatasetCounter][$this->MSSSetCounter]=$strSetXML;
			  $this->MSSSetCounter++;
		 }
	 }
	 
	 # The addDatasetsFromDatabase() function adds dataset and set elements from -
	 # database, by Default, from MySql recordset. You can use setDatabaseType() function -
	 # to set the type of database to work on.
	 function addDatasetsFromDatabase($query_result, $ctrlField, $valueField,$datsetParamArray="",$link=""){
			
			 # Initialize variables
			 $paramset="";
			 $tempContrl="";
			 if(is_array($datsetParamArray)==false){
			 	$datsetParamArray=array();
			 }
			 
			 # Calculate total no of array elements in datsetParamArray
			 $arrLimit=count($datsetParamArray);
			 $i=1;
			 $tempParam="";
			 if($this->DataBaseType=="mysql"){ 	
				 ##### For My SQL Connection
				 $FieldArray=explode($this->del,$valueField);
				 if(count($FieldArray)>1){
   				     ### Muli Series
					 # fetching recordset
					 while($row = mysql_fetch_array($query_result)){
					    # Add Category
						$this->addCategory($row[$ctrlField]);
					 }
					
					 $k=0;
					 # Add daatset for multiple fields
					 foreach ($FieldArray as $FieldName) {
					   					  
						   if($k<$arrLimit){
							  $tempParam = $datsetParamArray[$k];  
  						   }else{
							  $tempParam="";
						   }
						   # Add Dataset with adddataset() function
						   $this->addDataset($FieldName,$tempParam);
						   
						   # rewind query result
						   mysql_data_seek($query_result,0);
						   while($row = mysql_fetch_array($query_result)){ 
						   
						        # Generating URL link 
						        if($link==""){
							      $paramset="";
						        }else{
							      # Generating URL link from getLinkFromPattern
							      $paramset="link=" . urlencode($this->getLinkFromPattern($row,$link));
								}
								# add value to dataset
						        $this->addChartData($row[$FieldName], $paramset, "");
								
						   }
						  $k++; 
					 }
					
				  }else{			 
				 
					 ### Single Series
					 # fetching recordset
					 while($row = mysql_fetch_array($query_result)){
						   # Creating Control break depending on ctrlField
						   # if ctrlField value changes then dataset will be Generated
						   if ($tempContrl!=$row[$ctrlField]){
									if($i<=$arrLimit){
									  $tempParam = $datsetParamArray[$i-1];  
									}else{
									  $tempParam="";
									}
									# Add Dataset with adddataset() function
									$this->addDataset($row[$ctrlField],$tempParam);
									$tempContrl=$row[$ctrlField];
									$i++;
						   }
						# Generating URL link 
						   if($link==""){
							  $paramset="";
						   }else{
							  # Generating URL link from getLinkFromPattern
							  $paramset="link=" . urlencode($this->getLinkFromPattern($row,$link));
						   }
						   # add value to dataset
						   $this->addChartData($row[$valueField], $paramset, "");
						   
					}
			    }		
		  }elseif($this->DataBaseType=="oracle"){
				 # For Oracle Connection
				 # fetching recordset
				 while(OCIFetchInto($query_result, $row, OCI_ASSOC)){
					   # Create Control break depending on ctrlField
					   # if ctrlField value changes then dataset will be Generated
					   if ($tempContrl!=$row[$ctrlField]){
								if($i<=$arrLimit){
								  $tempParam = $datsetParamArray[$i-1];  
								}else{
								  $tempParam="";
								}
								# add Dataset 
								$this->addDataset($row[$ctrlField],$tempParam);
								$tempContrl=$row[$ctrlField];
								$i++;
					   }
					# Generating URL link 
					   if($link==""){
						  $paramset="";
					   }else{
						  # Generating URL link from getLinkFromPattern
						  $paramset="link=" . urlencode($this->getLinkFromPattern($row,$link));
					   }
					   # add value to dataset
					   $this->addChartData($row[$valueField], $paramset, "");
				}
		  }	 
	}
	 
	 # addDataFromDatabase funcion take 5 parameter like query_result, label field, value field 
	 # and initialize dataset variables and link
	 function addDataFromDatabase($query_result, $db_field_ChartData,$db_field_CategoryNames="", $strParam="",$LinkPlaceHolder=""){
	   	      	   
		$paramset="";		   
	   if($this->DataBaseType=="mysql"){	
		   # fetching recordset till eof
		   while($row = mysql_fetch_array($query_result)){
			
			          if($LinkPlaceHolder==""){
						  $paramset="";
					   }else{
						   # Getting link
						  $paramset="link=" . urlencode($this->getLinkFromPattern($row,$LinkPlaceHolder));
					   }
					   if ($strParam=""){
						 $strParam=$paramset;
					   }else{
						 $strParam .= ";" . $paramset; 
					   }
			
			 # covert to set element and save to $partXML
			 if($db_field_CategoryNames==""){
				$data=@$row[$db_field_ChartData];
				if($strParam!="")
					$this->addChartData($this->encodeSpecialChars($data),$strParam);
				else
				 $this->addChartData($this->encodeSpecialChars($data));
			}
			else{
				$data=@$row[$db_field_ChartData];
				$label=@$row[$db_field_CategoryNames];
				$this->addChartData($this->encodeSpecialChars($data),"name=" . $this->encodeSpecialChars($label) . ";" .$strParam,"" );
			}
         }
	   }elseif($this->DataBaseType=="oracle"){
		   # fetching recordset till eof
		   while(OCIFetchInto($query_result, $row, OCI_ASSOC)){
			
			          if($LinkPlaceHolder==""){
						  $paramset="";
					   }else{
						   # Getting link
						  $paramset="link=" . urlencode($this->getLinkFromPattern($row,$LinkPlaceHolder));
					   }
					   if ($strParam=""){
						 $strParam=$paramset;
					   }else{
						 $strParam .= ";" . $paramset; 
					   }
			
			 # covert to set element and save to $partXML
			 if($db_field_CategoryNames==""){
				$data=@$row[$db_field_ChartData];
				if($strParam!="")
					$this->addChartData($this->encodeSpecialChars($data),$strParam);
				else
				 $this->addChartData($this->encodeSpecialChars($data));
			}
			else{
				$data=@$row[$db_field_ChartData];
				$label=@$row[$db_field_CategoryNames];
				$this->addChartData($this->encodeSpecialChars($data),"name=" . $this->encodeSpecialChars($label) . ";" .$strParam,"" );
			}
	     }
       }
	 }
	 
	# setTLine create TrendLine parameter 
	function addTrendLine($strParam){
	   
	   $listArray=explode($this->del,$strParam);
	   foreach ($listArray as $valueArray) {
    	   $paramValue=explode("=",$valueArray,2);
		   if($this->validateParam($paramValue)==true){
		      $this->trendLines[$this->tLineCounter][$paramValue[0]]=$this->encodeSpecialChars($paramValue[1]);
		   }
	   }
	   $this->tLineCounter++;
	 }


	 #this function sets chart messages
	 function setChartMessage($strParam){
		$this->chartMSG="?";
				
		$listArray=explode($this->del,$strParam);
		foreach ($listArray as $valueArray) {
			$paramValue=explode("=",$valueArray,2);
			if($this->validateParam($paramValue)==true){
				$this->chartMSG.=$paramValue[0] . "=" . $this->encodeSpecialChars($paramValue[1]) . "&";
			}
		}
		$this->chartMSG=substr($this->chartMSG,0,strlen($this->chartMSG)-1);
	}

	 #### - This function is mostly for Future USE -----------------------------
	 # set JS constructor of FusionCharts.js
	 function setAddChartParams($debugMode, $registerWithJS=0, $c="", $scaleMode="", $lang=""){
	   $this->JSC["debugmode"]=$debugMode;           
	   $this->JSC["registerwithjs"]=$registerWithJS;  
	   $this->JSC["bgcolor"]=$c;                      
	   $this->JSC["scalemode"]=$scaleMode;            
	   $this->JSC["lang"]=$lang;  					  
	 	 
	 } 
	 
	 # The function SetInitParam() adds extra chart settings 
	 function setInitParam($tname,$tvalue){
        
		$trimName= strtolower(str_replace(" ","",$tname));
        $this->JSC[$trimName]=$tvalue;

	 }
	
	 # getXML render all class arrays to XML output
	 function getXML(){
				
		$this->strXML="";
		
		$strChartParam="";
		
		 
		$strChartParam=$this->getChartParamsXML();
		if($this->seriesType==1){
				   if(gettype(strpos($this->chartType,"line"))!="boolean"){
					  if(strpos($strChartParam,"lineColor")===false){
						$colorSet=$this->getColor(0);
						$this->setChartParams("lineColor=" . $colorSet );
					  }
					  
				   }
				   if(gettype(strpos($this->chartType,"area"))!="boolean"){
					  if(strpos($strChartParam,"areaBgColor")===false){
						$colorSet=$this->getColor(0);
						$this->setChartParams("areaBgColor=" . $colorSet );
					  }
				   }
		}
		
		
		# calling getChartParamsXML function for chart parameter
		$this->strXML  =  "<graph " . $this->getChartParamsXML() . " >";
		if ($this->seriesType >= 0 and $this->seriesType <= 4) {
			
			# calling getCategoriesXML function for Category element
			$this->strXML .= $this->getCategoriesXML();
			# calling getDatasetXML function for set element 
			$this->strXML .= $this->getDatasetXML();
			# calling getvTrendLinesXML function for vTrendLines element
			if($this->seriesType==3){
			  $this->strXML .= $this->getvTrendLinesXML();
			}  
			#  Calling getLinesetXML
			if($this->seriesType==4){
			  $this->strXML .= $this->getLinesetXML();
			} 
			# calling getTrendLinesXML function for TrendLines element
			$this->strXML .= $this->getTrendLinesXML();
			# calling getStylesXML function for Styles element
			$this->strXML .= $this->getStylesXML();
		
		}else if($this->seriesType == 5) {
			$this->strXML .= $this->getGanttCategoriesXML();
			$this->strXML .= $this->getProcessesXML();
			$this->strXML .= $this->getGanttDatatableXML();
			$this->strXML .= $this->getTasksXML();
			$this->strXML .= $this->getConnectorsXML();
			$this->strXML .= $this->getMilestonesXML();
			# calling getTrendLinesXML function for TrendLines element
			$this->strXML .= $this->getTrendLinesXML();
			# calling getStylesXML function for Styles element
			$this->strXML .= $this->getStylesXML();
			$this->strXML .= $this->getLegendXML();
		}	
		
		# Closing Chart element
		$this->strXML .= "</graph>";
		
		# Return XML output
		return $this->strXML;
	  }
	 
	 # set wMode
	 function setwMode($isTransparent=""){
	    $this->wMode=$isTransparent;
	 }
	 	 
	 # Function getXML render all class arrays to XML output
	 function renderChart($isHTML=false, $display=true){
		
		$this->strXML=$this->getXML();	
		$this->SWFFile=$this->SWFPath . "FCF_" . $this->chartSWF[$this->chartType][0]  . ".swf";
		
		if($this->noCache==true){
		  if($this->chartMSG==""){
		     $this->chartMSG = "?nocache=" . microtime();
		  }else{
		     $this->chartMSG .=  "&nocache=" . microtime();
		  }
		}
		
	    # print the charts
		if($isHTML==false){
		  if($display){
	         print $this->renderChartJS($this->SWFFile . $this->chartMSG,"",$this->strXML,$this->chartID, $this->width, $this->height,$this->JSC["debugmode"], $this->JSC["registerwithjs"],$this->wMode);
	      }else{
		    return $this->renderChartJS($this->SWFFile . $this->chartMSG,"",$this->strXML,$this->chartID, $this->width, $this->height,$this->JSC["debugmode"], $this->JSC["registerwithjs"],$this->wMode); 
		  }		 
		  
		}else{
		  if($display){
		    print $this->renderChartHTML($this->SWFFile . $this->chartMSG,"",$this->strXML,$this->chartID, $this->width, $this->height,$this->JSC["debugmode"], $this->JSC["registerwithjs"],$this->wMode);
		  }else{
		    return $this->renderChartHTML($this->SWFFile . $this->chartMSG,"",$this->strXML,$this->chartID, $this->width, $this->height,$this->JSC["debugmode"], $this->JSC["registerwithjs"],$this->wMode);
		  }	
		}  
		
	  } 
	  
	  # Sets whether chart SWF files are not to be cached 
      function setOffChartCaching($swfNoCache=false){
           $this->noCache=$swfNoCache;
      } 

	 # Renders Chart form External XML data source
	 function renderChartFromExtXML($dataXML){
		print $this->renderChartJS($this->SWFFile,"",$dataXML,$this->chartID, $this->width, $this->height, $this->JSC["debugmode"], $this->JSC["registerwithjs"], $this->wMode);
	 } 
     
    // RenderChartJS renders the JavaScript + HTML code required to embed a chart.
	// This function assumes that you've already included the FusionCharts JavaScript class
	// in your page.
	
	// $chartSWF - SWF File Name (and Path) of the chart which you intend to plot
	// $strURL - If you intend to use dataURL method for this chart, pass the URL as this parameter. Else, set it to "" (in case of dataXML method)
	// $strXML - If you intend to use dataXML method for this chart, pass the XML data as this parameter. Else, set it to "" (in case of dataURL method)
	// $chartId - Id for the chart, using which it will be recognized in the HTML page. Each chart on the page needs to have a unique Id.
	// $chartWidth - Intended width for the chart (in pixels)
	// $chartHeight - Intended height for the chart (in pixels)
	// $debugMode - Whether to start the chart in debug mode
	// $registerWithJS - Whether to ask chart to register itself with JavaScript
	// $setTransparent - Transparent mode
	function renderChartJS($chartSWF, $strURL, $strXML, $chartId, $chartWidth, $chartHeight, $debugMode=false, $registerWithJS=false, $setTransparent="") {
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
		//  3) Check whether to provide data using dataXML method or dataURL method
		//     save the data for usage below 
		$strHTML="";
		
		if ($strXML=="")
			$tempData = "\t//Set the dataURL of the chart\n\tchart_$chartId.setDataURL(\"$strURL\");";
		else
			$tempData = "\t//Provide entire XML data using dataXML method\n\tchart_$chartId.setDataXML(\"$strXML\");";
	
		// Set up necessary variables for the RENDERCHART
		$chartIdDiv = $chartId . "Div";
		$ndebugMode = $this->boolToNum($debugMode);
		$nregisterWithJS = $this->boolToNum($registerWithJS);
		$nsetTransparent=($setTransparent?"true":"false");
	
	
		// Create a string for output by the caller

		$strHTML .= "\n<!-- START Script Block for Chart $chartId --> \n\n";
		
		$strHTML .= "<div id=\"$chartIdDiv\">\n";
		$strHTML .=	"\tChart.\n";
		$strHTML .= "</div>\n";
		$strHTML .= "<script type=\"text/javascript\" >\n";	
			//Instantiate the Chart	
		$strHTML .= "\tvar chart_$chartId = new FusionCharts(\"$chartSWF\", \"$chartId\", \"$chartWidth\", \"$chartHeight\", \"$ndebugMode\", \"$nregisterWithJS\", \"" . $this->JSC["bgcolor"] . "\",\"" . $this->JSC["scalemode"] . "\",\"" . $this->JSC["lang"] . "\"); \n";
       
	   	if($nsetTransparent=="true"){
		     $strHTML .= "\tchart_$chartId.setTransparent(\"$nsetTransparent\");\n";
        }
		
		$strHTML .= $tempData . "\n";
		//Finally, render the chart.
		$strHTML .=	"\tchart_$chartId.render(\"$chartIdDiv\");\n";
		$strHTML .= "</script>\n\n";
		$strHTML .= "<!-- END Script Block for Chart $chartId -->\n";
		
		return $strHTML;
	  
	}


    //RenderChartHTML function renders the HTML code for the JavaScript. This
    //method does NOT embed the chart using JavaScript class. Instead, it uses
    //direct HTML embedding. So, if you see the charts on IE 6 (or above), you'll
    //see the "Click to activate..." message on the chart.
    // $chartSWF - SWF File Name (and Path) of the chart which you intend to plot
    // $strURL - If you intend to use dataURL method for this chart, pass the URL as this parameter. Else, set it to "" (in case of dataXML method)
    // $strXML - If you intend to use dataXML method for this chart, pass the XML data as this parameter. Else, set it to "" (in case of dataURL method)
    // $chartId - Id for the chart, using which it will be recognized in the HTML page. Each chart on the page needs to have a unique Id.
    // $chartWidth - Intended width for the chart (in pixels)
    // $chartHeight - Intended height for the chart (in pixels)
    // $debugMode - Whether to start the chart in debug mode
	// $registerWithJS - Whether to ask chart to register itself with JavaScript
	// $setTransparent - Transparent mode
    function renderChartHTML($chartSWF, $strURL, $strXML, $chartId, $chartWidth, $chartHeight, $debugMode=false,$registerWithJS=false, $setTransparent="") {
        // Generate the FlashVars string based on whether dataURL has been provided or dataXML.
		
		$strHTML="";
        $strFlashVars = "&chartWidth=" . $chartWidth . "&chartHeight=" . $chartHeight . "&debugMode=" . $this->boolToNum($debugMode);
		
		$strFlashVars .= "&scaleMode=" . $this->JSC["scalemode"] . "&lang=" . $this->JSC["lang"];
		
        if ($strXML=="")
            // DataURL Mode
            $strFlashVars .= "&dataURL=" . $strURL;
        else
            //DataXML Mode
            $strFlashVars .= "&dataXML=" . $strXML;
        
        $nregisterWithJS = $this->boolToNum($registerWithJS);
        if($setTransparent!=""){
          $nsetTransparent=($setTransparent==false?"opaque":"transparent");
        }else{
          $nsetTransparent="window";
        }

        $strHTML .= "\n<!-- START Code Block for Chart $chartId -->\n\n";

         $HTTP="http";
         if(strtolower($_SERVER['HTTPS'])=="on")
         {
            $HTTP="https";
         } 

		 $Strval = $_SERVER['HTTP_USER_AGENT'];
		 $pos=strpos($Strval,"MSIE");
		 if($pos===false){

            $strHTML .= "<embed src=\"$chartSWF\" FlashVars=\"$strFlashVars&registerWithJS=$nregisterWithJS\" quality=\"high\" width=\"$chartWidth\" height=\"$chartHeight\" name=\"$chartId\" " . ($this->JSC["bgcolor"]!="")? " bgcolor=\"" . $this->JSC["bgcolor"] . "\"":"" . " allowScriptAccess=\"always\"  type=\"application/x-shockwave-flash\"  pluginspage=\"$HTTP://www.macromedia.com/go/getflashplayer\" wmode=\"$nsetTransparent\" \n";
            
         }else{
            $strHTML .= "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" codebase=\"$HTTP://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\" width=\"$chartWidth\" height=\"$chartHeight\" id=\"$chartId\"> \n";
            $strHTML .= "\t<param name=\"allowScriptAccess\" value=\"always\" /> \n";
            $strHTML .= "\t<param name=\"movie\" value=\"$chartSWF\" /> \n";		
            $strHTML .= "\t<param name=\"FlashVars\" value=\"$strFlashVars&registerWithJS=$nregisterWithJS\" /> \n";
            $strHTML .= "\t<param name=\"quality\" value=\"high\"  /> \n";
            $strHTML .= "\t<param name=\"wmode\" value=\"$nsetTransparent\"  /> \n"; 
               //Set background color
	           if($this->JSC["bgcolor"] !="") {
		          $strHTML .=  "\t<param name=\"bgcolor\" value=\"" . $this->JSC["bgcolor"] .  "\" /> \n";
		       }
			
            $strHTML .= "</object>\n";
            $strHTML .= "<!-- END Code Block for Chart $chartId -->\n";

       }
	   return $strHTML;
	}

    // The function boolToNum() function converts boolean values to numeric (1/0)
    function boolToNum($bVal) {
        return (($bVal==true) ? 1 : 0);
    }


##------------ PRIVATE FUNCTIONS ----------------------------------------------------------------
	 
	 # getDatasetXML create set chart xml 
	 function getDatasetXML(){
       # Calling dataset function depending on seriesType
	   switch ($this->seriesType){
	   case 1 :
	     return $this->getSSDatasetXML();
		 break;
	   case 2 :
	     return $this->getMSDatasetXML();
	     break;
	   case 3 :
	   	 return $this->getMSDatasetXML();
	     break;
	   case 4 :
	     return $this->getMSStackedDatasetXML();
	     break;
	   }
	 }
	 # By getChartParamsXML, we can fetch charts array and convert into XML
	 # and return like "caption='xyz' xAxisName='x side' ............
	 function getChartParamsXML(){
		$partXML="";	
		# feching charts each array and converting into chat parameter
		foreach($this->chartParams as $part_type => $part_name){
		   $partXML .= $part_type . "='" . $this->encodeSpecialChars($part_name) . "' ";
		}
		# Return Chart Parameter
		return $partXML;
	 }
	 
	 
	 # Function getCategoriesXML for getting Category part XML
	 function getCategoriesXML(){
	   if($this->seriesType>1){
		   $partXML="";
		   # adding categories parameter
		   $partXML="<categories " . $this->categoriesParam . " >";
		   if($this->categoryNamesCounter>1){
		   foreach($this->categoryNames as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					 if($part_name!="Array"){
						 $partXML .= $part_name;
					}
				   } 
		   }
		   }
		   # Closing <categories>
		   $partXML .="</categories>";
		   return $partXML;
	   }
	 }
	 # creating single set element
     #       <set value='30' />
     #       <set value='26' />

	 function getSSDatasetXML(){
	   if($this->seriesType==1){
		   $partXML="";
		   foreach($this->dataset as $part_type => $part_name){
			   if($part_name!=""){ 
				 # adding elements 
				 if($part_name!="Array"){
				   $partXML .= $part_name;
				 }
			   } 
		   }
		   return $partXML;
	   }
	 }
	 
	 # getMSDatasetXML for getting datset part XML
	 #     <dataset seriesName='Product A' color='AFD8F8' showValues='0'>
     #       <set value='30' />
     #       <set value='26' />
     #     </dataset>
	 function getMSDatasetXML(){
	   if($this->seriesType>1){
		   $partXML="";
		   foreach($this->dataset as $part_type => $part_name){
			   $partXML .="<dataset  " . $this->datasetParam[$part_type] . " >";
			   foreach($this->dataset[$part_type] as $part_type1 => $part_name1){
					   if($part_name1!=""){ 
						 # Adding elements 
						 if($part_name1!="Array"){
						   $partXML .= $part_name1;
						 }
					   } 
			   }
			   $partXML .="</dataset>";
		   }
		   return $partXML;
	   }
	 }
	 

	 # getTrendLinesXML create XML output depending on trendLines array
	 #  <trendLines>
	 #    <line startValue='700000' color='009933' displayvalue='Target' /> 
	 # </trendLines>
	 function getTrendLinesXML(){
		$partXML="";
		$lineXML="";	
		# fetching trendLines array
		foreach($this->trendLines as $l_type => $l_name){
		  # staring line element
		  $lineXML .="<line ";
		  # fetching trendLines array with in array element
		  foreach($this->trendLines[$l_type] as $part_type => $part_name){
	
				$lineXML .= $part_type . "='" . $this->encodeSpecialChars($part_name) . "' ";
	
		  }
		  # close line element
		  $lineXML .=" />";
		}
		# if line element present then adding $lineXML with in trendLines element 
		
		$pos = strpos($lineXML, "=");
		if ($pos!==false){
		   $partXML = "<trendLines>" . $lineXML . "</trendLines>"; 
		}else{
		   # return nothing
		   $partXML="";
		}
		# return trendLines xml
		return $partXML;
	 }
	 
	 
	 
 	 # adding set element to dataset element for seriesType 1 and 2
	 function setSSMSDataArray($value="",$setParam="",$vlineParam = "" ){
	     $strSetXML="";
		 $strParam="";
		 $color=0;
		 if($vlineParam==""){
		   if($setParam!=""){
               $strParam = $this->ConvertParamToXMLAttribute($setParam);
			   
		   }
		  				  
		   $colorSet="";
		   if ($this->UserColorON == true){
		   		if($this->seriesType==1 && (gettype(strpos($this->chartType,"line"))=="boolean" && gettype(strpos($this->chartType,"area"))=="boolean")){
				  if(strpos(strtolower($strParam)," color")===false){
					 $colorSet=" color='" . $this->getColor($this->setCounter) . "' ";
				  }   
			   }
		   }else{
			   if($this->seriesType==1 && (gettype(strpos($this->chartType,"pie"))=="boolean" && gettype(strpos($this->chartType,"line"))=="boolean" && gettype(strpos($this->chartType,"area"))=="boolean")){
				  if(strpos(strtolower($strParam)," color")===false){
					 $colorSet=" color='" . $this->getColor($this->setCounter) . "' ";
				  }   
			   }
		   }
			  # setting set parameter 
			  $strSetXML ="<set  value='" . $value . "' " . $strParam . $colorSet . " />";
         
		 }else{
		   $strParam = $this->ConvertParamToXMLAttribute($strParam);
		   
		   # setting vline parameter
		   $strSetXML="<vLine " . $strParam . "  />"; 
		 }
	     return $strSetXML;
	 }

   ## - - - -   - -   Array Init Functions  - - --- - -- - - - - - - -- - - - - -
   
	 # Function createCategory create array element with in Categories
	 function createCategory($catID){
		 $this->categoryNames[$catID]= array();
	 }
	 # createDataset dataset array element
	 function createDataset($dataID){
		 $this->dataset[$dataID]= array();
	 }
	 # creating set  dataset array element
	 function createDataValues($datasetID, $dataID){
		 $this->dataset[$datasetID][$dataID]= array();
	 }
	 # createTrendLines create TrendLines array
	 function createTrendLines($lineID){
		$this->trendLines[$lineID] = array();
	 }
	 # setTLine create TrendLine parameter 
	 function setTLine($lineID,$paramName, $paramValue){
		 $this->trendLines[$lineID][$paramName]=$paramValue;
	 }


  # ----- ----------    -----  Misc utility functions  ---- ------ -----------

	 # converting ' and " to %26apos; and &quot; 
	 function encodeSpecialChars($strValue){
	 
	    $pattern="/%(?![\da-f]{2}|[\da-f]{4})/i";
		$strValue=preg_replace($pattern, "%25", $strValue);
		
	    if ($this->encodeChars==true){
			$strValue=str_replace("&","%26",$strValue);
			$strValue=str_replace("'","%26apos;",$strValue);
			$strValue=str_replace("\"","%26quot;",$strValue);
					
			$strValue=preg_replace("/\<a/i", "%26lt;A", $strValue);
			$strValue=preg_replace("/\<\/a/i", "%26lt;/A", $strValue);
			
			$strValue=preg_replace("/\<i/i", "%26lt;I", $strValue);
			$strValue=preg_replace("/\<\/i/i", "%26lt;/I", $strValue);
			
			$strValue=preg_replace("/\<u/i", "%26lt;U", $strValue);
			$strValue=preg_replace("/\<\/u/i", "%26lt;/U", $strValue);
			
			$strValue=preg_replace("/\<li/i", "%26lt;LI", $strValue);
			$strValue=preg_replace("/\<\/li/i", "%26lt;/LI", $strValue);
			
			$strValue=preg_replace("/\<font/i", "%26lt;FONT", $strValue);
			$strValue=preg_replace("/\<\/font/i", "%26lt;/FONT", $strValue);
			
			$strValue=preg_replace("/\<p/i", "%26lt;P", $strValue);
			$strValue=preg_replace("/\<\/p/i", "%26lt;/P", $strValue);
			
			$strValue=preg_replace("/\<br/i", "%26lt;BR", $strValue);
			
			$strValue=preg_replace("/\<b/i", "%26lt;B", $strValue);
			$strValue=preg_replace("/\<\/b/i", "%26lt;/B", $strValue);
			
			$strValue=str_replace("<", "%ab",$strValue);
		    $strValue=str_replace(">", "%26gt;",$strValue);	
	    }else{
			$strValue=str_replace("'","&apos;",$strValue);
			$strValue=str_replace("\"","&quot;",$strValue);
			
			$strValue=preg_replace("/\<a/i", "&lt;A", $strValue);
			$strValue=preg_replace("/\<\/a/i", "&lt;/A", $strValue);
			
			$strValue=preg_replace("/\<i/i", "&lt;I", $strValue);
			$strValue=preg_replace("/\<\/i/i", "&lt;/I", $strValue);
			
			$strValue=preg_replace("/\<u/i", "&lt;U", $strValue);
			$strValue=preg_replace("/\<\/u/i", "&lt;/U", $strValue);
			
			$strValue=preg_replace("/\<li/i", "&lt;LI", $strValue);
			$strValue=preg_replace("/\<\/li/i", "&lt;/LI", $strValue);
			
			$strValue=preg_replace("/\<font/i", "&lt;FONT", $strValue);
			$strValue=preg_replace("/\<\/font/i", "&lt;/FONT", $strValue);
			
			$strValue=preg_replace("/\<p/i", "&lt;P", $strValue);
			$strValue=preg_replace("/\<\/p/i", "&lt;/P", $strValue);
								
			$strValue=preg_replace("/\<br/i", "&lt;BR", $strValue);
					
			$strValue=preg_replace("/\<b/i", "&lt;B", $strValue);
			$strValue=preg_replace("/\<\/b/i", "&lt;/B", $strValue);
			
			$strValue=str_replace("<","%ab",$strValue);
			$strValue=str_replace(">", "&gt;",$strValue);	
	    }
	  	    
		$strValue=str_replace("=","%3d",$strValue);
		$strValue=str_replace("+","%2b",$strValue);
		
		$strValue=str_replace("¢","%a2",$strValue);
		$strValue=str_replace("£","%a3",$strValue);
		$strValue=str_replace("€","%E2%82%AC",$strValue);
		$strValue=str_replace("¥","%a5",$strValue);
		$strValue=str_replace("₣","%e2%82%a3",$strValue);
	
	    return $strValue;
	   
	 }
	 
    # Its convert pattern link to original link 
	# abcd.php?cid=##Field_name_1##&pname=##Field_name_2##
    function getLinkFromPattern($row,$tempLink){			
		# convert link into array break on '##'
		$aa=explode("##",$tempLink);
		# Reading array
		foreach($aa as $v){
		  # Finding '=' into array
		  $pos = strpos($v, "=");
			  # not found '=' 
			  if($pos === false){
			  	if($v!=""){
					$pet="##" . $v . "##";
	   			    $tempLink=str_replace($pet,$row[$v],$tempLink); 
				}
			  }
 		 }
		 return $tempLink;
	 }		

	 
	 # convertion of semi colon(;) separeted paramater to XML attribute
	 function ConvertParamToXMLAttribute($strParam){
	 	 		 
		 $xmlParam="";
		 $listArray=explode($this->del,$strParam);
		 foreach ($listArray as $valueArray) {
		   $paramValue=explode("=",$valueArray,2);
		   if($this->validateParam($paramValue)==true){
			   # creating parameter set
		       $xmlParam .= $paramValue[0] . "='" . $this->encodeSpecialChars($paramValue[1]) . "' ";
		   }
		}
	
		# Return
        return $xmlParam;
			
	 }
	 
	 function validateParam($paramValue){
	     if(count($paramValue)>=2){
		    if(trim($paramValue[0])==""){
			  return false;
			}
		    return true;
		 }else{
		    return false;
		 }
	 }

	 # Getting Charts series type from charts array. 1 => single series, 2=> multi-series, 3=> scatter and bubble, 4=> MSStacked. defult 1 => single series
	 function getSeriesType(){
	    $sValue=1;	
		if(is_array($this->chartSWF[$this->chartType])){
		  $sValue=$this->chartSWF[$this->chartType][1];
		}else{
		  $sValue=1;
		}
		$this->seriesType=$sValue; 
	 }
	  	 
	 #this function returns a color from a list of colors
	 function getColor($counter){
		
		$strColor="";
		if ($this->UserColorON == false){
		  $strColor=$this->arr_FCColors[$counter % count($this->arr_FCColors)];
		}else{
		  $strColor=$this->UserColor[$counter % count($this->UserColor)];
		}
		
		return $strColor;
	 }
	 
	 # Clear User Color 
	 function ClearUserColor()
     {
          $this->UserColorON = false;
     }
	 
	 # add User Colors
	 function addColors($ColorList)
        {
		   $listArray=explode($this->del, $ColorList);
 		   $this->UserColorON = true;
		   foreach ($listArray as $valueArray) {
		  	   $this->UserColor[$this->userColorCounter]=$valueArray;
			   $this->userColorCounter++;
	    	}
        }
		
  ### ----- Pupulate Color and Chart SWF array  ------ ------- ---------------------
	 function colorInit(){
	    $this->arr_FCColors[] = "AFD8F8";
		$this->arr_FCColors[] = "F6BD0F";
		$this->arr_FCColors[] = "8BBA00";
		$this->arr_FCColors[] = "FF8E46";
		$this->arr_FCColors[] = "008E8E";
		$this->arr_FCColors[] = "D64646";
		$this->arr_FCColors[] = "8E468E";
		$this->arr_FCColors[] = "588526";
		$this->arr_FCColors[] = "B3AA00";
		$this->arr_FCColors[] = "008ED6";
		$this->arr_FCColors[] = "9D080D";
		$this->arr_FCColors[] = "A186BE";
		$this->arr_FCColors[] = "CC6600";
		$this->arr_FCColors[] = "FDC689";
		$this->arr_FCColors[] = "ABA000";
		$this->arr_FCColors[] = "F26D7D";
		$this->arr_FCColors[] = "FFF200";
		$this->arr_FCColors[] = "0054A6";
		$this->arr_FCColors[] = "F7941C";
		$this->arr_FCColors[] = "CC3300";
		$this->arr_FCColors[] = "006600";
		$this->arr_FCColors[] = "663300";
		$this->arr_FCColors[] = "6DCFF6";
		
	 }
	 


	 # Setting FusionCharts SWF file array list and series 
	 function setChartArrays(){
	 
	    $this->chartSWF['area2d'][0]="Area2D";
		$this->chartSWF['area2d'][1]=1;
		$this->chartSWF['bar2d'][0]="Bar2D";
		$this->chartSWF['bar2d'][1]=1;
		$this->chartSWF['column2d'][0]="Column2D";
		$this->chartSWF['column2d'][1]=1;
		$this->chartSWF['column3d'][0]="Column3D";
		$this->chartSWF['column3d'][1]=1;
		$this->chartSWF['doughnut2d'][0]="Doughnut2D";
		$this->chartSWF['doughnut2d'][1]=1;
		$this->chartSWF['doughnut3d'][0]="Doughnut3D";
		$this->chartSWF['doughnut3d'][1]=1;
		$this->chartSWF['line'][0]="Line";
		$this->chartSWF['line'][1]=1;
		$this->chartSWF['pie2d'][0]="Pie2D";
		$this->chartSWF['pie2d'][1]=1;		
		$this->chartSWF['pie3d'][0]="Pie3D";
		$this->chartSWF['pie3d'][1]=1;	
		$this->chartSWF['funnel'][0]="Funnel";
		$this->chartSWF['funnel'][1]=1;	
				
		$this->chartSWF['msarea'][0]="MSArea";
		$this->chartSWF['msarea'][1]=2;
		$this->chartSWF['msarea2d'][0]="MSArea2D";
		$this->chartSWF['msarea2d'][1]=2;
		$this->chartSWF['msbar2d'][0]="MSBar2D";
		$this->chartSWF['msbar2d'][1]=2;
		$this->chartSWF['mscolumn2d'][0]="MSColumn2D";
		$this->chartSWF['mscolumn2d'][1]=2;
		$this->chartSWF['mscolumn3d'][0]="MSColumn3D";
		$this->chartSWF['mscolumn3d'][1]=2;
		$this->chartSWF['mscolumn3dlinedy'][0]="MSColumn3DLineDY";
		$this->chartSWF['mscolumn3dlinedy'][1]=2;
		$this->chartSWF['mscolumnLine3D'][0]="MSColumnLine3D";
		$this->chartSWF['mscolumnLine3D'][1]=2;
		$this->chartSWF['mscombi2d'][0]="MSCombi2D";
		$this->chartSWF['mscombi2d'][1]=2;
		$this->chartSWF['mscombidy2d'][0]="MSCombiDY2D";
		$this->chartSWF['mscombidy2d'][1]=2;
		$this->chartSWF['msline'][0]="MSLine";
		$this->chartSWF['msline'][1]=2;		
		$this->chartSWF['scrollarea2d'][0]="ScrollArea2D";
		$this->chartSWF['scrollarea2d'][1]=2;		
		$this->chartSWF['scrollcolumn2d'][0]="ScrollColumn2D";
		$this->chartSWF['scrollcolumn2d'][1]=2;		
		$this->chartSWF['scrollcombi2d'][0]="ScrollCombi2D";
		$this->chartSWF['scrollcombi2d'][1]=2;
		$this->chartSWF['scrollcombidy2d'][0]="ScrollCombiDY2D";
		$this->chartSWF['scrollcombidy2d'][1]=2;		
		$this->chartSWF['scrollline2d'][0]="ScrollLine2D";
		$this->chartSWF['scrollline2d'][1]=2;		
		$this->chartSWF['scrollstackedcolumn2d'][0]="ScrollStackedColumn2D";
		$this->chartSWF['scrollstackedcolumn2d'][1]=2;		
		$this->chartSWF['stackedarea2d'][0]="StackedArea2D";
		$this->chartSWF['stackedarea2d'][1]=2;		
		$this->chartSWF['stackedbar2d'][0]="StackedBar2D";
		$this->chartSWF['stackedbar2d'][1]=2;		
		$this->chartSWF['stackedbar3d'][0]="StackedBar3D";
		$this->chartSWF['stackedbar3d'][1]=2;
		$this->chartSWF['stackedcolumn2d'][0]="StackedColumn2D";
		$this->chartSWF['stackedcolumn2d'][1]=2;
		$this->chartSWF['stackedcolumn3d'][0]="StackedColumn3D";
		$this->chartSWF['stackedcolumn3d'][1]=2;		
		$this->chartSWF['stackedcolumn3dlinedy'][0]="StackedColumn3DLineDY";
		$this->chartSWF['stackedcolumn3dlinedy'][1]=2;	
		$this->chartSWF['mscolumn2dlinedy'][0]="MSColumn2DLineDY";
		$this->chartSWF['mscolumn2dlinedy'][1]=2;
			
		
		$this->chartSWF['bubble'][0]="Bubble";
		$this->chartSWF['bubble'][1]=3;
		$this->chartSWF['scatter'][0]="Scatter";
		$this->chartSWF['scatter'][1]=3;
		
		$this->chartSWF['msstackedcolumn2dlinedy'][0]="MSStackedColumn2DLineDY";
        $this->chartSWF['msstackedcolumn2dlinedy'][1]=4;	
	    $this->chartSWF['msstackedcolumn2d'][0]="MSStackedColumn2D";
		$this->chartSWF['msstackedcolumn2d'][1]=2;
		
		$this->chartSWF['gantt'][0]="Gantt";
        $this->chartSWF['gantt'][1]=5;
	 }

   ####################### GANTT CHART  (start) ######################################
   # ----------- Public Functions -----------------------------------------------

	 # Function addCategory adding Category and vLine element
	 function addGanttCategorySet($catParam=""){
		$this->GT_categories_Counter++;
		$this->GT_categories[$this->GT_categories_Counter]= array();
		$strParam="";
		 
		# cheking catParam not blank
		if($catParam!=""){
		   
		   $strParam = $this->ConvertParamToXMLAttribute($catParam);
		   
		}
		
		$this->GT_categoriesParam[$this->GT_categories_Counter]=$strParam;
     }
     # Function addGanttCategory adding Category 
	 function addGanttCategory($label="",$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
		       $strParam = $this->ConvertParamToXMLAttribute($catParam);
			   
		    }
			# adding label and parameter set to category 
		   $strCatXML ="<category name='" . $label . "' " . $strParam . " />";
         		 
		 # storing into GT_categories array
		 $this->GT_categories[$this->GT_categories_Counter][$this->GT_subcategories_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_subcategories_Counter++;
	 }
 
     # Setting Process Parameter into categoriesParam variables
	 function setGanttProcessesParams($strParam){
	   
	   $this->GT_processes_Param .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttProcess adding Process
	 function addGanttProcess($label="",$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
		       
			   $strParam = $this->ConvertParamToXMLAttribute($catParam);
			   
		    }
			# adding label and parameter set to category 
		   $strCatXML ="<process name='" . $label . "' " . $strParam . " />";
         		 
		 # storing into categoryNames array
		 $this->GT_processes[$this->GT_processes_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_processes_Counter++;
	 }


 
 # Setting Tasks Parameter into TaskParam variables
	 function setGanttTasksParams($strParam){
	 
	   $this->GT_Tasks_Param .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttTasks adding Tasks
	 function addGanttTask($label="",$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
			   
			   $strParam = $this->ConvertParamToXMLAttribute($catParam);
			   
		    }
			# adding label and parameter set to category 
		   $strCatXML ="<task name='" . $label . "' " . $strParam . " />";
         		 
		 # storing into GT_Tasks array
		 $this->GT_Tasks[$this->GT_Tasks_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_Tasks_Counter++;
	 }
	 


 # Setting Tasks Parameter into ConnectorsParam variables
	 function setGanttConnectorsParams($strParam){
	   $this->GT_Connectors_Param .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttConnector adding Connector
	 function addGanttConnector($From,$To,$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
			   $strParam = $this->ConvertParamToXMLAttribute($catParam);
			   
		    }
			# adding label and parameter set to category 
		   $strCatXML ="<connector fromTaskId='" . $From . "'  toTaskId='" . $To . "' " . $strParam . " />";
         		 
		 # storing into GT_Connectors array
		 $this->GT_Connectors[$this->GT_Connectors_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_Connectors_Counter++;
	 }
	 

 
 # Setting Milestones Parameter into MilestonesParam variables
	 function setGanttMilestonesParams($strParam){
	   $this->GT_Milestones_Param .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttMilestones adding Milestones
	 function addGanttMilestone($taskID,$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
		       $strParam = $this->ConvertParamToXMLAttribute($catParam);
			   
		    }
			# adding label and parameter set to Milestones
		   $strCatXML ="<milestone taskId='" . $taskID . "'  " . $strParam . " />";
         		 
		 # storing into GT_Milestones array
		 $this->GT_Milestones[$this->GT_Milestones_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_Milestones_Counter++;
	 }
	 

 
 # Setting Legend Parameter into LegendParam variables
	 function setGanttLegendParams($strParam){
	   
	   $this->GT_legend_Param .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttLegendItem adding LegendItem
	 function addGanttLegendItem($label,$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
			  
			   $strParam = $this->ConvertParamToXMLAttribute($catParam);
			  
		    }
			# adding label and parameter set to LegendItem
		   $strCatXML ="<item label='" . $label . "'  " . $strParam . " />";
         		 
		 # storing into GT_legend array
		 $this->GT_legend[$this->GT_legend_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_legend_Counter++;
	 }
	 
    
	# Setting Datatable Parameter into DatatableParam variables
	 function setGanttDatatableParams($strParam){
	 
	   $this->GT_datatableParam .= $this->ConvertParamToXMLAttribute($strParam);
	   
	 } 	 
	 
	 # Function addGanttDatacolumn adding Datacolumn
	 function addGanttDatacolumn($catParam=""){
		$this->GT_datatable_Counter++;
		$this->GT_datatable[$this->GT_datatable_Counter]= array();
		$strParam="";
		 
		# cheking catParam not blank
		if($catParam!=""){
		   $strParam = $this->ConvertParamToXMLAttribute($catParam);
		}
		
		$this->GT_dataColumnParam[$this->GT_datatable_Counter]=$strParam;
     }
     # Function addGanttColumnText adding ColumnText
	 function addGanttColumnText($label="",$catParam=""){
	     
	 	 $strCatXML="";
		 $strParam="";
		 
		   # cheking catParam not blank
		   if($catParam!=""){
			   $strParam = $this->ConvertParamToXMLAttribute($catParam);
		    }
			# adding label and parameter set to category 
		   $strCatXML ="<text label='" . $label . "' " . $strParam . " />";
         		 
		 # storing into GT_datatable array
		 $this->GT_datatable[$this->GT_datatable_Counter][$this->GT_subdatatable_Counter]=$strCatXML;
		 # Increase Counter
		 $this->GT_subdatatable_Counter++;
	 }

  ### ------------- Gantt Private Functoins ----------------------------------------------------------------------

	#-- Gantt array init ------------------------------------------------
    # Function createCategory create array element with in Categories
	 function createGanttCategory($catID){
		 $this->GT_categories[$catID]= array();
	 }
	# Function createGanttDatatable create array element with in Datatable
	 function createGanttDatatable($catID){
		 $this->GT_datatable[$catID]= array();
	 }


	#-- GANTT XML genetators -------------------------------------------
	
 	 # Function getCategoriesXML for getting Category part XML
	 function getGanttCategoriesXML(){
		   $partXML="";
		   foreach($this->GT_categories as $part_type => $part_name){
			   # adding categories parameter
			   $partXML .="<categories " . $this->GT_categoriesParam[$part_type] . " >";
			   foreach($this->GT_categories[$part_type] as $part_type1 => $part_name1){
					   if($part_name1!=""){ 
						 # adding elements 
							 $partXML .= $part_name1;
					   } 
			   }
			  # Closing <categories>
		      $partXML .="</categories>"; 
		   }
		   
		   return $partXML;
	 }
	 
	 # Function getProcessesXML for getting Processes part XML
	 function getProcessesXML(){
	   
		   $partXML="";
		   # adding processes parameter
		   $partXML="<processes " . $this->GT_processes_Param . " >";
		    foreach($this->GT_processes as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					
						 $partXML .= $part_name;
					
				   } 
		   }
		  
		   # Closing <categories>
		   $partXML .="</processes>";
		   return $partXML;
	   }


	 # Function getProcessesXML for getting Processes part XML
	 function getTasksXML(){
	   
		   $partXML="";
		   # adding processes parameter
		   $partXML="<tasks " . $this->GT_Tasks_Param . " >";
		    foreach($this->GT_Tasks as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					
						 $partXML .= $part_name;
					
				   } 
		   }
		  
		   # Closing <tasks>
		   $partXML .="</tasks>";
		   return $partXML;
	   }	


	 # Function getConnectorsXML for getting Connectors part XML
	 function getConnectorsXML(){
	       $c=0;
		   $partXML="";
		   # adding connectors parameter
		   $partXML="<connectors " . $this->GT_Connectors_Param . " >";
		    foreach($this->GT_Connectors as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					
						 $partXML .= $part_name;
						 $c++;				
				   } 
		   }
		  
		   # Closing <connectors>
		   $partXML .="</connectors>";
		   if ($c>0){
		   	   return $partXML;
		   }else{
		       return "";
		   }	   
	   }	      

	 # Function getMilestonesXML for getting Milestones part XML
	 function getMilestonesXML(){
	       $c=0;
		   $partXML="";
		   # adding Milestones parameter
		   $partXML="<milestones " . $this->GT_Milestones_Param . " >";
		   foreach($this->GT_Milestones as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					 $partXML .= $part_name;
					 $c++;
				   } 
		   }
		  
		   # Closing <milestones>
		   $partXML .="</milestones>";
		   if ($c>0) {
		     return $partXML;
		   }else{
		     return "";
		   }
	   }	      


	 # Function getLegendXML for getting Legend part XML
	 function getLegendXML(){
	       $c=0;
		   $partXML="";
		   # adding Legend parameter
		   $partXML="<legend " . $this->GT_legend_Param . " >";
		   foreach($this->GT_legend as $part_type => $part_name){
				   if($part_name!=""){ 
					 # adding elements 
					 $partXML .= $part_name;
					 $c++;
				   } 
		   }
		  
		   # Closing <milestones>
		   $partXML .="</legend>";
		   if ($c>0) {
		     return $partXML;
		   }else{
		     return "";
		   }
	   }	      	   


 	 # Function getGanttDatatableXML for getting Category part XML
	 function getGanttDatatableXML(){
		   $partXML="";
		   foreach($this->GT_datatable as $part_type => $part_name){
			   # adding dataColumn parameter
			   $partXML .="<dataColumn " . $this->GT_dataColumnParam[$part_type] . " >";
			   foreach($this->GT_datatable[$part_type] as $part_type1 => $part_name1){
					   if($part_name1!=""){ 
						 # adding elements 
							 $partXML .= $part_name1;
					   } 
			   }
			  # Closing <dataColumn>
		      $partXML .="</dataColumn>"; 
		   }
		   $allPart="<dataTable " . $this->GT_datatableParam . " >" . $partXML . "</dataTable>";
		   return $allPart;
	 }



 ####################### GANTT CHART  (end) ######################################



 #====================== For Future Use (start) =====================================
	
 ##---------PUBLIC functions ----------------------------------------------------
	 # adding Lineset array and parameter to it
	 function addLineset($seriesName, $strParam){
	   $this->createLineset();
	   $this->lineSetCounter++;
       $this->lineSet[$this->lineCounter][$this->lineSetCounter]= array();
	   		
	   $tempParam="";
	   $tempParam ="seriesName='" . $seriesName . "' ";
	   
	   $tempParam .= $this->ConvertParamToXMLAttribute($strParam); 
	  		
	   $this->lineIDCounter++;
	   # setting lineSetParam array with Parameter set	
	   $this->lineSetParam [$this->lineSetCounter]=$tempParam;
	   
	  	  
	 }
      
	 # adding Line's Set data 
	 function addLinesetData($value="",$setParam="",$vlineParam = "" ){
	     $strSetXML="";
		 # getting parameter set  
		 $strSetXML=$this->setSSMSDataArray($value,$setParam,$vlineParam);
         
		 # setting paramter to lineSet array
		 $this->lineSet[$this->lineCounter][$this->lineSetCounter][$this->lineIDCounter]=$strSetXML;
		 
		 # Increase lineIDCounter
		 $this->lineIDCounter++;
	 }
	 
	  
	 # adding ms dataset and parameter	 
	 function addMSSSubDataset($seriesName, $strParam){
	   $this->MSSSubDatasetCounter++;
       $this->MSSDataset[$this->MSSDatasetCounter][$this->MSSSubDatasetCounter]= array();
	   		
	   $tempParam="";
	   # creating seriesName
	   $tempParam ="seriesName='" . $seriesName . "' ";
	   $tempParam .= $this->ConvertParamToXMLAttribute($strParam);
	   		
	   $this->MSSSetCounter++;	
	   
	   # adding Parameter to MSSDatasetParams array
	   $this->MSSDatasetParams[$this->MSSDatasetCounter][$this->MSSSubDatasetCounter]=$tempParam;
	   
		  
	 }

	 # adding set element to dataset element for seriesType 3
	 function setScatterBubbleDataArray($value="",$setParam="",$vlineParam = "" ){
	     $strSetXML="";
		 $strParam="";
		 if($vlineParam==""){
		   if($setParam!=""){
		        $strParam = $this->ConvertParamToXMLAttribute($setParam);
			   
		   }
		   # adding Parameter into set elements
		   $strSetXML ="<set  x='" . $value . "' " . $strParam . " />";
         
		 }else{
		   # Parameter for vLine
		   $strParam = $this->ConvertParamToXMLAttribute($vlineParam);
		   	   
		   # adding vLine element
		   $strSetXML="<vLine " . $strParam . "  />"; 
		 }
	     return $strSetXML;
	 }
	 
	 
	# setvTLine create TrendLine parameter 
	 function setVTrendLines($strParam){
	   $listArray=explode($this->del,$strParam);
	   foreach ($listArray as $valueArray) {
    	   $paramValue=explode("=",$valueArray,2);
		   if($this->validateParam($paramValue)==true){
		     $this->vtrendLines[$this->vtLineCounter][$paramValue[0]]=$this->encodeSpecialChars($paramValue[1]);
		   }
	   }
	   $this->vtLineCounter++;
	 }
	 
	 
	 # setSubStylesParam create sub styles array to store parameters
	 function addStyleDef($styleName,$styleType,$strParam){
		$this->styles["definition"]["style"][$this->styleDefCounter]["name"]= $styleName;
		$this->styles["definition"]["style"][$this->styleDefCounter]["type"]= $styleType;
		
		$listArray=explode($this->del,$strParam);
	    foreach ($listArray as $valueArray) {
    	   $paramValue=explode("=",$valueArray,2);
		   if($this->validateParam($paramValue)==true){ 
		      $this->styles["definition"]["style"][$this->styleDefCounter][$paramValue[0]]= $this->encodeSpecialChars($paramValue[1]);
		   }
		}   
        $this->styleDefCounter++;
    	
	 }
     # apply styles
	 function addStyleApp($toObject,$styles){
		$this->styles["application"]["apply"][$this->styleAppCounter]["toObject"]= $toObject;
		$this->styles["application"]["apply"][$this->styleAppCounter]["styles"]= $styles;
		
        $this->styleAppCounter++;
	 }
	 

  ##---------PRIVATE functions ----------------------------------------------------
	
	## - --  - - XML generators  - - - - ---- - -- - - - -
		 # Function getLinesetXML for getting Lineset XML
	 function getLinesetXML(){
	   # if seriesType MSStackedColumn2DLineDY (4) then linset element will be Generate
	   if($this->seriesType==4){
		   $partXML="";
		   # Fetching lineSet array and Generating lineset xml element
		   foreach($this->lineSet as $part_type => $part_name){
		     $partXML .= "<lineset " . $this->lineSetParam[$part_type]   . " >";
		     foreach($this->lineSet[$part_type] as $part_type1 => $part_name1){ 
		         foreach($this->lineSet[$part_type][$part_type1] as $part_type2 => $part_name2){   
				   if ($part_type2!=""){
					  $partXML .= $part_name2;
					 } 
				 }	 
		       }
		     $partXML .= "</lineset>";
		   }
		   return $partXML;
	   }
	 }

	 # Function getMSStackedDatasetXML for getting datset part XML from ms stacked chart dataset array
	 # <dataset>
     #     <dataset seriesName='Product A' color='AFD8F8' showValues='0'>
     #       <set value='30' />
     #       <set value='26' />
     #     </dataset>
	 # </dataset>
	 
	 function getMSStackedDatasetXML(){
	   if($this->seriesType==4){
		   $partXML="";
		   
		   foreach($this->MSSDataset as $part_type => $part_name){
		     $partXML .= "<dataset>";
		     foreach($this->MSSDataset[$part_type] as $part_type1 => $part_name1){ 
		        $partXML .= "<dataset " . $this->MSSDatasetParams[$part_type][$part_type1] . " >";
		        foreach($this->MSSDataset[$part_type][$part_type1] as $part_type2 => $part_name2){ 
		             if ($part_type2!=""){
					    $partXML .= $part_name2;
					 } 
		        }
		        $partXML .= "</dataset>";
		     }
		     $partXML .= "</dataset>";
		   }
		   
		   return $partXML;
	   }
	 }
	 
	 


	 # getvTrendLinesXML create XML output depending on trendLines array
	 #  <vTrendlines>
	 #    <line displayValue='vTrendLines' startValue='5' endValue='6' alpha='10' color='ff0000'  />
	 # </vTrendlines>
	 function getvTrendLinesXML(){
		$partXML="";
		$lineXML="";	
		# fetching vtrendLines array
		foreach($this->vtrendLines as $l_type => $l_name){
		  # staring line element
		  $lineXML .="<line ";
		  # fetching vtrendLines array with in array element
		  foreach($this->vtrendLines[$l_type] as $part_type => $part_name){
				$lineXML .= $part_type . "='" . $this->encodeSpecialChars($part_name) . "' ";
		  }
		  # close line element
		  $lineXML .=" />";
		}
		# if line element present then adding $lineXML with in vtrendLines element 
		$pos = strpos($lineXML, "=");
        if ($pos !== false) {
		   $partXML = "<vTrendlines>" . $lineXML . "</vTrendlines>"; 
		}else{
		   # return nothing
		   $partXML="";
		}
		# return vtrendLines xml
		return $partXML;
	 }
	 # getStylesXML create the styles XML from styles array
	 /*
	 <styles>
       <definition>
         <style name='CanvasAnim' type='animation' param='_xScale' start='0' duration='1' />
       </definition>
       <application>
         <apply toObject='Canvas' styles='CanvasAnim' />
       </application>   
     </styles>
     */
	 function getStylesXML(){
		$partXML="";
		$lineXML="";	
	    # fetching styles array	
		foreach($this->styles as $s_type => $s_name){
		 $lineXML .="<" . $s_type . ">";
		 # fetching styles array with in array	
		 foreach($this->styles[$s_type] as $sub_type => $sub_name){
		  # creating dynamic element depend on array name
		  
		  # fetching styles array with in array	with array element 
		  foreach($this->styles[$s_type][$sub_type] as $part_type => $part_name){
			 $lineXML .="<" . $sub_type . " ";
			 foreach($this->styles[$s_type][$sub_type][$part_type] as $part_type1 => $part_name1){
	
				 # adding elements parameter
				 $lineXML .= $part_type1 . "='" . $this->encodeSpecialChars($part_name1) . "' ";
	
			 }
			 $lineXML .=" />";
		  }
		  
		 }
		 # closing open eleement
		 $lineXML .="</" . $s_type .  ">";
		}
		# adding $lineXML  with in style element
		# cheking element have any attribute or not
		$pos = strpos($lineXML, "=");
        if ($pos !== false) {
     		$partXML = "<styles>" . $lineXML . "</styles>"; 
		}else{
	        $partXML ="";	
		}
		# returning the part of xml
		return $partXML;
	 }
	 

	
	 
	## ---------- Array Init functions ----------------------------------------------
	 # create Lineset array 
	 function createLineset(){
		 $this->lineCounter++;
		 $this->lineSet[$this->lineCounter]= array();
	 }
     

	 # creating MS-Stacked ataset array element and parameter array
	 function createMSSDataset(){
		 $this->MSSDatasetCounter++;
		 $this->MSSDataset[$this->MSSDatasetCounter]= array();
		 $this->MSSDatasetParams[$this->MSSDatasetCounter]=array();
	 }
	 
	 # Creating set data with in datset
	 function createMSSSetData(){
		 $this->MSSSetCounter++;
		 $this->MSSDataset[$this->MSSDatasetCounter][$this->MSSSubDatasetCounter][$this->MSSSetCounter]= array();
	 }

	 # createStyles create array element with in styles array
	 function createStyles($styleID){
		 $this->styles[$styleID]= array();
	 }

	 # createSubStyles create array element with in styles array element with in sub styles array 
	 # element for storing sub element parameter
	 function createSubStyles($styleID,$subStyle){
		 $this->styles[$styleID][$subStyle]= array();
	 }
	 

	  # createvTrendLines create TrendLines array
	 function createvTrendLines($lineID){
		$this->vtrendLines[$lineID] = array();
	 }
	 
	 # setvTLine create TrendLine parameter 
	 function setvTLine($lineID,$paramName, $paramValue){
		 $this->vtrendLines[$lineID][$paramName]=$paramValue;
	 }
	
	 # create sub styles param
	 function createSubStylesParam($styleID,$subStyle,$subParam){
		 $this->styles[$styleID][$subStyle][$subParam]= array();
	 }
	 
	 # setSubStylesParam create sub styles array to store parameters
	 function setSubStylesParam($styleID,$subStyle,$subParam,$id,$value){
		 $this->styles[$styleID][$subStyle][$subParam][$id]= $value;
	 }

	 
	 
#====================== For Future Use (end) ======================================


}



?>
