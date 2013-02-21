/*
Pandora FMS - http://pandorafms.com

==================================================
Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
Please see http://pandorafms.org for full contribution list

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation; version 2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
*/

/**
 * Activity of main view.
 * 
 * @author Miguel de Dios Matías
 * 
 */

package pandorafms.pandorafmsandroidconsole;

import pandorafms.pandorafmsandroidconsole.R;
import pandorafms.pandorafmsandroidconsole.Help;

import android.os.Bundle;
import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;
import android.view.Menu;
import android.view.MenuItem;
import android.view.View;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.webkit.SslErrorHandler;
import android.widget.Toast;
import android.net.http.SslError;

public class PandoraWebView extends Activity {
	
	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		Intent i;
		
		setContentView(R.layout.activity_web_view);
		
		SharedPreferences preferences = getSharedPreferences(
			getString(R.string.const_string_preferences),
			Activity.MODE_PRIVATE);
		
		String url_pandora = preferences.getString("url_pandora", "");
		String user = preferences.getString("user", "");
		String password = preferences.getString("password", "");
		
		WebView myWebView = (WebView) findViewById(R.id.webview);
		WebSettings webSettings = myWebView.getSettings();
		webSettings.setJavaScriptEnabled(true);
		
		myWebView.setWebViewClient(new WebViewClient(){
			
			@Override
			public void onReceivedSslError(WebView view, SslErrorHandler handler, SslError error) {
				handler.proceed(); // Ignore SSL certificate errors
			}
			
			public void onPageFinished(WebView view, String url) {
				//Check the first load the page for to hide a toast with the
				//connection message
				
				//Close the CustomToast (I love this hack, fuck javalovers and yours patterns.).
				ConnectionCustomToast connectionCustomToast = new ConnectionCustomToast();
				if (connectionCustomToast.activity != null)
					connectionCustomToast.activity.finish();
			}
		});
		
		//Check if not empty the data of connection
		if (url_pandora.equals("")) {
			//Show the config dialog for to set a URL (normally the first execution)
			i = new Intent(this, Options.class);
			startActivityForResult(i, 666);
		}
		else {
			//myWebView.loadUrl("http://192.168.10.14/test.php");
			myWebView.loadUrl(url_pandora + "/index.php?action=login&password=" + password + "&user=" + user);
			Log.e("URL", url_pandora + "/index.php?action=login&password=" + password + "&user=" + user);
			
			i = new Intent(this, ConnectionCustomToast.class);
			startActivity(i);
		}
		
	}
	
	
	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		// Inflate the menu; this adds items to the action bar if it is present.
		getMenuInflater().inflate(R.menu.web_view, menu);
		return true;
	}
	
	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		Intent i;
		switch (item.getItemId()) {
			case R.id.options_button_menu_options:
				i = new Intent(this, Options.class);
				startActivityForResult(i, 666);
				break;
			case R.id.help_button_menu_options:
				i = new Intent(this, Help.class);
				startActivity(i);
				break;
			case R.id.refresh_button_menu_options:
				WebView myWebView = (WebView) findViewById(R.id.webview);
				
				myWebView.reload();
				break;
			case R.id.exit_button_menu_options:
				finish();
				break;
		}
		
		return true;
	}
	
	@Override
	public void onActivityResult(int requestCode, int resultCode, Intent data) {
		super.onActivityResult(requestCode, resultCode, data);
		
		switch(resultCode) {
			case 666:
				SharedPreferences preferences = getSharedPreferences(
					getString(R.string.const_string_preferences),
					Activity.MODE_PRIVATE);
				
				String url_pandora = preferences.getString("url_pandora", "");
				String user = preferences.getString("user", "");
				String password = preferences.getString("password", "");
				
				WebView myWebView = (WebView) findViewById(R.id.webview);
				myWebView.loadUrl(url_pandora + "/index.php?action=login&password=" + password + "&user=" + user);
				Log.e("URL", url_pandora + "/index.php?action=login&password=" + password + "&user=" + user);
				
				Intent i = new Intent(this, ConnectionCustomToast.class);
				startActivity(i);
				break;
		}
	}
}
