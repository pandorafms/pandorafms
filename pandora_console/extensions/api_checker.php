<?php
/**
 * Pandora FMS API Checker Extension.
 *
 * @category   API
 * @package    Pandora FMS
 * @subpackage Extensions
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

// Begin.


/**
 * Api Execution.
 *
 * @param string $url         Url.
 * @param string $ip          Ip.
 * @param string $pandora_url Pandora_url.
 * @param string $apipass     Apipass.
 * @param string $user        User.
 * @param string $password    Password.
 * @param string $op          Op.
 * @param string $op2         Op2.
 * @param string $id          Id.
 * @param string $id2         Id2.
 * @param string $return_type Return_type.
 * @param string $other       Other.
 * @param string $other_mode  Other_mode.
 * @param string $token       Token.
 *
 * @return array.
 */
function api_execute(
    string $url,
    string $ip,
    string $pandora_url,
    string $apipass,
    string $user,
    string $password,
    string $op,
    string $op2,
    string $id='',
    string $id2='',
    string $return_type='',
    string $other='',
    string $other_mode='',
    string $token=''
) {
    $data = [];

    if (empty($url) === true) {
        $url = 'http://'.$ip.$pandora_url.'/include/api.php?';

        if (empty($op) === false) {
            $data['op'] = $op;
        }

        if (empty($op2) === false) {
            $data['op2'] = $op2;
        }

        if (empty($id) === false) {
            $data['id'] = $id;
        }

        if (empty($id2) === false) {
            $data['id2'] = $id2;
        }

        if (empty($return_type) === false) {
            $data['return_type'] = $return_type;
        }

        if (empty($other) === false) {
            $data['other_mode'] = $other_mode;
            $data['other'] = $other;
        }

        // If token is not reported,use old method.
        if (empty($token) === true) {
            $data['apipass'] = $apipass;
            $data['user'] = $user;
            $data['password'] = $password;
        }
    }

    $url_protocol = parse_url($url)['scheme'];

    if ($url_protocol !== 'http' && $url_protocol !== 'https') {
        return [
            'url'    => $url,
            'result' => '',
        ];
    }

    $curlObj = curl_init($url);
    if (empty($data) === false) {
        $url .= http_build_query($data);
    }

    // set the content type json
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer '.$token,
    ];

    curl_setopt($curlObj, CURLOPT_URL, $url);
    curl_setopt($curlObj, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curlObj);
    curl_close($curlObj);

    return [
        'url'    => $url,
        'result' => $result,
    ];
}


/**
 * Perform API Checker
 *
 * @return void.
 */
