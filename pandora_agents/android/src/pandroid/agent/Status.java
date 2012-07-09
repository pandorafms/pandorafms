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

package pandroid.agent;

import java.util.Date;

import android.app.Activity;
import android.content.*;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

//import android.util.Log;

public class Status  extends Activity {
	Handler h = new Handler();
	public static final String LOG_TAG = "mark";
	
	/** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        if(Core.hasSim)
        	setContentView(R.layout.status);
        else
        	setContentView(R.layout.statusnosim);
        Core.loadLastValues(getApplicationContext());
        showLastValues();
        updateLastContactInfo();
        
        //connect automatically
        //Core.restartAgentListener(getApplicationContext());
        setButtonEvents();
        
        
        
    }
    
    public void onStart(){
    	super.onStart();
    	
    	// Update the UI each second 
    	h.post(new Runnable() {
        	@Override
        	public void run() {
        		Core.loadLastValues(getApplicationContext());
        		showLastValues();
        		updateLastContactInfo();
        		       		
        		h.postDelayed(this, 1000);
        	}
        });
    	//set to 1 for first contact
    }
    
    //For options
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.options_menu, menu);
        return true;
    }
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
    	Intent i;
        switch (item.getItemId()) {
            case R.id.help_button_menu_options:
            	i = new Intent(this, Help.class);
            	startActivity(i);
            	break;
            case R.id.about_button_menu_options:
            	i = new Intent(this, About.class);
            	startActivity(i);
            	break;
        }
        
        return true;
    }
    
	private void updateLastXML() {
		
		TextView xml = (TextView) this.findViewById(R.id.xml);
		
		//Core.restartAgentListener(getApplicationContext());
		
		SharedPreferences agentPreferences = getSharedPreferences(
    			getString(R.string.const_string_preferences),
    			Activity.MODE_PRIVATE);
		
		String lastXML = agentPreferences.getString("lastXML", "[no data]");
		
		xml.setText("Last XML builded: \n\n" + lastXML);
		
	}
	private void hideLastXML(){
		TextView xml = (TextView) this.findViewById(R.id.xml);
		xml.setText("");
	}
	
	private void updateLastContactInfo() {
		long lastContact = Core.lastContact;
		int contactError = Core.contactError;
		boolean alarmEnabled = Core.alarmEnabled;

        Date date = new Date();
        long timestamp = date.getTime() / 1000;
        long timeAgo = -1;
        
        //loading until error or connects
        TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
		lastContactInfo.setText(getString(R.string.loading));
        
        
        
        if(lastContact != -1){
            timeAgo = timestamp - lastContact;
        }
        
		int interval = Core.interval;

        if(timeAgo >= interval) {
        	timeAgo = 0;
        }
        
    	String stringAgo = "";
    	
    	//Check connection first
    	if(!alarmEnabled){
    		lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText(getString(R.string.contact_stopped_str));
    	}
    	
    	
    	else if(contactError == 1) {
        	lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText(getString(R.string.conctact_error_str));
        	//stopAgentListener();
        }
    	else if(lastContact == -1) {
    		stringAgo = getString(R.string.never_str);
    	}
    	else if(timeAgo == 0) {
    		stringAgo = getString(R.string.now_str);
    	}
    	else {
        	stringAgo = timeAgo + " " + getString(R.string.seconds_str);
        	lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#00FF00"));
    		lastContactInfo.setText(getString(R.string.last_contact_str) + stringAgo);
        }
    		   
	}//end updateLastContactInfo
	
	private void setButtonEvents() {
        // Set update button events
        Button start = (Button) findViewById(R.id.start);
        Button xml = (Button) findViewById(R.id.get_xml);
        Button hidexml = (Button) findViewById(R.id.hide_xml);
        Button stop = (Button) findViewById(R.id.stop);
        
        
        xml.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		updateLastXML();
        	}
        });
        
        hidexml.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		hideLastXML();
        	}
        });
        
        stop.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		Core.stopAgentListener();
        	}
        });
        
        start.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		//boolean result = Core.updateConf(getApplicationContext());
        		/*
        		if (result) {
        			Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.config_saved),
        	       		Toast.LENGTH_SHORT);
        	    	toast.show();
        		}
        		else {
        			Toast toast = Toast.makeText(getApplicationContext(),
            	       	getString(R.string.incorrect_update),
            	       	Toast.LENGTH_SHORT);
            	    	toast.show();
        		}
        		*/
        		Core.restartAgentListener(getApplicationContext());
        	}
        });
        
    }//end button events
	
	private void showLastValues() {
		
		// latitude
		TextView textView = (TextView)findViewById(R.id.latitude_value_str);
		textView.setText("");
		if (Core.latitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.latitude);
		}
		// longitude
		textView = (TextView)findViewById(R.id.longitude_value_str);
		textView.setText("");
		if (Core.longitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.longitude);
		}
		//Battery level
		textView = (TextView)findViewById(R.id.battery_value_str);
		textView.setText("");
		if (Core.batteryLevel != Core.CONST_INVALID_BATTERY_LEVEL) {
			textView.setText("" + Core.batteryLevel);
		}
		
		
		/*
		textView = (TextView)findViewById(R.id.orientation_value_str);
		textView.setText("");
		
		if (Core.orientation != Core.CONST_INVALID_ORIENTATION) {
			textView.setText("" + Core.orientation);
		}
		
		textView = (TextView)findViewById(R.id.proximity_value_str);
		textView.setText("");
		if (Core.proximity != Core.CONST_INVALID_PROXIMITY) {
			textView.setText("" + Core.proximity);
		}
		*/
		
		
		textView = (TextView)findViewById(R.id.task_value_str);
		textView.setText("");
		if (Core.taskStatus.equals("enabled") && Core.taskHumanName.length() != 0) {
			String text = Core.taskHumanName + " ( " + Core.task + " ): ";
			if (Core.taskRun.equals("true")) {
				text = text + "running";
			}
			else {
				text = text + "not running";
			}
			textView.setText(text);		
		}
		
		// freeMemory
		textView = (TextView)findViewById(R.id.memory_value_str);
		textView.setText("");
		if (Core.memoryStatus.equals("enabled")) {
			String textMemory = getString(R.string.memory_avaliable_str);
			textMemory = textMemory.replaceFirst("%i", "" + Core.availableRamKb);
			textMemory = textMemory.replaceFirst("%i", "" + Core.totalRamKb);
			textView.setText(textMemory);
		}
		// upTime
		textView = (TextView)findViewById(R.id.uptime_value);
		textView.setText("");
		if (Core.upTime != 0) {
			textView.setText("" + Core.upTime+" Seconds");
		}
		//  simID
		//Log.v(LOG_TAG, "HERE: "+Core.hasSim);
		if (Core.hasSim) {
			textView = (TextView)findViewById(R.id.sim_id_value);
			textView.setText("");
			textView.setText("" + Core.simID);
			
			// mobile operator
			textView = (TextView)findViewById(R.id.network_operator_value);
			textView.setText("");
			if (Core.networkOperator != null) {
				textView.setText("" + Core.networkOperator);
			}
			// SMSReceived
			textView = (TextView)findViewById(R.id.sms_received_value);
			textView.setText("");
			textView.setText("" + Core.SMSReceived);
		
			// SMSSent
			textView = (TextView)findViewById(R.id.sms_sent_value);
			textView.setText("");
			textView.setText("" + Core.SMSSent);
		
			// SMSReceived
			textView = (TextView)findViewById(R.id.network_type_value);
			textView.setText("");
			textView.setText("" + Core.networkType);
		
			textView = (TextView)findViewById(R.id.phone_type_value);
			textView.setText("");
			textView.setText("" + Core.phoneType);
		
			textView = (TextView)findViewById(R.id.signal_strength_value);
			textView.setText("");
			textView.setText("" + Core.signalStrength+"dB");
		
			textView = (TextView)findViewById(R.id.incoming_calls_value);
			textView.setText("");
			textView.setText("" + Core.incomingCalls);
		
			textView = (TextView)findViewById(R.id.missed_calls_value);
			textView.setText("");
			textView.setText("" + Core.missedCalls);

			textView = (TextView)findViewById(R.id.outgoing_calls_value);
			textView.setText("");
			textView.setText("" + Core.outgoingCalls);
		
			textView = (TextView)findViewById(R.id.receive_bytes_value);
			textView.setText("");
			textView.setText("" + Core.receiveBytes);
		
			textView = (TextView)findViewById(R.id.transmit_bytes_value);
			textView.setText("");
			textView.setText("" + Core.transmitBytes);
		
		}//end simID if
		
	}
}
