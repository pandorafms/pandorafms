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

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStreamWriter;
import java.io.RandomAccessFile;
import java.text.DecimalFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.List;
import java.util.concurrent.ExecutionException;

import org.apache.commons.lang3.StringEscapeUtils;

import android.app.ActivityManager;
import android.app.ActivityManager.MemoryInfo;
import android.app.ActivityManager.RunningAppProcessInfo;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.location.Criteria;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.net.TrafficStats;
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.PowerManager;
import android.os.PowerManager.WakeLock;
import android.os.SystemClock;
import android.telephony.PhoneStateListener;
import android.telephony.SignalStrength;
import android.telephony.TelephonyManager;
import android.util.Log;
import android.view.Gravity;
import android.widget.Toast;

public class PandroidAgentListener extends Service {
	
	private NotificationManager notificationManager;
	private Notification notification;
	
    Handler h = new Handler();
    String lastGpsContactDateTime = "";
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
	
	
	
//	private void contact(){
//        Date date = new Date();
//        
//    	Core.putSharedData("PANDROID_DATA", "contactError", "0", "integer");
//        Core.putSharedData("PANDROID_DATA", "lastContact", Long.toString(date.getTime() / 1000), "long");
//        
//        // Keep lastXML sended if is not empty (empty means error sending it)
//        String lastXML = buildXML();
//        
//        
//		String agentName = Core.getSharedData("PANDROID_DATA", "agentName", Core.defaultAgentName, "string");
//
//		String destFileName = agentName + "." + System.currentTimeMillis() + ".data";
//		
//		writeFile(destFileName, lastXML);
//
//		String[] tentacleData = {
//				  "-a",
//				  Core.getSharedData("PANDROID_DATA", "serverAddr", "", "string"),
//				  "-p",
//				  Core.defaultServerPort,
//				  "-v",
//				  "/data/data/pandroid.agent/files/" + destFileName
//	    		  };
//
//		int tentacleRet = new tentacle_client().tentacle_client(tentacleData);
//    	
//		// Deleting the file after send it
//		File file = new File("/data/data/pandroid.agent/files/" + destFileName);
//    	file.delete();
//		
//        if(tentacleRet == 0) {
//            Core.putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
//            if (Core.helloSignal >= 1)
//				Core.helloSignal = 0;
//            Core.updateConf(getApplicationContext());
//        }
//        else {
//        	Core.putSharedData("PANDROID_DATA", "contactError", "1", "integer");
//        }
//        
//        updateValues();
//	}
	
	
	
	private void contact(){
			
		
		Toast toast = Toast.makeText(getApplicationContext(),
		 
				    getString(R.string.loading),
		       		Toast.LENGTH_SHORT);
		toast.setGravity(Gravity.BOTTOM,0,0);
		toast.show();
		
		
		    
		Date date = new Date();
        
		HelperSharedPreferences.putSharedPreferencesLong(this, "lastContact", date.getTime() / 1000);
        Boolean xmlBuilt = true;
        String xml = "";
        
    	try {
			xml = new buildXMLTask().execute().get();
		} catch (InterruptedException e) {
			// TODO Auto-generated catch block
			xmlBuilt = false;
		} catch (ExecutionException e) {
			// TODO Auto-generated catch block
			xmlBuilt = false;
		}
    	
    	
        
        new contactTask().execute(xml);
        updateValues();
		
	}//end contact
    
        
    private class contactTask extends AsyncTask<String, Void, Integer>{
    	String destFileName = "";	
    	
    		@Override 
    		protected void onPreExecute(){
    			
    		}
    	
