#docker build -t pandorafms/pandorafms-open-stack-el8:$VERSION -f $HOME/code/pandorafms/extras/docker/centos8/pandora-stack/Dockerfile $HOME/code/pandorafms/extras/docker/centos8/pandora-stack/

FROM pandorafms/pandorafms-open-base-el8

ENV DBNAME=pandora
ENV DBUSER=pandora
ENV DBPASS=pandora
ENV DBHOST=pandora
ENV DBPORT=3306
ENV SLEEP=5
ENV RETRIES=1
ENV OPEN=1

ENV LC_ALL=C

RUN	rm -rf /etc/localtime ; ln -s /usr/share/zoneinfo/Europe/Madrid /etc/localtime 

COPY ./sources /opt/pandora
# Install the Pandora console
RUN dnf install -y /opt/pandora/pandorafms_console-*.rpm /opt/pandora/pandorafms_server-*.rpm ; yum clean all

EXPOSE 80 443 41121 162/udp 9995/udp

CMD sh /opt/pandora/init_pandora.sh
