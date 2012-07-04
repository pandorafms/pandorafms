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

//import java.util.Date;

//import android.app.Activity;
//import android.app.AlarmManager;
//import android.app.PendingIntent;
import android.app.TabActivity;
//import android.content.ComponentName;
//import android.content.Context;
import android.content.Context;
import android.content.Intent;
//import android.content.SharedPreferences;
//import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
//import android.telephony.TelephonyManager;
//import android.view.KeyEvent;
//import android.view.View;
//import android.view.View.OnClickListener;
//import android.view.View.OnKeyListener;
//import android.view.inputmethod.InputMethodManager;
//import android.widget.Button;
//import android.widget.CheckBox;
//import android.widget.EditText;
import android.telephony.TelephonyManager;
import android.widget.TabHost;
//import android.widget.TextView;
import android.util.Log;

public class PandroidAgent extends TabActivity {
	
	public static final String LOG_TAG = "mark";
//public class PandroidAgent extends Activity {
    Handler h = new Handler();
    
    int defaultInterval = 300; //important
    /*
    String defaultServerPort = "41121";
    String defaultServerAddr = "farscape.artica.es";
    String defaultAgentName = "pandroid";
    String defaultGpsStatus = "disabled"; // "disabled" or "enabled"
    */
    //boolean alarmEnabled;
    
    //boolean showLastXML = true;
    
    //String lastGpsContactDateTime = "";
    
    Thread thread = new Thread();
    //ComponentName service = null;
    //PendingIntent sender = null;
    //AlarmManager am = null;
    
    TabHost tabHost;
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        //if removed, battery -1 and agent reverts to defaults in core
        Core.restartAgentListener(getApplicationContext());
        String serviceName = Context.TELEPHONY_SERVICE;
		TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
		String hasSim = ""+(telephonyManager.getSimState() != TelephonyManager.SIM_STATE_UNKNOWN);
		Core.hasSim = Boolean.parseBoolean(hasSim);
		Log.v(LOG_TAG, "HERE: "+Core.hasSim);
        
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
        
        //tabHost.getTabWidget().getChildAt(0).getLayoutParams();
		//tabHost.getTabWidget().getChildAt(1).getLayoutParams();
        
    }
   public void switchTab(int tab){
        tabHost.setCurrentTab(tab);
    }	
}