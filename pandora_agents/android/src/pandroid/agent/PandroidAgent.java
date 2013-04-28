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

import java.io.File;

import android.app.Activity;
import android.app.TabActivity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.os.Bundle;
import android.os.Handler;
import android.telephony.TelephonyManager;
import android.widget.TabHost;


public class PandroidAgent extends TabActivity {
	
	Handler h = new Handler();
    int defaultInterval = 300;
    TabHost tabHost;
    
   
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        
        /*
        final Dialog dialog = new Dialog(this,android.R.style.Theme_Black_NoTitleBar_Fullscreen);
		dialog.setContentView(R.layout.welcome);
		dialog.setCancelable(false);
		dialog.getWindow().setFlags(LayoutParams.FLAG_FULLSCREEN, LayoutParams.FLAG_FULLSCREEN);
        
        
        dialog.show();
         
        final Handler handler = new Handler();
        handler.postDelayed(new Runnable() {
          @Override
          public void run() {
        	  dialog.dismiss(); 
          }
        }, 3000);   	
            	
        */
        
        //Requires The agent name to use installation id
        File installation = new File(getApplicationContext().getFilesDir(), "INSTALLATION");
        if(!installation.exists()){
        	Core.restartAgentListener(getApplicationContext());
        }
        else{
        	//Core.stopAgentListener();
        	Core.loadConf(this);
        	Core.alarmEnabled = true;
        	//new Intent(this, EventReceiver.class);
        }
        
        //Check whether device has a sim card, phone without a sim card present
        //return SIM_STATE_ABSENT but tablets only return SIM_STATE_UNKNOWN
        String serviceName = Context.TELEPHONY_SERVICE;
		TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
		String hasSim = ""+(telephonyManager.getSimState() != TelephonyManager.SIM_STATE_UNKNOWN);
		if(hasSim.equals("true"))
			hasSim = ""+(telephonyManager.getSimState() != TelephonyManager.SIM_STATE_ABSENT);
		Core.hasSim = Boolean.parseBoolean(hasSim);
		
	
		//Create layout with 2 tabs
		tabHost  = getTabHost();
        
        tabHost.addTab
		(
			tabHost.newTabSpec("Status")
			.setIndicator(getString(R.string.status_str))
			.setContent(new Intent(this, Status.class))
		);
        
        tabHost.addTab
		(
			tabHost.newTabSpec("Setup")
			.setIndicator(getString(R.string.setup_str))
			.setContent(new Intent(this, Setup.class))
		);
    }
    
    public void onPause(){
    	super.onPause();
    	Core.updateConf(getApplicationContext());
    	//Core.updateDatabase(this);
    }
    
    public void onDestroy(){
    	super.onDestroy();
    	Core.updateConf(getApplicationContext());
    	//Core.updateDatabase(this);
    	
    }
    
    //Sets hello signal to 1(first connect since pandroid was closed)
    public void onResume(){
    	super.onResume();
    	
    	if(Core.helloSignal == 0)
    		Core.helloSignal = 1;
    	Core.updateConf(getApplicationContext());
    }
    
    // Called from activity to allow tab switching
    public void switchTab(int tab){
        tabHost.setCurrentTab(tab);
    }
    
}