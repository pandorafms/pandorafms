package pandroid.agent;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.util.Log;

public class SMSActivity {
	 private static final String TAG = "SMSActivity";
	
	 
	static public void SMSReceived(Context context){
		
    	
	int defaultSMSReceived = 0;
	int mode = Activity.MODE_PRIVATE;
	
	
	
	SharedPreferences pref = context.getSharedPreferences("SMS_DATA", mode);
	int a = pref.getInt("SMSReceived", defaultSMSReceived);
	Log.i(TAG, "sms received: " + a);
	a++;
	Log.i(TAG, "sms received: " + a);
	
	
	SharedPreferences.Editor editor = pref.edit();
	editor.putInt("SMSReceived", a);
	
	}
}
