#!/bin/bash

sudo cat <<EOF > /etc/yum.repos.d/pandorafms.repo
[artica_pandorafms]
name=CentOS6 - PandoraFMS official repo
baseurl=http://firefly.artica.es/centos6
gpgcheck=0
enabled=1
EOF

yum update -y
yum makecache
sudo yum install -y git httpd cronie ntp openldap anytermd nfdump wget curl openldap plymouth xterm php php-gd graphviz php-mysql php-pear-DB php-pear php-pdo php-mbstring php-ldap php-snmp php-ldap php-common php-zip nmap xprobe2 mysql-server mysql git  cronie  ntp  wget  curl  xterm  postfix  wmic  perl-HTML-Tree perl-DBI perl-Crypt-SSLeay perl-DBD-mysql perl-libwww-perl perl-XML-Simple perl-XML-SAX perl-NetAddr-IP net-snmp net-tools perl-IO-Socket-INET6 perl-Socket6 nmap sudo xprobe2 make perl-CPAN perl-JSON net-snmp-perl perl-Time-HiRes perl-XML-Twig perl-Encode-Locale  net-snmp  net-snmp-utils  perl-Test-Simple fping pandorafms_server pandorafms_console pandorafms_agent_unix

yum clean all

# Enable vital services
sudo chkconfig --level 345 mysqld         on 2>/dev/null
sudo chkconfig --level 345 sshd          on 2>/dev/null
sudo chkconfig --level 345 httpd         on 2>/dev/null
sudo chkconfig --level 345 anytermd      on 2> /dev/null
sudo chkconfig --level 345 pandora_agent_daemon      on 2> /dev/null
sudo chkconfig --level 345 postfix      on 2> /dev/null
 

#Optimisations of the MySQL Database (thanks to Mr. CODDNS!)
sudo cat <<EOF > /etc/my.cnf
[mysqld]
datadir=/var/lib/mysql
socket=/var/lib/mysql/mysql.sock
user=mysql
character-set-server=utf8
skip-character-set-client-handshake
# Disabling symbolic-links is recommended to prevent assorted security risks
symbolic-links=0
# Mysql optimizations for Pandora FMS
# Please check the documentation in http://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:Optimization for better results

max_allowed_packet = 32M
innodb_buffer_pool_size = 256M
innodb_additional_mem_pool_size = 16M
innodb_lock_wait_timeout = 90
innodb_file_per_table
innodb_flush_log_at_trx_commit = 0
innodb_flush_method = O_DIRECT
innodb_log_file_size = 64M
innodb_log_buffer_size = 16M
innodb_io_capacity = 1500
thread_cache_size = 8
max_connections = 500

key_buffer_size=4M
read_buffer_size=128K
read_rnd_buffer_size=128K
sort_buffer_size=128K
join_buffer_size=4M

query_cache_type = 1
query_cache_size = 8M
query_cache_limit = 8M

sql_mode=""
EOF

sudo /etc/init.d/mysqld start
mysqladmin -u root password pandora;

# Set new random password for root and pandora users for mysql
echo "create database pandora;" | mysql -u root -ppandora
cat /var/www/html/pandora_console/pandoradb.sql | mysql -u root -ppandora -D pandora
cat /var/www/html/pandora_console/pandoradb_data.sql | mysql -u root -ppandora -D pandora
echo "flush privileges" | mysql -u root -ppandora

# Customize php.ini
sed -i -e "s/.*error_reporting =.*/error_reporting = E_ALL \& \~E_DEPRECATED \& \~E_NOTICE \& \~E_USER_WARNING/" /etc/php.ini
sed -i -e "s/.*max_execution_time =.*/max_execution_time = 0/" /etc/php.ini
sed -i -e "s/.*max_input_time =.*/max_input_time = -1/" /etc/php.ini
sed -i -e "s/.*upload_max_filesize =.*/upload_max_filesize = 800M/" /etc/php.ini
sed -i -e "s/.*memory_limit =.*/memory_limit = 500M/" /etc/php.ini

# Remove install.php
rm -Rf /var/www/html/pandora_console/install.php


# We need to remove a line in /etc/sudoers, which forbid tentacle_serverd to run
# because doesnt have a valid tty. Could be done with sed or just a inverse grep:

cat /etc/sudoers | grep -v requiretty > /tmp/sudoers
cat /tmp/sudoers > /etc/sudoers
rm -f /tmp/sudoers

echo "#Init Pandora DB" >> /etc/rc.local
echo "pass=\`curl -s http://169.254.169.254/latest/meta-data/instance-id\`;" >> /etc/rc.local
echo "sudo /etc/init.d/mysqld start" >> /etc/rc.local
echo "sleep 10" >> /etc/rc.local
echo "echo \"grant all privileges on pandora.* to pandora@localhost identified by '\$pass'\" | mysql -u root -ppandora"  >> /etc/rc.local
echo "mysqladmin -u root -ppandora password \$pass;" >> /etc/rc.local

# Substitute old database password for new random password

cat <<EOF >> /etc/rc.local
# This requires double encoding for $ !
# Crear config.php de pandora
cat > /var/www/html/pandora_console/include/config.php << EOF_configpandora
<?php
\\\$config["dbtype"] = "mysql";
\\\$config["dbname"]="pandora";
\\\$config["dbuser"]="pandora";
\\\$config["dbpass"]="\$pass";
\\\$config["dbhost"]="localhost";
\\\$config["homedir"]="/var/www/html/pandora_console";
\\\$config["homeurl"]="/pandora_console";
error_reporting(0);
\\\$ownDir = dirname(__FILE__) . '/';
include (\\\$ownDir . "config_process.php");
?>
EOF_configpandora
EOF

echo "chown apache apache /var/www/html/pandora_console/include/config.php" >> /etc/rc.local
echo "chmod 600 /var/www/html/pandora_console/include/config.php" >> /etc/rc.local

# Substitute old database password for new random password
echo "sed -i -e \"s/dbpass pandora/dbpass \$pass/g\" /etc/pandora/pandora_server.conf;" >> /etc/rc.local
echo "/etc/init.d/pandora_server start;" >> /etc/rc.local
echo "perl /usr/share/pandora_server/util/pandora_manage.pl /etc/pandora/pandora_server.conf --update_user 'admin' password \"\$pass\";" >> /etc/rc.local
echo "sudo chkconfig --level 345 pandora_server      on 2> /dev/null" >> /etc/rc.local

passwd -l root

shred -u /etc/ssh/*_key.pub
shred -u /etc/ssh/*_key
rm -Rf ~/.ssh
rm -Rf /home/ec2-user/.ssh/
shred -u /home/ec2-user/.*history
shred -u /root/.*history
history -w
history -c
