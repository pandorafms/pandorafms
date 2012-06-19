package pandroid.agent;

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

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStreamWriter;
import java.io.RandomAccessFile;
import java.text.DecimalFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.List;

import org.apache.commons.lang3.StringEscapeUtils;

import android.app.Activity;
import android.app.ActivityManager;
import android.app.ActivityManager.MemoryInfo;
//import android.app.ActivityManager.RunningAppProcessInfo;
import android.app.Service;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
//import android.content.pm.PackageManager;
//import android.hardware.Sensor;
//import android.hardware.SensorEvent;
//import android.hardware.SensorEventListener;
//import android.hardware.SensorManager;
import android.location.Criteria;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.PowerManager;
import android.os.PowerManager.WakeLock;
import android.os.SystemClock;
import android.telephony.ServiceState;
import android.telephony.TelephonyManager;
import android.util.Log;

public class PandroidAgentListener extends Service {
	
	public static final String LOG_TAG = "mark";
    Handler h = new Handler();

    int defaultInterval = 300;
    String defaultServerPort = "41121";
    String defaultServerAddr = "10.0.2.2";
    String defaultAgentName = "pandroidAgent";
    String defaultGpsStatus = "disabled"; // "disabled" or "enabled"
    String defaultTaskStatus = "disabled"; // "disabled" or "enabled"
    String defaultMemoryStatus = "disabled"; // "disabled" or "enabled"
    String defaultTask = "";
    String defaultTaskHumanName = "";
    String lastGpsContactDateTime = "";
    
    String osversion = "";
    String defaultSimID = "";
    String defaultUpTime = "0";
    String defaultNetworkOperator = "";
    String defaultSMSReceived = "5";

    boolean showLastXML = true;
    
