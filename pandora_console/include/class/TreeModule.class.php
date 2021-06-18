<?php
// Pandora FMS- http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/class/Tree.class.php';

class TreeModule extends Tree
{


    public function __construct($type, $rootType='', $id=-1, $rootID=-1, $serverID=false, $childrenMethod='on_demand', $access='AR')
    {
        global $config;

        parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

        $this->L1fieldName = 'name';
        $this->L1fieldNameSql = 'tam.nombre';
        $this->L1inner = '';
        $this->L1orderByFinal = 'name';
        $this->L1innerInside = 'INNER JOIN tagente_modulo tam 
        ON ta.id_agente = tam.id_agente';

        $this->L2condition = "AND tam.nombre = '".$this->symbol2name($this->rootID)."'";
    }


    protected function getData()
    {
        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'module') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->getThirdLevel();
        }
    }


    protected function getProcessedItemsFirstLevel($items)
    {
        $processed_items = [];
        foreach ($items as $key => $item) {
            $name = $this->name2symbol($item['name']);
            $processed_item = $this->getProcessedItem($item);
            $processed_item['id'] = $name;
            $processed_item['rootID'] = $name;
            $processed_items[] = $processed_item;
        }

        return $processed_items;
    }


}
