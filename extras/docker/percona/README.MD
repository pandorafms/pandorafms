![Image of Pandora FMS](https://pandorafms.org/wp-content/uploads/2016/06/logo_pandora_community.png)

# Pandora FMS database container

This container image is designed for optimal performance using Pandora FMS.

Is based on the official Percona database image turned to  get the maximum performance on PandoraFMS

## Official Gitgub repo

Dockerfiles and more info in our official repository: https://github.com/pandorafms/pandorafms/tree/develop/extras/docker/percona

## USAGE

Similar to the official percona image: 
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

## Environment Variables
When you start the percona image, you can adjust the configuration of the instance by passing one or more environment variables on the docker run command line. Do note that none of the variables below will have any effect if you start the container with a data directory that already contains a database: any pre-existing database will always be left untouched on container startup.

__MYSQL_ROOT_PASSWORD__ : This variable is mandatory and specifies the password that will be set for the root superuser account. In the above example, it was set to my-secret-pw.

__MYSQL_ROOT_HOST__ : By default, root can connect from anywhere. This option restricts root connections to be from the specified host only. Also localhost can be used here for the local-only root access.

__MYSQL_DATABASE__ :
This variable is optional and allows you to specify the name of a database to be created on image startup. If a user/password was supplied (see below) then that user will be granted superuser access (corresponding to GRANT ALL) to this database.

__MYSQL_USER, MYSQL_PASSWORD__ :These variables are optional, used in conjunction to create a new user and to set that user's password. This user will be granted superuser permissions (see above) for the database specified by the MYSQL_DATABASE variable. Both variables are required for a user to be created.
Do note that there is no need to use this mechanism to create the root superuser, that user gets created by default with the password specified by the MYSQL_ROOT_PASSWORD variable.

__MYSQL_ALLOW_EMPTY_PASSWORD__ : This is an optional variable. Set to yes to allow the container to be started with a blank password for the root user. NOTE: Setting this variable to yes is not recommended unless you really know what you are doing, since this will leave your instance completely unprotected, allowing anyone to gain complete superuser access.

__MYSQL_RANDOM_ROOT_PASSWORD__ : This is an optional variable. Set to yes to generate a random initial password for the root user (using pwgen). The generated root password will be printed to stdout (GENERATED ROOT PASSWORD: .....).

__MYSQL_ONETIME_PASSWORD__ : Sets root (not the user specified in MYSQL_USER!) user as expired once init is complete, forcing a password change on first login. NOTE: This feature is supported on MySQL 5.6+ only. Using this option on MySQL 5.5 will throw an appropriate error during initialization.

__MYSQL_INITDB_SKIP_TZINFO__ : At first run MySQL automatically loads from the local system the timezone information needed for the CONVERT_TZ() function. If it's is not what is intended, this option disables timezone loading.

__INIT_TOKUDB__ : Tuns on TokuDB Engine. It can be activated only when transparent huge pages (THP) are disabled.

__INIT_ROCKSDB__ : Tuns on RocksDB Engine.