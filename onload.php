<?php

require("db_functions.php");

echo "
var cuentaObj=0;


window.onload = function() {
//FormSetup();
// get parameters

    var query=this.location.search.substring(1);
    if (query.length > 0){
        var params=query.split(\"&\");
        for (var i=0 ; i < params.length ; i++){
            var pos = params[i].indexOf(\"=\");
            var name = params[i].substring(0, pos);
            var value = params[i].substring(pos + 1);
	    if (name == \"menuLeft\" )
		{
		menuLeft = value;
		} else if (name == \"menuTop\")
		{
			menuTop = value;
		}
	
        }
    }



  // relative, under heading
  menu1 = new xMenu2(
    false, true, false,         // absolute, horizontal, floating,
    0, 0, 3,                    // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#cccc99', '#cccc99',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );
  // top-right
  menu2 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    xClientWidth() - 75, xClientHeight() - 80, 5, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#ffccff', '#000080',       // activeColor, inactiveColor,
    '#FF6600', '#CC6600',       // activeBkgnd, inactiveBkgnd
    '#FF6600'                   // boxBkgnd
  );
  // bottom-left
  menu3 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    0, xClientHeight() - 80, 2, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140], 
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#cccc99', '#cccc99',       // activeBkgnd, inactiveBkgnd
    '#cccc99'             // boxWidthsArray,
/*    '#000080', '#FF9999',       // activeColor, inactiveColor,
    '#00CCFF', '#0099FF',       // activeBkgnd, inactiveBkgnd
    '#00CCFF'    */               // boxBkgnd
  );
  // bottom-right
  menu4 = new xMenu2(
    true, true, true,           // absolute, horizontal, floating,
    xClientWidth()-205, xClientHeight()-20, 4, // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000066', '#ffff66',       // activeColor, inactiveColor,
    '#cccc99', '#9999cc',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );
  scrollListener(); // initial slide
  xAddEventListener(document, \"mousemove\", menuHideListener, false);
  xAddEventListener(window, \"resize\", resizeListener, false);
  xAddEventListener(window, \"scroll\", scrollListener, false);

";



?>

        


<?



$objetos = dameObjetos();
// echo "var aObjeto_count = 0;";
// echo "aObjeto_count=1;";
echo "aObjeto = new Array(), aObjeto_count =".mysql_num_rows($objetos).";";
echo "cuentaObj = aObjeto_count;
     var aPos = new Array();
    var cookieData=GetCookie(\"objParams\");
    if (cookieData.length > 0){
        var parametros=cookieData.split(\"&\");
	for (var i=0 ; i < parametros.length ; i++){
            var posicion = parametros[i].indexOf(\"=\");
            var nombre = parametros[i].substring(0, posicion);
            var valor = parametros[i].substring(posicion + 1);

	    if (nombre == i+1 )
		{
		metaposicion = valor;
		    if (metaposicion.length > 0){
        		var metaparams=metaposicion.split(\"x\");
			posX=metaparams[0];
			posY=metaparams[1];
			indexX=nombre+\"X\";
			indexY=nombre+\"Y\";
			aPos[indexX]=posX;
			aPos[indexY]=posY;
;
		    }
		}
	
        }
    }	
";

$i=1;
global $aObjetos;
while ($objeto=mysql_fetch_array($objetos)){

$aObjetos[$i-1]=$objeto["id_objeto"]; // Array php de objetos

echo "aObjeto[".$i."] = new xFenster('fen".$objeto["id_objeto"]."', parseInt(aPos['".$i."X']), parseInt(aPos['".$i."Y']), 'fenBar".$objeto["id_objeto"]."', 'fenResBtn".$objeto["id_objeto"]."', 'fenMaxBtn".$objeto["id_objeto"]."');";

$i=$i+1;
}




echo"

  FormSetup();


}
";
?>
