<?php
/**
 * @package Include/help/ja
 */
?>
<h1>IP アドレス管理 (IPAM)</h1>
<br>
IPAM 拡張を利用して、指定したネットワークのホストの管理、検出、変更イベントの検出をすることができます。IP アドレス(IPv4 または IPv6)が変更された場合、ホスト(ping応答)やホスト名(DNS名前解決の利用)が存在するかどうかを知ることができます。また、OS の検出および、現在割り当てられている IP アドレスを追加することにより、現在の <?php echo get_product_name(); ?> エージェントに IP アドレスをリンクすることができます。IPAM 拡張は、ベースに自動検出サーバおよび自動検出スクリプトを利用します。ただし、何の設定も要りません。IPAM 拡張は全てを自動実行します。
<br><br>
IP 管理は、<?php echo get_product_name(); ?> エージェントで設定している監視と並行して動作します。IP アドレス管理を IPAM 拡張と関連づけることも、そうしないことも、好きにできます。管理された IP アドレスは、変化時にオプションでイベントを生成することができます。

<h2>IP 検出</h2>
ネットワークを設定する(ネットマスクまたはプレフィックスを利用)ことができ、ネットワークは、自動的に検出するかまたは、手動実行する設定ができます。これは、自動検出タスクを実行し、(IPv4 では nmap、IPv6 では ping を利用し)応答のある IP を検索します。ネットワーク検出の進捗、はステータス画面および自動検出サーバ画面でも見ることができます。
<br><br>

<h2>表示</h2>
ネットワーク IP アドレス管理と操作は、アイコン画面と編集画面の 2つの画面に分かれています。

<h3>アイコン画面</h3>
この表示は、使われている IP アドレスの数(管理されているアドレスのみ)と割合を含めた、ネットワーク情報をレポートします。フィルタした一覧を Excel/CSV にエクスポートすることもできます。<br><br>
アドレスは、大小のアイコンで表示されます。このアイコンは次の情報を示します。
<br><br>
<table width=100%>
<tr>
<th colspan=3>管理済</th>
</tr>
<tr>
<th>設定</th>
<th>応答ホスト</th>
<th>非応答ホスト</th>
</tr>
<tr>
<td>エージェント未割当<br><br>イベント無効</td>
<td class="center"><img src="../enterprise/images/ipam/green_host.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host.png"></td>
</tr>
<tr>
<td>エージェント割当済<br><br>イベント無効</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent.png"></td>
</tr>
<tr>
<td>エージェント未割当<br><br>イベント有効</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_alert.png"></td>
</tr>
<tr>
<td>エージェント割当済<br><br>イベント有効</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_agent_alert.png"></td>
<td class="center"><img src="../enterprise/images/ipam/red_host_agent_alert.png"></td>
</tr>
<tr>
<th colspan=3>未管理</th>
</tr>
<tr>
<th>設定</th>
<th>応答ホスト</th>
<th>非応答ホスト<</th>
</tr>
<tr>
<td class="w100px">IP アドレスが管理されていない場合、応答があるかどうかだけを見ることができます。</td>
<td class="center"><img src="../enterprise/images/ipam/green_host_dotted.png"></td>
<td class="center"><img src="../enterprise/images/ipam/not_host.png"></td>
</tr>
<tr>
<th colspan=3>未割当</th>
</tr>
<tr>
<td colspan=3>未割当のときはアイコンは青になります。</td>
</tr>
</table>
<br><br>
それぞれの IP アドレスは、右下にそれを編集する(管理者権限)リンクがあります。左下には、検出した OS を表示する小さいアイコンがあります。無効化アドレスでは OS の代わりに、次のようなアイコンが表示されます。<br><br><img src="../images/delete.png" class="w18px"><br><br>

メインのアイコンをクリックすると、関連づけられたエージェントおよび OS、IP および他の情報の設定、作成日時、最終編集日時、サーバで最後にチェックされ時間を含む、全ての IP 情報を表示するウインドウが開きます。この画面では、手動で IP アドレスが ping に応答するかどうか、リアルタイムのチェックをすることができます。この ping は、自動検出サーバで実行される通常の処理とは違い、コンソールで実行されることに注意してください。

<h3>編集画面</h3>
権限があれば、編集画面にアクセスできます。ここでは、IP アドレスが一覧で表示されます。必要な IP のみを表示するようにフィルタすることができます。すべて一度に変更および更新ができます。<br><br>

<?php echo get_product_name(); ?> エージェントおよび OS がある場合、ホスト名などいくつかのフィールドは、自動検出スクリプトによって自動的に入力されています。これらのフィールドは "手動" に設定して編集することができます。<br><br>

<table width=100%>
<tr>
<th colspan=2>手動と自動の切り替え</th>
</tr>
<tr>
<td class="center w25px"><img src="../images/manual.png"></td>
<td><b>手動モード</b>: この表示の場合、フィールドは自動検出システムで更新されません。手動で編集することができます。クリックすることにより、自動モードに切り替えることができます。
</td>
</tr>
<tr>
<td class="center w25px"><img src="../images/automatic.png"></td>
<td><b>自動モード</b>:このアイコンの場合、フィールドは自動検出スクリプトで更新されます。クリックすることにより、手動モードに切り替えることができます。</td>
</tr>
</table
<br><br>
<b>*手動設定の場合は、自動検出スクリプトで更新されません。</b><br><br>

その他、編集可能なフィールドは次の通りです。
<ul>
<li>- IP アドレスのイベント有効化。アドレスの存在性(応答があるかないか)がやホスト名が変わった場合に、イベントが生成されます。<br><br>
<b>アドレスが作成された時は、常にイベントを生成します。</b><br><br></li>
<li>- IP アドレスを <i>管理済</i> にする。このアドレスは、ネットワークに割り当てられたものと認識し、システムで管理されます。管理済アドレスのみを表示するようにフィルタできます。<br><br></li>
<li>- 無効化。無効化 IP アドレスは、自動検出スクリプトでチェックされません。<br><br></li>
<li>- コメント。それぞれのアドレスに対する任意のコメントフィールド。/li>
</ul>

<h2>フィルタ</h2>
両方の画面で、IP、ホスト名および、最新の更新によってソートすることができます。<br><br>
システム内の IP、ホスト名、それぞれの IP に対するコメントの文字列でフィルタすることもできます。検索ボックスの近くのチェックボックスをチェックすることにより、IP による完全一致になります。<br><br>

デフォルトでは、応答の無いホストは表示されませんが、フィルタを変更することができます。<br><br>
管理済の IP アドレスのみを表示することもできます。

<h2>サブネット計算</h2>

IPAM は、IPv4 および IPv6 のサブネットを計算するツールを含んでいます。<br><br>
このツールでは、IP アドレスとネットマスクを使って、ネットワークアドレス、ブロードキャストアドレス、サブネット内の最初と最後の有効な IP、ホストの合計数などのネットワークの情報を取得することができます。また、サブネットマスクが解りやすいように、バイナリ表示をすることもできます。
