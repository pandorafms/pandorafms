<?php
/**
 * @package Include/help/ja
 */
?>
<h1>トラップのタイプ</h1>
<ul>
    <li>Cold start (0): エージェントが(再)起動したことを示します。</li>
    <li>Warm start (1): エージェントの設定が変更されたことを示します。</li>
    <li>Link down (2): ネットワークインタフェースがダウンしたことを示します。</li>
    <li>Link up (3): ネットワークインタフェースがアップしたことを示します。</li>
    <li>Authentication failure (4): エージェントが許可されていない発信元からのリクエストを受けたことを示します。(通常はコミュニティで制限されています)</li>
    <li>EGP neighbor loss (5): EGP プロトコルを利用しているルータで、隣接ホストとの通信が切れたことを示します。</li>
    <li>Enterprise (6): このカテゴリに含まれるものは、ベンダー独自のトラップです。</li>
</ul>
