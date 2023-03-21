<?php
/**
 * @package Include/help/ja
 */
?>
<h1>イベント一覧</h1>

<br>
<br>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>承諾</h3>
    <?php html_print_image('images/tick.png', false, ['title' => '承諾済', 'alt' => '承諾済', 'width' => '10', 'height' => '10']); ?> - 承諾済<br>
    <?php html_print_image('images/tick_off.png', false, ['title' => '未承諾', 'alt' => '未承諾', 'width' => '10', 'height' => '10']); ?> - 未承諾
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>重要度</h3>
    <?php html_print_image('images/status_sets/default/severity_maintenance.png', false, ['title' => 'メンテナンス', 'alt' => 'メンテナンス']); ?>  - メンテナンス<br>
    <?php html_print_image('images/status_sets/default/severity_informational.png', false, ['title' => '情報', 'alt' => '情報']); ?> - 情報<br>
    <?php html_print_image('images/status_sets/default/severity_normal.png', false, ['title' => '正常', 'alt' => '正常']); ?> - 正常<br>
    <?php html_print_image('images/status_sets/default/severity_warning.png', false, ['title' => '警告', 'alt' => '警告']); ?> - 警告<br>
    <?php html_print_image('images/status_sets/default/severity_critical.png', false, ['title' => '障害', 'alt' => '障害']); ?> - 障害<br>
</div>

<div class="pdd_l_30px w150px float-left line_17px">
    <h3>アクション</h3>
    <?php html_print_image('images/ok.png', false, ['title' => '承諾する', 'alt' => '承諾する']); ?> - 承諾する<br>
    <?php html_print_image('images/delete.svg', false, ['title' => '削除する', 'alt' => '削除する']); ?> - 削除する<br>
    <?php html_print_image('images/page_lightning.png', false, ['title' => 'インシデントを作成する', 'alt' => 'インシデントを作成する']); ?> - インシデントを作成する
</div>

<div class="both">&nbsp;</div>
