<?php
/**
 * Pandora FMS - http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the  GNU Lesser General Public License
 * as published by the Free Software Foundation; version 2
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @package    Include
 * @subpackage Clippy
 */

// Begin.


/**
 * Starts clippy.
 *
 * @param string $sec2 Section.

 * @return void
 */
function clippy_start($sec2)
{
    global $config;

    if ($sec2 === false) {
        $sec2 = 'homepage';
    }

    $sec2 = str_replace('/', '_', $sec2);

    // Avoid some case the other parameters in the url.
    if (strstr($sec2, '&') !== false) {
        $chunks = explode('&', $sec2);
        $sec2 = $chunks[0];
    }

    if ($sec2 != 'homepage') {
        if (is_file('include/help/clippy/'.$sec2.'.php')) {
            include 'include/help/clippy/'.$sec2.'.php';

            $tours = clippy_start_page();
            clippy_write_javascript_helps_steps($tours);
        }

        // Add homepage for all pages for to show the "task sugestions".
        include 'include/help/clippy/homepage.php';

        $tours = clippy_start_page_homepage();
        clippy_write_javascript_helps_steps($tours);
    } else {
        include 'include/help/clippy/homepage.php';

        $tours = clippy_start_page_homepage();
        clippy_write_javascript_helps_steps($tours);
    }
}


/**
 * Undocumented function
 *
 * @return void
 */
function clippy_clean_help()
{
    set_cookie('clippy', null);
}


/**
 * Undocumented function
 *
 * @param something $tours Tour.
 *
 * @return void
 */
function clippy_write_javascript_helps_steps($tours)
{
    global $config;

    $first_step_by_default = false;
    if (isset($tours['first_step_by_default'])) {
        $first_step_by_default = $tours['first_step_by_default'];
    }

    // For the help context instead the clippy.
    $help_context = false;
    if (isset($tours['help_context'])) {
        $help_context = $tours['help_context'];
    }

    if ($help_context) {
        $name_obj_js_tour = '{clippy_obj}';
    } else {
        $name_obj_js_tour = 'intro';
    }

    $clippy = get_cookie('clippy', false);
    set_cookie('clippy', null);

    // Get the help steps from a task.
    $steps = null;
    if (isset($tours['tours'][$clippy])) {
        $steps = $tours['tours'][$clippy]['steps'];
    }

    if ($first_step_by_default) {
        if (empty($steps)) {
            // Get the first by default.
            $temp = reset($tours['tours']);
            $steps = $temp['steps'];
        }
    }

    if ($help_context) {
        foreach ($steps as $iterator => $step) {
            $init_step_context = false;
            if (isset($step['init_step_context'])) {
                $init_step_context = $step['init_step_context'];
            }

            if ($init_step_context) {
                unset($steps[$iterator]['init_step_context']);
                $steps[$iterator]['element'] = '{clippy}';
            }
        }
    }

    $conf = null;
    if (isset($tours['tours'][$clippy])) {
        $conf = $tours['tours'][$clippy]['conf'];
    }

    if ($first_step_by_default) {
        if (empty($conf)) {
            // Get the first by default.
            $temp = reset($tours['tours']);
            $conf = $temp['conf'];
        }
    }

    if (!empty($steps)) {
        foreach ($steps as $iterator => $element) {
            $steps[$iterator]['intro'] = "<div id='clippy_head_title'>".__('%s assistant', get_product_name()).'</div>'.$steps[$iterator]['intro'];
        }

        if (!empty($conf['name_obj_js_tour'])) {
            $name_obj_js_tour = $conf['name_obj_js_tour'];
        }

        $autostart = true;
        if (isset($conf['autostart'])) {
            $autostart = $conf['autostart'];
        }

        $other_js = '';
        if (!empty($conf['other_js'])) {
            $other_js = $conf['other_js'];
        }

        $exit_js = '';
        if (!empty($conf['exit_js'])) {
            $exit_js = $conf['exit_js'];
        }

        $complete_js = '';
        if (!empty($conf['complete_js'])) {
            $complete_js = $conf['complete_js'];
        }

        $show_bullets = 0;
        if (!empty($conf['show_bullets'])) {
            $show_bullets = (int) $conf['show_bullets'];
        }

        $show_step_numbers = 0;
        if (!empty($conf['show_step_numbers'])) {
            $show_step_numbers = (int) $conf['show_step_numbers'];
        }

        $doneLabel = __('End wizard');
        if (!empty($conf['done_label'])) {
            $doneLabel = $conf['done_label'];
        }

        $skipLabel = __('End wizard');
        if (!empty($conf['skip_label'])) {
            $skipLabel = $conf['skip_label'];
        }

        $help_context = false;
        ?>
        <script type="text/javascript">
            var <?php echo $name_obj_js_tour; ?> = null;
            
            $(document).ready(function() {
                <?php echo $name_obj_js_tour; ?> = introJs();
                
                <?php echo $name_obj_js_tour; ?>.setOptions({
                    steps: <?php echo json_encode($steps); ?>,
                    showBullets: 
        <?php
        if ($show_bullets) {
            echo 'true';
        } else {
            echo 'false';
        }
        ?>
     ,
                    showStepNumbers: 
        <?php
        if ($show_step_numbers) {
            echo 'true';
        } else {
            echo 'false';
        }
        ?>
     ,
                    nextLabel: "<?php echo __('Next &rarr;'); ?>",
                    prevLabel: "<?php echo __('&larr; Back'); ?>",
                    skipLabel: "<?php echo $skipLabel; ?>",
                    doneLabel: "<?php echo $doneLabel; ?>",
                    exitOnOverlayClick: false,
                    exitOnEsc: true, //false,
                })
                .oncomplete(function(value) {
                    <?php echo $complete_js; ?>;
                })
                .onexit(function(value) {
                    <?php echo $exit_js; ?>;
                    
                    exit = confirm("<?php echo __('Do you want to exit the help tour?'); ?>");
                    return exit;
                });
                
                <?php
                if (!empty($conf['next_help'])) {
                    ?>
                    clippy_set_help('<?php echo $conf['next_help']; ?>');
                    <?php
                }
                ?>
                
                <?php
                if ($autostart) {
                    echo $name_obj_js_tour;
                    ?>
                    .start();
                    <?php
                }
                ?>
            });
            
            <?php echo $other_js; ?>
        </script>
        <?php
    }
}


