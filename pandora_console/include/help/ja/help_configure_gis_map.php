<?php
/**
 * @package Include/help/ja
 */
?>
<h1>GIS マップ設定</h1>

<p>
このページで、GIS マップを設定します。
</p>
<h2>マップ名</h2>
<p>
それぞれのマップには <?php echo get_product_name(); ?> 内で区別するための名前を定義します。
</p>
<h2>利用マップの選択</h2>
<p>
最初のステップは、GIS マップで利用する<strong>メインのマップ</strong>を定義することです。
GIS マップを設定するためには、少なくとも一つ選択されている必要があります。
<?php html_print_image('images/add.png', false, ['alt' => 'Add']); ?>(追加)アイコンをクリックして、追加することも可能です。
</p>
<p>
意図しない設定情報の再入力を防ぐために、利用マップが設定されると、それを <?php echo get_product_name(); ?> はデフォルトのマップとして利用するかどうか尋ねます。
(ラジオボタンによって)デフォルトの利用マップが変更された場合は、それで良いか <?php echo get_product_name(); ?> は再度尋ねます。
</p>
<h2>マップパラメータ</h2>
<p>
一度利用マップが選択されると、個別のパラメータ設定が可能です。
マップの<strong>中心</strong>(マップを開いた時に表示される)、<strong>デフォルトの拡大レベル</strong>(マップを開いた時の拡大レベル)、<strong>デフォルト位置</strong>(位置情報を持たないエージェントが表示される位置)の設定ができます。
</p>
<p>
<strong>オプション:</strong>
</p>
<div>
<dl>
<dt>マップ名</dt>
<dd><strong>マップの名称</strong>を設定してください。わかりやすい名前にしてください。</dd>
<dt>グループ</dt>
<dd>マップを利用する<strong>グループ</strong>を ACL として設定します。</dd>
<dt>デフォルト拡大率</dt>
<dd>マップが開かれた時の、<strong>デフォルトの拡大レベル</strong>を設定してください。</dd>
<dt>中心経度</dt>
<dt>中心緯度</dt>
<dt>中心高度</dt>
<dd>マップが開かれた時の、マップの<strong>中心</strong>における<strong>経度</strong>、<strong>緯度</strong>、<strong>高度</strong>を設定してください。 </dd>
<dt>デフォルト経度</dt>
<dt>デフォルト緯度</dt>
<dt>デフォルト高度</dt>
<dd>位置情報が設定されていないエージェントのマップ上の<strong>デフォルト位置</strong>として、<strong>経度</strong>、<strong>経度</strong>、<strong>高度</strong>を設定してください。</dd>
</dl>
</div>
<h2>レイヤ設定</h2>
<p>
それぞれのマップはエージェントを表示するための一つ以上のレイヤ<sup><span class="font_75p">1</span></sup>を持っています。
それぞれのレイヤは、エージェントの<strong>グループ</strong>や<strong>エージェント一覧</strong>を表示することができます。
これは、それぞれのレイヤに表示するエージェントを設定するのに便利です。
</p>
<p>
レイヤは、<strong>表示</strong>、<strong>非表示</strong>の設定ができ、<strong>グループ</strong>を選択や<strong>エージェント</strong>の追加ができます。
一度レイヤが定義されると、左側の定義済レイヤに移ります。(定義は、マップ全体が保存されるまでは保存されません。)
ここでは、再度、<strong>順番の変更</strong> (<?php html_print_image('images/up.png', false, ['alt' => 'move up icon']); ?>、<?php html_print_image('images/down.png', false, ['alt' => 'move down icon']); ?>)、<strong>削除</strong> (<?php html_print_image('images/delete.svg', false, ['alt' => 'delete icon']); ?>)、<strong>編集</strong> (<?php html_print_image('images/edit.svg', false, ['alt' => 'edit icon']); ?>) ができます。
</p>
<hr/>
<sup><span class="font_75p">1</span></sup> <span class="font_85p">デフォルトマップでは、エージェント名で一つのレイヤしか表示しない場合は、レイヤを持たない設定も可能です。</span>
