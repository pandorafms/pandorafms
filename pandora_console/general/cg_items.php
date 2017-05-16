<?php 
$stacked = db_get_sql('select stacked from tgraph where id_graph = '.$_GET['data']);
$num_items = db_get_sql('select count(*) from tgraph_source where id_graph = '.$_GET['data']);
echo "$stacked,$num_items";


?>