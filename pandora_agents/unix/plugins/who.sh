#!/bin/sh
echo "<module>";
echo "<name><![CDATA[who]]></name>";
echo "<type><![CDATA[async_string]]></type>";
echo "<data><![CDATA["
WHO=`who`
if [ "$WHO" = "" ]; then
	echo "None"
else
	echo $WHO
fi
echo "]]></data>"
echo "</module>"