/**
 * Undocumented function
 *
 * @param string $help Help.
 *
 * @return void
 */
function clippy_context_help($help=null)
{
    global $config;

    if ($config['tutorial_mode'] == 'expert') {
        return;
    }

    $id = uniqid('id_');

    $return = '';

    include_once $config['homedir'].'/include/help/clippy/'.$help.'.php';

    ob_start();
    $function = 'clippy_'.$help;
    $tours = $function();
    clippy_write_javascript_helps_steps($tours);
    $code = ob_get_clean();

    $code = str_replace('{clippy}', '#'.$id, $code);
    $code = str_replace('{clippy_obj}', 'intro_'.$id, $code);

    if ($help === 'module_unknow') {
        $img = html_print_image(
            'images/info-warning.svg',
            true,
            [
                'class' => 'main_menu_icon invert_filter',
                'style' => 'margin-left: -25px;',
            ]
        );
    } else {
        $img = html_print_image(
            'images/info-warning.svg',
            true,
            ['class' => 'main_menu_icon invert_filter']
        );
    }

    $return = $code.'<div id="'.$id.'" class="inline"><a onclick="show_'.$id.'();" href="javascript: void(0);" >'.$img.'</a></div>
        <script type="text/javascript">
        
        function show_'.$id.'() {
            confirmDialog({
                title: "'.__('You have unknown modules in this agent.').'",
                message: "'.('Unknown modules are modules which receive data normally at least in one occassion, but at this time are not receving data. Please check our troubleshoot help page to help you determine why you have unknown modules.').'",
                strOKButton: "'.__('Close').'",
                hideCancelButton: true,
                size: 675,
            });
        }
        
        $(document).ready(function() {
            (function pulse_'.$id.'() {
                $("#'.$id.' img")
                    .delay(100)
                    .animate({\'opacity\': 1})
                    .delay(400)
                    .animate({\'opacity\': 0}, pulse_'.$id.');
            })();
            
            //$("#'.$id.' img").pulsate ();
        });
        </script>
        ';

    return $return;
}
