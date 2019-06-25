<?php
/**
 * Credentials management auxiliary functions.
 *
 * @category   Credentials management library.
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Begin.


/**
 * Returns an array with all the credentials matching filter and ACL.
 *
 * @param array   $fields     Fields array or 'count' keyword to retrieve count.
 * @param array   $filter     Filters to be applied.
 * @param integer $offset     Offset (pagination).
 * @param integer $limit      Limit (pagination).
 * @param string  $order      Sort order.
 * @param string  $sort_field Sort field.
 *
 * @return array With all results or false if error.
 * @throws Exception On error.
 */
function credentials_get_all(
    $fields,
    array $filter,
    $offset=null,
    $limit=null,
    $order=null,
    $sort_field=null
) {
    $sql_filters = [];
    $order_by = '';
    $pagination = '';

    global $config;

    $user_is_admin = users_is_admin();

    if (!is_array($filter)) {
        error_log('[credential_get_all] Filter must be an array.');
        throw new Exception('[credential_get_all] Filter must be an array.');
    }

    $count = false;
    if (!is_array($fields) && $fields == 'count') {
        $fields = ['cs.*'];
        $count = true;
    } else if (!is_array($fields)) {
        error_log('[credential_get_all] Fields must be an array or "count".');
        throw new Exception('[credential_get_all] Fields must be an array or "count".');
    }

    if (isset($filter['free_search']) && !empty($filter['free_search'])) {
        $sql_filters[] = vsprintf(
            ' AND (lower(cs.username) like lower("%%%s%%")
                OR cs.identifier like "%%%s%%"
                OR lower(cs.product) like lower("%%%s%%"))',
            array_fill(0, 3, $filter['free_search'])
        );
    }

    if (isset($filter['filter_id_group']) && $filter['filter_id_group'] > 0) {
        $propagate = db_get_value(
            'propagate',
            'tgrupo',
            'id_grupo',
            $filter['filter_id_group']
        );

        if (!$propagate) {
            $sql_filters[] = sprintf(
                ' AND cs.id_group = %d ',
                $filter['filter_id_group']
            );
        } else {
            $groups = [ $filter['filter_id_group'] ];
            $childrens = groups_get_childrens($id_group, null, true);
            if (!empty($childrens)) {
                foreach ($childrens as $child) {
                    $groups[] = (int) $child['id_grupo'];
                }
            }

            $filter['filter_id_group'] = $groups;
            $sql_filters[] = sprintf(
                ' AND cs.id_group IN (%s) ',
                join(',', $filter['filter_id_group'])
            );
        }
    }

    if (isset($filter['group_list']) && is_array($filter['group_list'])) {
        $sql_filters[] = sprintf(
            ' AND cs.id_group IN (%s) ',
            join(',', $filter['group_list'])
        );
    }

    if (isset($order)) {
        $dir = 'asc';
        if ($order == 'desc') {
            $dir = 'desc';
        };

        if (in_array(
            $sort_field,
            [
                'group',
                'identifier',
                'product',
                'username',
                'options',
            ]
        )
        ) {
            $order_by = sprintf(
                'ORDER BY `%s` %s',
                $sort_field,
                $dir
            );
        }
    }

    if (isset($limit) && $limit > 0
        && isset($offset) && $offset >= 0
    ) {
        $pagination = sprintf(
            ' LIMIT %d OFFSET %d ',
            $limit,
            $offset
        );
    }

    $sql = sprintf(
        'SELECT %s
         FROM tcredential_store cs
         LEFT JOIN tgrupo tg
            ON tg.id_grupo = cs.id_group
         WHERE 1=1
         %s
         %s
         %s',
        join(',', $fields),
        join(',', $sql_filters),
        $order_by,
        $pagination
    );

    if ($count) {
        $sql = sprintf('SELECT count(*) as n FROM ( %s ) tt', $sql);

        return db_get_value_sql($sql);
    }

    return db_get_all_rows_sql($sql);
}


/**
 * Retrieves target key from keystore or false in case of error.
 *
 * @param string $identifier Key identifier.
 *
 * @return array Key or false if error.
 */
function get_key($identifier)
{
    return db_get_row_filter(
        'tcredential_store',
        [ 'identifier' => $identifier ]
    );
}


/**
 * Minor function to dump json message as ajax response.
 *
 * @param string  $type   Type: result || error.
 * @param string  $msg    Message.
 * @param boolean $delete Deletion messages.
 *
 * @return void
 */
