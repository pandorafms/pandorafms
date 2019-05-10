START TRANSACTION;

DELETE FROM `tevent_response` WHERE `name` LIKE 'Create&#x20;Integria&#x20;IMS&#x20;incident&#x20;from&#x20;event';
INSERT INTO `tnews` (`id_news`, `author`, `subject`, `text`, `timestamp`) VALUES (1,'admin','Welcome&#x20;to&#x20;Pandora&#x20;FMS&#x20;Console','&amp;lt;p&amp;gt;&#x20;&amp;lt;center&amp;gt;&amp;lt;img&#x20;src=&quot;https://pandorafms.com/wp-content/uploads/2018/04/img_colabora_con_nosotros.png&quot;&#x20;alt=&quot;img&#x20;colabora&#x20;con&#x20;nosotros&#x20;-&#x20;Support&quot;&#x20;width=&quot;191&quot;&#x20;height=&quot;207&quot;&#x20;/&amp;gt;&amp;lt;/center&amp;gt;&amp;lt;p&#x20;style=&quot;text-align:&#x20;center;&#x20;font-size:&#x20;13px;&quot;&amp;gt;Hello,&#x20;congratulations,&#x20;if&#x20;you&apos;ve&#x20;arrived&#x20;here&#x20;you&#x20;already&#x20;have&#x20;an&#x20;operational&#x20;monitoring&#x20;console.&#x20;Remember&#x20;that&#x20;our&#x20;forums&#x20;and&#x20;online&#x20;documentation&#x20;are&#x20;available&#x20;24x7&#x20;to&#x20;get&#x20;you&#x20;out&#x20;of&#x20;any&#x20;trouble.&#x20;You&#x20;can&#x20;replace&#x20;this&#x20;message&#x20;with&#x20;a&#x20;personalized&#x20;one&#x20;at&#x20;Admin&#x20;tools&#x20;-&amp;amp;gt;&#x20;Site&#x20;news.&amp;lt;/p&amp;gt;&#x20;',NOW());


COMMIT;
