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
import java.io.Serializable;
import java.util.ArrayList;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.TabActivity;
import android.content.Intent;
import android.os.Bundle;
import android.util.Log;
import android.widget.TabHost;

public class PandroidEventviewerActivity extends TabActivity implements Serializable {
	public ArrayList<EventListItem> eventList;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        this.eventList = new ArrayList<EventListItem>();
        
        final TabHost tabHost = getTabHost();
        
        
        test();
        
        
        Intent i_main = new Intent(this, Main.class);
        
		tabHost.addTab
		(
			tabHost.newTabSpec(getResources().getString(R.string.item_tab_main_text))
			.setIndicator(getResources().getString(R.string.item_tab_main_text),
				this.getResources().getDrawable(R.drawable.house)
			)
			.setContent(i_main)
		);
		
		Intent i_event_list = new Intent(this, EventList.class);
		i_event_list.putExtra("object", this);

		tabHost.addTab
		(
			tabHost.newTabSpec(getResources().getString(R.string.item_tab_event_list_text))
			.setIndicator(getResources().getString(R.string.item_tab_event_list_text),
				this.getResources().getDrawable(R.drawable.lightning_go)
			)
			.setContent(i_event_list)
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
	    	parameters.add(new BasicNameValuePair("other", ";||||||1315015715||20|1"));
	    	
	    	UrlEncodedFormEntity entity = new UrlEncodedFormEntity(parameters);
	    	
	    	httpPost.setEntity(entity);
	    	
	    	HttpResponse response = httpClient.execute(httpPost);
	    	HttpEntity entityResponse = response.getEntity();
	    	
	    	String return_api = convertStreamToString(entityResponse.getContent());
	    	
	    	String[] lines = return_api.split("\n");
	    	
	    	for (int i= 0; i < lines.length; i++) {
	    		String[] items = lines[i].split(";", 21);
	    		
	    		EventListItem event = new EventListItem();
	    		if (items[0].length() == 0) {
	    			event.id_event = 0;
	    		}
	    		else {
	    			event.id_event = Integer.parseInt(items[0]);
	    		}
	    		if (items[1].length() == 0) {
	    			event.id_agent = 0;
	    		}
	    		else {
	    			event.id_agent = Integer.parseInt(items[1]);
	    		}
	    		event.id_user = items[2];
	    		if (items[3].length() == 0) {
	    			event.id_group = 0;
	    		}
	    		else {
	    			event.id_group = Integer.parseInt(items[3]);
	    		}
	    		if (items[4].length() == 0) {
	    			event.status = 0;
	    		}
	    		else {
	    			event.status = Integer.parseInt(items[4]);
	    		}
	    		event.timestamp = items[5];
	    		event.event = items[6];
	    		if (items[7].length() == 0) {
	    			event.utimestamp = 0;
	    		}
	    		else {
	    			event.utimestamp = Integer.parseInt(items[7]);
	    		}
	    		event.event_type = items[8];
	    		if (items[9].length() == 0) {
	    			event.id_agentmodule = 0;
	    		}
	    		else {
	    			event.id_agentmodule = Integer.parseInt(items[9]);
	    		}
	    		if (items[10].length() == 0) {
	    			event.id_alert_am = 0;
	    		}
	    		else {
	    			event.id_alert_am = Integer.parseInt(items[10]);
	    		}
	    		if (items[11].length() == 0) {
	    			event.criticity = 0;
	    		}
	    		else {
	    			event.criticity = Integer.parseInt(items[11]);
	    		}
	    		event.user_comment = items[12];
	    		event.tags = items[13];
	    		event.agent_name = items[14];
	    		event.group_name = items[15];
	    		event.group_icon = items[16];
	    		event.description_event = items[17];
	    		event.description_image = items[18];
	    		event.criticity_name = items[19];
	    		event.criticity_image = items[20];
	    		
	    		this.eventList.add(event);
	    	}
    	}
    	catch (Exception e) {
    		Log.e("ERROR THE ", e.getMessage());
    		
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