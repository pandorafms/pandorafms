<?php
/**
 * Credential store
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Help Feedback
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
global $config;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
ui_require_css_file('pandora');
/**
 * Class HelpFeedBack.
 */
class HelpFeedBack extends Wizard
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'loadFeedbackForm',
        'sendMailMethod',
    ];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */


    public function ajaxMethod($method)
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param string $ajax_controller Controller.
     *
     * @return object
     */
    public function __construct($ajax_controller)
    {
        $this->ajaxController = $ajax_controller;

        return $this;
    }


    /**
     * Main method.
     *
     * @return void
     */
    public function run()
    {
        ui_require_css_file('help_feedback');

        $help_url = get_parameter('url', null);
        if ($help_url === null) {
            echo __('Page not found');
        } else {
            ?>
        <iframe width="100%" height="100%" frameBorder="0" 
            src="<?php echo $help_url; ?>">
            <?php echo __('Browser not compatible.'); ?>
        </iframe>
            <?php
        }

        echo '<div class="help_feedback">';
        // Load feedback form.
        echo $this->loadFeedbackForm();
        echo '</div>';
    }


    /**
     * Loads a feedback form
     *
     * @return​ ​string HTML code for form.
     *
     * @return Function loadFeedbackForm.
     */
    public function loadFeedbackForm()
    {
        global $config;

        ui_require_css_file('helper');

        $form = [
            'action'   => '#',
            'id'       => 'feedback_form',
            'onsubmit' => 'return false;',
        ];

        $inputs = [
            [
                'wrapper'       => 'div',
                'block_id'      => 'btn_section',
                'class'         => '',
                'direct'        => 1,
                'block_content' => [
                    [
                        'arguments' => [
                            'label'      => __('Sugesstion'),
                            'type'       => 'button',
                            'attributes' => 'class="sub ok btn_sug"',
                            'name'       => 'option_1',
                            'id'         => 'option_1',
                            'script'     => 'change_option1()',
                        ],
                    ],
                    [
                        'arguments' => [
                            'label'      => __('Something is not quite right'),
                            'type'       => 'button',
                            'attributes' => 'class="sub ok btn_something"',
                            'name'       => 'option_2',
                            'id'         => 'option_2',
                            'script'     => 'change_option2()',
                        ],
                    ],
                ],
            ],
            [

                'label'     => __('What Happend?'),
                'arguments' => [
                    'class' => 'textarea_feedback',
                    'id'    => 'feedback_text',
                    'type'  => 'textarea',
                    'name'  => 'feedback_text',
                ],
            ],
            [
                'label'     => __('Your Email'),
                'arguments' => [
                    'id'          => 'feedback_email',
                    'name'        => 'feedback_email',
                    'input_class' => 'email_feedback',
                    'class'       => 'email_feedback',
                    'type'        => 'text',
                ],
            ],
            [
                'arguments' => [
                    'button_class' => 'btn_submit',
                    'class'        => 'btn_submit',
                    'attributes'   => 'class="sub next btn_submit_feed_back"',
                    'type'         => 'submit',
                    'id'           => 'submit_feedback',
                    'label'        => __('Submit'),
                ],
            ],
        ];

        $output = ui_toggle(
            $this->printForm(
                [
                    'form'   => $form,
                    'inputs' => $inputs,
                ],
                true
            ),
            __('Feedback'),
            '',
            '',
            true,
            false,
            '',
            'no-border'
        );

        $output .= $this->loadJS();
        return $output;
    }


    /**
     * Function send_mail_method,we use send_email_attachment method
     * from functions_cron.php.
     *
     * @param​ ​string​ $feedback_option type fo mail.
     * @param​ ​string​ $feedback_text text mail.
     * @param​ ​string​ $feedback_mail costumer mail.
     *
     * @return integer Status of the email send task.
     */
    public function sendMailMethod()
    {
        $subject = get_parameter('feedback_option', null);
        $feedback_text = get_parameter('feedback_text', null);
        $feedback_mail = get_parameter('feedback_email', null);

        $subject;

        if ($subject === null) {
            echo json_encode(['error' => __('No ha seleccionado una opcion')]);
            exit;
        }

        enterprise_include_once('include/functions_cron.php');

        $feedback_text .= '
        From '.$feedback_mail.' ';

        $res = enterprise_hook('send_email_attachment', ['feedback@artica.es', $feedback_text, $subject]);

        return $res;

    }


    public function loadJS()
    {
        ob_start();
        ?>
    <script type="text/javascript">

    var option_selected = "";
    function change_option1() {
        option_selected = "<?php echo __('Suggestion'); ?>";
        document.getElementById("button-option_2").className = "btn_sug_not_selected";
        document.getElementById("button-option_1").className = "sub ok btn_sug";


        }

    function change_option2() {
        option_selected = "<?php echo __('Something is not quite rigth'); ?>";
        document.getElementById("button-option_1").className = "btn_sug_not_selected";
        document.getElementById("button-option_2").className = "sub ok btn_sug";

    }

    console.log(option_selected);
        // Set values to data.
        $("#feedback_form").on('submit', function() {
            // Make the AJAX call to send mails.
            $.ajax({
                type: "POST",
                url: "ajax.php",
                dataType: "html",
                data: {
                    page: "<?php echo $this->ajaxController; ?>",
                    method: 'sendMailMethod',
                    feedback_option: option_selected,
                    feedback_text: $("textarea[name=feedback_text]").val(),
                    feedback_email: $("input[name=feedback_email]").val()
                },
                success: function (data) {
                    console.log(data);
                    if (data == 1) {
                        alert('Message sent');
                    } else {
                        console.error("Error in AJAX call to send help feedback mail")
                    }
                },
                error: function (data) {
                        console.error("Fatal error in AJAX call to send help feedback mail")
                }
            });
        });

    </script>
        <?php
        return ob_get_clean();
    }


}