function ajax_msg($type, $msg, $delete=false)
{
    $msg_err = 'Failed while saving: %s';
    $msg_ok = 'Successfully saved into keystore ';

    if ($delete) {
        $msg_err = 'Failed while removing: %s';
        $msg_ok = 'Successfully deleted ';
    }

    if ($type == 'error') {
        echo json_encode(
            [
                $type => ui_print_error_message(
                    __(
                        $msg_err,
                        $msg
                    ),
                    '',
                    true
                ),
            ]
        );
    } else {
        echo json_encode(
            [
                $type => ui_print_success_message(
                    __(
                        $msg_ok,
                        $msg
                    ),
                    '',
                    true
                ),
            ]
        );
    }

    exit;
}


/**
 * Generates inputs for new/update forms.
 *
 * @param array $values Values or null.
 *
 * @return string Inputs.
 */
function print_inputs($values=null)
{
    if (!is_array($values)) {
        $values = [];
    }

    $return = '';
    $return .= html_print_input(
        [
            'label'       => __('Identifier'),
            'name'        => 'identifier',
            'input_class' => 'flex-row',
            'type'        => 'text',
            'value'       => $values['identifier'],
            'disabled'    => (bool) $values['identifier'],
            'return'      => true,
        ]
    );
    $return .= html_print_input(
        [
            'label'       => __('Group'),
            'name'        => 'id_group',
            'id'          => 'id_group',
            'input_class' => 'flex-row',
            'type'        => 'select_groups',
            'selected'    => $values['id_group'],
            'return'      => true,
            'class'       => 'w50p',
        ]
    );
    $return .= html_print_input(
        [
            'label'       => __('Product'),
            'name'        => 'product',
            'input_class' => 'flex-row',
            'type'        => 'select',
            'script'      => 'calculate_inputs()',
            'fields'      => [
                'CUSTOM' => __('Custom'),
                'AWS'    => __('Aws'),
                'AZURE'  => __('Azure'),
                // 'GOOGLE' => __('Google'),
            ],
            'selected'    => $values['product'],
            'disabled'    => (bool) $values['product'],
            'return'      => true,
        ]
    );
    $user_label = __('Username');
    $pass_label = __('Password');
    $extra_1_label = __('Extra');
    $extra_2_label = __('Extra (2)');
    $extra1 = true;
    $extra2 = true;

    // Remember to update credential_store.php also.
    switch ($values['product']) {
        case 'AWS':
            $user_label = __('Access key ID');
            $pass_label = __('Secret access key');
            $extra1 = false;
            $extra2 = false;
        break;

        case 'AZURE':
            $user_label = __('Account ID');
            $pass_label = __('Password');
            $extra_1_label = __('Tenant or domain name');
            $extra_2_label = __('Subscription id');
        break;

        case 'GOOGLE':
            // Need further investigation.
        case 'CUSTOM':
        default:
            // Use defaults.
        break;
    }

    $return .= html_print_input(
        [
            'label'       => $user_label,
            'name'        => 'username',
            'input_class' => 'flex-row',
            'type'        => 'text',
            'value'       => $values['username'],
            'return'      => true,
        ]
    );
    $return .= html_print_input(
        [
            'label'       => $pass_label,
            'name'        => 'password',
            'input_class' => 'flex-row',
            'type'        => 'password',
            'value'       => $values['password'],
            'return'      => true,
        ]
    );
    if ($extra1) {
        $return .= html_print_input(
            [
                'label'       => $extra_1_label,
                'name'        => 'extra_1',
                'input_class' => 'flex-row',
                'type'        => 'password',
                'value'       => $values['extra_1'],
                'return'      => true,
            ]
        );
    }

    if ($extra2) {
        $return .= html_print_input(
            [
                'label'       => $extra_2_label,
                'name'        => 'extra_2',
                'input_class' => 'flex-row',
                'type'        => 'password',
                'value'       => $values['extra_2'],
                'return'      => true,
                'display'     => $extra2,
            ]
        );
    }

    return $return;
}


/**
 * Retrieve all identifiers available for current user.
 *
 * @param string $product Target product.
 *
 * @return array Of account identifiers.
 */
function credentials_list_accounts($product)
{
    global $config;

    check_login();

    include_once $config['homedir'].'/include/functions_users.php';

    static $user_groups;

    if (!isset($user_groups)) {
        $user_groups = users_get_groups(
            $config['id_user'],
            'AW'
        );

        // Always add group 'ALL' because 'ALL' group credentials
        // must be available for all users.
        if (is_array($user_groups)) {
            $user_groups = ([0] + array_keys($user_groups));
        } else {
            $user_groups = [0];
        }
    }

    $creds = credentials_get_all(
        ['identifier'],
        [
            'product'    => $product,
            'group_list' => $user_groups,
        ]
    );

    if ($creds === false) {
        return [];
    }

    $ret = array_reduce(
        $creds,
        function ($carry, $item) {
            $carry[$item['identifier']] = $item['identifier'];
            return $carry;
        }
    );

    return $ret;
}