	@Override
	public void onCreate() {
	}
	
	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		PowerManager pm = (PowerManager) getSystemService(Context.POWER_SERVICE);
        WakeLock wakeLock = pm.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "MyWakeLock");
        wakeLock.acquire();
		updateValues();
		contact();
		wakeLock.release();
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
    	
		// Deleting the file after send it
		File file = new File("/data/data/pandroid.agent/files/" + destFileName);
    	file.delete();
		
        if(tentacleRet == 0) {
            putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
        }
        else {
        	putSharedData("PANDROID_DATA", "contactError", "1", "integer");
        }
        
        updateValues();
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
		

		
		
		buffer += "<agent_data " +
			"description='' group='' os_name='android' os_version='"+Build.VERSION.RELEASE+"' " +		//change to read real version of os
			"interval='"+ interval +"' version='4.0(Build 111012)' " + 
			"timestamp='" + getHumanDateTime(-1) + "' agent_name='" + agentName + "' " +
			"timezone_offset='0'" + gpsData +">\n";
		
		
		
		// Modules
		String orientation = getSharedData("PANDROID_DATA", "orientation", "361", "float");
		String proximity = getSharedData("PANDROID_DATA", "proximity", "-1.0", "float");
		String batteryLevel = getSharedData("PANDROID_DATA", "batteryLevel", "-1", "integer");
		//String taskStatus = getSharedData("PANDROID_DATA", "taskStatus", "disabled", "string");
		//String taskRun = getSharedData("PANDROID_DATA", "taskRun", "false", "string");
		String taskHumanName = getSharedData("PANDROID_DATA", "taskHumanName", "", "string");
		taskHumanName = StringEscapeUtils.escapeHtml4(taskHumanName);
		
		//String task = getSharedData("PANDROID_DATA", "task", "", "string");
		String memoryStatus = getSharedData("PANDROID_DATA", "memoryStatus", defaultMemoryStatus, "string");
		String availableRamKb = getSharedData("PANDROID_DATA", "availableRamKb", "0" , "long");
		String totalRamKb = getSharedData("PANDROID_DATA", "totalRamKb", "0", "long");
		String sim_ID = getSharedData("PANDROID_DATA", "simID", defaultSimID, "string");
		String upTime = getSharedData("PANDROID_DATA", "upTime", defaultUpTime, "long");
		String networkOperator  = getSharedData("PANDROID_DATA", "networkOperator", defaultNetworkOperator, "string");
		//String SMSReceived = getSharedData("PANDROID_DATA", "SMSReceived", defaultSMSReceived, "integer");
		
		buffer += buildmoduleXML("battery_level", "The current Battery level", "generic_data", batteryLevel);	
		
		if(!orientation.equals("361.0")) {
			buffer += buildmoduleXML("orientation", "The actually device orientation (in degrees)", "generic_data", orientation);		
		}
		
		if(!proximity.equals("-1.0")) {
			buffer += buildmoduleXML("proximity", "The actually device proximity detector (0/1)", "generic_data", proximity);		
		}
		
		/*
		if (taskStatus.equals("enabled")) {
			buffer += buildmoduleXML("taskHumanName", "The task's human name.", "async_string", taskHumanName);
			buffer += buildmoduleXML("task", "The task's package name.", "async_string", task);
			if (taskRun.equals("true")) {
				buffer += buildmoduleXML("taskRun", "The task is running.", "async_proc", "1");
			}
			else {
				buffer += buildmoduleXML("taskRun", "The task is running.", "async_proc", "0");
			}
		}
		*/
		
		if (memoryStatus.equals("enabled")) {
			
			Float freeMemory = new Float((Float.valueOf(availableRamKb) / Float.valueOf(totalRamKb)) * 100.0);
			
			DecimalFormat formatPercent = new DecimalFormat("#.##");
			buffer += buildmoduleXML("freeRamMemory", "The percentage of available ram.", "generic_data",
				formatPercent.format(freeMemory.doubleValue()));
		}
		//buffer += buildmoduleXML("last_gps_contact", "Datetime of the last geo-location contact", "generic_data", lastGpsContactDateTime);
		
		buffer += buildmoduleXML("simID", "The Sim ID.", "generic_data_string", sim_ID);
		buffer += buildmoduleXML("upTime","Total device uptime in seconds.", "generic_data", upTime);
		buffer += buildmoduleXML("networkOperator","Currently registered network operator", "generic_data_string", networkOperator);
		//buffer += buildmoduleXML("SMSRecieved","Number of SMS recieved", "generic_data", SMSReceived);
		// End_Modules
		
		buffer += "</agent_data>";
		//Log.v("mark",buffer);
		return buffer;
	}
	
    private void writeFile(String fileName, String textToWrite) {
    	try { // catches IOException below
    		FileOutputStream fOut = openFileOutput(fileName, MODE_WORLD_READABLE);
    		OutputStreamWriter osw = new OutputStreamWriter(fOut); 
    		
    		// Write the string to the file
    		osw.write(textToWrite);
    		/* ensure that everything is really written out and close */
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
		Log.e("PANDROID providers count", "" + providers.size());

		/* Loop over the array backwards, and if you get an accurate location, then break out the loop*/
		Location loc = null;

		for (int i=providers.size()-1; i>=0; i--) {
			Log.e("PANDROID providers", providers.get(i));
		    loc = lm.getLastKnownLocation(providers.get(i));
		    if (loc != null) break;
		}

		if (loc != null) {
			Log.e("PANDROID", "loc != null");
			//if(latitude != loc.getLatitude() || longitude != loc.getLongitude()) {
				lastGpsContactDateTime = getHumanDateTime(-1);
			//}
            putSharedData("PANDROID_DATA", "latitude", new Double(loc.getLatitude()).toString(), "float");
            putSharedData("PANDROID_DATA", "longitude", new Double(loc.getLongitude()).toString(), "float");
		}
		else {
			Criteria criteria = new Criteria();
			criteria.setAccuracy(Criteria.ACCURACY_COARSE);
			criteria.setPowerRequirement(Criteria.POWER_LOW);
			criteria.setAltitudeRequired(false);
			criteria.setBearingRequired(false);
			criteria.setCostAllowed(true);
			String bestProvider = lm.getBestProvider(criteria, true);
			
			lm.requestLocationUpdates(bestProvider, defaultInterval, 1000,
				new LocationListener() {
					public void onLocationChanged(Location location) {
						putSharedData("PANDROID_DATA", "latitude",
							new Double(location.getLatitude()).toString(), "float");
				        putSharedData("PANDROID_DATA", "longitude",
				        	new Double(location.getLongitude()).toString(), "float");
					}
					public void onStatusChanged(String s, int i, Bundle bundle) {
						
					}
					public void onProviderEnabled(String s) {
						// try switching to a different provider
					}
					public void onProviderDisabled(String s) {
						putSharedData("PANDROID_DATA", "enabled_location_provider",
							"disabled", "string");
					}
				});
		}
		
    }
    
    private void batteryLevel() {
        BroadcastReceiver batteryLevelReceiver = new BroadcastReceiver() {
            public void onReceive(Context context, Intent intent) {
            	try {
            		context.unregisterReceiver(this);
            	}
            	catch (IllegalArgumentException e) {
            		//None
            	}
                
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
    
    /*private void sensors() {
    	// Sensor listeners
    	
        SensorEventListener orientationLevelReceiver = new SensorEventListener() {
            public void onSensorChanged(SensorEvent sensorEvent) {
                putSharedData("PANDROID_DATA", "orientation", Float.toString(sensorEvent.values[0]), "float");
            }
            public void onAccuracyChanged(Sensor sensor, int accuracy) {
            }
        };
        
        SensorEventListener proximityLevelReceiver = new SensorEventListener() {
            public void onSensorChanged(SensorEvent sensorEvent) {
                putSharedData("PANDROID_DATA", "proximity", Float.toString(sensorEvent.values[0]), "float");
            }
            public void onAccuracyChanged(Sensor sensor, int accuracy) {
            }
        };
        
        // Sensor management
        
    	SensorManager sensorManager = (SensorManager)getSystemService(SENSOR_SERVICE);  

        sensorManager = 
            (SensorManager)getSystemService( SENSOR_SERVICE  );
        List<Sensor> sensors = sensorManager.getSensorList( Sensor.TYPE_ALL );
        Sensor proxSensor = null;
        Sensor orientSensor = null;
        
        for( int i = 0 ; i < sensors.size() ; ++i ) {
        	switch(sensors.get( i ).getType()) {
	    		case Sensor.TYPE_ORIENTATION:
	                orientSensor = sensors.get( i );
	                break;
	    		case Sensor.TYPE_PROXIMITY:
	                proxSensor = sensors.get( i );
	                break;
        	}
        }
        
        if( orientSensor != null ) {
                sensorManager.registerListener( 
                        orientationLevelReceiver, 
                        orientSensor,
                        (20));
                        //SensorManager.SENSOR_DELAY_UI );
        }
        
        if( proxSensor != null ) {
            sensorManager.registerListener( 
                    proximityLevelReceiver, 
                    proxSensor,
                    //(defaultInterval * 1000000));
                    (20));
                    //SensorManager.SENSOR_DELAY_UI );
        }
    }//end sensors
    */
	private void updateValues() {
        batteryLevel();
        String gpsStatus = getSharedData("PANDROID_DATA", "gpsStatus", defaultGpsStatus, "string");
        
        if(gpsStatus.equals("enabled")) {
        	Log.e("PANDROID AGENT", "ENABLED");
			gpsLocation();
        }
        else {
        	Log.e("PANDROID AGENT", "DISABLED");
            putSharedData("PANDROID_DATA", "latitude", "181.0", "float");
            putSharedData("PANDROID_DATA", "longitude", "181.0", "float");
        }
        
        //sensors();
        //getTaskStatus();
        getMemoryStatus();
        getSimID();
        getUpTime();
        getNetworkOperator();
        //getSMSReceived();
	}
	
	private void getMemoryStatus() {
		String memoryStatus = getSharedData("PANDROID_DATA", "memoryStatus", defaultMemoryStatus, "string");
		long availableRamKb = 0;
		long totalRamKb = 0;
		
		if (memoryStatus.equals("enabled")) {
			MemoryInfo mi = new MemoryInfo();
			ActivityManager activityManager = (ActivityManager) getSystemService(ACTIVITY_SERVICE);
			activityManager.getMemoryInfo(mi);
			availableRamKb = mi.availMem / 1024;
			totalRamKb = 0;
			
			try {
		        RandomAccessFile reader = new RandomAccessFile("/proc/meminfo", "r");
		        
		        String line = reader.readLine();
		        line = line.replaceAll("[ ]+", " ");
		        String[] tokens = line.split(" ");
		        
		        totalRamKb = Long.valueOf(tokens[1]);
			}
			catch (IOException ex) {
		        ex.printStackTrace();
		    }
		}
		
		putSharedData("PANDROID_DATA", "availableRamKb", "" + availableRamKb, "long");
		putSharedData("PANDROID_DATA", "totalRamKb", "" + totalRamKb, "long");
	}
	
	/*private void getTaskStatus() {
		String taskStatus = getSharedData("PANDROID_DATA", "taskStatus", defaultTaskStatus, "string");
		String task = getSharedData("PANDROID_DATA", "task", defaultTask, "string");
		String taskHumanName = getSharedData("PANDROID_DATA", "taskHumanName", defaultTaskHumanName, "string");
		String run = "false";
		
		if (taskStatus.equals("enabled")) {
			if ((task.length() != 0) && (taskHumanName.length() != 0)) {
				ActivityManager activityManager = (ActivityManager)getApplication().getSystemService(ACTIVITY_SERVICE);
				List<RunningAppProcessInfo> runningAppProcessInfos = activityManager.getRunningAppProcesses();
				PackageManager pm = getApplication().getPackageManager();
				RunningAppProcessInfo runningAppProcessInfo;
				
				for (int i = 0; i < runningAppProcessInfos.size(); i++) {
					runningAppProcessInfo = runningAppProcessInfos.get(i);
					
					if (task.equals(runningAppProcessInfo.processName)) {
						run = "true";
						break;
					}
				}
			}
		}
		putSharedData("PANDROID_DATA", "taskRun", run, "string");
	}//end gettaskstatus
	*/
	/**
	 * Retrieves the simID of the device if available
	 */
	public void getSimID(){
		String simID = getSharedData("PANDROID_DATA", "simID", defaultSimID, "string");
		
	    String serviceName = Context.TELEPHONY_SERVICE;
	    TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
	    simID = telephonyManager.getSimSerialNumber();
	    
		
		putSharedData("PANDROID_DATA", "simID", simID, "string");
	}
	/**
	 * Retrieves the time in seconds since the device was switched on
	 */
	public void getUpTime(){
		String upTime = defaultUpTime;
	
		upTime = ""+SystemClock.elapsedRealtime()/1000;
		
		//Log.v(LOG_TAG, upTime);
		putSharedData("PANDROID_DATA", "upTime", upTime, "long");
	}
	public void getNetworkOperator(){
		String networkOperator = defaultNetworkOperator;
		
		
		String serviceName = Context.TELEPHONY_SERVICE;
	    TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
		networkOperator = telephonyManager.getNetworkOperatorName();
	    
		if(networkOperator != null)
		Log.v(LOG_TAG, networkOperator);
		putSharedData("PANDROID_DATA", "networkOperator", networkOperator, "string");
	}
	/*
	public void getSMSReceived(){
		String SMSReceived = defaultSMSReceived;
	
		SMSReceived = getSharedData("SMS_DATA", "SMSReceived", defaultSMSReceived, "integer");
		
		Log.v(LOG_TAG, "SMSReceived:"+SMSReceived);
		//putSharedData("PANDROID_DATA", "upTime", upTime, "long");
	}
	*/
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

	    public void onLocationChanged(Location loc) {
            putSharedData("PANDROID_DATA", "latitude", new Double(loc.getLatitude()).toString(), "float");
            putSharedData("PANDROID_DATA", "longitude", new Double(loc.getLongitude()).toString(), "float");
	    }
	    
	    	
	    public void onProviderDisabled(String provider) {
	    }
	
	    
	    public void onProviderEnabled(String provider) {
	    }
	
	    
	
	    public void onStatusChanged(String provider, int status, Bundle extras) {
	    }

    }/* End of Class MyLocationListener */
}
