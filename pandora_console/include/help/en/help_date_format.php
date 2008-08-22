      <p class="para">
       The format of the outputted date string. See the formatting
       options below.
      </p>
      <p class="para">

       </p><table border="5">
        <caption><b>The following characters are recognized in the
        <i><tt class="parameter">format</tt></i>
 parameter string</b></caption>
        <colgroup>

         </colgroup><thead valign="middle">
          <tr valign="middle">
           <th colspan="1"><i><tt class="parameter">format</tt></i>

 character</th>
           <th colspan="1">Description</th>
           <th colspan="1">Example returned values</th>
          </tr>

         </thead>

         <tbody class="tbody" valign="middle">

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Day</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>d</i></td>

           <td colspan="1" rowspan="1" align="left">Day of the month, 2 digits with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> to <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>D</i></td>

           <td colspan="1" rowspan="1" align="left">A textual representation of a day, three letters</td>
           <td colspan="1" rowspan="1" align="left"><i>Mon</i> through <i>Sun</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>j</i></td>

           <td colspan="1" rowspan="1" align="left">Day of the month without leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> to <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>l</i> (lowercase 'L')</td>

           <td colspan="1" rowspan="1" align="left">A full textual representation of the day of the week</td>
           <td colspan="1" rowspan="1" align="left"><i>Sunday</i> through <i>Saturday</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>N</i></td>

           <td colspan="1" rowspan="1" align="left">ISO-8601 numeric representation of the day of the week (added in
           PHP 5.1.0)</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> (for Monday) through <i>7</i> (for Sunday)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>S</i></td>

           <td colspan="1" rowspan="1" align="left">English ordinal suffix for the day of the month, 2 characters</td>
           <td colspan="1" rowspan="1" align="left">
            <i>st</i>, <i>nd</i>, <i>rd</i> or
            <i>th</i>.  Works well with <i>j</i>

           </td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>w</i></td>
           <td colspan="1" rowspan="1" align="left">Numeric representation of the day of the week</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> (for Sunday) through <i>6</i> (for Saturday)</td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>z</i></td>
           <td colspan="1" rowspan="1" align="left">The day of the year (starting from 0)</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> through <i>365</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Week</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>W</i></td>
           <td colspan="1" rowspan="1" align="left">ISO-8601 week number of year, weeks starting on Monday (added in PHP 4.1.0)</td>
           <td colspan="1" rowspan="1" align="left">Example: <i>42</i> (the 42nd week in the year)</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Month</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>F</i></td>

           <td colspan="1" rowspan="1" align="left">A full textual representation of a month, such as January or March</td>
           <td colspan="1" rowspan="1" align="left"><i>January</i> through <i>December</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>m</i></td>

           <td colspan="1" rowspan="1" align="left">Numeric representation of a month, with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> through <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>M</i></td>

           <td colspan="1" rowspan="1" align="left">A short textual representation of a month, three letters</td>
           <td colspan="1" rowspan="1" align="left"><i>Jan</i> through <i>Dec</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>n</i></td>

           <td colspan="1" rowspan="1" align="left">Numeric representation of a month, without leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> through <i>12</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>t</i></td>

           <td colspan="1" rowspan="1" align="left">Number of days in the given month</td>
           <td colspan="1" rowspan="1" align="left"><i>28</i> through <i>31</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Year</em></td>

           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>L</i></td>
           <td colspan="1" rowspan="1" align="left">Whether it's a leap year</td>

           <td colspan="1" rowspan="1" align="left"><i>1</i> if it is a leap year, <i>0</i> otherwise.</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>o</i></td>
           <td colspan="1" rowspan="1" align="left">ISO-8601 year number. This has the same value as
            <i>Y</i>, except that if the ISO week number
            (<i>W</i>) belongs to the previous or next year, that year
            is used instead. (added in PHP 5.1.0)</td>

           <td colspan="1" rowspan="1" align="left">Examples: <i>1999</i> or <i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Y</i></td>
           <td colspan="1" rowspan="1" align="left">A full numeric representation of a year, 4 digits</td>

           <td colspan="1" rowspan="1" align="left">Examples: <i>1999</i> or <i>2003</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>y</i></td>
           <td colspan="1" rowspan="1" align="left">A two digit representation of a year</td>

           <td colspan="1" rowspan="1" align="left">Examples: <i>99</i> or <i>03</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Time</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>

           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>a</i></td>
           <td colspan="1" rowspan="1" align="left">Lowercase Ante meridiem and Post meridiem</td>
           <td colspan="1" rowspan="1" align="left"><i>am</i> or <i>pm</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>A</i></td>
           <td colspan="1" rowspan="1" align="left">Uppercase Ante meridiem and Post meridiem</td>
           <td colspan="1" rowspan="1" align="left"><i>AM</i> or <i>PM</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>B</i></td>
           <td colspan="1" rowspan="1" align="left">Swatch Internet time</td>
           <td colspan="1" rowspan="1" align="left"><i>000</i> through <i>999</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>g</i></td>
           <td colspan="1" rowspan="1" align="left">12-hour format of an hour without leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> through <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>G</i></td>
           <td colspan="1" rowspan="1" align="left">24-hour format of an hour without leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>0</i> through <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>h</i></td>
           <td colspan="1" rowspan="1" align="left">12-hour format of an hour with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>01</i> through <i>12</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>H</i></td>
           <td colspan="1" rowspan="1" align="left">24-hour format of an hour with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> through <i>23</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>i</i></td>
           <td colspan="1" rowspan="1" align="left">Minutes with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> to <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>s</i></td>
           <td colspan="1" rowspan="1" align="left">Seconds, with leading zeros</td>
           <td colspan="1" rowspan="1" align="left"><i>00</i> through <i>59</i></td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>u</i></td>
           <td colspan="1" rowspan="1" align="left">Milliseconds (added in PHP 5.2.2)</td>
           <td colspan="1" rowspan="1" align="left">Example: <i>54321</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Timezone</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>e</i></td>
           <td colspan="1" rowspan="1" align="left">Timezone identifier (added in PHP 5.1.0)</td>
           <td colspan="1" rowspan="1" align="left">Examples: <i>UTC</i>, <i>GMT</i>, <i>Atlantic/Azores</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>I</i> (capital i)</td>
           <td colspan="1" rowspan="1" align="left">Whether or not the date is in daylight saving time</td>
           <td colspan="1" rowspan="1" align="left"><i>1</i> if Daylight Saving Time, <i>0</i> otherwise.</td>

          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>O</i></td>
           <td colspan="1" rowspan="1" align="left">Difference to Greenwich time (GMT) in hours</td>
           <td colspan="1" rowspan="1" align="left">Example: <i>+0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>P</i></td>
           <td colspan="1" rowspan="1" align="left">Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3)</td>
           <td colspan="1" rowspan="1" align="left">Example: <i>+02:00</i></td>
          </tr>

          <tr valign="middle">

           <td colspan="1" rowspan="1" align="left"><i>T</i></td>
           <td colspan="1" rowspan="1" align="left">Timezone abbreviation</td>
           <td colspan="1" rowspan="1" align="left">Examples: <i>EST</i>, <i>MDT</i> ...</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>Z</i></td>
           <td colspan="1" rowspan="1" align="left">Timezone offset in seconds. The offset for timezones west of UTC is always
           negative, and for those east of UTC is always positive.</td>
           <td colspan="1" rowspan="1" align="left"><i>-43200</i> through <i>50400</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="center"><em class="emphasis">Full Date/Time</em></td>
           <td colspan="1" rowspan="1" align="left">---</td>
           <td colspan="1" rowspan="1" align="left">---</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>c</i></td>

           <td colspan="1" rowspan="1" align="left">ISO 8601 date (added in PHP 5)</td>
           <td colspan="1" rowspan="1" align="left">2004-02-12T15:19:21+00:00</td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>r</i></td>
           <td colspan="1" rowspan="1" align="left"><a href="http://www.faqs.org/rfcs/rfc2822" class="link external">» RFC 2822</a> formatted date</td>

           <td colspan="1" rowspan="1" align="left">Example: <i>Thu, 21 Dec 2000 16:01:07 +0200</i></td>
          </tr>

          <tr valign="middle">
           <td colspan="1" rowspan="1" align="left"><i>U</i></td>
           <td colspan="1" rowspan="1" align="left">Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)</td>
           <td colspan="1" rowspan="1" align="left">See also <a href="function.time.php" class="function">time()</a></td>

          </tr>

         </tbody>
        

       </table>
  
  <h3 class="title">Examples</h3>

<table cellpadding=4 cellspacing=4 class=datos>
<th>Format String <th>Output sample
<tr>
<td>F j, Y, g:i a <td>           March 10, 2001, 5:16 pm
<tr>
<td>m.d.y         <td>           03.10.01
<tr>
<td>j, n, Y       <td>           10, 3, 2001
<tr>
<td>Ymd           <td>20010310
<tr>
<td>h-i-s, j-m-y, it is w Day z  <td>05-16-17, 10-03-01, 1631 1618 6 Fripm01
<tr>
<td>\i\t \i\s \t\h\e jS \d\a\y. <td>It is the 10th day.

<tr>
<td>
D M j G:i:s T Y					<td>Sat Mar 10 15:16:08 MST 2001

<tr>
<td>H:i:s <td>17:16:17
</table>

