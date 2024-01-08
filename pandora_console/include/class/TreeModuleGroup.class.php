<?php
// Pandora FMS- https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/class/Tree.class.php';

class TreeModuleGroup extends Tree
{


    public function __construct($type, $rootType='', $id=-1, $rootID=-1, $serverID=false, $childrenMethod='on_demand', $access='AR')
    {
        global $config;

        parent::__construct($type, $rootType, $id, $rootID, $serverID, $childrenMethod, $access);

        $this->L1fieldName = 'id_module_group';
        $this->L1fieldNameSql = 'tam.id_module_group';
        $this->L1extraFields = [
            'tmg.name',
            'tmg.id_mg AS id',
        ];
        $this->L1inner = 'INNER JOIN tmodule_group tmg ON tmg.id_mg = x2.g';
        $this->L1innerInside = 'INNER JOIN tagente_modulo tam ON ta.id_agente = tam.id_agente
                                INNER JOIN tagente_estado tae ON tae.id_agente_modulo = tam.id_agente_modulo';
        $this->L1orderByFinal = 'tmg.name';

        $this->L2condition = 'AND tam.id_module_group = '.$this->rootID;
    }


    protected function getData()
    {
        if ($this->id == -1) {
            $this->getFirstLevel();
        } else if ($this->type == 'module_group') {
            $this->getSecondLevel();
        } else if ($this->type == 'agent') {
            $this->getThirdLevel();
        }
    }


}
