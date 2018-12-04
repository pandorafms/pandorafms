#!/bin/bash
#
# Sample PWRD maintenance script
# **********************************************************************

# **********************************************************************
# Settings
hub_ip="192.168.1.10"
hub_port="4444"

# Customize PWR global installation directory
PWR_FIREFOX_INSTALLDIR="/opt"

# **********************************************************************

if [ "$1" == "-k" ]; then
	killall --older-than 1h firefox >/dev/null  2>&1

elif [ "$1" == "-s" ]; then
	/etc/init.d/pwrd start-node http://$hub_ip:$hub_port/grid/register > /dev/null 2>&1

elif [ "$1" == "-f" ]; then
	if [ "`firefox --version`" != "Mozilla Firefox 47.0.1" ]; then
		$PWR_FIREFOX_INSTALLDIR/restore_firefox.sh >/dev/null 2>&1
		echo `date +"%c"` Firefox restored > /tmp/restore_firefox.log
		[`/etc/init.d/pwrd status | grep "Node is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart-node http://$hub_ip:$hub_port/grid/register > /dev/null 2>&1
		[`/etc/init.d/pwrd status | grep "PWRD is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart > /dev/null 2>&1
	fi

elif [ "$1" == "-r" ]; then
	if [ ` ps aux | grep "java -jar" | grep -v grep | wc -l` -lt 1 ]; then
		[`/etc/init.d/pwrd status | grep "Node is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart-node http://$hub_ip:$hub_port/grid/register > /dev/null 2>&1
		[`/etc/init.d/pwrd status | grep "PWRD is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart > /dev/null 2>&1
		echo  `date +"%c"` PWRD restarted, java process not found > /tmp/pwrd_restart_detected.log
	fi

elif [ "$1" == "-c1" ]; then
	if [ $(/etc/pandora/plugins/grep_log /var/log/pwr/pwr_std.log check_pwrd ".*"  | wc -l) -eq 0 ]; then
		[`/etc/init.d/pwrd status | grep "Node is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart-node http://$hub_ip:$hub_port/grid/register > /dev/null 2>&1
		[`/etc/init.d/pwrd status | grep "PWRD is running" | wc -l` -eq 1 ] && /etc/init.d/pwrd restart > /dev/null 2>&1
		echo  $(date +"%c") PWRD restarted, no output detected in log >  /tmp/pwrd_restart_detected.log
	fi

elif [ "$1" == "-c2" ]; then
	if [ $(/etc/pandora/plugins/grep_log /var/log/pwr/pwr_std.log check_pwrd_err_conn "refused" | grep "$hub_ip:$hub_port [/$hub_ip] failed:" | wc -l) -gt 0 ]; then
		/etc/init.d/pwrd restart-node http://$hub_ip:$hub_port/grid/register > /dev/null 2>&1
		echo  $(date +"%c") PWRD restarted, lost connection with hub > /tmp/pwrd_restart_detected.log
	fi
fi



