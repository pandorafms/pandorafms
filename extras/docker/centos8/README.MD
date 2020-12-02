![Image of Pandora FMS](https://pandorafms.org/wp-content/uploads/2016/06/logo_pandora_community.png)


Pandora FMS is a __monitoring solution__ that provides unparalleled flexibility for IT to address both immediate and unforeseen operational issues, including infrastructure and IT processes.

It uniquely enables business and IT to adapt to changing needs through a flexible and rapid approach to IT and business deployment. 

For community support you can visit our forums at [forums.pandorafms.org](http://forums.pandorafms.org). Check out our community website at [pandorafms.org](http://pandorafms.org), and if you need sales information or/and professional support, visit [pandorafms.com](http://pandorafms.com).

# Pandora FMS full stack based on Centos 8

## Try it

You can try it on a cloud env for 4 hours using play with docker just click in the icon bellow, wait for the pulling image and the containers will be up and running automatically

If you want to open the console just click on the port 8080 on the upside and will be redirected to the pandora consola.

[![Try in PWD](https://raw.githubusercontent.com/play-with-docker/stacks/master/assets/images/button.png)](https://labs.play-with-docker.com/?stack=https://raw.githubusercontent.com/rafaelameijeiras/PandoraFMS/master/pandorafms_community/docker-compose-official.yml)


https://www.youtube.com/watch?v=Hw2P6hHQpeI

Note: could take a couple of minutes for the console to be ready so if you click in the 8080 port and nothing and have a blank page wait a little and reload the page

## Usage
```
docker run --name Pandora_new %container_name% --rm \
-p %local_httpd_port%:80 \
-p %local_tentacle_port%:41121 \
-e DBHOST=%Mysql_Server_IP% \
-e DBNAME=%database_name% \
-e DBUSER=%Mysql_user% \
-e DBPASS=%Mysql_pass% \
-e DBPORT=%Mysql_port% \
-e INSTANCE_NAME=%server name% \
-ti pandorafms-open-stack-el8
```
Example:
```
docker run --name Pandora_new --rm \
-p 8081:80 \
-p 41125:41121 \
-e DBHOST=192.168.80.45 \
-e DBNAME=pandora_demos_1 \
-e DBUSER=pandora \
-e DBPASS=pandora \
-e DBPORT=3306 \
-e INSTANCE_NAME=pandora201 \
-ti pandorafms-open-stack-el8
```

### Integrated database for PandoraFMS container
There is a preconfigured database image in this repo to connect the Pandora environment  so you can up the database and then point the pandora container to the database.

Example:
```
docker run --name Pandora_DB \
-p 3306:3306 \
-e MYSQL_ROOT_PASSWORD=pandora \
-e MYSQL_DATABASE=pandora \
-e MYSQL_USER=pandora \
-e MYSQL_PASSWORD=pandora \
-d pandorafms/pandorafms-percona-base
```

This creates a Percona mysql docker and a database called Pandora with grants to the pandora user (optional) and the credentials for root user. 

In this example we expose the 3306 for database connection. 

Using this configuration (getting the container ip from DB container ip) you can execute the next container Pandora pointing to it:

```
docker run --name Pandora_new --rm \
-p 8081:80 \
-p 41125:41121 \
-e DBHOST=<percona container ip> \
-e DBNAME=pandora \
-e DBUSER=pandora \
-e DBPASS=pandora \
-e DBPORT=3306 \
-e INSTANCE_NAME=pandora_inst \
-ti pandorafms/pandorafms-open-stack-el8
```

### Docker Compose Stack

if you want to run an easy to deploy stack you may use the docker-compose.yml file

```
version: '3.1'
services:
  db:
    image: pandorafms/pandorafms-percona-base
    restart: always
    #command: ["mysqld", "--innodb-buffer-pool-size=900M"]
    environment:
      MYSQL_ROOT_PASSWORD: pandora
      MYSQL_DATABASE: pandora
      MYSQL_USER: pandora
      MYSQL_PASSWORD: pandora
    networks:
     - pandora

  pandora:
    image: pandorafms/pandorafms-open-stack-el8:latest
    restart: always
    depends_on:
      - db
    environment:
      MYSQL_ROOT_PASSWORD: pandora
      DBHOST: db
      DBNAME: pandora
      DBUSER: pandora
      DBPASS: pandora
      DBPORT: 3306
      INSTANCE_NAME: pandora01
      PUBLICURL: ""
      SLEEP: 5
      RETRIES: 10
    networks:
     - pandora
    ports:
      - "8080:80"
      - "41121:41121"
      - "162:162/udp"
      - "9995:9995/udp"
networks:
  pandora:
```
just by running: `docker-compose -f <docker-compose-file> up`

## Important Parameters:

* __INSTANCE_NAME__: Pandora Server name
* __DBHOST__: DB host IP  to MySQL engine
* __DBNAME__: The name of the database. If your user have enough permissions to create databases, the container will create it in case automatically if didn't exist
* __DBUSER__: The user to connect MySQL engine.
* __DBPASS__: User password to connect MySQL engine.
* __DBPORT__: The port to connect MySQL engine. by default 33306
* __PUBLICURL__: Define a public URL. Useful when Pandora is used behind a reverse proxy
* __SLEEP__: Time to wait between retries
* __RETRIES__: How many times Pandora will try to connect to MySQL engine before fail.

Note1: the SLEEP and RETRIES variables will be used to wait for database container to fully start, if you are in a slower system maybe you will need to increase these variables values, in this example will wait 5 seconds for the database container to be up and retries 3 times.

