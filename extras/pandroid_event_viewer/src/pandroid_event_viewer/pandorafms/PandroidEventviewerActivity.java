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
import java.util.Date;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.R.bool;
import android.app.Activity;
import android.app.TabActivity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.widget.TabHost;

public class PandroidEventviewerActivity extends TabActivity implements Serializable {
	//Data aplication
	public ArrayList<EventListItem> eventList;
	public long count_events;
	
	//Flags
	public boolean loadInProgress;
	public boolean getNewListEvents;
	
	//Configuration
	public String url;
    public String user;
    public String password;
    
    //Parameters to search in the API
    public String agentNameStr;
    public int id_group;
    public long timestamp;
    public int severity;
    public int pagination;
    public long offset;
    
    public Intent intent_service;
    
    public Core core;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        
        SharedPreferences preferences = getSharedPreferences(
        	this.getString(R.string.const_string_preferences), 
        	Activity.MODE_PRIVATE);
            
        this.url = preferences.getString("url", "");
        this.user = preferences.getString("user", "");
        this.password = preferences.getString("password", "");
        
        //this.timestamp = 1315015715;
        this.timestamp = new Date().getTime() / 1000;
        this.pagination = 20;
        this.offset = 0;
        this.agentNameStr = "";
        this.severity = -1;
        
        this.eventList = new ArrayList<EventListItem>();
        this.loadInProgress = false;
        this.getNewListEvents = true;
        
        final TabHost tabHost = getTabHost();
        
        //Check if the preferences is setted, if not show the option activity.
        if ((user.length() == 0) && (password.length() == 0)
        	&& (url.length() == 0)) {
        	
        	Intent i = new Intent(this, Options.class);
        	i.putExtra("object", this);
        	
        	startActivity(i);
        }
        else {
        	this.loadInProgress = true;
        }
        
        executeBackgroundGetEvents();
        
        //Start the background service for the notifications
        this.core = new Core();
        this.core.startServiceEventWatcher(getApplicationContext());
        
        Intent i_main = new Intent(this, Main.class);
        i_main.putExtra("object", this);
        i_main.putExtra("core", this.core);
        
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
    
    public String serializeParams2Api() {
    	String return_var = "";
    	
    	return_var += ';'; //Separator for the csv
    	return_var += "|";
    	return_var += Integer.toString(this.severity); //Criticity or severity
    	return_var += "|";
    	return_var += this.agentNameStr; //The agent name
    	return_var += "|";
    	return_var += ""; //Name of module
    	return_var += "|";
    	return_var += ""; //Name of alert template
    	return_var += "|";
    	return_var += ""; //Id user
    	return_var += "|";
    	return_var += Long.toString(this.timestamp); //The minimun timestamp
    	return_var += "|";
    	return_var += ""; //The maximum timestamp
    	return_var += "|";
    	return_var += Integer.toString(this.pagination); //The pagination of list events
    	return_var += "|";
    	return_var += Long.toString(this.offset); //The offset of list events
    	
    	return return_var;
    }
    
    public void getEvents(boolean newEvents) {
    	
    	try {
            DefaultHttpClient httpClient = new DefaultHttpClient();
            UrlEncodedFormEntity entity;
            HttpPost httpPost;
            List<NameValuePair> parameters;
            HttpResponse response;
            HttpEntity entityResponse;
            String return_api;
    		
	    	httpPost = new HttpPost(this.url);
	    	
	    	//Get total count.
	    	parameters = new ArrayList<NameValuePair>();
	    	parameters.add(new BasicNameValuePair("user", this.user));
	    	parameters.add(new BasicNameValuePair("pass", this.password));
	    	parameters.add(new BasicNameValuePair("op", "get"));
	    	parameters.add(new BasicNameValuePair("op2", "events"));
	    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
	    	parameters.add(new BasicNameValuePair("return_type", "csv"));
	    	parameters.add(new BasicNameValuePair("other", serializeParams2Api() + "|total"));
	    	entity = new UrlEncodedFormEntity(parameters);
	    	httpPost.setEntity(entity);
	    	response = httpClient.execute(httpPost);
	    	entityResponse = response.getEntity();
	    	return_api = Core.convertStreamToString(entityResponse.getContent());
	    	return_api = return_api.replace("\n", "");
	    	this.count_events = new Long(return_api).longValue();
	    	
	    	if (this.count_events == 0) {
	    		return;
	    	}
	    	
	    	//Get the list of events.
	    	parameters = new ArrayList<NameValuePair>();
	    	parameters.add(new BasicNameValuePair("user", this.user));
	    	parameters.add(new BasicNameValuePair("pass", this.password));
	    	parameters.add(new BasicNameValuePair("op", "get"));
	    	parameters.add(new BasicNameValuePair("op2", "events"));
	    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
	    	parameters.add(new BasicNameValuePair("return_type", "csv"));
	    	parameters.add(new BasicNameValuePair("other", serializeParams2Api()));
	    	entity = new UrlEncodedFormEntity(parameters);
	    	httpPost.setEntity(entity);
	    	response = httpClient.execute(httpPost);
	    	entityResponse = response.getEntity();
	    	
	    	return_api = Core.convertStreamToString(entityResponse.getContent());
	    	Log.e("return_api", return_api);
	    	
	    	String[] lines = return_api.split("\n");
	    	
	    	if (return_api.length() == 0) {
	    		return;
	    	}
	    	
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
	    		
	    		event.opened = false;
	    		
	    		this.eventList.add(event);
	    	}
    	}
    	catch (Exception e) {
    		Log.e("ERROR THE ", e.getMessage());
    		
    		return;
    	}
    }
    
    public void executeBackgroundGetEvents() {
    	new GetEventsAsyncTask().execute();
    }

    
    public class GetEventsAsyncTask extends AsyncTask<Void, Void, Void> {

		@Override
		protected Void doInBackground(Void... params) {
			
			if (getNewListEvents) {
				getEvents(true);
			}
			else {
				getEvents(false);
			}
			
			return null;
		}
		
		@Override
		protected void onPostExecute(Void unused)
		{
			Intent i = new Intent("eventlist.java");
			
			if (getNewListEvents) {
				loadInProgress = false;
				getNewListEvents = false;
				
				i.putExtra("load_more", 0);			
			}
			else {
				i.putExtra("load_more", 1);
			}
			
			getApplicationContext().sendBroadcast(i);	
		}
    }
}