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
	        			
	        						
	        			SharedPreferences pref = context.getSharedPreferences("PANDROID_DATA", mode);
	        			int a = pref.getInt("SMSReceived", defaultSMSReceived);
	        			
	        			a++;
	        			
	        			SharedPreferences.Editor editor = pref.edit();
	        			editor.putInt("SMSReceived", a);
	        			editor.commit();
	        		
	            
                    }//end if
	               
	               
	                
	        }//end onRecieve
}//end class

