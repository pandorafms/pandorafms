
function insertFormulario(tipo)
{
if (tipo == 'nuevo_agente')
{

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=\"http://localhost/console/xmenu2.php\" method=\"post\">"
+  "  <P>"
+   " <LABEL for=\"firstname\">First name: </LABEL>"
+   "           <INPUT type=\"text\" id=\"firstname\"><BR>"
+    "<LABEL for=\"lastname\">Last name: </LABEL>"
+   "           <INPUT type=\"text\" id=\"lastname\"><BR>"
+   " <LABEL for=\"email\">email: </LABEL>"
+    "          <INPUT type=\"text\" id=\"email\"><BR>"
+   " <INPUT type=\"radio\" name=\"sex\" value=\"Male\"> Male<BR>"
+   " <INPUT type=\"radio\" name=\"sex\" value=\"Female\"> Female<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");
}

else {

document.write(
"<div id='xForm' class='demoBox'>"
+ "<div id='formCerrBtn' class='demoBtn'>X</div>"
+  "<div id='xFormBar' class='demoBar'>FORMULARIO</div>"
+  "<div class='demoContent'>"
 +"<FORM action=\"http://somesite.com/prog/adduser\" method=\"post\">"
+  "  <P>"
+   " <LABEL for=\"firstname\">Otro: </LABEL>"
+   "           <INPUT type=\"text\" id=\"firstname\"><BR>"
+    "<LABEL for=\"lastname\">Last name: </LABEL>"
+   "           <INPUT type=\"text\" id=\"lastname\"><BR>"
+   " <LABEL for=\"email\">email: </LABEL>"
+    "          <INPUT type=\"text\" id=\"email\"><BR>"
+   " <INPUT type=\"radio\" name=\"sex\" value=\"Male\"> Male<BR>"
+   " <INPUT type=\"radio\" name=\"sex\" value=\"Female\"> Female<BR>"
+   " <INPUT type=\"submit\" value=\"Send\"> <INPUT type=\"reset\">"
+   " </P>"
+" </FORM>"
+  "</div>"
+ "</div>");

}

}