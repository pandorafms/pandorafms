package pandroid_event_viewer.pandorafms;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.util.Log;

/**
 * This receiver will start the service on phone boot.
 * 
 * @author Santiago Munín González
 * 
 */
public class OnBootLoader extends BroadcastReceiver {
	private static String TAG = "OnBootLoader";

	@Override
	public void onReceive(Context context, Intent intent) {
		Log.i(TAG, "onReceive: starting service");
		Core.setFetchFrequency(context);
	}

}
