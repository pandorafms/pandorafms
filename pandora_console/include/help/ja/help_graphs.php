<?php
/*
 * @package Include/help/ja
 */
?>


<style type="text/css">

* {
    font-size: 1em;
}

img.hlp_graphs {
    width: 80%;
    max-width: 800px;
    min-width: 400px;
    margin: 15px auto;
    display: block;
}

ul.clean {
    list-style-type: none;
}

b {
    font-size: 0.90em!important;
}
dl dt {
    margin-top: 1em;
    font-weight: bold;
}
dl {
    margin-bottom: 2em;
}

div.img_title {
    text-align: center;
    font-size: 0.8em;
    font-style: italic;
    width: 100%;
    margin-top: 4em;
}
</style>

<body class="hlp_graphs">
<h1><?php echo get_product_name(); ?> におけるグラフ処理</h1>

<p><?php echo get_product_name(); ?>では、グラフは指定した期間においてモジュールが持つ値を表現します。</p>
<p><?php echo get_product_name(); ?>には、大量のデータが保存されるため、2つの異なるタイプの機能を提供しています。</p>

<h2>通常グラフ</h2>

<img class="hlp_graphs" src="<?php echo $config['homeurl']; ?>images/help/chart_normal_sample.png" alt="regular chart sample" />

<h4>一般的な特性</h4>
<p>基本的なレベルでモジュールに保存された情報を表現するグラフがあります。</p>
<p>モジュールの変動する値の近似値を見ることができます。<p>
<p>モジュールデータは表示をシンプルにするための<i>箱</i>に分割され、<b>全ての値が表示されるわけではありません</b>。これは、表示を <b>最大</b>、<b>最小</b>、<b>平均</b>の 3つのグラフに分割することによって補完しています。</p>

<ul class="clean">
<li><b>利点</b>: 多くのリソースを消費することなく素早く表示されます。</li>
<li><b>欠点</b>: 提供される情報はおおよその値です。それらが表す監視状況は、イベント発生状況に基づいて計算されます。</li>



<h4>表示オプション</h4>

<dl>
<dt>リフレッシュ時間</dt>
<dd>グラフが再生成された時間です。</dd>

<dt>平均のみ</dt>
<dd>平均のみのグラフが生成されます。</dd>

<dt>開始日時</dt>
<dd>この日時までのグラフが生成されます。</dd>

<dt>開始時間</dt>
<dd>グラフが生成されるまでの時間、分、秒です。</dd>

<dt>拡大率(Zoom)</dt>
<dd>グラフの拡大率です。</dd>

<dt>時間範囲</dt>
<dd>データを集める時間設定です。</dd>

<dt>イベント表示</dt>
<dd>一番上にイベント情報をポイント表示します。</dd>

<dt>アラート表示</dt>
<dd>一番上に発生したアラートの情報をポイント表示します。</dd>

<dt>パーセント表示</dt>
<dd>グラフにパーセント表示の線を追加します。(<?php echo get_product_name(); ?> の表示オプションで設定できます)</dd>

<dt>時間比較 (重ね合わせ)</dt>
<dd>同一のグラフを重ね合わせて表示します。ただし、選択した期間より前との重ね合わせです。例えば、期間として 1週間を選択し、このオプションをチェックすると、選択した期間の前の 1週間が重ねあわされて表示されます。</dd>

<dt>時間比較 (分割)</dt>
<dd>同一のグラフを表示します。ただし、選択した期間より前のグラフを別々に表示します。例えば、期間として 1週間を選択し、このオプションをチェックすると、選択した期間の前の 1週間のグラフも表示されます。</dd>

<dt>不明グラフ表示</dt>
<dd><?php echo get_product_name(); ?> がモジュールの状態を把握していない、データ欠損、ソフトウエアエージェントの接続断などがあった期間を、グレーの箱で表示します。</dd>

<dt>詳細グラフ表示 (TIP)</dt>
<dd>生成モードを "通常" から "TIP" へ切り替えます。このモードでは、グラフを近似値ではなく実データで表示します。そのため、生成にかかる時間は長くなります。このグラフでは、次に説明するより詳細の情報が参照できます。</dd>

</dl>




<br />
<br />


<h2>詳細グラフ</h2>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_sample.png" alt="TIP chart sample" />

<h4>一般的な特性</h4>
<p><b>実データ</b>を表現するグラフです。</p>
<p>モジュールが収集したデータをそのままの状態で表示します。</p>
<p>実データのため、追加のグラフ(平均、最小、最大)で情報を補足する必要はありませせん。</p>
<p>通常のグラフど同様に、不明状態の期間の計算に対応していますが、存在する場合は補完されます。</p>
<p>通常と TIP グラフで提供される表示例:</p>

<div class="img_title">不明期間の通常グラフの例</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_normal_detail.png" alt="Normal chart detail" />

<div class="img_title">不明期間の TIP グラフの例</div>
<img class="hlp_graphs "src="<?php echo $config['homeurl']; ?>images/help/chart_tip_detail.png" alt="TIP chart detail" />

<br />

<ul class="clean">
<li><b>利点</b>: 表示されるのは実データです。モジュールのデータを最も忠実に表現します。</li>
<li><b>欠点</b>: 通常のグラフより処理が遅くなります。表示する時間間隔やデータ量によっては、表示が遅くなります。</li>
</ul>

</body>

