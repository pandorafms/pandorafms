for a in `ls`; do ESTA=`find ../es/$a 2> /dev/null | wc -l`; if [ $ESTA == 0 ]; then cp $a /tmp; fi ; done 

