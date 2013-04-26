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


import android.app.Activity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;

	public class SMSBroadcastReceiver extends BroadcastReceiver {

	        private static final String SMS_RECEIVED = "android.provider.Telephony.SMS_RECEIVED";
	        private static final String TAG = "SMSBroadcastReceiver";

	        @Override
	        public void onReceive(Context context, Intent intent) {
	             Log.i(TAG, "Intent recieved: " + intent.getAction());

	                if (intent.getAction().equals(SMS_RECEIVED)) {
	    
	        			int defaultSMSReceived = 0;
	        			int mode = Activity.MODE_PRIVATE;
	        			
	        			SharedPreferences pref = PandroidAgent.getSharedPrefs();
	        			int sms = pref.getInt("SMSReceived", defaultSMSReceived);
	        			
	        			sms++;
	        			
	        			SharedPreferences.Editor editor = pref.edit();
	        			editor.putInt("SMSReceived", sms);
	        			editor.commit();
	        		
	                }//end if
       
	        }//end onRecieve
}//end class

