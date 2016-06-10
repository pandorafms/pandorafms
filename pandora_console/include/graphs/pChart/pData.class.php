<?php
 /*
     pDraw - class to manipulate data arrays

     Version     : 2.1.0
     Made by     : Jean-Damien POGOLOTTI
     Last Update : 26/01/11

     This file can be distributed under the license you can find at :

                       http://www.pchart.net/license

     You can find the whole class documentation on the pChart web site.
 */

 /* Axis configuration */
 define("AXIS_FORMAT_DEFAULT"	, 680001);
 define("AXIS_FORMAT_TIME"	, 680002);
 define("AXIS_FORMAT_DATE"	, 680003);
 define("AXIS_FORMAT_METRIC"	, 680004);
 define("AXIS_FORMAT_CURRENCY"	, 680005);

 /* Axis position */
 define("AXIS_POSITION_LEFT"	, 681001);
 define("AXIS_POSITION_RIGHT"	, 681002);
 define("AXIS_POSITION_TOP"	, 681001);
 define("AXIS_POSITION_BOTTOM"	, 681002);

 /* Axis position */
 define("AXIS_X"		, 682001);
 define("AXIS_Y"		, 682002);

 /* Define value limits */
 define("ABSOLUTE_MIN"          , -10000000000000);
 define("ABSOLUTE_MAX"          , 10000000000000);

 /* Replacement to the PHP NULL keyword */
 define("VOID"                  , 0.12345);

 /* pData class definition */
 class pData
  {
   var $Data;

   var $Palette = array("0"=>array("R"=>138,"G"=>226,"B"=>52,"Alpha"=>100),
                        "1"=>array("R"=>204,"G"=>0,"B"=>0,"Alpha"=>100),
                        "2"=>array("R"=>255,"G"=>204,"B"=>0,"Alpha"=>100),
                        "3"=>array("R"=>195,"G"=>195,"B"=>195,"Alpha"=>100),
                        "4"=>array("R"=>59,"G"=>160,"B"=>255,"Alpha"=>100),
                        "5"=>array("R"=>176,"G"=>46,"B"=>224,"Alpha"=>100),
                        "6"=>array("R"=>224,"G"=>46,"B"=>117,"Alpha"=>100),
                        "7"=>array("R"=>92,"G"=>224,"B"=>46,"Alpha"=>100),
                        "8"=>array("R"=>224,"G"=>176,"B"=>46,"Alpha"=>100));

   /* Class creator */
   function pData()
    {
     $this->Data = "";
     $this->Data["XAxisDisplay"] = AXIS_FORMAT_DEFAULT;
     $this->Data["XAxisFormat"]  = NULL;
     $this->Data["XAxisName"]    = NULL;
     $this->Data["XAxisUnit"]    = NULL;
     $this->Data["Abscissa"]     = NULL;
     $this->Data["Axis"][0]["Display"]  = AXIS_FORMAT_DEFAULT;
     $this->Data["Axis"][0]["Position"] = AXIS_POSITION_LEFT;
     $this->Data["Axis"][0]["Identity"] = AXIS_Y;
    }

   /* Add a single point or an array to the given serie */
   function addPoints($Values,$SerieName="Serie1")
    {
     if (!isset($this->Data["Series"][$SerieName]))
      $this->initialise($SerieName);

     if ( is_array($Values) )
      {
       foreach($Values as $Key => $Value)
        { $this->Data["Series"][$SerieName]["Data"][] = $Value; }
      }
     else
      $this->Data["Series"][$SerieName]["Data"][] = $Values;

     if ( $Values != VOID )
      {
       $this->Data["Series"][$SerieName]["Max"] = max($this->stripVOID($this->Data["Series"][$SerieName]["Data"]));
       $this->Data["Series"][$SerieName]["Min"] = min($this->stripVOID($this->Data["Series"][$SerieName]["Data"]));
      }
    }

   /* Strip VOID values */
   function stripVOID($Values)
    { $Result = ""; foreach($Values as $Key => $Value) { if ( $Value != VOID ) { $Result[] = $Value; } } return($Result); }

   /* Return the number of values contained in a given serie */
   function getSerieCount($Serie=NULL)
    { if (isset($this->Data["Series"][$Serie]["Data"])) { return(sizeof($this->Data["Series"][$Serie]["Data"])); } else { return(0); } }

   /* Remove a serie from the pData object */
   function removeSerie($Serie=NULL)
    { if (isset($this->Data["Series"][$Serie])) { unset($this->Data["Series"][$Serie]); } }

   /* Return a value from given serie & index */
   function getValueAt($Serie,$Index=0)
    { if (isset($this->Data["Series"][$Serie]["Data"][$Index])) { return($this->Data["Series"][$Serie]["Data"][$Index]); } else { return(NULL); } }

   /* Return the values array */
   function getValues($Serie=NULL)
    { if (isset($this->Data["Series"][$Serie]["Data"])) { return($this->Data["Series"][$Serie]["Data"]); } else { return(NULL); } }

   /* Reverse the values in the given serie */
   function reverseSerie($Serie=NULL)
    { if (isset($this->Data["Series"][$Serie]["Data"])) { $this->Data["Series"][$Serie]["Data"] = array_reverse($this->Data["Series"][$Serie]["Data"]); } }

   /* Return the sum of the serie values */
   function getSum($Serie)
    { if (isset($this->Data["Series"][$Serie])) { return(array_sum($this->Data["Series"][$Serie]["Data"])); } else { return(NULL); } }

   /* Return the max value of a given serie */
   function getMax($Serie)
    { if (isset($this->Data["Series"][$Serie]["Max"])) { return($this->Data["Series"][$Serie]["Max"]); } else { return(NULL); } }

   /* Return the min value of a given serie */
   function getMin($Serie)
    { if (isset($this->Data["Series"][$Serie]["Min"])) { return($this->Data["Series"][$Serie]["Min"]); } else { return(NULL); } }

   /* Set the description of a given serie */
   function setSerieDescription($Serie=NULL,$Description="My serie")
    { if (isset($this->Data["Series"][$Serie]) ) { $this->Data["Series"][$Serie]["Description"] = $Description; } }

   /* Set a serie as "drawable" while calling a rendering function */
   function setSerieDrawable($Serie=NULL ,$Drawable=TRUE)
    { if (isset($this->Data["Series"][$Serie]) ) { $this->Data["Series"][$Serie]["isDrawable"] = $Drawable; } }

   /* Set the icon associated to a given serie */
   function setSeriePicture($Serie=NULL,$Picture=NULL)
    { if (isset($this->Data["Series"][$Serie]) ) { $this->Data["Series"][$Serie]["Picture"] = $Picture; } }

   /* Set the name of the X Axis */
   function setXAxisName($Name=NULL)
    { $this->Data["XAxisName"] = $Name; }

   /* Set the display mode of the  X Axis */
   function setXAxisDisplay($Mode,$Format=NULL)
    { $this->Data["XAxisDisplay"] = $Mode; $this->Data["XAxisFormat"]  = $Format; }

   /* Set the unit that will be displayed on the X axis */
   function setXAxisUnit($Unit)
    { $this->Data["XAxisUnit"] = $Unit; }

   /* Set the serie that will be used as abscissa */
   function setAbscissa($Serie)
    { if (isset($this->Data["Series"][$Serie])) { $this->Data["Abscissa"] = $Serie; } }

   /* Create a scatter group specifyin X and Y data series */
   function setScatterSerie($SerieX,$SerieY,$ID=0)
    { if (isset($this->Data["Series"][$SerieX]) && isset($this->Data["Series"][$SerieY]) ) { $this->initScatterSerie($ID); $this->Data["ScatterSeries"][$ID]["X"] = $SerieX; $this->Data["ScatterSeries"][$ID]["Y"] = $SerieY; } }

   /* Set the description of a given scatter serie */
   function setScatterSerieDescription($ID,$Description="My serie")
    { if (isset($this->Data["ScatterSeries"][$ID]) ) { $this->Data["ScatterSeries"][$ID]["Description"] = $Description; } }

   /* Set the icon associated to a given scatter serie */
   function setScatterSeriePicture($ID,$Picture=NULL)
    { if (isset($this->Data["ScatterSeries"][$ID]) ) { $this->Data["ScatterSeries"][$ID]["Picture"] = $Picture; } }

   /* Set a scatter serie as "drawable" while calling a rendering function */
   function setScatterSerieDrawable($ID ,$Drawable=TRUE)
    { if (isset($this->Data["ScatterSeries"][ID]) ) { $this->Data["ScatterSeries"][ID]["isDrawable"] = $Drawable; } }

   /* Define if a scatter serie should be draw with ticks */
   function setScatterSerieTicks($ID,$Width=0)
    { if ( isset($this->Data["ScatterSeries"][$ID]) ) { $this->Data["ScatterSeries"][$ID]["Ticks"] = $Width; } }

   /* Define if a scatter serie should be draw with a special weight */
   function setScatterSerieWeight($ID,$Weight=0)
    { if ( isset($this->Data["ScatterSeries"][$ID]) ) { $this->Data["ScatterSeries"][$ID]["Weight"] = $Weight; } }

   /* Associate a color to a scatter serie */
   function setScatterSerieColor($ID,$Format)
    {
     $R	    = isset($Format["R"]) ? $Format["R"] : 0;
     $G	    = isset($Format["G"]) ? $Format["G"] : 0;
     $B	    = isset($Format["B"]) ? $Format["B"] : 0;
     $Alpha = isset($Format["Alpha"]) ? $Format["Alpha"] : 100;

     if ( isset($this->Data["ScatterSeries"][$ID]) )
      {
       $this->Data["ScatterSeries"][$ID]["Color"]["R"] = $R;
       $this->Data["ScatterSeries"][$ID]["Color"]["G"] = $G;
       $this->Data["ScatterSeries"][$ID]["Color"]["B"] = $B;
       $this->Data["ScatterSeries"][$ID]["Color"]["Alpha"] = $Alpha;
      }
    }

   /* Compute the series limits for an individual and global point of view */
   function limits()
    {
     $GlobalMin = ABSOLUTE_MAX;
     $GlobalMax = ABSOLUTE_MIN;
	
     foreach($this->Data["Series"] as $Key => $Value)
      {
       if ( $this->Data["Abscissa"] != $Key && $this->Data["Series"][$Key]["isDrawable"] == TRUE)
        {
         if ( $GlobalMin > $this->Data["Series"][$Key]["Min"] ) { $GlobalMin = $this->Data["Series"][$Key]["Min"]; }
         if ( $GlobalMax < $this->Data["Series"][$Key]["Max"] ) { $GlobalMax = $this->Data["Series"][$Key]["Max"]; }
        }
      }
     $this->Data["Min"] = $GlobalMin;
     $this->Data["Max"] = $GlobalMax;

     return(array($GlobalMin,$GlobalMax));
    }

   /* Mark all series as drawable */
   function drawAll()
    { foreach($this->Data["Series"] as $Key => $Value) { if ( $this->Data["Abscissa"] != $Key ) { $this->Data["Series"][$Key]["isDrawable"]=TRUE; } } }    

   /* Return the average value of the given serie */
   function getSerieAverage($Serie)
    {
     if ( isset($this->Data["Series"][$Serie]) )
      return(array_sum($this->Data["Series"][$Serie]["Data"])/sizeof($this->Data["Series"][$Serie]["Data"]));
     else
      return(NULL);
    }

   /* Return the x th percentil of the given serie */
   function getSeriePercentile($Serie="Serie1",$Percentil=95)
    {
     if (!isset($this->Data["Series"][$Serie]["Data"])) { return(NULL); }

     $Values = count($this->Data["Series"][$Serie]["Data"])-1;
     if ( $Values < 0 ) { $Values = 0; }

     $PercentilID  = floor(($Values/100)*$Percentil+.5);
     $SortedValues = $this->Data["Series"][$Serie]["Data"];
     sort($SortedValues);

     if ( is_numeric($SortedValues[$PercentilID]) )
      return($SortedValues[$PercentilID]);
     else
      return(NULL);
    }

   /* Add random values to a given serie */
   function addRandomValues($SerieName="Serie1",$Options="")
    {
     $Values    = isset($Options["Values"]) ? $Options["Values"] : 20;
     $Min       = isset($Options["Min"]) ? $Options["Min"] : 0;
     $Max       = isset($Options["Max"]) ? $Options["Max"] : 100;
     $withFloat = isset($Options["withFloat"]) ? $Options["withFloat"] : FALSE;

     for ($i=0;$i<=$Values;$i++)
      {
       if ( $withFloat ) { $Value = rand($Min*100,$Max*100)/100; } else { $Value = rand($Min,$Max); }
       $this->addPoints($Value,$SerieName);
      }
    }

   /* Test if we have valid data */
   function containsData()
    {
     if (!isset($this->Data["Series"])) { return(FALSE); }

     $Result = FALSE;
     foreach($this->Data["Series"] as $Key => $Value)
      { if ( $this->Data["Abscissa"] != $Key && $this->Data["Series"][$Key]["isDrawable"]==TRUE) { $Result=TRUE; } }
     return($Result);
    }

   /* Set the display mode of an Axis */
   function setAxisDisplay($AxisID,$Mode=AXIS_FORMAT_DEFAULT,$Format=NULL)
    {
     if ( isset($this->Data["Axis"][$AxisID] ) )
      {
       $this->Data["Axis"][$AxisID]["Display"] = $Mode;
       if ( $Format != NULL ) { $this->Data["Axis"][$AxisID]["Format"] = $Format; }
      }
    }

   /* Set the position of an Axis */
   function setAxisPosition($AxisID,$Position=AXIS_POSITION_LEFT)
    { if ( isset($this->Data["Axis"][$AxisID] ) ) { $this->Data["Axis"][$AxisID]["Position"] = $Position; } }

   /* Associate an unit to an axis */
   function setAxisUnit($AxisID,$Unit)
    { if ( isset($this->Data["Axis"][$AxisID] ) ) { $this->Data["Axis"][$AxisID]["Unit"] = $Unit; } }

   /* Associate a name to an axis */
   function setAxisName($AxisID,$Name)
    { if ( isset($this->Data["Axis"][$AxisID] ) ) { $this->Data["Axis"][$AxisID]["Name"] = $Name; } }

   /* Associate a color to an axis */
   function setAxisColor($AxisID,$Format)
    {
     $R	    = isset($Format["R"]) ? $Format["R"] : 0;
     $G	    = isset($Format["G"]) ? $Format["G"] : 0;
     $B	    = isset($Format["B"]) ? $Format["B"] : 0;
     $Alpha = isset($Format["Alpha"]) ? $Format["Alpha"] : 100;

     if ( isset($this->Data["Axis"][$AxisID] ) )
      {
       $this->Data["Axis"][$AxisID]["Color"]["R"] = $R;
       $this->Data["Axis"][$AxisID]["Color"]["G"] = $G;
       $this->Data["Axis"][$AxisID]["Color"]["B"] = $B;
       $this->Data["Axis"][$AxisID]["Color"]["Alpha"] = $Alpha;
      }
    }


   /* Design an axis as X or Y member */
   function setAxisXY($AxisID,$Identity=AXIS_Y)
    { if ( isset($this->Data["Axis"][$AxisID] ) ) { $this->Data["Axis"][$AxisID]["Identity"] = $Identity; } }

   /* Associate one data serie with one axis */
   function setSerieOnAxis($Serie,$AxisID)
    {
     $PreviousAxis = $this->Data["Series"][$Serie]["Axis"];

     /* Create missing axis */
     if ( !isset($this->Data["Axis"][$AxisID] ) )
      { $this->Data["Axis"][$AxisID]["Position"] = AXIS_POSITION_LEFT; $this->Data["Axis"][$AxisID]["Identity"] = AXIS_Y;}

     $this->Data["Series"][$Serie]["Axis"] = $AxisID;

     /* Cleanup unused axis */
     $Found = FALSE;
     foreach($this->Data["Series"] as $SerieName => $Values) { if ( $Values["Axis"] == $PreviousAxis ) { $Found = TRUE; } }
     if (!$Found) { unset($this->Data["Axis"][$PreviousAxis]); }
    }

   /* Define if a serie should be draw with ticks */
   function setSerieTicks($Serie,$Width=0)
    { if ( isset($this->Data["Series"][$Serie]) ) { $this->Data["Series"][$Serie]["Ticks"] = $Width; } }

  /* Define if a serie should be draw with a special weight */
   function setSerieWeight($Serie,$Weight=0)
    { if ( isset($this->Data["Series"][$Serie]) ) { $this->Data["Series"][$Serie]["Weight"] = $Weight; } }

   /* Set the color of one serie */
   function setPalette($Serie,$Format=NULL)
    {
     $R	    = isset($Format["R"]) ? $Format["R"] : 0;
     $G	    = isset($Format["G"]) ? $Format["G"] : 0;
     $B	    = isset($Format["B"]) ? $Format["B"] : 0;
     $Alpha = isset($Format["Alpha"]) ? $Format["Alpha"] : 100;
     $BorderR = isset($Format["BorderR"]) ? $Format["BorderR"] : $R;
     $BorderG = isset($Format["BorderG"]) ? $Format["BorderG"] : $G;
     $BorderB = isset($Format["BorderB"]) ? $Format["BorderB"] : $B;

     if (isset($this->Data["Series"][$Serie]) )
      {
       $OldR = $this->Data["Series"][$Serie]["Color"]["R"]; 
       $OldG = $this->Data["Series"][$Serie]["Color"]["G"]; 
       $OldB = $this->Data["Series"][$Serie]["Color"]["B"];
       $this->Data["Series"][$Serie]["Color"]["R"] = $R;
       $this->Data["Series"][$Serie]["Color"]["G"] = $G;
       $this->Data["Series"][$Serie]["Color"]["B"] = $B;
       $this->Data["Series"][$Serie]["Color"]["BorderR"] = $BorderR;
       $this->Data["Series"][$Serie]["Color"]["BorderG"] = $BorderG;
       $this->Data["Series"][$Serie]["Color"]["BorderB"] = $BorderB;
       $this->Data["Series"][$Serie]["Color"]["Alpha"] = $Alpha;

       /* Do reverse processing on the internal palette array */
       foreach ($this->Palette as $Key => $Value) { 
        	if ($Value["R"] == $OldR && $Value["G"] == $OldG && $Value["B"] == $OldB) { 
        		$this->Palette[$Key]["R"] = $R; 
        		$this->Palette[$Key]["G"] = $G; 
        		$this->Palette[$Key]["B"] = $B; 
         		$this->Palette[$Key]["BorderR"] = $BorderR; 
        		$this->Palette[$Key]["BorderG"] = $BorderG; 
        		$this->Palette[$Key]["BorderB"] = $BorderB; 
        		$this->Palette[$Key]["Alpha"] = $Alpha;} 
       		}
       }
    }

   /* Load a palette file */
   function loadPalette($FileName,$Overwrite=FALSE)
    {
     if ( !file_exists($FileName) ) { return(-1); }
     if ( $Overwrite ) { $this->Palette = ""; }

     $fileHandle = @fopen($FileName, "r");
     if (!$fileHandle) { return(-1); }
     while (!feof($fileHandle))
      {
       $buffer = fgets($fileHandle, 4096);
       if ( preg_match("/,/",$buffer) )
        {
         list($R,$G,$B,$Alpha) = preg_split("/,/",$buffer);
         if ( $this->Palette == "" ) { $ID = 0; } else { $ID = count($this->Palette); }
         $this->Palette[$ID] = array("R"=>$R,"G"=>$G,"B"=>$B,"Alpha"=>$Alpha);
        }
      }
     fclose($fileHandle);

     /* Apply changes to current series */
     $ID = 0;
     if ( isset($this->Data["Series"]))
      {
       foreach($this->Data["Series"] as $Key => $Value)
        {
         if ( !isset($this->Palette[$ID]) )
          $this->Data["Series"][$Key]["Color"] = array("R"=>0,"G"=>0,"B"=>0,"Alpha"=>0);
         else
          $this->Data["Series"][$Key]["Color"] = $this->Palette[$ID];
         $ID++;
        }
      }
    }

   /* Initialise a given scatter serie */
   function initScatterSerie($ID)
    {
     if ( isset($this->Data["ScatterSeries"][$ID]) ) { return(0); }

     $this->Data["ScatterSeries"][$ID]["Description"]	= "Scatter ".$ID;
     $this->Data["ScatterSeries"][$ID]["isDrawable"]	= TRUE;
     $this->Data["ScatterSeries"][$ID]["Picture"]	= NULL;
     $this->Data["ScatterSeries"][$ID]["Ticks"]		= 0;
     $this->Data["ScatterSeries"][$ID]["Weight"]	= 0;

     if ( isset($this->Palette[$ID]) )
      $this->Data["ScatterSeries"][$ID]["Color"] = $this->Palette[$ID];
     else
      {
       $this->Data["ScatterSeries"][$ID]["Color"]["R"] = rand(0,255);
       $this->Data["ScatterSeries"][$ID]["Color"]["G"] = rand(0,255);
       $this->Data["ScatterSeries"][$ID]["Color"]["B"] = rand(0,255);
       $this->Data["ScatterSeries"][$ID]["Color"]["Alpha"] = 100;
      }
    }

   /* Initialise a given serie */
   function initialise($Serie)
    {
     if ( isset($this->Data["Series"]) ) { $ID = count($this->Data["Series"]); } else { $ID = 0; }

     $this->Data["Series"][$Serie]["Description"]	= $Serie;
     $this->Data["Series"][$Serie]["isDrawable"]	= TRUE;
     $this->Data["Series"][$Serie]["Picture"]		= NULL;
     $this->Data["Series"][$Serie]["Max"]		= NULL;
     $this->Data["Series"][$Serie]["Min"]		= NULL;
     $this->Data["Series"][$Serie]["Axis"]		= 0;
     $this->Data["Series"][$Serie]["Ticks"]		= 0;
     $this->Data["Series"][$Serie]["Weight"]		= 0;

     if ( isset($this->Palette[$ID]) )
      $this->Data["Series"][$Serie]["Color"] = $this->Palette[$ID];
     else
      {
       $this->Data["Series"][$Serie]["Color"]["R"] = rand(0,255);
       $this->Data["Series"][$Serie]["Color"]["G"] = rand(0,255);
       $this->Data["Series"][$Serie]["Color"]["B"] = rand(0,255);
       $this->Data["Series"][$Serie]["Color"]["Alpha"] = 100;
      }
    }
     
   function normalize($NormalizationFactor=100,$UnitChange=NULL,$Round=1)
    {
     $Abscissa = $this->Data["Abscissa"];

     $SelectedSeries = "";
     $MaxVal         = 0;
     foreach($this->Data["Axis"] as $AxisID => $Axis)
      {
       if ( $UnitChange != NULL ) { $this->Data["Axis"][$AxisID]["Unit"] = $UnitChange; }

       foreach($this->Data["Series"] as $SerieName => $Serie)
        {
         if ($Serie["Axis"] == $AxisID && $Serie["isDrawable"] == TRUE && $SerieName != $Abscissa)
          {
           $SelectedSeries[$SerieName] = $SerieName;

           if ( count($Serie["Data"] ) > $MaxVal ) { $MaxVal = count($Serie["Data"]); }
          }
        }
      }

     for($i=0;$i<=$MaxVal-1;$i++)
      {
       $Factor = 0;
       foreach ($SelectedSeries as $Key => $SerieName )
        {
         $Value = $this->Data["Series"][$SerieName]["Data"][$i];
         if ( $Value != VOID )
          $Factor = $Factor + abs($Value);
        }

       if ( $Factor != 0 )
        {
         $Factor = $NormalizationFactor / $Factor;

         foreach ($SelectedSeries as $Key => $SerieName )
          {
           $Value = $this->Data["Series"][$SerieName]["Data"][$i];

           if ( $Value != VOID && $Factor != $NormalizationFactor )
            $this->Data["Series"][$SerieName]["Data"][$i] = round(abs($Value)*$Factor,$Round);
           elseif ( $Value == VOID || $Value == 0 )
            $this->Data["Series"][$SerieName]["Data"][$i] = VOID;
           elseif ( $Factor == $NormalizationFactor )
            $this->Data["Series"][$SerieName]["Data"][$i] = $NormalizationFactor;
          }
        }
      }

     foreach ($SelectedSeries as $Key => $SerieName )
      {
       $this->Data["Series"][$SerieName]["Max"] = max($this->stripVOID($this->Data["Series"][$SerieName]["Data"]));
       $this->Data["Series"][$SerieName]["Min"] = min($this->stripVOID($this->Data["Series"][$SerieName]["Data"]));
      }
    }

   /* Load data from a CSV (or similar) data source */
   function importFromCSV($FileName,$Options="")
    {
     $Delimiter		= isset($Options["Delimiter"]) ? $Options["Delimiter"] : ",";
     $GotHeader		= isset($Options["GotHeader"]) ? $Options["GotHeader"] : FALSE;
     $SkipColumns	= isset($Options["SkipColumns"]) ? $Options["SkipColumns"] : array(-1);
     $DefaultSerieName	= isset($Options["DefaultSerieName"]) ? $Options["DefaultSerieName"] : "Serie";

     $Handle = @fopen($FileName,"r");
     if ($Handle)
      {
       $HeaderParsed = FALSE; $SerieNames = "";
       while (!feof($Handle))
        {
         $Buffer = fgets($Handle, 4096);
         $Buffer = str_replace(chr(10),"",$Buffer);
         $Buffer = str_replace(chr(13),"",$Buffer);
         $Values = preg_split("/".$Delimiter."/",$Buffer);

         if ( $Buffer != "" )
          {
           if ( $GotHeader && !$HeaderParsed )
            {
             foreach($Values as $Key => $Name) { if ( !in_array($Key,$SkipColumns) ) { $SerieNames[$Key] = $Name; } }
             $HeaderParsed = TRUE;
            }
           else
            {
             if ($SerieNames == "" ) { foreach($Values as $Key => $Name) {  if ( !in_array($Key,$SkipColumns) ) { $SerieNames[$Key] = $DefaultSerieName.$Key; } } }
             foreach($Values as $Key => $Value) {  if ( !in_array($Key,$SkipColumns) ) { $this->addPoints($Value,$SerieNames[$Key]); } }
            }
          }
        }
       fclose($Handle);
      }
    }

   /* Return the data & configuration of the series */
   function getData()
    { return($this->Data); }

   /* Return the palette of the series */
   function getPalette()
    { return($this->Palette); }

   /* Called by the scaling algorithm to save the config */
   function saveAxisConfig($Axis) { $this->Data["Axis"]=$Axis; }

   /* Called by the scaling algorithm to save the orientation of the scale */
   function saveOrientation($Orientation) { $this->Data["Orientation"]=$Orientation; }

   /* Class string wrapper */
   function __toString()
    { return("pData object."); }
  }
?>
