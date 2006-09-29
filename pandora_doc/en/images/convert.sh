#!/bin/bash

for a in `ls *.gif`
do
   nombre=`echo $a | cut -f 1 -d "." `
   convert $nombre.gif $nombre.png
done

