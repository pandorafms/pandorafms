package pandroid.agent;


import android.content.BroadcastReceiver;

	import android.content.Context;
	import android.content.Intent;
	import android.util.Log;

	public class SMSBroadcastReceiver extends BroadcastReceiver {

	        private static final String SMS_RECEIVED = "android.provider.Telephony.SMS_RECEIVED";
	        private static final String TAG = "SMSBroadcastReceiver";

	        @Override
	        public void onReceive(Context context, Intent intent) {
	             Log.i(TAG, "Intent recieved: " + intent.getAction());

	                //if (intent.getAction() == "android.provider.Telephony.SMS_RECEIVED") {
	                   //sms recieved
	                	//Intent i = new Intent(context, SMSActivity.class);
	                	//i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
                       // context.startActivity(i);
	          //   SMSActivity.SMSReceived(this);
                        
	          // }//end if
	        }//end onRecieve
}//end class

