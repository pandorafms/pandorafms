<?php
/**
 * @package Include/help/ja
 */
?>
      <p class="para">
       日時フォーマット一覧
      </p>
      <p class="para">

       </p><table border="5">
        <caption><b>次の文字が、
        <i><tt class="parameter">フォーマット</tt></i>
 に利用できます。</b></caption>
        <colgroup>

         </colgroup><thead valign="middle">
          <tr valign="middle">
           <th colspan="1"><i><tt class="parameter">フォーマット</tt></i>

 設定文字</th>
           <th colspan="1">説明</th>
           <th colspan="1">出力例</th>
          </tr>

         </thead>

         <tbody class="tbody" valign="middle">

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">日</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>d</i></td>

           <td colspan="1" rowspan="1" align="left">2桁で日にちを表示します。(一桁の場合は頭に 0をつけます)</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> 〜 <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>D</i></td>

           <td colspan="1" rowspan="1" align="left">3文字の英語表記で曜日を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>Mon</i> 〜 <i>Sun</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>j</i></td>

           <td colspan="1" rowspan="1" align="left">日にちを表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> 〜 <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>l</i> (小文字の'L')</td>

           <td colspan="1" rowspan="1" align="left">英語表記で曜日を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>Sunday</i> 〜 <i>Saturday</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>N</i></td>

           <td colspan="1" rowspan="1" align="left">ISO-8601 形式の数値で曜日を表示します。(PHP 5.1.0 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> (月) 〜 <i>7</i> (for 日)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>S</i></td>

           <td colspan="1" rowspan="1" align="left">英語の日にち表示サフィックスを付与します。</td>
           <td colspan="1" rowspan="1" align="left">
            <i>st</i>, <i>nd</i>, <i>rd</i> や
            <i>th</i>。  <i>j</i>オプションと同時に利用します。

           </td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>w</i></td>
           <td colspan="1" rowspan="1" align="left">数値で曜日を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> (日) 〜 <i>6</i> (土)</td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>z</i></td>
           <td colspan="1" rowspan="1" align="left">年の初めからの日にちを表示します。(0から開始)</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> 〜 <i>365</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">週</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>W</i></td>
           <td colspan="1" rowspan="1" align="left">ISO-8601 形式での年の初めからの週を表示します。週は月曜から始まります。(PHP 4.1.0 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left">例: <i>42</i> (その年の 42週目)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">月</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>F</i></td>

           <td colspan="1" rowspan="1" align="left">January, March といった、英語表記の月を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>January</i> 〜 <i>December</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>m</i></td>

           <td colspan="1" rowspan="1" align="left">月を数値で表示します。一桁の場合は頭に 0を追加します。</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> 〜 <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>M</i></td>

           <td colspan="1" rowspan="1" align="left">3文字の省略形英語表記で月を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>Jan</i> 〜 <i>Dec</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>n</i></td>

           <td colspan="1" rowspan="1" align="left">月を数値で表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> 〜 <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>t</i></td>

           <td colspan="1" rowspan="1" align="left">その月の日数を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>28</i> 〜 <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">年</em></td>

           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>L</i></td>
           <td colspan="1" rowspan="1" align="left">うるう年かどうかを表示します。</td>

           <td colspan="1" rowspan="1" align="left">うるう年であれば<i>1</i>、そうでなければ<i>0</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>o</i></td>
           <td colspan="1" rowspan="1" align="left">ISO-8601 形式の年を表示します。これは、ISO形式の週番号(<i>W</i>)が前年もしくは翌年に依存する場合を除き<i>Y</i>と同じ値になります。(PHP 5.1.0 で追加されました)</td>

           <td colspan="1" rowspan="1" align="left">例: <i>1999</i>、<i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Y</i></td>
           <td colspan="1" rowspan="1" align="left">4桁で年を表示します。</td>

           <td colspan="1" rowspan="1" align="left">例: <i>1999</i>、<i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>y</i></td>
           <td colspan="1" rowspan="1" align="left">2桁で年を表示します。</td>

           <td colspan="1" rowspan="1" align="left">例: <i>99</i>、<i>03</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">時間</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>

           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>a</i></td>
           <td colspan="1" rowspan="1" align="left">午前、午後を小文字で表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>am</i> または <i>pm</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>A</i></td>
           <td colspan="1" rowspan="1" align="left">午前、午後を大文字で表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>AM</i> または <i>PM</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>B</i></td>
           <td colspan="1" rowspan="1" align="left">スウォッチ・インターネットタイムを表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>000</i> 〜 <i>999</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>g</i></td>
           <td colspan="1" rowspan="1" align="left">12時間フォーマットで時間を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> 〜 <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>G</i></td>
           <td colspan="1" rowspan="1" align="left">24時間フォーマットで時間を表示します。</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> 〜 <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>h</i></td>
           <td colspan="1" rowspan="1" align="left">12時間フォーマットで時間を表示します。1桁の場合は頭に0を付けます。</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> 〜 <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>H</i></td>
           <td colspan="1" rowspan="1" align="left">24時間フォーマットで時間を表示します。1桁の場合は頭に0を付けます。</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> 〜 <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>i</i></td>
           <td colspan="1" rowspan="1" align="left">分を表示します。1桁の場合は頭に0を付けます。</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> 〜 <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>s</i></td>
           <td colspan="1" rowspan="1" align="left">秒を表示します。1桁の場合は頭に0を付けます。</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> 〜 <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>u</i></td>
           <td colspan="1" rowspan="1" align="left">ミリ秒を表示します。(PHP 5.2.2 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left">例: <i>54321</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">タイムゾーン</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>e</i></td>
           <td colspan="1" rowspan="1" align="left">タイムゾーンを表示します。(PHP 5.1.0 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left">例: <i>UTC</i>, <i>GMT</i>, <i>Atlantic/Azores</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>I</i> (アルファベットの'アイ')</td>
           <td colspan="1" rowspan="1" align="left">サマータイムかどうかを表示します。</td>
           <td colspan="1" rowspan="1" align="left">サマータイムの場合は<i>1</i>、そうでなければ<i>0</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>O</i></td>
           <td colspan="1" rowspan="1" align="left">グリニッジ標準時間(GMT)との差を表示します。</td>
           <td colspan="1" rowspan="1" align="left">例: <i>+0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>P</i></td>
           <td colspan="1" rowspan="1" align="left">グリニッジ標準時間(GMT)との差を表示します。時間と分の間にコロンを入れます。(PHP 5.1.3 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left">例: <i>+02:00</i></td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>T</i></td>
           <td colspan="1" rowspan="1" align="left">タイムゾーンの略称を表示します。</td>
           <td colspan="1" rowspan="1" align="left">例: <i>EST</i>, <i>MDT</i> ...</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Z</i></td>
           <td colspan="1" rowspan="1" align="left">タイムゾーンの差を秒で表示します。UTCの西であれば常に負の値になり、UTCの東であれば常に正の値になります。</td>
           <td colspan="1" rowspan="1" align="left"><i>-43200</i> 〜 <i>50400</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">定型日時表示</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>c</i></td>

           <td colspan="1" rowspan="1" align="left">ISO 8601 形式を表示します。(PHP 5 で追加されました)</td>
           <td colspan="1" rowspan="1" align="left">2004-02-12T15:19:21+00:00</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>r</i></td>
           <td colspan="1" rowspan="1" align="left"><a href="http://www.faqs.org/rfcs/rfc2822" class="link external">RFC 2822</a>形式を表示します。</td>

           <td colspan="1" rowspan="1" align="left">例: <i>Thu, 21 Dec 2000 16:01:07 +0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>U</i></td>
           <td colspan="1" rowspan="1" align="left">Unixエポックタイムを表示します。(1970年 1月 1日 00:00:00 GMT からの経過時間)</td>
           <td colspan="1" rowspan="1" align="left">次のリンクを参照: <a href="http://es.php.net/manual/en/function.time.php" class="function">time()</a></td>

          </tr>

         </tbody>
        

       </table>
  
  <h3 class="title">例</h3>

<table cellpadding=4 cellspacing=4 class=datos>
<tr>
<th>フォーマット設定</th><th>表示例</th>
</tr>
<tr>
<td>F j, Y, g:i a</td><td>           March 10, 2001, 5:16 pm</td>
</tr>
<tr>
<td>m.d.y</td><td>           03.10.01</td>
</tr>
<tr>
<td>j, n, Y</td><td>           10, 3, 2001</td>
</tr>
<tr>
<td>Ymd</td><td>20010310</td>
</tr>
<tr>
<td>h-i-s, j-m-y, it is w Day z</td><td>05-16-17, 10-03-01, 1631 1618 6 Fripm01</td>
</tr>
<tr>
<td>\i\t \i\s \t\h\e jS \d\a\y.</td><td>It is the 10th day.</td>
</tr>

<tr>
<td>D M j G:i:s T Y                    </td><td>Sat Mar 10 15:16:08 MST 2001</td>
</tr>
<tr>
<td>H:i:s</td><td>17:16:17</td>
</table>