package pandroid.agent;

import java.util.Date;
import java.util.List;

import android.app.Activity;
import android.app.Service;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Bundle;
import android.os.IBinder;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;

import android.os.Handler;

import java.io.IOException;
import java.io.OutputStreamWriter;
import java.io.FileOutputStream;
import java.util.Calendar;

public class PandroidAgentListener extends Service {
    Handler h = new Handler();

    int defaultInterval = 300;
    String defaultServerPort = "41121";
    String defaultServerAddr = "10.0.2.2";
    String defaultAgentName = "pandroidAgent";
    String defaultGpsStatus = "disabled"; // "disabled" or "enabled"
    String lastGpsContactDateTime = "";

    boolean showLastXML = true;
    
	@Override
	public void onCreate() {
	}
	
	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		updateValues();
		contact();
		stopSelf(startId);
	    return START_NOT_STICKY;
	}

	@Override
	public IBinder onBind(Intent intent) {
		return null;
	}
	
	private void contact(){
        Date date = new Date();
        
    	putSharedData("PANDROID_DATA", "contactError", "0", "integer");
        putSharedData("PANDROID_DATA", "lastContact", Long.toString(date.getTime() / 1000), "long");
        
        // Keep lastXML sended if is not empty (empty means error sending it)
        String lastXML = buildXML();
        
		String agentName = getSharedData("PANDROID_DATA", "agentName", defaultAgentName, "string");

		String destFileName = agentName + "." + System.currentTimeMillis() + ".data";
		
		writeFile(destFileName, lastXML);

		String[] tentacleData = {
				  "-a",
				  getSharedData("PANDROID_DATA", "serverAddr", "", "string"),
				  "-p",
				  defaultServerPort,
				  "-v",
				  "/data/data/pandroid.agent/files/" + destFileName
	    		  };

		int tentacleRet = new tentacle_client().tentacle_client(tentacleData);
		//int tentacleRet = 0;
		
        if(tentacleRet == 0) {
            putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
        }
        else {
        	putSharedData("PANDROID_DATA", "contactError", "1", "integer");
        }
	}
	
	private String buildXML(){
		String buffer = "";
		String gpsData = "";
		buffer += "<?xml version='1.0' encoding='utf-8'?>\n";
		
		String latitude = getSharedData("PANDROID_DATA", "latitude", "181", "float");
		String longitude = getSharedData("PANDROID_DATA", "longitude", "181", "float");

		if(!latitude.equals("181.0") && !longitude.equals("181.0")) {
			gpsData = " latitude='" + latitude + "' longitude='" + longitude + "'";
		}
		
		String agentName = getSharedData("PANDROID_DATA", "agentName", defaultAgentName, "string");
		String interval = getSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer");
		
		buffer += "<agent_data description='' group='' os_name='android' os_version='2.1' interval='"+ interval +"' version='3.2RC1(Build 101103)' timestamp='" + getHumanDateTime(-1) + "' agent_name='" + agentName + "' timezone_offset='0'" + gpsData +">\n";
		
		// Modules
		buffer += buildmoduleXML("battery_level", "The actually device battery level", "generic_data", getSharedData("PANDROID_DATA", "batteryLevel", "-1", "integer"));		
		//buffer += buildmoduleXML("last_gps_contact", "Datetime of the last geo-location contact", "generic_data", lastGpsContactDateTime);
		
		// End_Modules
		
		buffer += "</agent_data>";
		
		return buffer;
	}
	
    private void writeFile(String fileName, String textToWrite) {
    	try { // catches IOException below
    	                        FileOutputStream fOut = openFileOutput(fileName,
    	                                                                MODE_WORLD_READABLE);
    	                        OutputStreamWriter osw = new OutputStreamWriter(fOut); 
    	 
    	                        // Write the string to the file
    	                        osw.write(textToWrite);
    	                        /* ensure that everything is
    	                         * really written out and close */
    	                        osw.flush();
    	                        osw.close();
    	} catch (IOException e) {

    	}

    }
    
	private String buildmoduleXML(String name, String description, String type, String data){
		String buffer = "";
		buffer += "  <module>\n";
		buffer += "    <name><![CDATA[" + name + "]]></name>\n";
		buffer += "    <description><![CDATA[" + description + "]]></description>\n";
		buffer += "    <type><![CDATA[" + type + "]]></type>\n";
		buffer += "    <data><![CDATA[" + data + "]]></data>\n";
		buffer += "  </module>\n";
		
		return buffer;
	}
	
    private void gpsLocation() {
    	// Starts with GPS, if no GPS then gets network location
    	
		LocationManager lm = (LocationManager) getSystemService(Context.LOCATION_SERVICE);  
		List<String> providers = lm.getProviders(true);

		/* Loop over the array backwards, and if you get an accurate location, then break out the loop*/
		Location loc = null;

		for (int i=providers.size()-1; i>=0; i--) {
		    loc = lm.getLastKnownLocation(providers.get(i));
		    if (loc != null) break;
		}

		if (loc != null) {
			//if(latitude != loc.getLatitude() || longitude != loc.getLongitude()) {
				lastGpsContactDateTime = getHumanDateTime(-1);
			//}
            putSharedData("PANDROID_DATA", "latitude", new Double(loc.getLatitude()).toString(), "float");
            putSharedData("PANDROID_DATA", "longitude", new Double(loc.getLongitude()).toString(), "float");
		}
		else {             
            putSharedData("PANDROID_DATA", "latitude", "181", "float");
            putSharedData("PANDROID_DATA", "longitude", "181", "float");
		}
		
    }
    
    private void batteryLevel() {
        BroadcastReceiver batteryLevelReceiver = new BroadcastReceiver() {
            public void onReceive(Context context, Intent intent) {
                context.unregisterReceiver(this);
                int rawlevel = intent.getIntExtra("level", -1);
                int scale = intent.getIntExtra("scale", -1);
                if (rawlevel >= 0 && scale > 0) {
                    putSharedData("PANDROID_DATA", "batteryLevel", new Integer((rawlevel * 100) / scale).toString(), "integer");
                }
            }
        };
        IntentFilter batteryLevelFilter = new IntentFilter(Intent.ACTION_BATTERY_CHANGED);
        registerReceiver(batteryLevelReceiver, batteryLevelFilter);
    }

	private void updateValues() {
        batteryLevel();
        String gpsStatus = getSharedData("PANDROID_DATA", "gpsStatus", defaultGpsStatus, "string");
        
        if(gpsStatus.equals("enabled")) {
			gpsLocation();
        }
        else {
            putSharedData("PANDROID_DATA", "latitude", "181.0", "float");
            putSharedData("PANDROID_DATA", "longitude", "181.0", "float");
        }
	}
	
    private void putSharedData(String preferenceName, String tokenName, String data, String type) {
		int mode = Activity.MODE_PRIVATE;
		SharedPreferences agentPreferences = getSharedPreferences(preferenceName, mode);
		SharedPreferences.Editor editor = agentPreferences.edit();
		
		if(type == "boolean") {
			editor.putBoolean(tokenName, Boolean.parseBoolean(data));
		}
		else if(type == "float") {
			editor.putFloat(tokenName, Float.parseFloat(data));
		}
		else if(type == "integer") {
			editor.putInt(tokenName, Integer.parseInt(data));
		}
		else if(type == "long") {
			editor.putLong(tokenName, Long.parseLong(data));
		}
		else if(type == "string") {
			editor.putString(tokenName, data);
		}
		
		editor.commit();
    }
    
    private String getSharedData(String preferenceName, String tokenName, String defaultValue, String type) {
		int mode = Activity.MODE_PRIVATE;
		SharedPreferences agentPreferences = getSharedPreferences(preferenceName, mode);
		
		if(type == "boolean") {
			boolean a = agentPreferences.getBoolean(tokenName, Boolean.parseBoolean(defaultValue));
			return new Boolean(a).toString();
		}
		else if(type == "float") {
			float a = agentPreferences.getFloat(tokenName, Float.parseFloat(defaultValue));
			return new Float(a).toString();
		}
		else if(type == "integer") {
			int a = agentPreferences.getInt(tokenName, Integer.parseInt(defaultValue));
			return new Integer(a).toString();
		}
		else if(type == "long") {
			long a = agentPreferences.getLong(tokenName, Long.parseLong(defaultValue));
			return new Long(a).toString();
		}
		else if(type == "string") {
			return agentPreferences.getString(tokenName, defaultValue);
		}
		
		return "";
    }
	
    private String getHumanDateTime(long unixtime){
        Calendar dateTime = Calendar.getInstance();
        if(unixtime != -1) {
        	dateTime.setTimeInMillis(unixtime);
        }
        String humanDateTime;
        
        humanDateTime = dateTime.get(Calendar.YEAR) + "/";
        
        int month = dateTime.get(Calendar.MONTH) + 1;
        if(month < 10) {
        	humanDateTime += "0";
        }
    	humanDateTime += month + "/";

    	int day = dateTime.get(Calendar.DAY_OF_MONTH);
        if(day < 10) {
        	humanDateTime += "0";
        }
    	humanDateTime += day + " ";
    	
    	int hour = dateTime.get(Calendar.HOUR_OF_DAY);
        if(hour < 10) {
        	humanDateTime += "0";
        }
    	humanDateTime += hour + ":";
    	
    	int minute = dateTime.get(Calendar.MINUTE);
        if(minute < 10) {
        	humanDateTime += "0";
        }
    	humanDateTime += minute + ":";
        
    	int second = dateTime.get(Calendar.SECOND);
        if(second < 10) {
        	humanDateTime += "0";
        }
    	humanDateTime += second;
    	
        return humanDateTime;
    }
    
    ///////////////////////////////////////////
    // Getting values from device functions
    ///////////////////////////////////////////
    
    public class MyLocationListener implements LocationListener {

	    @Override
	    public void onLocationChanged(Location loc) {
            putSharedData("PANDROID_DATA", "latitude", new Double(loc.getLatitude()).toString(), "float");
            putSharedData("PANDROID_DATA", "longitude", new Double(loc.getLongitude()).toString(), "float");
	    }
	    
	    @Override	
	    public void onProviderDisabled(String provider) {
	    }
	
	    @Override
	    public void onProviderEnabled(String provider) {
	    }
	
	    @Override
	
	    public void onStatusChanged(String provider, int status, Bundle extras) {
	    }

    }/* End of Class MyLocationListener */
}
