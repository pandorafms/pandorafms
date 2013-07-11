<?php
 /*
     pPie - class to draw pie charts

     Version     : 2.1.0
     Made by     : Jean-Damien POGOLOTTI
     Last Update : 26/01/11

     This file can be distributed under the license you can find at :

                       http://www.pchart.net/license

     You can find the whole class documentation on the pChart web site.
 */

 /* Class return codes */
 define("PIE_NO_ABSCISSA"	, 140001);
 define("PIE_NO_DATASERIE"	, 140002);
 define("PIE_SUMISNULL"		, 140003);
 define("PIE_RENDERED"		, 140000);

 define("PIE_LABEL_COLOR_AUTO"	, 140010);
 define("PIE_LABEL_COLOR_MANUAL", 140011);

 define("PIE_VALUE_NATURAL"	, 140020);
 define("PIE_VALUE_PERCENTAGE"	, 140021);

 /* pPie class definition */
 class pPie
  {
   var $pChartObject;
   var $pDataObject;

   /* Class creator */
   function pPie($Object,$pDataObject)
    {
     /* Cache the pChart object reference */
     $this->pChartObject = $Object;

     /* Cache the pData object reference */
     $this->pDataObject  = $pDataObject;
    }

   /* Draw a pie chart */
   function draw2DPie($X,$Y,$Format="")
    {
     /* Rendering layout */
     $Radius		= isset($Format["Radius"]) ? $Format["Radius"] : 60;
     $DataGapAngle	= isset($Format["DataGapAngle"]) ? $Format["DataGapAngle"] : 0;
     $DataGapRadius	= isset($Format["DataGapRadius"]) ? $Format["DataGapRadius"] : 0;
     $SecondPass	= isset($Format["SecondPass"]) ? $Format["SecondPass"] : TRUE;
     $Border		= isset($Format["Border"]) ? $Format["Border"] : FALSE;
     $BorderR		= isset($Format["BorderR"]) ? $Format["BorderR"] : 255;
     $BorderG		= isset($Format["BorderG"]) ? $Format["BorderG"] : 255;
     $BorderB		= isset($Format["BorderB"]) ? $Format["BorderB"] : 255;
     $Shadow		= isset($Format["Shadow"]) ? $Format["Shadow"] : FALSE;
     $DrawLabels	= isset($Format["DrawLabels"]) ? $Format["DrawLabels"] : FALSE;
     $LabelColor	= isset($Format["LabelColor"]) ? $Format["LabelColor"] : PIE_LABEL_COLOR_MANUAL;
     $LabelR		= isset($Format["LabelR"]) ? $Format["LabelR"] : 0;
     $LabelG		= isset($Format["LabelG"]) ? $Format["LabelG"] : 0;
     $LabelB		= isset($Format["LabelB"]) ? $Format["LabelB"] : 0;
     $LabelAlpha	= isset($Format["LabelAlpha"]) ? $Format["LabelAlpha"] : 100;

     /* Data Processing */
     $Data    = $this->pDataObject->getData();
     $Palette = $this->pDataObject->getPalette();

     /* Do we have an abscissa serie defined? */
     if ( $Data["Abscissa"] == "" ) { return(PIE_NO_ABSCISSA); }

     /* Try to find the data serie */
     $DataSerie = "";
     foreach ($Data["Series"] as $SerieName => $SerieData)
      { if ( $SerieName != $Data["Abscissa"]) { $DataSerie = $SerieName; } }

     /* Do we have data to compute? */
     if ( $DataSerie == "" ) { return(PIE_NO_DATASERIE); }

     /* Compute the pie sum */
     $SerieSum = $this->pDataObject->getSum($DataSerie);

     /* Do we have data to draw? */
     if ( $SerieSum == 0 ) { return(PIE_SUMISNULL); }

     /* Dump the real number of data to draw */
     $Values = "";
     $PaletteAux = array(); // Fix to store only necesary colors
     foreach ($Data["Series"][$DataSerie]["Data"] as $Key => $Value)
      { if ($Value != 0) { $Values[$Key] = $Value; $PaletteAux[] = $Palette[$Key]; } }
	 
	 $Palette = $PaletteAux;
	 
     /* Compute the wasted angular space between series */
     if (count($Values)==1) { $WastedAngular = 0; } else { $WastedAngular = count($Values) * $DataGapAngle; }

     /* Compute the scale */
     $ScaleFactor = (360 - $WastedAngular) / $SerieSum;

     $RestoreShadow = $this->pChartObject->Shadow;
     if ( $this->pChartObject->Shadow )
      {
       $this->pChartObject->Shadow = FALSE;

       $ShadowFormat = $Format; $ShadowFormat["Shadow"] = TRUE;
       $this->draw2DPie($X+$this->pChartObject->ShadowX,$Y+$this->pChartObject->ShadowY,$ShadowFormat);
      }

     /* Draw the polygon pie elements */
     $Step = 360 / (2 * PI * $Radius);
     $Offset = 0; $ID = 0;
     foreach($Values as $Key => $Value)
      {
       if ( $Shadow )
        $Settings = array("R"=>$this->pChartObject->ShadowR,"G"=>$this->pChartObject->ShadowG,"B"=>$this->pChartObject->ShadowB,"Alpha"=>$this->pChartObject->Shadowa);
       else
        $Settings = array("R"=>$Palette[$ID]["R"],"G"=>$Palette[$ID]["G"],"B"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);

       if ( !$SecondPass && !$Shadow )
        {
         if ( !$Border )
          $Settings["Surrounding"] = 10;
         else
          { $Settings["BorderR"] = $BorderR; $Settings["BorderG"] = $BorderG; $Settings["BorderB"] = $BorderB; }
        }

       $Plots = "";
       $EndAngle = $Offset+($Value*$ScaleFactor); if ( $EndAngle > 360 ) { $EndAngle = 360; }

       $Angle = ($EndAngle - $Offset)/2 + $Offset;
       if ($DataGapAngle == 0)
        { $X0 = $X; $Y0 = $Y; }
       else
        {
         $X0 = cos(($Angle-90)*PI/180) * $DataGapRadius + $X;
         $Y0 = sin(($Angle-90)*PI/180) * $DataGapRadius + $Y;
        }

       $Plots[] = $X0; $Plots[] = $Y0;


       for($i=$Offset;$i<=$EndAngle;$i=$i+$Step)
        {
         $Xc = cos(($i-90)*PI/180) * $Radius + $X;
         $Yc = sin(($i-90)*PI/180) * $Radius + $Y;

         if ( $SecondPass && ( $i<90 )) { $Yc++; }
         if ( $SecondPass && ( $i>180 && $i<270 )) { $Xc++; }
         if ( $SecondPass && ( $i>=270 )) { $Xc++; $Yc++; }

         $Plots[] = $Xc; $Plots[] = $Yc;
        }

       $this->pChartObject->drawPolygon($Plots,$Settings);

       if ( $DrawLabels && !$Shadow && !$SecondPass )
        {
         if ( $LabelColor == PIE_LABEL_COLOR_AUTO )
          { $Settings = array("FillR"=>$Palette[$ID]["R"],"FillG"=>$Palette[$ID]["G"],"FillB"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);}
         else
          { $Settings = array("FillR"=>$LabelR,"FillG"=>$LabelG,"FillB"=>$LabelB,"Alpha"=>$LabelAlpha); }

         $Angle = ($EndAngle - $Offset)/2 + $Offset;
         $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
         $Yc = sin(($Angle-90)*PI/180) * $Radius + $Y;

         $Label = $Data["Series"][$Data["Abscissa"]]["Data"][$Key];

         $Settings["Angle"]  = 360-$Angle;
         $Settings["Length"] = 25;
         $Settings["Size"]   = 8;
         $this->pChartObject->drawArrowLabel($Xc,$Yc," ".$Label." ",$Settings);
        }

       $Offset = $i + $DataGapAngle; $ID++;
      }

     /* Second pass to smooth the angles */
     if ( $SecondPass )
      {
       $Step = 360 / (2 * PI * $Radius);
       $Offset = 0; $ID = 0;
       foreach($Values as $Key => $Value)
        {
         $FirstPoint = TRUE;
         if ( $Shadow )
          $Settings = array("R"=>$this->pChartObject->ShadowR,"G"=>$this->pChartObject->ShadowG,"B"=>$this->pChartObject->ShadowB,"Alpha"=>$this->pChartObject->Shadowa);
         else
          {
           if ( $Border )
            $Settings = array("R"=>$BorderR,"G"=>$BorderG,"B"=>$BorderB);
           else
            $Settings = array("R"=>$Palette[$ID]["R"],"G"=>$Palette[$ID]["G"],"B"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);
          }

         $EndAngle = $Offset+($Value*$ScaleFactor); if ( $EndAngle > 360 ) { $EndAngle = 360; }

         if ($DataGapAngle == 0)
          { $X0 = $X; $Y0 = $Y; }
         else
          {
           $Angle = ($EndAngle - $Offset)/2 + $Offset;
           $X0 = cos(($Angle-90)*PI/180) * $DataGapRadius + $X;
           $Y0 = sin(($Angle-90)*PI/180) * $DataGapRadius + $Y;
          }
         $Plots[] = $X0; $Plots[] = $Y0;

         for($i=$Offset;$i<=$EndAngle;$i=$i+$Step)
          {
           $Xc = cos(($i-90)*PI/180) * $Radius + $X;
           $Yc = sin(($i-90)*PI/180) * $Radius + $Y;

           if ( $FirstPoint ) { $this->pChartObject->drawLine($Xc,$Yc,$X0,$Y0,$Settings); } { $FirstPoint = FALSE; }
  
           $this->pChartObject->drawAntialiasPixel($Xc,$Yc,$Settings);
          }
         $this->pChartObject->drawLine($Xc,$Yc,$X0,$Y0,$Settings);

         if ( $DrawLabels && !$Shadow )
          {
           if ( $LabelColor == PIE_LABEL_COLOR_AUTO )
            { $Settings = array("FillR"=>$Palette[$ID]["R"],"FillG"=>$Palette[$ID]["G"],"FillB"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);}
           else
            { $Settings = array("FillR"=>$LabelR,"FillG"=>$LabelG,"FillB"=>$LabelB,"Alpha"=>$LabelAlpha); }

           $Angle = ($EndAngle - $Offset)/2 + $Offset;
           $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
           $Yc = sin(($Angle-90)*PI/180) * $Radius + $Y;

           $Label = $Data["Series"][$Data["Abscissa"]]["Data"][$Key];

           $Settings["Angle"]  = 360-$Angle;
           $Settings["Length"] = 25;
           $Settings["Size"]   = 8;

           $this->pChartObject->drawArrowLabel($Xc,$Yc," ".$Label." ",$Settings);
          }
  
         $Offset = $i + $DataGapAngle; $ID++;
        }
      }

     $this->pChartObject->Shadow = $RestoreShadow;

     return(PIE_RENDERED);
    }

   /* Draw a 3D pie chart */
   function draw3DPie($X,$Y,$Format="")
    {
     /* Rendering layout */
     $Radius		= isset($Format["Radius"]) ? $Format["Radius"] : 80;
     $SkewFactor	= isset($Format["SkewFactor"]) ? $Format["SkewFactor"] : .5;
     $SliceHeight	= isset($Format["SliceHeight"]) ? $Format["SliceHeight"] : 20;
     $DataGapAngle	= isset($Format["DataGapAngle"]) ? $Format["DataGapAngle"] : 0;
     $DataGapRadius	= isset($Format["DataGapRadius"]) ? $Format["DataGapRadius"] : 0;
     $SecondPass	= isset($Format["SecondPass"]) ? $Format["SecondPass"] : TRUE;
     $Border		= isset($Format["Border"]) ? $Format["Border"] : FALSE;
     $Shadow		= isset($Format["Shadow"]) ? $Format["Shadow"] : FALSE;
     $DrawLabels	= isset($Format["DrawLabels"]) ? $Format["DrawLabels"] : FALSE;
     $LabelColor	= isset($Format["LabelColor"]) ? $Format["LabelColor"] : PIE_LABEL_COLOR_MANUAL;
     $LabelR		= isset($Format["LabelR"]) ? $Format["LabelR"] : 0;
     $LabelG		= isset($Format["LabelG"]) ? $Format["LabelG"] : 0;
     $LabelB		= isset($Format["LabelB"]) ? $Format["LabelB"] : 0;
     $LabelAlpha	= isset($Format["LabelAlpha"]) ? $Format["LabelAlpha"] : 100;
     $WriteValues	= isset($Format["WriteValues"]) ? $Format["WriteValues"] : PIE_VALUE_PERCENTAGE;
     $ValueSuffix	= isset($Format["ValueSuffix"]) ? $Format["ValueSuffix"] : "";
     $ValueR		= isset($Format["ValueR"]) ? $Format["ValueR"] : 255;
     $ValueG		= isset($Format["ValueG"]) ? $Format["ValueG"] : 255;
     $ValueB		= isset($Format["ValueB"]) ? $Format["ValueB"] : 255;
     $ValueAlpha	= isset($Format["ValueAlpha"]) ? $Format["ValueAlpha"] : 100;

     /* Error correction for overlaying rounded corners */
     if ( $SkewFactor < .5 ) { $SkewFactor = .5; }

     /* Data Processing */
     $Data    = $this->pDataObject->getData();
     $Palette = $this->pDataObject->getPalette();

     /* Do we have an abscissa serie defined? */
     if ( $Data["Abscissa"] == "" ) { return(PIE_NO_ABSCISSA); }

     /* Try to find the data serie */
     $DataSerie = "";
     foreach ($Data["Series"] as $SerieName => $SerieData)
      { if ( $SerieName != $Data["Abscissa"]) { $DataSerie = $SerieName; } }

     /* Do we have data to compute? */
     if ( $DataSerie == "" ) { return(PIE_NO_DATASERIE); }

     /* Compute the pie sum */
     $SerieSum = $this->pDataObject->getSum($DataSerie);

     /* Do we have data to draw? */
     if ( $SerieSum == 0 ) { return(PIE_SUMISNULL); }

     /* Dump the real number of data to draw */
     $Values = "";
     $PaletteAux = array(); // Fix to store only necesary colors
     foreach ($Data["Series"][$DataSerie]["Data"] as $Key => $Value)
      { if ($Value != 0) { $Values[$Key] = $Value; $PaletteAux[] = $Palette[$Key]; } }
	 
	 $Palette = $PaletteAux;
	 
     /* Compute the wasted angular space between series */
     if (count($Values)==1) { $WastedAngular = 0; } else { $WastedAngular = count($Values) * $DataGapAngle; }

     /* Compute the scale */
     $ScaleFactor = (360 - $WastedAngular) / $SerieSum;

     $RestoreShadow = $this->pChartObject->Shadow;
     if ( $this->pChartObject->Shadow ) { $this->pChartObject->Shadow = FALSE; }

     /* Draw the polygon pie elements */
     $Step   = 360 / (2 * PI * $Radius);
     $Offset = 360;
      
	 $ID = count($Values)-1;

	 // Commented due to fix bellow
     // $Values = array_reverse($Values);
     $Slice  = 0; $Slices = ""; $SliceColors = ""; $Visible = ""; $SliceAngle = "";
     foreach($Values as $Key => $Value)
      {
  
       $Settings = array("R"=>$Palette[$ID]["R"],"G"=>$Palette[$ID]["G"],"B"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);
       $SliceColors[$Slice] = $Settings;

       $StartAngle = $Offset;
       $EndAngle   = $Offset-($Value*$ScaleFactor); if ( $EndAngle < 0 ) { $EndAngle = 0; }

       if ( $StartAngle > 180 ) { $Visible[$Slice]["Start"] = TRUE; } else { $Visible[$Slice]["Start"] = TRUE; }
       if ( $EndAngle < 180 )   { $Visible[$Slice]["End"] = FALSE; } else { $Visible[$Slice]["End"] = TRUE; }

       if ($DataGapAngle == 0)
        { $X0 = $X; $Y0 = $Y; }
       else
        {
         $Angle = ($EndAngle - $Offset)/2 + $Offset;
         $X0 = cos(($Angle-90)*PI/180) * $DataGapRadius + $X;
         $Y0 = sin(($Angle-90)*PI/180) * $DataGapRadius*$SkewFactor + $Y;
        }
       $Slices[$Slice][] = $X0; $Slices[$Slice][] = $Y0; $SliceAngle[$Slice][] = 0;

       for($i=$Offset;$i>=$EndAngle;$i=$i-$Step)
        {
         $Xc = cos(($i-90)*PI/180) * $Radius + $X;
         $Yc = sin(($i-90)*PI/180) * $Radius*$SkewFactor + $Y;

         if ( ($SecondPass || $RestoreShadow ) && ( $i<90 )) { $Yc++; }
         if ( ($SecondPass || $RestoreShadow ) && ( $i>90 && $i<180 )) { $Xc++; }
         if ( ($SecondPass || $RestoreShadow ) && ( $i>180 && $i<270 )) { $Xc++; }
         if ( ($SecondPass || $RestoreShadow ) && ( $i>=270 )) { $Xc++; $Yc++; }

         $Slices[$Slice][] = $Xc; $Slices[$Slice][] = $Yc; $SliceAngle[$Slice][] = $i;
        }

       $Offset = $i - $DataGapAngle; $ID--; $Slice++;
      }

     /* Draw the bottom shadow if needed */
     if ( $RestoreShadow && ($this->pChartObject->ShadowX != 0 || $this->pChartObject->ShadowY !=0 ))
      {
       foreach($Slices as $SliceID => $Plots)
        {
         $ShadowPie = "";
         for($i=0;$i<count($Plots);$i=$i+2)
          { $ShadowPie[] = $Plots[$i]+$this->pChartObject->ShadowX; $ShadowPie[] = $Plots[$i+1]+$this->pChartObject->ShadowY; }

         $Settings = array("R"=>$this->pChartObject->ShadowR,"G"=>$this->pChartObject->ShadowG,"B"=>$this->pChartObject->ShadowB,"Alpha"=>$this->pChartObject->Shadowa,"NoBorder"=>TRUE);
         $this->pChartObject->drawPolygon($ShadowPie,$Settings);
        }

       $Step = 360 / (2 * PI * $Radius);
       $Offset = 360;
       foreach($Values as $Key => $Value)
        {
         $EndAngle = $Offset-($Value*$ScaleFactor); if ( $EndAngle < 0 ) { $EndAngle = 0; }

         for($i=$Offset;$i>=$EndAngle;$i=$i-$Step)
          {
           $Xc = cos(($i-90)*PI/180) * $Radius + $X + $this->pChartObject->ShadowX;
           $Yc = sin(($i-90)*PI/180) * $Radius*$SkewFactor + $Y + $this->pChartObject->ShadowY;

           $this->pChartObject->drawAntialiasPixel($Xc,$Yc,$Settings);
          }

         $Offset = $i - $DataGapAngle; $ID--;
        }
      }

     /* Draw the bottom pie splice */
     foreach($Slices as $SliceID => $Plots)
      {
       $Settings = $SliceColors[$SliceID];  $Settings["NoBorder"] = TRUE;
       $this->pChartObject->drawPolygon($Plots,$Settings);

       if ( $SecondPass )
        {
         $Settings = $SliceColors[$SliceID];
         if ( $Border )
          { $Settings["R"]+= 30; $Settings["G"]+= 30; $Settings["B"]+= 30;; }
  
         $Angle = $SliceAngle[$SliceID][1];
         $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
         $Yc = sin(($Angle-90)*PI/180) * $Radius*$SkewFactor + $Y;
         $this->pChartObject->drawLine($Plots[0],$Plots[1],$Xc,$Yc,$Settings);

         $Angle = $SliceAngle[$SliceID][count($SliceAngle[$SliceID])-1];
         $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
         $Yc = sin(($Angle-90)*PI/180) * $Radius*$SkewFactor + $Y;
         $this->pChartObject->drawLine($Plots[0],$Plots[1],$Xc,$Yc,$Settings);
        }
      }

     /* Draw the two vertical edges */
     $Slices      = array_reverse($Slices);
     $SliceColors = array_reverse($SliceColors);
     foreach($Slices as $SliceID => $Plots)
      {
       $Settings = $SliceColors[$SliceID];
       $Settings["R"]+= 10; $Settings["G"]+= 10; $Settings["B"]+= 10; $Settings["NoBorder"] = TRUE;

       if ( $Visible[$SliceID]["Start"] )
        {
         $this->pChartObject->drawLine($Plots[2],$Plots[3],$Plots[2],$Plots[3]- $SliceHeight,array("R"=>255,"G"=>255,"B"=>255));
         $Border = "";
         $Border[] = $Plots[0]; $Border[] = $Plots[1]; $Border[] = $Plots[0]; $Border[] = $Plots[1] - $SliceHeight;
         $Border[] = $Plots[2]; $Border[] = $Plots[3] - $SliceHeight; $Border[] = $Plots[2]; $Border[] = $Plots[3]; 
         $this->pChartObject->drawPolygon($Border,$Settings);
        }
      }

     $Slices      = array_reverse($Slices);
     $SliceColors = array_reverse($SliceColors);
     foreach($Slices as $SliceID => $Plots)
      {
       $Settings = $SliceColors[$SliceID];
       $Settings["R"]+= 10; $Settings["G"]+= 10; $Settings["B"]+= 10; $Settings["NoBorder"] = TRUE;
       if ( $Visible[$SliceID]["End"] )
        {
         $this->pChartObject->drawLine($Plots[count($Plots)-2],$Plots[count($Plots)-1],$Plots[count($Plots)-2],$Plots[count($Plots)-1]- $SliceHeight,array("R"=>255,"G"=>255,"B"=>255));

         $Border = "";
         $Border[] = $Plots[0]; $Border[] = $Plots[1]; $Border[] = $Plots[0]; $Border[] = $Plots[1] - $SliceHeight;
         $Border[] = $Plots[count($Plots)-2]; $Border[] = $Plots[count($Plots)-1] - $SliceHeight; $Border[] = $Plots[count($Plots)-2]; $Border[] = $Plots[count($Plots)-1]; 
         $this->pChartObject->drawPolygon($Border,$Settings);
        }
      }

     /* Draw the rounded edges */
     foreach($Slices as $SliceID => $Plots)
      {
       $Settings = $SliceColors[$SliceID];
       $Settings["R"]+= 10; $Settings["G"]+= 10; $Settings["B"]+= 10; $Settings["NoBorder"] = TRUE;

       for ($j=2;$j<count($Plots)-2;$j=$j+2)
        {
         $Angle = $SliceAngle[$SliceID][$j/2];
         if ( $Angle < 270 && $Angle > 90 )
          {
           $Border = "";
           $Border[] = $Plots[$j];   $Border[] = $Plots[$j+1];
           $Border[] = $Plots[$j+2]; $Border[] = $Plots[$j+3];
           $Border[] = $Plots[$j+2]; $Border[] = $Plots[$j+3] - $SliceHeight;
           $Border[] = $Plots[$j];   $Border[] = $Plots[$j+1] - $SliceHeight;
           $this->pChartObject->drawPolygon($Border,$Settings);
          }
        }

       if ( $SecondPass )
        {
         $Settings = $SliceColors[$SliceID];
         if ( $Border )
          { $Settings["R"]+= 30; $Settings["G"]+= 30; $Settings["B"]+= 30; }
  
         $Angle = $SliceAngle[$SliceID][1];
         if ( $Angle < 270 && $Angle > 90 )
          {
           $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
           $Yc = sin(($Angle-90)*PI/180) * $Radius*$SkewFactor + $Y;
           $this->pChartObject->drawLine($Xc,$Yc,$Xc,$Yc-$SliceHeight,$Settings);
          }

         $Angle = $SliceAngle[$SliceID][count($SliceAngle[$SliceID])-1];
         if ( $Angle < 270 && $Angle > 90 )
          {
           $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
           $Yc = sin(($Angle-90)*PI/180) * $Radius*$SkewFactor + $Y;
           $this->pChartObject->drawLine($Xc,$Yc,$Xc,$Yc-$SliceHeight,$Settings);
          }

         if ( $SliceAngle[$SliceID][1] > 270 && $SliceAngle[$SliceID][count($SliceAngle[$SliceID])-1] < 270 )
          {
           $Xc = cos((270-90)*PI/180) * $Radius + $X;
           $Yc = sin((270-90)*PI/180) * $Radius*$SkewFactor + $Y;
           $this->pChartObject->drawLine($Xc,$Yc,$Xc,$Yc-$SliceHeight,$Settings);
          }

         if ( $SliceAngle[$SliceID][1] > 90 && $SliceAngle[$SliceID][count($SliceAngle[$SliceID])-1] < 90 )
          {
           $Xc = cos((0)*PI/180) * $Radius + $X;
           $Yc = sin((0)*PI/180) * $Radius*$SkewFactor + $Y;
           $this->pChartObject->drawLine($Xc,$Yc,$Xc,$Yc-$SliceHeight,$Settings);
          }

        }
      }

     /* Draw the top splice */
     foreach($Slices as $SliceID => $Plots)
      {
       $Settings = $SliceColors[$SliceID];
       $Settings["R"]+= 20; $Settings["G"]+= 20; $Settings["B"]+= 20;

       $Top = "";
       for($j=0;$j<count($Plots);$j=$j+2) { $Top[] = $Plots[$j]; $Top[] = $Plots[$j+1]- $SliceHeight; }
       $this->pChartObject->drawPolygon($Top,$Settings);
      }
		
		
		/* Second pass to smooth the angles */
		if ( $SecondPass )
		{
			$Step = 360 / (2 * PI * $Radius);
			$Offset = 360;
			$ID = count($Values)-1;
			foreach($Values as $Key => $Value)
			{
				$FirstPoint = TRUE;
				if ( $Shadow )
					$Settings = array("R"=>$this->pChartObject->ShadowR,"G"=>$this->pChartObject->ShadowG,"B"=>$this->pChartObject->ShadowB,"Alpha"=>$this->pChartObject->Shadowa);
				else
				{
					if ( $Border )
					{
						$Settings = array(
							"R" => $Palette[$ID]["R"] + 30,
							"G" => $Palette[$ID]["G"] + 30,
							"B" => $Palette[$ID]["B"] + 30,
							"Alpha" => $Palette[$ID]["Alpha"]);
					}
					else
						$Settings = array("R"=>$Palette[$ID]["R"],"G"=>$Palette[$ID]["G"],"B"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);
				}
				
				$EndAngle = $Offset-($Value*$ScaleFactor); if ( $EndAngle < 0 ) { $EndAngle = 0; }
				
				if ($DataGapAngle == 0)
				{ $X0 = $X; $Y0 = $Y- $SliceHeight; }
				else
				{
					$Angle = ($EndAngle - $Offset)/2 + $Offset;
					$X0 = cos(($Angle-90)*PI/180) * $DataGapRadius + $X;
					$Y0 = sin(($Angle-90)*PI/180) * $DataGapRadius*$SkewFactor + $Y - $SliceHeight;
				}
				$Plots[] = $X0; $Plots[] = $Y0;
				
				for ($i=$Offset;$i>=$EndAngle;$i=$i-$Step)
				{
					$Xc = cos(($i-90)*PI/180) * $Radius + $X;
					$Yc = sin(($i-90)*PI/180) * $Radius*$SkewFactor + $Y - $SliceHeight;
					
					if ( $FirstPoint )
					{ $this->pChartObject->drawLine($Xc,$Yc,$X0,$Y0,$Settings); }
					else
					{ $FirstPoint = FALSE; }
					
					$this->pChartObject->drawAntialiasPixel($Xc,$Yc,$Settings);
					if ($i < 270 && $i > 90 )
					{ $this->pChartObject->drawAntialiasPixel($Xc,$Yc+$SliceHeight,$Settings); }
				}
				$this->pChartObject->drawLine($Xc,$Yc,$X0,$Y0,$Settings);
				
				$Offset = $i - $DataGapAngle; $ID--;
			}
		}
		
     if ( $WriteValues != NULL )
      {
       $Step = 360 / (2 * PI * $Radius);
       $Offset = 360; $ID = count($Values)-1;
       $Settings = array("Align"=>TEXT_ALIGN_MIDDLEMIDDLE,"R"=>$ValueR,"G"=>$ValueG,"B"=>$ValueB,"Alpha"=>$ValueAlpha);
       foreach($Values as $Key => $Value)
        {
         $EndAngle = $Offset-($Value*$ScaleFactor); if ( $EndAngle < 0 ) { $EndAngle = 0; }

         $Angle = ($EndAngle - $Offset)/2 + $Offset;
         $Xc = cos(($Angle-90)*PI/180) * ($Radius)/2 + $X;
         $Yc = sin(($Angle-90)*PI/180) * ($Radius*$SkewFactor)/2 + $Y - $SliceHeight;

         if ( $WriteValues == PIE_VALUE_PERCENTAGE )
          $Display = round(( 100 / $SerieSum ) * $Value)."%";
         elseif ( $WriteValues == PIE_VALUE_NATURAL )
          $Display = $Value.$ValueSuffix;

         $this->pChartObject->drawText($Xc,$Yc,$Display,$Settings);

         $Offset = $EndAngle - $DataGapAngle; $ID--;
        }
      }

     if ( $DrawLabels )
      {
       $Step = 360 / (2 * PI * $Radius);
       $Offset = 360; $ID = count($Values)-1;
       foreach($Values as $Key => $Value)
        {
         if ( $LabelColor == PIE_LABEL_COLOR_AUTO )
          { $Settings = array("FillR"=>$Palette[$ID]["R"],"FillG"=>$Palette[$ID]["G"],"FillB"=>$Palette[$ID]["B"],"Alpha"=>$Palette[$ID]["Alpha"]);}
         else
          { $Settings = array("FillR"=>$LabelR,"FillG"=>$LabelG,"FillB"=>$LabelB,"Alpha"=>$LabelAlpha); }

         $EndAngle = $Offset-($Value*$ScaleFactor); if ( $EndAngle < 0 ) { $EndAngle = 0; }

         $Angle = ($EndAngle - $Offset)/2 + $Offset;
         $Xc = cos(($Angle-90)*PI/180) * $Radius + $X;
         $Yc = sin(($Angle-90)*PI/180) * $Radius*$SkewFactor + $Y - $SliceHeight;

         if ( isset($Data["Series"][$Data["Abscissa"]]["Data"][$ID]) )
          {
           $Label = $Data["Series"][$Data["Abscissa"]]["Data"][$ID];

           $Settings["Angle"]  = 360-$Angle;
           $Settings["Length"] = 25;
           $Settings["Size"]   = 8;
           $this->pChartObject->drawArrowLabel($Xc,$Yc," ".$Label." ",$Settings);
          }

         $Offset = $EndAngle - $DataGapAngle; $ID--;
        }
      }

     $this->pChartObject->Shadow = $RestoreShadow;

     return(PIE_RENDERED);
    }

   /* Draw the legend of pie chart */
   function drawPieLegend($X,$Y,$Format="")
    {
     $FontName		= isset($Format["FontName"]) ? $Format["FontName"] : $this->pChartObject->FontName;
     $FontSize		= isset($Format["FontSize"]) ? $Format["FontSize"] : $this->pChartObject->FontSize;
     $FontR		= isset($Format["FontR"]) ? $Format["FontR"] : $this->pChartObject->FontColorR;
     $FontG		= isset($Format["FontG"]) ? $Format["FontG"] : $this->pChartObject->FontColorG;
     $FontB		= isset($Format["FontB"]) ? $Format["FontB"] : $this->pChartObject->FontColorB;
     $BoxSize		= isset($Format["BoxSize"]) ? $Format["BoxSize"] : 5;
     $Margin		= isset($Format["Margin"]) ? $Format["Margin"] : 5;
     $R			= isset($Format["R"]) ? $Format["R"] : 200;
     $G			= isset($Format["G"]) ? $Format["G"] : 200;
     $B			= isset($Format["B"]) ? $Format["B"] : 200;
     $Alpha		= isset($Format["Alpha"]) ? $Format["Alpha"] : 100;
     $BorderR		= isset($Format["BorderR"]) ? $Format["BorderR"] : 255;
     $BorderG		= isset($Format["BorderG"]) ? $Format["BorderG"] : 255;
     $BorderB		= isset($Format["BorderB"]) ? $Format["BorderB"] : 255;
     $Surrounding	= isset($Format["Surrounding"]) ? $Format["Surrounding"] : NULL;
     $Style		= isset($Format["Style"]) ? $Format["Style"] : LEGEND_ROUND;
     $Mode		= isset($Format["Mode"]) ? $Format["Mode"] : LEGEND_VERTICAL;

     if ( $Surrounding != NULL ) { $BorderR = $R + $Surrounding; $BorderG = $G + $Surrounding; $BorderB = $B + $Surrounding; }

     $YStep = max($this->pChartObject->FontSize,$BoxSize) + 5;
     $XStep = $BoxSize + 5;

     /* Data Processing */
     $Data    = $this->pDataObject->getData();
     $Palette = $this->pDataObject->getPalette();

     /* Do we have an abscissa serie defined? */
     if ( $Data["Abscissa"] == "" ) { return(PIE_NO_ABSCISSA); }

     $Boundaries = ""; $Boundaries["L"] = $X; $Boundaries["T"] = $Y; $Boundaries["R"] = 0; $Boundaries["B"] = 0; $vY = $Y; $vX = $X;
     foreach($Data["Series"][$Data["Abscissa"]]["Data"] as $Key => $Value)
      {
       $BoxArray = $this->pChartObject->getTextBox($vX+$BoxSize+4,$vY+$BoxSize/2,$FontName,$FontSize,0,$Value);

       if ( $Mode == LEGEND_VERTICAL )
        {
         if ( $Boundaries["T"] > $BoxArray[2]["Y"]+$BoxSize/2 ) { $Boundaries["T"] = $BoxArray[2]["Y"]+$BoxSize/2; }
         if ( $Boundaries["R"] < $BoxArray[1]["X"]+2 ) { $Boundaries["R"] = $BoxArray[1]["X"]+2; }
         if ( $Boundaries["B"] < $BoxArray[1]["Y"]+2+$BoxSize/2 ) { $Boundaries["B"] = $BoxArray[1]["Y"]+2+$BoxSize/2; }
         $vY=$vY+$YStep;
        }
       elseif ( $Mode == LEGEND_HORIZONTAL )
        {
         if ( $Boundaries["T"] > $BoxArray[2]["Y"]+$BoxSize/2 ) { $Boundaries["T"] = $BoxArray[2]["Y"]+$BoxSize/2; }
         if ( $Boundaries["R"] < $BoxArray[1]["X"]+2 ) { $Boundaries["R"] = $BoxArray[1]["X"]+2; }
         if ( $Boundaries["B"] < $BoxArray[1]["Y"]+2+$BoxSize/2 ) { $Boundaries["B"] = $BoxArray[1]["Y"]+2+$BoxSize/2; }
         $vX=$Boundaries["R"]+$XStep;
        }
      }
     $vY=$vY-$YStep; $vX=$vX-$XStep;

     $TopOffset  = $Y - $Boundaries["T"];
     if ( $Boundaries["B"]-($vY+$BoxSize) < $TopOffset ) { $Boundaries["B"] = $vY+$BoxSize+$TopOffset; }

     if ( $Style == LEGEND_ROUND )
      $this->pChartObject->drawRoundedFilledRectangle($Boundaries["L"]-$Margin,$Boundaries["T"]-$Margin,$Boundaries["R"]+$Margin,$Boundaries["B"]+$Margin,$Margin,array("R"=>$R,"G"=>$G,"B"=>$B,"Alpha"=>$Alpha,"BorderR"=>$BorderR,"BorderG"=>$BorderG,"BorderB"=>$BorderB));
     elseif ( $Style == LEGEND_BOX )
      $this->pChartObject->drawFilledRectangle($Boundaries["L"]-$Margin,$Boundaries["T"]-$Margin,$Boundaries["R"]+$Margin,$Boundaries["B"]+$Margin,array("R"=>$R,"G"=>$G,"B"=>$B,"Alpha"=>$Alpha,"BorderR"=>$BorderR,"BorderG"=>$BorderG,"BorderB"=>$BorderB));

     $RestoreShadow = $this->pChartObject->Shadow; $this->pChartObject->Shadow = FALSE;
     foreach($Data["Series"][$Data["Abscissa"]]["Data"] as $Key => $Value)
      {
       $R = $Palette[$Key]["R"]; $G = $Palette[$Key]["G"]; $B = $Palette[$Key]["B"];

       $this->pChartObject->drawFilledRectangle($X+1,$Y+1,$X+$BoxSize+1,$Y+$BoxSize+1,array("R"=>0,"G"=>0,"B"=>0,"Alpha"=>20));
       $this->pChartObject->drawFilledRectangle($X,$Y,$X+$BoxSize,$Y+$BoxSize,array("R"=>$R,"G"=>$G,"B"=>$B,"Surrounding"=>20));
       if ( $Mode == LEGEND_VERTICAL )
        {
         $this->pChartObject->drawText($X+$BoxSize+4,$Y+$BoxSize/2,$Value,array("R"=>$FontR,"G"=>$FontG,"B"=>$FontB,"Align"=>TEXT_ALIGN_MIDDLELEFT));
         $Y=$Y+$YStep;
        }
       elseif ( $Mode == LEGEND_HORIZONTAL )
        {
         $BoxArray = $this->pChartObject->drawText($X+$BoxSize+4,$Y+$BoxSize/2,$Value,array("R"=>$FontR,"G"=>$FontG,"B"=>$FontB,"Align"=>TEXT_ALIGN_MIDDLELEFT));
         $X=$BoxArray[1]["X"]+2+$XStep;
        }
      }

     $this->Shadow = $RestoreShadow;
    }

   /* Set the color of the specified slice */
   function setSliceColor($SliceID,$Format="")
    {
     $R		= isset($Format["R"]) ? $Format["R"] : 0;
     $G		= isset($Format["G"]) ? $Format["G"] : 0;
     $B		= isset($Format["B"]) ? $Format["B"] : 0;
     $Alpha	= isset($Format["Alpha"]) ? $Format["Alpha"] : 100;

     $this->pDataObject->Palette[$SliceID]["R"]     = $R;
     $this->pDataObject->Palette[$SliceID]["G"]     = $G;
     $this->pDataObject->Palette[$SliceID]["B"]     = $B;
     $this->pDataObject->Palette[$SliceID]["Alpha"] = $Alpha;
    }
  }
?>
