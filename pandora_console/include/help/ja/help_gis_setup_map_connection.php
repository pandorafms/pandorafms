<?php
/**
 * @package Include/help/ja
 */
?>
<h1>GIS 利用マップ設定</h1>

<p>
このページは、管理者が<strong>利用する GIS マップサーバ</strong>を設定するためのものです。
</p>

<h2>マップの種類</h2>
<p>
現在、<?php echo get_product_name(); ?> は、OpenStreetマップ、Google マップ、静的画像の 3種類のマップをサポートしています。
</p>
<h3>Open Street マップ</h3>
<p>
Open Street マップを利用するためには、以下に示す Open Street マップサーバにアクセスできる必要があります。
(詳細は、<a href="http://wiki.openstreetmap.org/wiki/ja:Main_Page">http://wiki.openstreetmap.org/wiki/ja:Main_Page</a> を参照してください。また、<a href="http://wiki.openstreetmap.org/wiki/ja:Mapnik">http://wiki.openstreetmap.org/wiki/ja:Mapnik</a> にサンプルがあります。)
</p>
<pre>
http://tile.openstreetmap.org/${z}/${x}/${y}.png
</pre>
<p>
OpenStreetマップの利用は、<a href="http://wiki.openstreetmap.org/wiki/Licence">ライセンス</a>に従っています。
</p>
<h3>Google マップ</h3>
<p>
最初に Google MAPS API に登録し、フリーの API キーを取得する必要があります。
これに関しては、以下を参照してください。<br/>
<a href="http://code.google.com/intl/ja/apis/maps/signup.html">http://code.google.com/intl/ja/apis/maps/signup.html</a></p>
<p>Google API キーは次のようになっています。</p>
<pre>
ABQIAAAAZuJY-VSG4gOH73b6mcUw1hTfSvFQRXGUGjHx8f036YCF-UKjgxT9lUhqOJx7KDHSnFnt46qnj89SOQ
</pre>
<h3>静的画像</h3>
<p>
一つの画像ソースとして、静的画像ファイル(PNG など)を利用することも可能です。
これを利用するには、<strong>URL</strong>、画像の<strong>位置情報</strong>、そして、<strong>高さ</strong>と<strong>幅</strong>を設定する必要があります。
</p>