        	@Override
        	protected Integer doInBackground(String... lastXML) { 
        		
        		
        		//Check for files
        	
        		String[] buffer = getApplicationContext().fileList();
//        		for(int i = 0; i<buffer.length; i++){
//        			Log.d("buffer", buffer[i]);
//        		}
        		Integer tentacleRet = null;
        		//files in buffer
        		boolean contact = true;
        		int i = 1;
        		while(getApplicationContext().fileList().length > 1 && contact) {
        			
        			destFileName = buffer[i];
        			
        			String[] tentacleData = {
            				"-a",
            				HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "serverAddr", ""),
            				"-p",
            				Core.defaultServerPort,
            				"-v",
            				"/data/data/pandroid.agent/files/" + destFileName
      	    		};
        			
            		
            		tentacleRet = new tentacle_client().tentacle_client(tentacleData);
            		
            		if(tentacleRet == 0) {
            			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "contactError", 0);
            			// Deleting the file after send it
            			// move to only delete if sent successfully
            			File file = new File("/data/data/pandroid.agent/files/" + destFileName);
            			file.delete();
            			if (Core.helloSignal >= 1)
            				Core.helloSignal = 0;
            				Core.updateConf(getApplicationContext());
            				
            		}
            		if(tentacleRet == -1){
            			//file not deleted
            			HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "contactError", 1);
            			contact = false;
            		}
            		i++;
            	
        		}
        		
        	return tentacleRet;
        	
        	}//end doInBackground
        	
        	
   }
    
    private class buildXMLTask extends AsyncTask<Void, Void, String>{
    	
    	@Override
    	protected String doInBackground(Void... v) { 
    		
    		String lastXML = buildXML();
    		
    		String destFileName = "";
    		String agentName = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "agentName", Core.defaultAgentName);
    		destFileName = agentName + "." + System.currentTimeMillis() + ".data";
    		
    		long bufferSize = 0;
    		String[] buffer = getApplicationContext().fileList();
    		
    		for(int i = 1; i<buffer.length;i++){
    			File file = new File("/data/data/pandroid.agent/files/" + buffer[i]);
    			bufferSize += file.length();
    		}
    		Log.d("Buffer size:",""+bufferSize);
    		
    		//Check if size of buffer is less than a value
    		if((bufferSize/1024) < Core.bufferSize){
    			writeFile(destFileName, lastXML);
    			HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "lastXML", lastXML);
    		}else{
    			//buffer full
    		}
    		HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "lastXML", lastXML);
    		
    		return lastXML;
    		
    	}
    }
   
    ////////////////////////////////////////////////////////////////////////////////////////
    //  From unfinished task of buffering unsent xml files when no connection available   //
    ////////////////////////////////////////////////////////////////////////////////////////
    
    /*
	public boolean saveArray(String[] array, String arrayName, Context mContext) {   
	    SharedPreferences prefs = mContext.getSharedPreferences("PANDROID_DATA", 0);  
	    SharedPreferences.Editor editor = prefs.edit();  
	    editor.putInt(arrayName +"_size", array.length);  
	    for(int i=0;i<array.length;i++)  
	        editor.putString(arrayName + "_" + i, array[i]);  
	    return editor.commit();  
	} 
	
	public String[] loadArray(String arrayName, Context mContext) {  
	    SharedPreferences prefs = mContext.getSharedPreferences("PANDROID_DATA", 0);  
	    int size = prefs.getInt(arrayName + "_size", 0);  
	    String array[] = new String[size];  
	    for(int i=0;i<size;i++)  
	        array[i] = prefs.getString(arrayName + "_" + i, null);  
	    return array;  
	}  
	*/
	
	////////////////////////////////////////////////////////////////////////////////////////
	
	
	private String buildXML(){
		String buffer = "";
		String gpsData = "";
		buffer += "<?xml version='1.0' encoding='iso-8859-1'?>\n";
		
		String latitude = ""+HelperSharedPreferences.getSharedPreferencesFloat(getApplicationContext(), "latitude", 181);
		String longitude = ""+HelperSharedPreferences.getSharedPreferencesFloat(getApplicationContext(), "longitude", 181);

		if(!latitude.equals("181.0") && !longitude.equals("181.0")) {
			gpsData = " latitude='" + latitude + "' longitude='" + longitude + "'";
		}
		
		String agentName = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "agentName", Core.defaultAgentName);
		String interval = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "interval", Core.defaultInterval);
		
		buffer += "<agent_data " +
			"description='' group='' os_name='android' os_version='"+Build.VERSION.RELEASE+"' " +		
			"interval='"+ interval +"' version='4.0(Build 111012)' " + 
			"timestamp='" + getHumanDateTime(-1) + "' agent_name='" + agentName + "' " +
			"timezone_offset='0'" + gpsData +">\n";
		
		// 																					//
		//									MODULES											//
		//																					//
		
		String orientation = ""+HelperSharedPreferences.getSharedPreferencesFloat(getApplicationContext(), "orientation", 361);
		String proximity = ""+HelperSharedPreferences.getSharedPreferencesFloat(getApplicationContext(), "proximity", -1.0f);
		String batteryLevel = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "batteryLevel", -1);
		String taskStatus = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "taskStatus", "disabled");
		String taskRun = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "taskRun", "false");
		String taskHumanName = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "taskHumanName", "");
		taskHumanName = StringEscapeUtils.escapeHtml4(taskHumanName);
		
		String task = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "task", "");
		String memoryStatus = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "memoryStatus", Core.defaultMemoryStatus);
		String availableRamKb = ""+HelperSharedPreferences.getSharedPreferencesLong(getApplicationContext(), "availableRamKb", 0);
		String totalRamKb = ""+HelperSharedPreferences.getSharedPreferencesLong(getApplicationContext(), "totalRamKb", 0);
		String SimID = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "simID", Core.defaultSimID);
		String upTime = ""+HelperSharedPreferences.getSharedPreferencesLong(getApplicationContext(), "upTime", Core.defaultUpTime);
		String networkOperator = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "networkOperator", Core.defaultNetworkOperator);
		String SMSReceived = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "SMSReceived", Core.defaultSMSReceived);
		String SMSSent = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "SMSSent", Core.defaultSMSSent);
		String networkType = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "networkType", Core.defaultNetworkType);
		String phoneType = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "networkType", Core.defaultNetworkType);
		String signalStrength = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "signalStrength", Core.defaultSignalStrength);
		String incomingCalls = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "incomingCalls", Core.defaultIncomingCalls);
		String missedCalls = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "missedCalls", Core.defaultMissedCalls);
		String outgoingCalls = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "outgoingCalls", Core.defaultOutgoingCalls);
		String receiveBytes = ""+HelperSharedPreferences.getSharedPreferencesLong(getApplicationContext(), "receiveBytes", Core.defaultReceiveBytes);
		String transmitBytes = ""+HelperSharedPreferences.getSharedPreferencesLong(getApplicationContext(), "transmitBytes", Core.defaultTransmitBytes);
		String helloSignal = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "helloSignal", Core.defaultHelloSignal);
		String roaming = ""+HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "roaming", Core.defaultRoaming);
		
		String simIDReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "simIDReport", Core.defaultSimIDReport);
		String DeviceUpTimeReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "DeviceUpTimeReport", Core.defaultDeviceUpTimeReport);
		String NetworkOperatorReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "NetworkOperatorReport", Core.defaultNetworkOperatorReport);
		String NetworkTypeReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "NetworkTypeReport", Core.defaultNetworkTypeReport);
		String PhoneTypeReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "PhoneTypeReport", Core.defaultPhoneTypeReport);
		String SignalStrengthReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "SignalStrengthReport", Core.defaultSignalStrengthReport);
		String ReceivedSMSReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "ReceivedSMSReport", Core.defaultReceivedSMSReport);
		String SentSMSReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "SentSMSReport", Core.defaultSentSMSReport);
		String IncomingCallsReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "IncomingCallsReport", Core.defaultIncomingCallsReport);
		String MissedCallsReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "MissedCallsReport", Core.defaultMissedCallsReport);
		String OutgoingCallsReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "OutgoingCallsReport", Core.defaultOutgoingCallsReport);
		String BytesReceivedReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "BytesReceivedReport", Core.defaultBytesReceivedReport);
		String BytesSentReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "BytesSentReport", Core.defaultBytesSentReport);
		String HelloSignalReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "HelloSignalReport", Core.defaultHelloSignalReport);
		String BatteryLevelReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "BatteryLevelReport", Core.defaultBatteryLevelReport);
		String RoamingReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "RoamingReport", Core.defaultRoamingReport);
		String InventoryReport = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "InventoryReport", Core.defaultInventoryReport);

		if(InventoryReport.equals("enabled"))
		{
			buffer += buildInventoryXML();
		}
		
		if (BatteryLevelReport.equals("enabled")) 
			buffer += buildmoduleXML("battery_level", "The current Battery level", "generic_data", batteryLevel);	
		
		if(!orientation.equals("361.0")) {
			buffer += buildmoduleXML("orientation", "The actually device orientation (in degrees)", "generic_data", orientation);		
		}
		
		if(!proximity.equals("-1.0")) {
			buffer += buildmoduleXML("proximity", "The actually device proximity detector (0/1)", "generic_data", proximity);		
		}		
		
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
		
		if (memoryStatus.equals("enabled")) {
			
			Float freeMemory = new Float((Float.valueOf(availableRamKb) / Float.valueOf(totalRamKb)) * 100.0);
			
			DecimalFormat formatPercent = new DecimalFormat("#.##");
			buffer += buildmoduleXML("freeRamMemory", "The percentage of available ram.", "generic_data",
				formatPercent.format(freeMemory.doubleValue()));
		}
		buffer += buildmoduleXML("last_gps_contact", "Datetime of the last geo-location contact", "generic_data", lastGpsContactDateTime);
		if (DeviceUpTimeReport.equals("enabled"))
			buffer += buildmoduleXML("upTime","Total device uptime in seconds.", "generic_data", upTime);
		
		if (Core.hasSim){
			if (simIDReport.equals("enabled"))
				buffer += buildmoduleXML("simID", "The Sim ID.", "generic_data_string", SimID);
			if (NetworkOperatorReport.equals("enabled"))
				buffer += buildmoduleXML("networkOperator","Currently registered network operator", "generic_data_string", networkOperator);
			if (NetworkTypeReport.equals("enabled"))
				buffer += buildmoduleXML("networkType","Current network type", "generic_data_string", networkType);
			if (PhoneTypeReport.equals("enabled"))
				buffer += buildmoduleXML("phoneType","Phone type", "generic_data_string", phoneType);
			if (SignalStrengthReport.equals("enabled"))
				buffer += buildmoduleXML("signalStrength","Signal strength (dB)", "generic_data_string", signalStrength);
			if (ReceivedSMSReport.equals("enabled"))
				buffer += buildmoduleXML("SMSRecieved","Number of SMS received", "generic_data", SMSReceived);
			if (SentSMSReport.equals("enabled"))
				buffer += buildmoduleXML("SMSSent","Number of SMS sent", "generic_data", SMSSent);
			if (IncomingCallsReport.equals("enabled"))
				buffer += buildmoduleXML("incomingCalls","Incoming calls", "generic_data", incomingCalls);
			if (MissedCallsReport.equals("enabled"))
				buffer += buildmoduleXML("missedCalls","Missed calls", "generic_data", missedCalls);
			if (OutgoingCallsReport.equals("enabled"))
				buffer += buildmoduleXML("outgoingCalls","Outgoing calls", "generic_data", outgoingCalls);
			if (BytesReceivedReport.equals("enabled"))
				buffer += buildmoduleXML("receiveBytes","Bytes received(mobile)", "generic_data", receiveBytes);
			if (BytesSentReport.equals("enabled"))
				buffer += buildmoduleXML("transmitBytes","Bytes transmitted(mobile)", "generic_data", transmitBytes);
			if (RoamingReport.equals("enabled"))
				buffer += buildmoduleXML("roaming","Device is roaming", "generic_data", roaming);
		}// end if sim card
		if (HelloSignalReport.equals("enabled"))
			buffer += buildmoduleXML("helloSignal","Hello Signal", "generic_data", helloSignal);
		
		
		//UTF-8 TEST//
		
		//String iso_8859_1String = "ÀÁÈÉÌÍÙÚÜàáèéìíòóùúü";
		
		//buffer += buildmoduleXML("iso-8859-1Test","Testing iso-8859-1 Values", "generic_data_string", iso_8859_1String);
			
		
		// End_Modules
		
		buffer += "</agent_data>";
		
		return buffer;
		
	}// end buildXML
	
    private void writeFile(String fileName, String textToWrite) {
    	try { // catches IOException below
    		/*
    		String UTF8 = "utf8";
    		int BUFFER_SIZE = 8192;
    		
    		FileOutputStream fOut = openFileOutput(fileName, MODE_WORLD_READABLE);
    		OutputStreamWriter osw = new OutputStreamWriter(fOut, UTF8); 
    		
    		BufferedWriter bw = new BufferedWriter(osw,BUFFER_SIZE);
    		
    		// Write the string to the file
    		bw.write(textToWrite);
    		//ensure that everything is really written out and close
    		bw.flush();
    		bw.close();
    		*/
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
	
	 
	
	private String buildInventoryXML(){
		
		String module_xml = "";
		
		module_xml += "\t<inventory>\n";
		module_xml += "\t\t<inventory_module>\n\t\t\t<name><![CDATA[";
		module_xml += "Software";
		module_xml += "]]></name>\n";
		module_xml += "\t\t\t<datalist>\n";
		    
		List<PackageInfo> packs = getPackageManager().getInstalledPackages(0);
		for(int i=0;i<packs.size();i++) {
		      module_xml += "\t\t\t\t<data><![CDATA[";
		      
		      PackageInfo p = packs.get(i);
		           		            
		      module_xml += p.applicationInfo.loadLabel(getPackageManager()).toString();
		      module_xml += ";"+ p.versionName;
		      module_xml += ";"+ p.packageName;
		      module_xml += "]]></data>\n";
		}
		
		/* Close the datalist and module_inventory */
		module_xml += "\t\t\t</datalist>\n\t\t</inventory_module>\n";
		/* Close inventory */
		module_xml += "\t</inventory>\n";
		//Log.d(LOG_TAG,module_xml);
		        
		return module_xml;
	}
	
	private void gpsLocation() {
    	// Starts with GPS, if no GPS then gets network location
    	
		LocationManager lm = (LocationManager) getSystemService(Context.LOCATION_SERVICE);  
		List<String> providers = lm.getProviders(true);
		Log.d("PANDROID providers count", "" + providers.size());

		/* Loop over the array backwards, and if you get an accurate location, then break out the loop*/
		Location loc = null;

		for (int i=providers.size()-1; i>=0; i--) {
			Log.d("PANDROID providers", providers.get(i));
		    loc = lm.getLastKnownLocation(providers.get(i));
		    if (loc != null) break;
		}

		if (loc != null) {
			Log.d("PANDROID", "loc != null");
			//if(latitude != loc.getLatitude() || longitude != loc.getLongitude()) {
				lastGpsContactDateTime = getHumanDateTime(-1);
			//}
			HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "latitude", (float) loc.getLatitude());
			HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "longitude", (float) loc.getLongitude());
		}
		else {
			Criteria criteria = new Criteria();
			criteria.setAccuracy(Criteria.ACCURACY_COARSE);
			criteria.setPowerRequirement(Criteria.POWER_LOW);
			criteria.setAltitudeRequired(false);
			criteria.setBearingRequired(false);
			criteria.setCostAllowed(true);
			String bestProvider = lm.getBestProvider(criteria, true);
			
			// If not provider found, abort GPS retrieving
			if (bestProvider == null) {
				Log.e("LOCATION", "No location provider found!");
				return;
			}
			
			lm.requestLocationUpdates(bestProvider, Core.defaultInterval, 1000,
				new LocationListener() {
					public void onLocationChanged(Location location) {
						HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "latitude",(float) location.getLatitude());
						HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "longitude",(float) location.getLongitude());
					}
					public void onStatusChanged(String s, int i, Bundle bundle) {
						
					}
					public void onProviderEnabled(String s) {
						// try switching to a different provider
					}
					public void onProviderDisabled(String s) {
						HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "enabled_location_provider", "disabled");
					}
				});
		}
		
    }
    
    private void batteryLevel() {
    	
    	Intent batteryIntent = getApplicationContext().registerReceiver(null, new IntentFilter(Intent.ACTION_BATTERY_CHANGED));
    		int rawlevel = batteryIntent.getIntExtra("level", -1);
    		int scale = batteryIntent.getIntExtra("scale", -1);
    		//double level = -1;
    		if (rawlevel >= 0 && scale > 0) {
    			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "batteryLevel", (rawlevel * 100) / scale);
    		}
    }
    
    /*private void sensors() {
    	// Sensor listeners
    	
        SensorEventListener orientationLevelReceiver = new SensorEventListener() {
            public void onSensorChanged(SensorEvent sensorEvent) {
                Core.putSharedData("PANDROID_DATA", "orientation", Float.toString(sensorEvent.values[0]), "float");
            }
            public void onAccuracyChanged(Sensor sensor, int accuracy) {
            }
        };
        
        SensorEventListener proximityLevelReceiver = new SensorEventListener() {
            public void onSensorChanged(SensorEvent sensorEvent) {
                Core.putSharedData("PANDROID_DATA", "proximity", Float.toString(sensorEvent.values[0]), "float");
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
        String gpsStatus = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "gpsStatus", Core.defaultGpsStatus);
        
        if(gpsStatus.equals("enabled")) {
        	Log.d("PANDROID AGENT", "ENABLED");
			gpsLocation();
        }
        else {
        	Log.d("PANDROID AGENT", "DISABLED");
        	HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "latitude", 181.0f);
        	HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "longitude", 181.0f);
        }
        
        //sensors();
        getTaskStatus();
        getMemoryStatus();
        getUpTime();
        
        if(Core.hasSim)
        {
        	getSimID();
        	getNetworkOperator();
        	getSMSSent();
        	getNetworkType();
        	getPhoneType();
        	getSignalStrength();
        	getCalls();
        	getDataBytes();
        	getRoaming();
        }
	}
	
	private void getMemoryStatus() {
		String memoryStatus = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "memoryStatus", Core.defaultMemoryStatus);
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
		        reader.close();
		        line = line.replaceAll("[ ]+", " ");
		        String[] tokens = line.split(" ");
		        
		        totalRamKb = Long.valueOf(tokens[1]);
			}
			catch (IOException ex) {
		        ex.printStackTrace();
		    }
		}
		
		HelperSharedPreferences.putSharedPreferencesLong(getApplicationContext(), "availableRamKb", availableRamKb);
		HelperSharedPreferences.putSharedPreferencesLong(getApplicationContext(), "totalRamKb", totalRamKb);
	}// end getMemoryStatus
	
	private void getTaskStatus() {
		String taskStatus = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "taskStatus", Core.defaultTaskStatus);
		String task = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "task", Core.defaultTask);
		String taskHumanName = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "taskHumanName", Core.defaultTaskHumanName);
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
		HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "taskRun", run);
	}//end getTaskStatus
	
	/**
	 * 	Retrieves the simID of the device if available
	 */
	private void getSimID(){
		
			String simID = HelperSharedPreferences.getSharedPreferencesString(getApplicationContext(), "simID", Core.defaultSimID);
		
			String serviceName = Context.TELEPHONY_SERVICE;
			TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
			simID = telephonyManager.getSimSerialNumber();
			HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "simID", simID);
	}
	/**
	 * 	Retrieves the time in seconds since the device was switched on
	 */
	private void getUpTime(){
		long upTime = Core.defaultUpTime;
		upTime = SystemClock.elapsedRealtime()/1000;
		if(upTime != 0)
			HelperSharedPreferences.putSharedPreferencesLong(getApplicationContext(), "upTime", upTime);
	}
	/**
	 * 	Retrieve currently registered network operator, i.e. vodafone, movistar, etc...
	 */
	private void getNetworkOperator(){
		String networkOperator = Core.defaultNetworkOperator;
		String serviceName = Context.TELEPHONY_SERVICE;
	    TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
		networkOperator = telephonyManager.getNetworkOperatorName();
	    
		if(networkOperator != null)
			HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "networkOperator", networkOperator);
	}
	/**
	 *  Retrieves the number of sent sms messages using the android messaging app only
	 */
	private void getSMSSent(){
		
		int SMSSent = Core.defaultSMSSent;
		SMSSent = HelperSharedPreferences.getSharedPreferencesInt(getApplicationContext(), "SMSSent", Core.defaultSMSSent);
		Uri allMessages = Uri.parse("content://sms/sent");
		Cursor c = getContentResolver().query(allMessages, null, null, null, null);
		int totalMessages = 0;
		
		while (c.moveToNext()) 
		{
		    String messageBody = c.getString(c.getColumnIndex("body"));
		    long messageLength = messageBody.length();
		    double numberOfMessages = messageLength / 160.0;
		    double numberOfMessagesRoundedUp = Math.ceil(numberOfMessages);
		    totalMessages = (int) (totalMessages + numberOfMessagesRoundedUp);
		}
		
		c.close();
		
		SMSSent = totalMessages;
		
		if(SMSSent != 0)
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "SMSSent", SMSSent);
		
	}// end getSMSSent
	/**
	 *  Retrieve the type of data network currently connected to, i.e. edge, gprs, etc...
	 */
	private void getNetworkType()
	{
		String networkType = Core.defaultNetworkType;
		TelephonyManager tM = (TelephonyManager)getSystemService(Context.TELEPHONY_SERVICE);
		int nT = tM.getNetworkType();

		switch (nT)
		{
			case 0:
				networkType = "Unknown";
				break;
			case 1:
				networkType = "GPRS";
				break;
			case 2:
				networkType = "EDGE";
				break;
			case 3:
				networkType = "UMTS";
				break;
			case 4:
				networkType = "CDMA";
				break;
			case 5:
				networkType = "EVDO rev. 0";
				break;
			case 6:
				networkType = "EVDO rev. A";
				break;
			case 7:
				networkType = "1xRTT";
				break;
			case 8:
				networkType = "HSDPA";
				break;
			case 9:
				networkType = "HSUPA";
				break;
			case 10:
				networkType = "HSPA";
				break;
			case 11:
				networkType = "iDen";
				break;
			case 12:
				networkType = "EVDO rev. B";
				break;
			case 13:
				networkType = "LTE";
				break;
			case 14:
				networkType = "eHRPD";
				break;      
			case 15:
				networkType = "HSPA+";
				break;          
		}
		if(networkType != null)
			HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "networkType", networkType);
		
	}// end getNetworkType
	
	/**
	 *  Retrieve the type of mobile network currently conncected to, i.e. gms, cdma, etc...
	 */
	private void getPhoneType()
	{
		String phoneType = Core.defaultPhoneType;
		TelephonyManager tM = (TelephonyManager)getSystemService(Context.TELEPHONY_SERVICE);
		int pT = tM.getPhoneType();

		switch (pT)
		{
			case 0: 
				phoneType = "none";
				break;
			case 1: 
				phoneType = "GSM";
				break;
			case 2: 
				phoneType = "CDMA";
				break;
			case 3: 
				phoneType = "SIP";
				break;
		}
		if(phoneType != null)
			HelperSharedPreferences.putSharedPreferencesString(getApplicationContext(), "phoneType", phoneType);
	}// end getPhoneType
	
	/**
	 *  Retrieves the number of incoming, missed and outgoing calls
	 */
	private void getCalls()
	{
		Cursor c = getApplicationContext().getContentResolver().query(android.provider.CallLog.Calls.CONTENT_URI, null, null, null, null);
		c.moveToFirst();
		int typeColumn = c.getColumnIndex(android.provider.CallLog.Calls.TYPE);
		int incoming = 0;
		int outgoing = 0;
		int missed = 0;
		
		if(c.isFirst()){
			 
			while (c.isAfterLast() == false) {
                    int callType = c.getInt(typeColumn);
                    
                    switch(callType){                    
                    	case android.provider.CallLog.Calls.INCOMING_TYPE:
                    		incoming++;
                    		break;
                    	case android.provider.CallLog.Calls.MISSED_TYPE:
                           	missed++;
                            break;
                    	case android.provider.CallLog.Calls.OUTGOING_TYPE:
                            outgoing++;
                            break;
                    }
                    c.moveToNext();
			}
            
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "incomingCalls", incoming);
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "missedCalls", missed);
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "outgoingCalls", outgoing);
		}
		
		c.close();
	}// end getCalls
	/**
	 *  Retrieves the current cell signal strength in dB
	 */
	private void getSignalStrength()
	{	
		TelephonyManager telephone 	= 	(TelephonyManager)getSystemService(Context.TELEPHONY_SERVICE);
		signalListener phoneState 	= 	new signalListener();
		telephone.listen(phoneState ,PhoneStateListener.LISTEN_SIGNAL_STRENGTHS);
	}
	
	private class signalListener extends PhoneStateListener
	{
		@Override
		public void onSignalStrengthsChanged(SignalStrength signalStrength)
		{
			super.onSignalStrengthsChanged(signalStrength);
			int signalStrengthValue = Core.defaultSignalStrength;
			if (signalStrength.isGsm()) {
                if (signalStrength.getGsmSignalStrength() != 99)
                    signalStrengthValue = (signalStrength.getGsmSignalStrength() * 2 - 113);
                else
                    signalStrengthValue = (signalStrength.getGsmSignalStrength());
            }
			else{
               signalStrengthValue = (signalStrength.getCdmaDbm());
            }
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "signalStrength", signalStrengthValue);
		}
	};
	/**
	 *  Retrieves the number of sent/received bytes using the mobile network
	 */
	private void getDataBytes()
	{
		
		long receiveBytes = TrafficStats.getMobileRxBytes();
		long transmitBytes = TrafficStats.getMobileTxBytes();
		
		if (receiveBytes != TrafficStats.UNSUPPORTED && transmitBytes != TrafficStats.UNSUPPORTED) 
		{
			HelperSharedPreferences.putSharedPreferencesLong(getApplicationContext(), "receiveBytes", receiveBytes);
			HelperSharedPreferences.putSharedPreferencesLong(getApplicationContext(), "transmitBytes", transmitBytes);
		}
	}
	/**
	 * Retrieves whether the device is currently connected to a roaming network
	 */
	private void getRoaming()
	{
		TelephonyManager telephone 	= 	(TelephonyManager)getSystemService(Context.TELEPHONY_SERVICE);
		boolean roaming = telephone.isNetworkRoaming();
		
		if(roaming)
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "roaming", 1);
		else
			HelperSharedPreferences.putSharedPreferencesInt(getApplicationContext(), "roaming", 0);
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
    
    public static void CancelNotification(Context ctx, int notifyId) {
	    String ns = Context.NOTIFICATION_SERVICE;
	    NotificationManager nMgr = (NotificationManager) ctx.getSystemService(ns);
	    nMgr.cancel(notifyId);
	}
  
    
    
    ///////////////////////////////////////////
    // Getting values from device functions
    ///////////////////////////////////////////
    
    public class MyLocationListener implements LocationListener {
    
		@Override
	    public void onLocationChanged(Location loc) {
			HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "latitude", (float)loc.getLatitude());
			HelperSharedPreferences.putSharedPreferencesFloat(getApplicationContext(), "longitude", (float)loc.getLongitude());
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
