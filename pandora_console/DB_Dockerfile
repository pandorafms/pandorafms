FROM mysql:5.5
MAINTAINER Pandora FMS Team <info@pandorafms.com>

WORKDIR /pandorafms/pandora_console

ADD pandoradb.sql /docker-entrypoint-initdb.d
ADD pandoradb_data.sql /docker-entrypoint-initdb.d

ENV MYSQL_DATABASE=pandora

RUN echo " \n\
sed -i \"1iUSE \$MYSQL_DATABASE\" /docker-entrypoint-initdb.d/pandoradb.sql \n\
sed -i \"1iUSE \$MYSQL_DATABASE\" /docker-entrypoint-initdb.d/pandoradb_data.sql \n\
" >> /docker-entrypoint-initdb.d/create_pandoradb.sh
