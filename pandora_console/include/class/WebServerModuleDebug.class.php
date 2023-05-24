<?php
/**
 * WebServer Module debug feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Web Server Module
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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
global $config;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * Class WebServerModuleDebug.
 */
class WebServerModuleDebug extends Wizard
{

    /**
     * Controller Url.
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Timeout for HTTP requests.
     *
     * @var integer
     */
    private $requestTimeout;

    /**
     * CURL Query.
     *
     * @var string
     */
    private $query;

    /**
     * Id of the current module.
     *
     * @var integer
     */
    private $idAgentModule;


    /**
     * Class constructor.
     *
     * @param string  $ajaxController Ajax Page Controller.
     * @param integer $idAgentModule  Id of the module.
     */
    public function __construct(string $ajaxController, int $idAgentModule)
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access event viewer'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        // Parameter assigments.
        $this->ajaxController = $ajaxController;
        $this->query = '';
        $this->idAgentModule = $idAgentModule;
        // Hardcoded request timeout.
        $this->requestTimeout = 15;

        return $this;

    }


    /**
     * Run Module Debug window.
     *
     * @return void
     */
    public function run()
    {
        // Added all necessary basic files for QueryResult.
        ui_require_css_file('ace');
        ui_require_javascript_file('ace', 'include/javascript/ace/');
        // Load Javascript.
        $this->loadJS();
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');
        // Specific CSS for this feature.
        ui_require_css_file('WebServerModuleDebug', '/include/styles/', true);

    }


    /**
     * Show the modal with the QueryResult.
     *
     * @return void
     */
    public function showWebServerDebug()
    {
        // Show QueryResult editor.
        ui_query_result_editor('webserverdebug');
        // Spinner for wait loads.
        html_print_div(
            [
                'id'      => 'WebServerDebugSpinner',
                'style'   => 'visibility: hidden;',
                'content' => __('Performing query. Please wait.').'&nbsp;'.html_print_image('images/spinner.gif', true),
            ]
        );
        ?>

        <script type="text/javascript">
            $(document).ready(function(){
                // Query section
                var query = ace.edit("webserverdebug_editor");
                let queryDefined = "<?php echo $this->defineQuery(); ?>";
                let queryRegex = /([-]+[a-zA-Z]\s)|(([-]{2})+[a-z]+[-]*[a-z]*)/g;
                query.setValue(queryDefined.replace(queryRegex, "\n$&"));
                query.clearSelection();
                // Result section
                var results = ace.edit("webserverdebug_view");
                var text = '';
                results.setTheme("ace/theme/textmate");
                results.session.setMode("ace/mode/json");
                results.renderer.setShowGutter(false);
                results.setReadOnly(true);
                results.setShowPrintMargin(false);

                $("#button-execute_query").click(function() {
                    // Show the spinner.
                    showSpinner(true);
                    // Empty the results container.
                    results.setValue("");
                    // Get the entire text.
                    text = query.getValue();
                    // There are not values in the query section.
                    if (text === null || text === undefined) {
                        results.setValue('<?php echo __('No results'); ?>');
                        results.clearSelection();
                        // Hide spinner.
                        showSpinner(false);
                        return;
                    }
                    // Clean the carriage jumps.
                    text = text.split("\n").join("");
                    // Call to the method for execute the command.
                    $.ajax({
                        method: "post",
                        url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                        data: {
                            page: "<?php echo $this->ajaxController; ?>",
                            method: "executeCommand",
                            text: text,
                            idAgentModule: "<?php echo $this->idAgentModule; ?>",
                        },
                        datatype: "json",
                        success: function(result) {
                            results.setValue(result);
                        },
                        error: function(e) {
                            results.setValue('<?php echo __('Error performing execution'); ?>');
                        },
                        complete: function() {
                            results.clearSelection();
                            showSpinner(false);
                        }
                    });
                });

            });
        </script>

        <?php
    }


    /**
     * Definition of the query
     *
     * @return string
     */
    private function defineQuery()
    {
        // Get the value of the debug_content.
        $outputDebugQuery = db_get_value_filter(
            'debug_content',
            'tagente_modulo',
            [
                'id_agente_modulo' => $this->idAgentModule,
            ]
        );

        $this->query = ($outputDebugQuery !== false) ? $outputDebugQuery : __('Please, wait for a first execution of module');

        return $this->query;
    }


    /**
     * Perform the cURL execution.
     *
     * @return void
     * @throws Exception $e Error message.
     */
    public function executeCommand()
    {
        try {
            $executionForPerform = io_safe_output(get_parameter('text'));
            // If the execution comes empty.
            if (empty($executionForPerform) === true) {
                throw new Exception('Execution failed');
            }

            // For security reasons, only allow the 'curl' command.
            $executionForPerform = strstr($executionForPerform, 'curl');
            // Avoid pipes or concatenation of commands.
            $unallowedChars = [
                '|',
                '&',
                '||',
                '&&',
                ';',
                '\n',
            ];
            $executionForPerform = str_replace(
                $unallowedChars,
                ' ',
                $executionForPerform
            );
            // Set execution timeout.
            $executionForPerform .= sprintf(
                $executionForPerform.' -m %d',
                $this->requestTimeout
            );

            // Perform the execution.
            system($executionForPerform, $returnCode);
            // If execution does not got well.
            if ($returnCode != 0) {
                switch ($returnCode) {
                    case '2':
                    throw new Exception('Failed to initialize. Review the syntax.');

                    case '3':
                    throw new Exception('URL malformed. The syntax was not correct.');

                    case '5':
                    throw new Exception('Couldn\'t resolve proxy. The given proxy host could not be resolved.');

                    case '6':
                    throw new Exception('Couldn\'t resolve host. The given remote host could not be resolved.');

                    case '7':
                    throw new Exception('Failed to connect to host.');

                    default:
                    throw new Exception('Failed getting data.');
                }
            }
        } catch (Exception $e) {
            // Show execution error message.
            echo __($e->getMessage());
        }

        exit;
    }


    /**
     * Loads JS and return code.
     *
     * @return string
     */
    public function loadJS()
    {
        $str = '';
        ob_start();
        ?>
        <script type="text/javascript">

            $(document).ready(function(){
                $('#button-btn_debugModule').click(function() {
                    load_modal({
                    target: $("#modal"),
                    form: "add_module_form",
                    url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
                    ajax_callback: showMsg,
                    modal: {
                        title: "<?php echo __('Debug'); ?>",
                    },
                    extradata: [
                    {
                        name: "idAgentModule",
                        value: "<?php echo $this->idAgentModule; ?>"
                    }],
                    onshow: {
                        page: "<?php echo $this->ajaxController; ?>",
                        width: 800,
                        method: "showWebServerDebug"
                    }
                    });

                });
                
            });

            /**
             * Toggle the visibility of spinner.
             */
            function showSpinner(setVisibility) {
                var spinner = $('#WebServerDebugSpinner');
                if (setVisibility) {
                    spinner.css('visibility', 'visible');
                } else {
                    spinner.css('visibility', 'hidden');
                }
            }

            /**
            * Process ajax responses and shows a dialog with results.
            */
            function showMsg(data) {
                var title = "<?php echo __('Success'); ?>";
                var text = "";
                var failed = 0;
                try {
                    data = JSON.parse(data);
                    text = data["result"];
                } catch (err) {
                    title = "<?php echo __('Failed'); ?>";
                    text = err.message;
                    failed = 1;
                }
                if (!failed && data["error"] != undefined) {
                    title = "<?php echo __('Failed'); ?>";
                    text = data["error"];
                    failed = 1;
                }
                if (data["report"] != undefined) {
                    data["report"].forEach(function(item) {
                        text += "<br>" + item;
                    });
                }
            
                $("#msg").empty();
                $("#msg").html(text);
                $("#msg").dialog({
                width: 450,
                position: {
                    my: "center",
                    at: "center",
                    of: window,
                    collision: "fit"
                },
                title: title
                });
            }

        </script>
        

        <?php
        // Get the JS script.
        $str = ob_get_clean();
        // Return the loaded JS.
        echo $str;
        return $str;
    }


}