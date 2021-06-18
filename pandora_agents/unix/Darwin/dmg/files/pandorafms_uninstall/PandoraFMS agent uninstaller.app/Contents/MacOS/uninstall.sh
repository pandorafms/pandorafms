#!/usr/bin/env bash
cd "$(dirname "$0")"

CONFIRM=`"$(dirname "$0")/../Resources/confirm_uninstall"`
if [ "$CONFIRM" -ne "1" ]
then
	exit 0
fi

OUTPUT=`"$(dirname "$0")/../Resources/ask_root"`
ERROR="$?"

if [ "$?" -gt "0" ]
then
	exit 0
fi

echo $OUTPUT | `sudo -S "$(dirname "$0")/../Resources/uninstall"`
