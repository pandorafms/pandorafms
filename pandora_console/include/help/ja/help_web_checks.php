<?php
/**
 * @package Include/help/ja
 */
?>
<h1>ウェブモニタリング</h1>

<p>
拡張ウェブモニタリングは、Pandora FMS エンタープライズ版の Goliat で動作する機能です。
<br><br>
以下に、GOLIAT ウェブチェックモジュールのサンプルを示します。
<br>
</p>
<pre>


task_begin
post http://galaga.artica.es/pandora/index.php?login=1
variable_name nick
variable_value demo
variable_name pass
variable_value demo
cookie 1
resource 1
task_end

task_begin
get http://galaga.artica.es/pandora/index.php?sec=messages&amp;sec2=operation/messages/message
cookie 1
resource 1
check_string Read messages
task_end


</pre>
