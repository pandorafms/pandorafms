// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

package pandroid_event_viewer.pandorafms;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.Socket;
import java.net.UnknownHostException;
import java.security.KeyManagementException;
import java.security.KeyStore;
import java.security.KeyStoreException;
import java.security.NoSuchAlgorithmException;
import java.security.UnrecoverableKeyException;
import java.security.cert.CertificateException;
import java.security.cert.X509Certificate;
import java.util.ArrayList;
import java.util.List;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLContext;
import javax.net.ssl.TrustManager;
import javax.net.ssl.X509TrustManager;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.conn.scheme.Scheme;
import org.apache.http.conn.scheme.SchemeRegistry;
import org.apache.http.conn.ssl.SSLSocketFactory;
import org.apache.http.conn.ssl.X509HostnameVerifier;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.impl.conn.SingleClientConnManager;
import org.apache.http.message.BasicNameValuePair;

import android.app.TabActivity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.TabHost;

public class PandroidEventviewerActivity extends TabActivity {
	public ArrayList<EventListItem> eventList;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        final TabHost tabHost = getTabHost();
        
        test();
        
		tabHost.addTab
		(
			tabHost.newTabSpec(getResources().getString(R.string.item_tab_main_text))
			.setIndicator(getResources().getString(R.string.item_tab_main_text),
				this.getResources().getDrawable(R.drawable.house)
			)
			.setContent(new Intent(this, Main.class))
		);

		tabHost.addTab
		(
			tabHost.newTabSpec(getResources().getString(R.string.item_tab_event_list_text))
			.setIndicator(getResources().getString(R.string.item_tab_event_list_text),
				this.getResources().getDrawable(R.drawable.lightning_go)
			)
			.setContent(new Intent(this, EventList.class))
		);
		
		tabHost.getTabWidget().getChildAt(0).getLayoutParams().height=45;
		tabHost.getTabWidget().getChildAt(1).getLayoutParams().height=45;
    }
    
    public void test() {
    	try {
            DefaultHttpClient httpClient = new DefaultHttpClient();
    		
    		
    		
	    	HttpPost httpPost = new HttpPost(
	    		"http://192.168.70.112/pandora_console/include/api.php");
	    	
	    	List<NameValuePair> parameters = new ArrayList<NameValuePair>(2);
	    	parameters.add(new BasicNameValuePair("user", "admin"));
	    	parameters.add(new BasicNameValuePair("pass", "pandora"));
	    	parameters.add(new BasicNameValuePair("op", "get"));
	    	parameters.add(new BasicNameValuePair("op2", "events"));
	    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
	    	parameters.add(new BasicNameValuePair("return_type", "csv"));
	    	parameters.add(new BasicNameValuePair("other", ";||||||1315015715||10|1"));
	    	
	    	UrlEncodedFormEntity entity = new UrlEncodedFormEntity(parameters);
	    	
	    	httpPost.setEntity(entity);
	    	
	    	HttpResponse response = httpClient.execute(httpPost);
	    	HttpEntity entityResponse = response.getEntity();
	    	
	    	String str = convertStreamToString(entityResponse.getContent());
	    	Log.e("test2", str);
    	}
    	catch (Exception e) {
    		Log.e("test2", e.getMessage());
    		
    		return;
    	}
    }
    
    private String convertStreamToString (InputStream is)
    {
    	BufferedReader reader = new BufferedReader(new
    	InputStreamReader(is), 8*1024);
    	StringBuilder sb = new StringBuilder();
    	
    	String line = null;
    	try {
    		while ((line = reader.readLine()) != null) {
    			sb.append(line + "\n");
    		}
    	} catch (IOException e) {
    		e.printStackTrace();
    	} finally {
    		try {
    			is.close();
    		} catch (IOException e) {
    			e.printStackTrace();
    		}
    	}
    	
    	return sb.toString();
    }

}