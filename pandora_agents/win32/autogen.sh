#!/bin/sh
touch NEWS
aclocal \
&& autoconf \
&& autoheader \
&& automake --add-missing
