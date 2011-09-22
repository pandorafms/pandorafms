package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.util.Log;

public class AlarmReceiver extends BroadcastReceiver {
	public String url;
	public String user;
	public String password;

	@Override
	public void onReceive(Context context, Intent intent) {
		// TODO Auto-generated method stub
		Log.e("OnetimeAlarmReceiver", "onReceive");
		
		checkNewEvents(context);
		
		test(context);
	}
	
	public void checkNewEvents(Context context) {
		if (this.url == null) {
	        SharedPreferences preferences = context.getSharedPreferences(
	        	context.getString(R.string.const_string_preferences), 
	        	Activity.MODE_PRIVATE);
	            
	        this.url = preferences.getString("url", "");
	        this.user = preferences.getString("user", "");
	        this.password = preferences.getString("password", "");
	        
	        Log.e("checkNewEvents", this.url + "," + this.user + "," + this.password);
		}
	}

    private static final int HELLO_ID = 1;
    public void test(Context context) {
    	String ns = Context.NOTIFICATION_SERVICE;
    	NotificationManager mNotificationManager = (NotificationManager) context.getSystemService(ns);
    	
    	
    	
    	mNotificationManager.cancel(HELLO_ID);
    	
    	
    	
    	int icon = R.drawable.pandorafms_logo;
    	CharSequence tickerText = "Hello";
    	long when = System.currentTimeMillis();

    	Notification notification = new Notification(icon, tickerText, when);
    	
    	
    	
    	notification.flags |= Notification.FLAG_AUTO_CANCEL;
    	
    	
    	
    	CharSequence contentTitle = "My notification";
    	CharSequence contentText = "Hello World!";
    	Intent notificationIntent = new Intent(context, Options.class);
    	PendingIntent contentIntent = PendingIntent.getActivity(context, 0, notificationIntent, 0);

    	notification.setLatestEventInfo(context, contentTitle, contentText, contentIntent);
    	
    	
    	
    	
    	mNotificationManager.notify(HELLO_ID, notification);
    }
}
