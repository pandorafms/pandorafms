var webPage = require("webpage");
var page = webPage.create();
var url =
  "http://nova/pandora_console/operation/agentes/stat_win.php?type=sparse&period=86400&id=136574&label=QXJ0aWNhJiN4MjA7d2ViJiN4MjA7cGFnZSYjeDIwO2V4YW1wbGU%3D&refresh=600&draw_events=0";

var r = page.addCookie({
  name: "PHPSESSID" /* required property */,
  value: "23qu7l1sgb3iq3bkaedr724hp3" /* required property */,
  path: "/pandora_console",
  domain: "nova"
});

console.log(r);

page.viewportSize = { width: 750, height: 350 };
page.open(url, function start(status) {
  page.render("output.jpeg", { format: "jpeg", quality: "100" });
  phantom.exit();
});
