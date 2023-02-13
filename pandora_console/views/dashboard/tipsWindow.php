<div class="window">
<div class="tips_header">
    <p><input type="checkbox" name="tips_in_start"/>Ver típs al iniciar</p>
</div>
<div class="carousel <?php echo ($files === false) ? 'invisible' : ''; ?>">
    <button class="next_step">></button>
    <div class="images">
    <?php if ($files !== false) : ?>
        <?php foreach ($files as $key => $file) : ?>
            <img src="<?php echo $file['filename']; ?>" />
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
    <button class="prev_step"><</button>
</div>

<div class="description">
    <h2 id="title_tip"><?php echo $title; ?></h2>
    <p id="text_tip">
        <?php echo $text; ?>
    </p>
    <a href="<?php echo $url; ?>" id="url_tip"><?php echo __('Más información'); ?></a>
</div>
<div class="actions">
    <button id="next_tip"><?php echo __('Siguiente'); ?></button>
</div>
</div>