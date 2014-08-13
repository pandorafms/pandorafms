#!/bin/sh
echo "<module>";
echo "<name>who</name>";
echo "<type>async_string</type>";
echo "<data><![CDATA["
WHO=`who`
if [ "$WHO" = "" ]; then
	echo "None"
else
	echo $WHO
fi
echo "]]></data>"
echo "</module>"

