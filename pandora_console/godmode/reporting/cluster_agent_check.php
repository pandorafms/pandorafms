<?php

  $name_agent = get_parameter('name_agent',0);

  $exist_agent = agents_get_agent_id($name_agent);

  echo $exist_agent;
  return;

?>