function extension_api_checker()
{
    global $config;

    check_login();

    if ((bool) check_acl($config['id_user'], 0, 'PM') === false) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Profile Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $url = io_safe_output(get_parameter('url', ''));

    $ip = io_safe_output(get_parameter('ip', '127.0.0.1'));
    $pandora_url = io_safe_output(get_parameter('pandora_url', $config['homeurl_static']));
    $apipass = io_safe_output(get_parameter('apipass', ''));
    $user = io_safe_output(get_parameter('user', $config['id_user']));
    $password = io_safe_output(get_parameter('password', ''));

    $op = io_safe_output(get_parameter('op', 'get'));
    $op2 = io_safe_output(get_parameter('op2', 'test'));
    $id = io_safe_output(get_parameter('id', ''));
    $id2 = io_safe_output(get_parameter('id2', ''));
    $return_type = io_safe_output(get_parameter('return_type', ''));
    $other = io_safe_output(get_parameter('other', ''));
    $other_mode = io_safe_output(get_parameter('other_mode', 'url_encode_separator_|'));
    $token = get_parameter('token');

    $api_execute = (bool) get_parameter('api_execute', false);

    $return_call_api = '';
    if ($api_execute === true) {
        $return_call_api = api_execute(
            $url,
            $ip,
            $pandora_url,
            $apipass,
            $user,
            $password,
            $op,
            $op2,
            urlencode($id),
            urlencode($id2),
            $return_type,
            urlencode($other),
            $other_mode,
            $token
        );
    }

    ui_print_page_header(
        __('API checker'),
        'images/extensions.png',
        false,
        '',
        true,
        ''
    );

    $table = new stdClass();
    $table->data = [];

    $row = [];
    $row[] = __('IP');
    $row[] = html_print_input_text('ip', $ip, '', 50, 255, true);
    $table->data[] = $row;

    $row = [];
    $row[] = __('%s Console URL', get_product_name());
    $row[] = html_print_input_text('pandora_url', $pandora_url, '', 50, 255, true);
    $table->data[] = $row;

    $row = [];
    $row[] = __('API Token').ui_print_help_tip(__('Use API Token instead API Pass, User and Password.'), true);
    $row[] = html_print_input_text('token', $token, '', 50, 255, true);
    $table->data[] = $row;

    $row = [];
    $row[] = __('API Pass');
    $row[] = html_print_input_password('apipass', $apipass, '', 50, 255, true);
    $table->data[] = $row;

    $row = [];
    $row[] = __('User');
    $row[] = html_print_input_text('user', $user, '', 50, 255, true);
    $table->data[] = $row;

    $row = [];
    $row[] = __('Password');
    $row[] = html_print_input_password('password', $password, '', 50, 255, true);
    $table->data[] = $row;

    $table2 = new stdClass();
    $table2->data = [];

    $row = [];
    $row[] = __('Action (get or set)');
    $row[] = html_print_input_text('op', $op, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('Operation');
    $row[] = html_print_input_text('op2', $op2, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('ID');
    $row[] = html_print_input_text('id', $id, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('ID 2');
    $row[] = html_print_input_text('id2', $id2, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('Return Type');
    $row[] = html_print_input_text('return_type', $return_type, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('Other');
    $row[] = html_print_input_text('other', $other, '', 50, 255, true);
    $table2->data[] = $row;

    $row = [];
    $row[] = __('Other Mode');
    $row[] = html_print_input_text('other_mode', $other_mode, '', 50, 255, true);
    $table2->data[] = $row;

    $table3 = new stdClass();
    $table3->data = [];

    $row = [];
    $row[] = __('Raw URL');
    $row[] = html_print_input_text('url', $url, '', 50, 2048, true);
    $table3->data[] = $row;

    echo "<form method='post'>";
    echo '<fieldset>';
    echo '<legend>'.__('Credentials').'</legend>';
    html_print_table($table);
    echo '</fieldset>';

    echo '<fieldset>';
    echo '<legend>'.__('Call parameters').' '.ui_print_help_tip(__('Action: get Operation: module_last_value id: 63'), true).'</legend>';
    html_print_table($table2);
    echo '</fieldset>';
    echo "<div class='right'>";
    echo '</div>';

    echo '<fieldset>';
    echo '<legend>'.__('Custom URL').'</legend>';
    html_print_table($table3);
    echo '</fieldset>';

    html_print_input_hidden('api_execute', 1);

    html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => html_print_submit_button(
                __('Call'),
                'submit',
                false,
                [ 'icon' => 'next' ],
                true
            ),
        ]
    );

    echo '</form>';

    if ($api_execute === true) {
        echo '<fieldset>';
        echo '<legend>'.__('Result').'</legend>';
        echo __('URL').'<br />';
        html_print_input_password('url', $return_call_api['url'], '', 150, 255, false, true);
        echo "&nbsp;<a id='show_icon' title='".__('Show URL')."' href='javascript: show_url();'>";
        html_print_image('images/input_zoom.png');
        echo '</a>';
        echo '<br />';
        echo __('Result').'<br />';
        html_print_textarea('result', 30, 20, $return_call_api['result'], 'readonly="readonly"');
        echo '</fieldset>';
    }
    ?>
    <script>
    function show_url() {
        if ($("#password-url").attr('type') == 'password') {
            $("#password-url").attr('type', 'text');
            $("#show_icon").attr('title', '<?php echo __('Hide URL'); ?>');
        }
        else {
            $("#password-url").attr('type', 'password');
            $("#show_icon").attr('title', '<?php echo __('Show URL'); ?>');
        }
    }
    </script>
    <?php
}


extensions_add_godmode_function('extension_api_checker');
extensions_add_godmode_menu_option(__('API checker'), 'PM', 'gextensions', null, 'v1r1');

