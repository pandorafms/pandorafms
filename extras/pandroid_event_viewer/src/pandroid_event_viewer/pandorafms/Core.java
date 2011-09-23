package pandroid_event_viewer.pandorafms;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;

import android.content.Context;
import android.content.Intent;
import android.os.Parcel;
import android.os.Parcelable;

public class Core implements Parcelable{
	public Intent intent_service;
	//public Context context; //Fucking marshall exception
	
	public Core() {
		intent_service = null;
		//context = null; //Fucking marshall exception
	}
	
	public Core(Parcel in) {
		intent_service = (Intent)in.readValue(null);
		//context = (Context)in.readValue(null); //Fucking marshall exception
	}
	
	public void startServiceEventWatcher(Context context) {
		if (intent_service == null) {
			
			intent_service = new Intent(context, PandroidEventviewerService.class);
			//this.context = context; //Fucking marshall exception
		}
		
		context.startService(intent_service);
	}
	
	public void stopServiceEventWatcher(Context context) {
		context.stopService(this.intent_service);
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
}
