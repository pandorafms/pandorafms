<?php
$data = (int) get_parameter('data');
$stacked = db_get_sql('select stacked from tgraph where id_graph = '.$data);
$num_items = db_get_sql('select count(*) from tgraph_source where id_graph = '.$data);
echo "$stacked,$num_items";
