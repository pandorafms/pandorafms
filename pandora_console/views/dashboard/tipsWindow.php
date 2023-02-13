<?php
/**
 * Dashboards Modal for tips
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
?>
<div class="window">
<div class="tips_header">
    <p class="title"><?php echo __('¡Hola! estos son los tips del día.'); ?></p>
    <p><?php echo html_print_checkbox('tips_in_start', true, true, true); ?>Ver típs al iniciar</p>
</div>
<div class="carousel <?php echo ($files === false) ? 'invisible' : ''; ?>">
    <div class="images">
    <?php if ($files !== false) : ?>
        <?php foreach ($files as $key => $file) : ?>
            <img src="<?php echo $file['filename']; ?>" />
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<div class="description">
    <h2 id="title_tip"><?php echo $title; ?></h2>
    <p id="text_tip">
        <?php echo $text; ?>
    </p>
    <?php if (empty($url) === false) : ?>
        <a href="<?php echo $url; ?>" id="url_tip"><?php echo __('Ver más info'); ?> <span class="arrow_tips">→</span></a>
    <?php endif; ?>
</div>

<div class="ui-dialog-buttonset">
    <button type="button" class="submit-cancel-tips ui-button ui-corner-all ui-widget">Quizás luego</button>
    <button type="button" class="submit-next-tips ui-button ui-corner-all ui-widget">De acuerdo</button>
</div>
</div>