window.onload = function() {
//FormSetup();
// get parameters

    var query=this.location.search.substring(1);
    if (query.length > 0){
        var params=query.split("&");
        for (var i=0 ; i<params.length ; i++){
            var pos = params[i].indexOf("=");
            var name = params[i].substring(0, pos);
            var value = params[i].substring(pos + 1);
	    if (name == "menuLeft" )
		{
		menuLeft = value;
		} else if (name == "menuTop")
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
  xAddEventListener(document, "mousemove", menuHideListener, false);
  xAddEventListener(window, "resize", resizeListener, false);
  xAddEventListener(window, "scroll", scrollListener, false);

var aObjeto = new Array(), aObjeto_count =3;aObjeto[1] = new xFenster('fen31', i*100, i*100, 'fenBar31', 'fenResBtn31', 'fenMaxBtn31');aObjeto[2] = new xFenster('fen30', i*100, i*100, 'fenBar30', 'fenResBtn30', 'fenMaxBtn30');aObjeto[3] = new xFenster('fen29', i*100, i*100, 'fenBar29', 'fenResBtn29', 'fenMaxBtn29');


/*

var fen = new Array(), fen_count = 3;
for (var i = 1; i <= fen_count; ++i) {
    fen[i] = new xFenster('fen'+i, i*100, i*100, 'fenBar'+i, 'fenResBtn'+i, 'fenMaxBtn'+i);
  }*/

  FormSetup();

}