<?php
/**
 * View for delete agents in Massive Operations
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use PandoraFMS\Agent;
use PandoraFMS\Enterprise\Metaconsole\Node;

// Begin.
check_login();

if ((bool) check_acl($config['id_user'], 0, 'AW') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access massive agent deletion section'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_alerts.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_massive_operations.php';


/**
 * Bulk operations Delete.
 *
 * @param array $id_agents Agents to delete.
 *
 * @return boolean
 */
function process_manage_delete($id_agents)
{
    if (empty($id_agents) === true) {
        ui_print_error_message(__('No agents selected'));
        return false;
    }

    $id_agents = (array) $id_agents;

    $count_deleted = 0;
    $agent_id_restore = 0;
    foreach ($id_agents as $id_agent) {
        if (is_metaconsole() === true) {
            $array_id = explode('|', $id_agent);
            try {
                $node = new Node((int) $array_id[0]);
                $node->connect();

                $agent = new Agent((int) $array_id[1]);
                $success = $agent->delete();

                $node->disconnect();

                $success = agent_delete_from_metaconsole(
                    $array_id[1],
                    $array_id[0]
                );
            } catch (\Exception $e) {
                // Unexistent agent.
                $success = false;
                $node->disconnect();
            }
        } else {
            try {
                $agent = new Agent($id_agent);
                $success = $agent->delete();
            } catch (\Exception $e) {
                // Unexistent agent.
                $success = false;
            }
        }

        if ($success === false) {
            $agent_id_restore = $id_agent;
            break;
        }

        $count_deleted++;
    }

    if ($success === false) {
        if (is_metaconsole() === true) {
            $array_id = explode('|', $agent_id_restore);
            $alias = agents_get_alias_metaconsole(
                $array_id[1],
                'none',
                $array_id[0]
            );
        } else {
            $alias = agents_get_alias($agent_id_restore);
        }

        ui_print_error_message(
            sprintf(
                __('There was an error deleting the agent, the operation has been cancelled Could not delete agent %s'),
                $alias
            )
        );

        return false;
    } else {
        ui_print_success_message(
            sprintf(
                __(
                    'Successfully deleted (%s)',
                    $count_deleted
                )
            )
        );

        return true;
    }
}


$id_group = (is_metaconsole() === true) ? get_parameter('id_group', '') : (int) get_parameter('id_group');
$id_agents = get_parameter('id_agents');
$recursion = get_parameter('recursion');
$delete = (bool) get_parameter_post('delete');

if ($delete === true) {
    $result = process_manage_delete($id_agents);

    if (empty($id_agents) === true) {
        $info = '{"Agent":"empty"}';
    } else {
        $info = '{"Agent":"'.implode(',', $id_agents).'"}';
    }

    if ($result === true) {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Delete agent ',
            false,
            false,
            $info
        );
    } else {
        db_pandora_audit(
            AUDIT_LOG_MASSIVE_MANAGEMENT,
            'Fail try to delete agent',
            false,
            false,
            $info
        );
    }
}


$url = 'index.php?sec=gmassive&sec2=godmode/massive/massive_operations&option=delete_agents';
if (is_metaconsole() === true) {
    $url = 'index.php?sec=advanced&sec2=advanced/massive_operations&tab=massive_agents&pure=0&option=delete_agents';
}

echo '<form method="post" id="form_agent" action="'.$url.'">';

$params = [
    'id_group'  => $id_group,
    'recursion' => $recursion,
];
echo get_table_inputs_masive_agents($params);

if (is_metaconsole() === true || is_management_allowed() === true) {
    attachActionButton('delete', 'delete', '100%', false, $SelectAction);
}

echo '</form>';

echo '<h3 class="error invisible" id="message"> </h3>';

ui_require_jquery_file('form');
ui_require_jquery_file('pandora.controls');
?>

<script type="text/javascript">
    $(document).ready (function () {
        // Check Metaconsole.
        var metaconsole = '<?php echo (is_metaconsole() === true) ? 1 : 0; ?>';
        form_controls_massive_operations_agents(metaconsole);
    });
</script>
