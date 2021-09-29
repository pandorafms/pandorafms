<?php

require_once __DIR__.'/../include/config.php';
require_once __DIR__.'/../vendor/autoload.php';

if (file_exists(__DIR__.'/../'.ENTERPRISE_DIR.'/load_enterprise.php') === true) {
    include_once __DIR__.'/../'.ENTERPRISE_DIR.'/load_enterprise.php';
}

if (isset($_SERVER['argc']) === false) {
    exit(1);
}

global $config;

use PandoraFMS\Agent;

$ids = \db_get_all_rows_filter('tagente', [], ['id_agente']);
if ($ids === false) {
    echo "Unable to find agents\n";
    $ids = [];
}

$policies = \db_get_all_rows_filter('tpolicies', [], 'id,name');
$policies = array_reduce(
    $policies,
    function ($carry, $item) {
        $carry[$item['name']] = $item['id'];
        return $carry;
    },
    []
);

foreach ($ids as $a) {
    try {
        $agent = new Agent($a['id_agente']);
        if ($agent->hasRemoteCapabilities() === true) {
            $agent_policies = $agent->getConfPolicies();

            $oldIds = [];
            $newIds = [];
            foreach ($agent_policies as $oldId => $name) {
                $oldIds[] = $oldId;
                $newIds[] = $policies[io_safe_input($name)];
            }

            $res_update_con_policy = $agent->updatePolicyIds(
                $oldIds,
                $newIds
            );
            if ($res_update_con_policy === false) {
                echo 'Failed ['.$agent->name()."]\n";
            } else {
                echo 'Agent '.io_safe_output($agent->alias())." updated successfully\n";
            }
        } else {
            echo 'Agent '.io_safe_output($agent->alias())." skipped\n";
        }
    } catch (Exception $e) {
        echo $e->getMessage()."\n";
    }
}
