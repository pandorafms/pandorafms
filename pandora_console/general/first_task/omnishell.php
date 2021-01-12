<?php
/**
 * Omnishell first task.
 *
 * @category   Omnishell
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;
check_login();
ui_require_css_file('first_task');
?>
<?php ui_print_info_message(['no_close' => true, 'message' => __('There is no command defined yet.') ]); ?>

<div class="new_task">
    <div class="image_task">
        <?php echo html_print_image('images/first_task/omnishell.png', true, ['title' => __('Omnishell')]); ?>
    </div>
    <div class="text_task">
        <h3> <?php echo __('Omnishell'); ?></h3><p id="description_task"> 
            <?php
            echo '<p>'.__(
                'Omnishell is an enterprise feature which allows you to execute a structured command along any agent in your %s. The only requirement is to have remote configuration enabled in your agent.',
                io_safe_output(get_product_name())
            ).'</p>';

            echo '<p>'.__(
                'You can execute any command on as many agents you need, and check the execution on all of them using the Omnishell Command View'
            ).'</p>';
            ?>
        </p>
    <?php
    if (enterprise_installed()) {
        ?>
    <form action="index.php?sec=gextensions&sec2=enterprise/tools/omnishell&page=1" method="post">
        <input type="submit" class="button_task" value="<?php echo __('Define a command'); ?>" />
    </form>
        <?php
    }
    ?>
    </div>
</div>
