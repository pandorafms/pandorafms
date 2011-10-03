package pandroid_event_viewer.pandorafms;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.Serializable;
import java.util.Calendar;

import android.content.Context;
import android.content.Intent;

public class Core implements Serializable {
	private static final long serialVersionUID = 7071445033114548174L;
	
	public Intent intent_service;
	//public Context context; //Fucking marshall exception
	
	public Core() {
		intent_service = null;
		//context = null; //Fucking marshall exception
	}
	
	public void startServiceEventWatcher(Context context) {
		if (intent_service == null) {
			
			intent_service = new Intent(context, PandroidEventviewerService.class);
			//this.context = context; //Fucking marshall exception
		}
		
		context.startService(intent_service);
	}
	
	public void stopServiceEventWatcher(Context context) {
		if (intent_service == null) {
			
			intent_service = new Intent(context, PandroidEventviewerService.class);
			//this.context = context; //Fucking marshall exception
		}
		
		context.stopService(this.intent_service);
	}
	
	///////////////////////
	/*
	public Core(Parcel in) {
		intent_service = (Intent)in.readValue(null);
		//context = (Context)in.readValue(null); //Fucking marshall exception
	}
	
	public static final Parcelable.Creator<Core> CREATOR
		= new Parcelable.Creator<Core>() {

			@Override
			public Core createFromParcel(Parcel source) {
				// TODO Auto-generated method stub
				return new Core(source);
			}

			@Override
			public Core[] newArray(int size) {
				// TODO Auto-generated method stub
				return new Core[size];
			}
		};

	@Override
	public int describeContents() {
		// TODO Auto-generated method stub
		return 0;
	}

	@Override
	public void writeToParcel(Parcel dest, int flags) {
		// TODO Auto-generated method stub
		dest.writeValue(this.intent_service);
		//dest.writeValue(this.context); //Fucking marshall exception
	}
	*/
	///////////
	
	
	public static String convertStreamToString (InputStream is)
    {
    	BufferedReader reader = new BufferedReader(new
    	InputStreamReader(is), 8*1024);
    	StringBuilder sb = new StringBuilder();
    	
    	String line = null;
    	try {
    		while ((line = reader.readLine()) != null) {
    			sb.append(line + "\n");
    		}
    	} catch (IOException e) {
    		e.printStackTrace();
    	} finally {
    		try {
    			is.close();
    		} catch (IOException e) {
    			e.printStackTrace();
    		}
    	}
    	
    	return sb.toString();
    }
	
	public long convertMaxTimeOldEventValuesToTimestamp(long timestamp, int arrayKey) {
		long time = timestamp;
		long return_var = 0;
		
		if (time == 0) {
			Calendar c = Calendar.getInstance();
			time = c.getTimeInMillis() / 1000;
		}
		
		switch (arrayKey) {
			case 0:
				return_var = time - 30 * 60;
				break;
			case 1:
				return_var = time - 60 * 60;
				break;
			case 2:
				return_var = time - 2 * (60 * 60);
				break;
			case 3:
				return_var = time - 3 * (60 * 60);
				break;
			case 4:
				return_var = time - 4 * (60 * 60);
				break;
			case 5:
				return_var = time - 5 * (60 * 60);
				break;
			case 6:
				return_var = time - 8 * (60 * 60);
				break;
			case 7:
				return_var = time - 10 * (60 * 60);
				break;
			case 8:
				return_var = time - 12 * (60 * 60);
				break;
			case 9:
				return_var = time - 24 * (60 * 60);
				break;
			case 10:
				return_var = time - 2 * (24 * 60 * 60);
				break;
			case 11:
				return_var = time - 3 * (24 * 60 * 60);
				break;
			case 12:
				return_var = time - 4 * (24 * 60 * 60);
				break;
			case 13:
				return_var = time - 5 * (24 * 60 * 60);
				break;
			case 14:
				return_var = time - 7 * (24 * 60 * 60);
				break;
			case 15:
				return_var = time - 2 * (7 * 24 * 60 * 60);
				break;
			case 16:
				return_var = time - 30 * (24 * 60 * 60);
				break;
		}
		
		return return_var;
	}
}
