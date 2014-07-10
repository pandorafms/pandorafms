<?php
/**
 * @package Include/help/ja
 */
?>
<h1>SNMPトラップ - アラートフィールドマクロ</h1>
<p>

データフィールドがある場合、アラートでそれを利用する必要があります。この目的のために、特別な _snmp_fX_ というマクロを利用できます。これらのマクロは、SNMP トラップアラート以外では無効です。
<br><br>
メッセージを生成するには、フィールド1に次のような設定をします。
<br><br>
&nbsp;Chassis Alert: _snmp_f2_ in device _snmp_f1_
<br><br>
任意のアラートのフィールドX (1 から 10) で以下のマクロを利用できます。
<br><br>
<b>_data_</b>: トラップ全体<br>
<b>_agent_</b>: エージェント名<br>
<b>_address_</b>: IP アドレス<br>
<b>_timestamp_</b>: トラップのタイムスタンプ<br>
<b>_snmp_oid_</b>: トラップの OID<br>
<b>_snmp_value_</b>: トラップ OID の値<br>
</p>
