
#docker build -t pandorafms/pandorafms-percona-base -f $HOME/code/pandorafms/extras/docker/percona/Dockerfile $HOME/code/pandorafms/extras/docker/percona

FROM percona:5.7

RUN echo -e '#PandoraFMS configuration \n\ 
[mysqld] \n\
max_allowed_packet = 64M \n\
innodb_buffer_pool_size = 500M \n\
innodb_lock_wait_timeout = 90 \n\
innodb_file_per_table \n\
innodb_flush_log_at_trx_commit = 0 \n\
innodb_flush_method = O_DIRECT \n\
innodb_log_file_size = 64M \n\
innodb_log_buffer_size = 16M \n\
innodb_io_capacity = 100 \n\
thread_cache_size = 8 \n\
thread_stack    = 256K \n\
max_connections = 100 \n \n\
key_buffer_size=4M \n\
read_buffer_size=128K \n\
read_rnd_buffer_size=128K \n\
sort_buffer_size=128K \n\
join_buffer_size=4M \n\n\
query_cache_type = 1 \n\
query_cache_size = 64M \n\
query_cache_min_res_unit = 2k \n\
query_cache_limit = 256K \n\n\
sql_mode="" ' > /etc/my.cnf.d/pandora.cnf

EXPOSE 3306
