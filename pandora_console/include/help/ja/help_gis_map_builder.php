<?php
/**
 * @package Include/help/ja
 */
?>
<h1>GIS マップビルダー</h1>

<p>
このページでは定義済のマップ一覧を表示しています。
それらの編集や削除、参照ができます。
また、このページで、<?php echo get_product_name(); ?> の<strong>デフォルトマップ</strong>が定義されています。
</p>
マップを作成するためには、利用マップの設定が必要です。
利用マップの設定は、<strong>設定</strong>メニューで管理者権限にて実施してください。
<p>
</p>
<p>
オプション:
</p>
<div>
<dl>
<dt>マップ名</dt>
<dd><strong>マップ名</strong>をクリックすると、そのマップを編集できます。</dd>
<dt><?php html_print_image('images/eye.png', false, ['alt' => 'View']); ?> View</dt>
<dd>表示アイコンをクリックすると、マップを<strong>参照</strong>できます。</dd>
<dt>デフォルトラジオボタン</dt>
<dd><strong>ラジオボタン</strong>をクリックすると、そのマップが<strong>デフォルトマップ</strong>に設定されます。</dd>
<dt><?php html_print_image('images/delete.svg', false, ['alt' => 'Delete']); ?> 削除</dt>
<dd>削除アイコンをクリックすると、そのマップを<strong>削除</strong>します。</dd>
<dt>作成ボタン</dt>
<dd>作成ボタンをクリックすると、新しいマップを<strong>作成</strong>できます。</dd>
</dl>
</div>
