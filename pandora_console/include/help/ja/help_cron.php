<?php
/**
 * @package Include/help/ja
 */
?>
<h1>サーバモジュールの cron</h1>

設定パラメータ <b>Cron from</b> および <b>Cron to</b> を用いることにより、特定の時間にのみモジュールを実行するようにできます。
設定のための書式は、<a class="font_14px" href="https://en.wikipedia.org/wiki/Cron">cron</a> に似ています。
<?php echo get_product_name(); ?> コンソールに表示され、それぞれのパラメータには 3つのオプションがあります。

<h4>Cron from: any</h4>

パラメータによるモジュールに対する制限はありません。値が何であっても実行され、cron のアスタリスク(*)と同等です。この場合、<b>Cron to</b> は無視されます。

<h4>Cron from: different from any. Cron to: any</h4>

パラメータにマッチする日時の間のみモジュールが実行されます。cron に一つの値を記載するのと同等です。

<h4>Cron from: different from any. Cron to: different from any</h4>

モジュールは、<b>Cron from</b> と <b>Cron to</b> で指定された間の時間のみ実行されます。
cron で、ハイフンを用いた数値(n-n)を記載するのと同等です。

<h2>エージェントの実行間隔</h2>

cron 条件が満たされている限り、エージェントは実行間隔に従って実行されます。

<h2>例</h2>

<ul>
    <li><i>* * * * *</i>: cron 設定なし。</li>
    <li><i>15 20 * * *</i>: 毎日 20:15 に実行します。</li>
    <li><i>* 20 * * *</i>: 毎日 20時台、20:00 から 20:59 の間に実行します。</li>
    <li><i>* 8-19 * * *</i>: 毎日 8:00 から 19:59 の間に実行します。</li>
    <li><i>15-45 * 1-16 * *</i>: 毎月 1日から 16日まで、毎時 15分から 45分の間で実行します。</li>
    <li><i>* * * 5 *</i>: 5月にのみ実行します。</li>
<ul>
