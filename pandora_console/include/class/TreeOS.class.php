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

class TreeOS extends Tree
{


    public function __construct($type, $rootType='', $id=-1, $rootID=-1, $serverID=false, $childrenMethod='on_demand', $access='AR')
    {
        global $config;

        parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

        $this->L1fieldName = 'id_os';
        $this->L1fieldNameSql = 'ta.id_os';
        $this->L1extraFields = [
            'tco.name',
            'tco.id_os AS id',
            'tco.icon_name AS iconHTML',
        ];
        $this->L1inner = 'INNER JOIN tconfig_os tco ON tco.id_os = x2.g';
        $this->L1innerInside = 'INNER JOIN tagente_modulo tam 
                                    ON ta.id_agente = tam.id_agente';
        $this->L1orderByFinal = 'tco.name';

        $this->L2condition = 'AND ta.id_os = '.$this->rootID;
    }


    protected function getData()
    {
        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'os') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->getThirdLevel();
        }
    }


}
