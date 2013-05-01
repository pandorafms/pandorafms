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

import android.app.Activity;
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
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.content.pm.PackageInfo;
import android.content.pm.PackageManager;
import android.hardware.Sensor;
import android.hardware.SensorEvent;
import android.hardware.SensorEventListener;
import android.hardware.SensorManager;
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
	
	
    Handler h = new Handler();
    String lastGpsContactDateTime = "";
    double latitude;
    double longitude;
    //boolean showLastXML = true;
    
    
    private LocationManager locmgr = null;
    
    
	@Override
	public void onCreate() {
		
//		try {
//            notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
//        } catch (Exception e) {
//            Log.e("notification", e.toString());
//        }
//		
//		if(Core.NotificationCheck == "enabled"){
//			
//			Notification notification = new Notification(R.drawable.icon, getText(R.string.ticker_text),
//					System.currentTimeMillis());
//			Intent notificationIntent = new Intent(this,PandroidAgent.class);
//			notificationIntent.setAction("android.intent.action.MAIN");
//			notificationIntent.addCategory("android.intent.category.LAUNCHER");
//			PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, notificationIntent, Notification.FLAG_NO_CLEAR);
//			notification.setLatestEventInfo(this, getText(R.string.notification_title), getText(R.string.notification_message), pendingIntent); 
//			notification.flags |= Notification.FLAG_ONGOING_EVENT;
//			notificationManager.notify(1, notification);
//		}
//		else{
//			CancelNotification(getApplicationContext(),42);
//		}
	}
	
	
	
	@Override
	public int onStartCommand(Intent intent, int flags, int startId) {
		PowerManager pm = (PowerManager) getSystemService(Context.POWER_SERVICE);
        WakeLock wakeLock = pm.newWakeLock(PowerManager.PARTIAL_WAKE_LOCK, "MyWakeLock");
        wakeLock.acquire();
        Core.loadConf(this);
		updateValues();
		contact();
		
		try {
            NotificationManager notificationManager = (NotificationManager) getSystemService(Context.NOTIFICATION_SERVICE);
            Notification notification = new Notification(R.drawable.icon, getText(R.string.ticker_text),
    				System.currentTimeMillis());
    		Intent notificationIntent = new Intent(this,PandroidAgent.class);
    		notificationIntent.setAction("android.intent.action.MAIN");
    		notificationIntent.addCategory("android.intent.category.LAUNCHER");
    		PendingIntent pendingIntent = PendingIntent.getActivity(this, 0, notificationIntent, Notification.FLAG_NO_CLEAR);
    		notification.setLatestEventInfo(this, getText(R.string.notification_title), getText(R.string.notification_message), pendingIntent); 
    		notification.flags |= Notification.FLAG_ONGOING_EVENT;
    		
    		
    		if(Core.NotificationCheck == "enabled"){
    			CancelNotification(getApplicationContext(),42);
    			notificationManager.notify(42, notification);
    		}
    		else{
    			CancelNotification(getApplicationContext(),42);
    		}
    		
    		
        } catch (Exception e) {
            Log.e("notification", e.toString());
        }
		
		
		finally{
			wakeLock.release();
		}
		
	    return START_NOT_STICKY;
	}

	@Override
	public IBinder onBind(Intent intent) {
		return null;
	}
	
	
	
//	private void contact(){
//        Date date = new Date();
//        
//    	putSharedData("PANDROID_DATA", "contactError", "0", "integer");
//        putSharedData("PANDROID_DATA", "lastContact", Long.toString(date.getTime() / 1000), "long");
//        
//        // Keep lastXML sended if is not empty (empty means error sending it)
//        String lastXML = buildXML();
//        
//        
//		String agentName = getSharedData("PANDROID_DATA", "agentName", Core.defaultAgentName, "string");
//
//		String destFileName = agentName + "." + System.currentTimeMillis() + ".data";
//		
//		writeFile(destFileName, lastXML);
//
//		String[] tentacleData = {
//				  "-a",
//				  getSharedData("PANDROID_DATA", "serverAddr", "", "string"),
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
//            putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
//            if (Core.helloSignal >= 1)
//				Core.helloSignal = 0;
//            Core.updateConf(getApplicationContext());
//        }
//        else {
//        	putSharedData("PANDROID_DATA", "contactError", "1", "integer");
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
        
    	putSharedData("PANDROID_DATA", "lastContact", Long.toString(date.getTime() / 1000), "long");
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
    	//TODO ensure not a problem
        //updateValues();
		
	}//end contact
    
        
    private class contactTask extends AsyncTask<String, Void, Integer>{
    	String destFileName = "";	
    	
    		@Override 
    		protected void onPreExecute(){
    			
    		}
    	
        	@Override
        	protected Integer doInBackground(String... lastXML) { 
        		
        		
        		String[] buffer = getApplicationContext().fileList();
        		Integer tentacleRet = null;
        		
        		
        		boolean contact = true;
        		int i = 1;
        		while(getApplicationContext().fileList().length > 1 && contact) {
        			
        			destFileName = buffer[i];
        			
        			String[] tentacleData = {
            				"-a",
            				getSharedData("PANDROID_DATA", "serverAddr", "", "string"),
            				"-p",
            				Core.defaultServerPort,
            				"-v",
            				"/data/data/pandroid.agent/files/" + destFileName
      	    		};
        			
            		
            		tentacleRet = new tentacle_client().tentacle_client(tentacleData);
            		
            		if(tentacleRet == 0) {
            			putSharedData("PANDROID_DATA", "contactError", "0", "integer");
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
            			putSharedData("PANDROID_DATA", "contactError", "1", "integer");
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
    		String agentName = getSharedData("PANDROID_DATA", "agentName", Core.defaultAgentName, "string");
    		destFileName = agentName + "." + System.currentTimeMillis() + ".data";
    		
    		
    		long bufferSize = 0;
    		String[] buffer = getApplicationContext().fileList();
    		
    		for(int i = 1; i<buffer.length;i++){
    			File file = new File("/data/data/pandroid.agent/files/" + buffer[i]);
    			bufferSize += file.length();
    		}
    		
    		//Check if size of buffer is less than a value
    		if((bufferSize/1024) < Core.bufferSize){
    			writeFile(destFileName, lastXML);
    			putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
    		}else{
    			//buffer full
    		}
    		putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
    		
    		return lastXML;
    		
    	}
    }
   
    private String buildXML(){
		String buffer = "";
		String gpsData = "";
		buffer += "<?xml version='1.0' encoding='iso-8859-1'?>\n";
		
		String latitude = getSharedData("PANDROID_DATA", "latitude", "181", "float");
		String longitude = getSharedData("PANDROID_DATA", "longitude", "181", "float");

		if(!latitude.equals("181.0") && !longitude.equals("181.0")) {
			gpsData = " latitude='" + latitude + "' longitude='" + longitude + "'";
		}
		
		String interval = getSharedData("PANDROID_DATA", "interval", Integer.toString(Core.defaultInterval), "integer");
		String agentName = getSharedData("PANDROID_DATA", "agentName", Core.defaultAgentName, "string");
		
		
		buffer += "<agent_data " +
			"description='' group='' os_name='android' os_version='"+Build.VERSION.RELEASE+"' " +		
			"interval='"+ interval +"' version='4.0(Build 111012)' " + 
			"timestamp='" + getHumanDateTime(-1) + "' agent_name='" + agentName + "' " +
			"timezone_offset='0'" + gpsData +">\n";
		
		// 																					//
		//									MODULES											//
		//																					//
		
//		String orientation = getSharedData("PANDROID_DATA", "orientation", "361", "float");
//		String proximity = getSharedData("PANDROID_DATA", "proximity", "-1.0", "float");
		String batteryLevel = getSharedData("PANDROID_DATA", "batteryLevel", "-1", "integer");
		String taskStatus = getSharedData("PANDROID_DATA", "taskStatus", "disabled", "string");
		String taskRun = getSharedData("PANDROID_DATA", "taskRun", "false", "string");
		String taskHumanName = getSharedData("PANDROID_DATA", "taskHumanName", "", "string");
		taskHumanName = StringEscapeUtils.escapeHtml4(taskHumanName);
		String task = getSharedData("PANDROID_DATA", "task", "", "string");
		String memoryStatus = getSharedData("PANDROID_DATA", "memoryStatus", Core.defaultMemoryStatus, "string");
		String availableRamKb = getSharedData("PANDROID_DATA", "availableRamKb", "0" , "long");
		String totalRamKb = getSharedData("PANDROID_DATA", "totalRamKb", "0", "long");
		String upTime = getSharedData("PANDROID_DATA", "upTime", ""+Core.defaultUpTime, "long");
		String helloSignal = getSharedData("PANDROID_DATA", "helloSignal", ""+Core.defaultHelloSignal, "integer");
		
		String SimID = getSharedData("PANDROID_DATA", "simID", Core.defaultSimID, "string");
		String networkOperator  = getSharedData("PANDROID_DATA", "networkOperator", Core.defaultNetworkOperator, "string");
		String networkType = getSharedData("PANDROID_DATA", "networkType", Core.defaultNetworkType, "string");
		String phoneType = getSharedData("PANDROID_DATA", "networkType", Core.defaultNetworkType, "string");
		String signalStrength = getSharedData("PANDROID_DATA", "signalStrength", ""+Core.defaultSignalStrength, "integer");
		String SMSReceived = getSharedData("PANDROID_DATA", "SMSReceived", ""+Core.defaultSMSReceived, "integer");
		String SMSSent = getSharedData("PANDROID_DATA", "SMSSent", ""+Core.defaultSMSSent, "integer");
		String incomingCalls = getSharedData("PANDROID_DATA", "incomingCalls", ""+Core.defaultIncomingCalls, "integer");
		String missedCalls = getSharedData("PANDROID_DATA", "missedCalls", ""+Core.defaultMissedCalls, "integer");
		String outgoingCalls = getSharedData("PANDROID_DATA", "outgoingCalls", ""+Core.defaultOutgoingCalls, "integer");
		String receiveBytes = getSharedData("PANDROID_DATA", "receiveBytes", ""+Core.defaultReceiveBytes, "long");
		String transmitBytes = getSharedData("PANDROID_DATA", "transmitBytes", ""+Core.defaultTransmitBytes, "long");
		String roaming = getSharedData("PANDROID_DATA", "roaming", ""+Core.defaultRoaming, "integer");
		
		String simIDReport = getSharedData("PANDROID_DATA", "simIDReport", Core.defaultSimIDReport, "string");
		String DeviceUpTimeReport = getSharedData("PANDROID_DATA", "DeviceUpTimeReport", Core.defaultDeviceUpTimeReport, "string");
		String NetworkOperatorReport = getSharedData("PANDROID_DATA", "NetworkOperatorReport", Core.defaultNetworkOperatorReport, "string");
		String NetworkTypeReport = getSharedData("PANDROID_DATA", "NetworkTypeReport", Core.defaultNetworkTypeReport, "string");
		String PhoneTypeReport = getSharedData("PANDROID_DATA", "PhoneTypeReport", Core.defaultPhoneTypeReport, "string");
		String SignalStrengthReport = getSharedData("PANDROID_DATA", "SignalStrengthReport", Core.defaultSignalStrengthReport, "string");
		String ReceivedSMSReport = getSharedData("PANDROID_DATA", "ReceivedSMSReport", Core.defaultReceivedSMSReport, "string");
		String SentSMSReport = getSharedData("PANDROID_DATA", "SentSMSReport", Core.defaultSentSMSReport, "string");
		String IncomingCallsReport = getSharedData("PANDROID_DATA", "IncomingCallsReport", Core.defaultIncomingCallsReport, "string");
		String MissedCallsReport = getSharedData("PANDROID_DATA", "MissedCallsReport", Core.defaultMissedCallsReport, "string");
		String OutgoingCallsReport = getSharedData("PANDROID_DATA", "OutgoingCallsReport", Core.defaultOutgoingCallsReport, "string");
		String BytesReceivedReport = getSharedData("PANDROID_DATA", "BytesReceivedReport", Core.defaultBytesReceivedReport, "string");
		String BytesSentReport = getSharedData("PANDROID_DATA", "BytesSentReport", Core.defaultBytesSentReport, "string");
		String HelloSignalReport = getSharedData("PANDROID_DATA", "HelloSignalReport", Core.defaultHelloSignalReport, "string");
		String BatteryLevelReport = getSharedData("PANDROID_DATA", "BatteryLevelReport", Core.defaultBatteryLevelReport, "string");
		String RoamingReport = getSharedData("PANDROID_DATA", "RoamingReport", Core.defaultRoamingReport, "string");
		String InventoryReport = getSharedData("PANDROID_DATA", "InventoryReport", Core.defaultInventoryReport, "string");

		if(InventoryReport.equals("enabled"))
		{
			buffer += buildInventoryXML();
		}
		
		if (BatteryLevelReport.equals("enabled")) 
			buffer += buildmoduleXML("battery_level", "The current Battery level", "generic_data", batteryLevel);	
		
//		if(!orientation.equals("361.0")) {
//			buffer += buildmoduleXML("orientation", "The actually device orientation (in degrees)", "generic_data", orientation);		
//		}
//		
//		if(!proximity.equals("-1.0")) {
//			buffer += buildmoduleXML("proximity", "The actually device proximity detector (0/1)", "generic_data", proximity);		
//		}		
		
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
		
		if (HelloSignalReport.equals("enabled"))
			buffer += buildmoduleXML("helloSignal","Hello Signal", "generic_data", helloSignal);
		
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
		
		/* Close the data list and module_inventory */
		module_xml += "\t\t\t</datalist>\n\t\t</inventory_module>\n";
		/* Close inventory */
		module_xml += "\t</inventory>\n";
		//Log.d(LOG_TAG,module_xml);
		        
		return module_xml;
	}
	
	private void gpsLocation(){
		
		
		//Start a location listener
	    LocationListener onLocationChange=new LocationListener() {
	        public void onLocationChanged(Location loc) {
	            Log.d("latitude",""+loc.getLatitude());
	            Log.d("Longitude",""+loc.getLongitude());   
	            
	        }
	         
	        public void onProviderDisabled(String provider) {
	        // required for interface, not used
	        }
	         
	        public void onProviderEnabled(String provider) {
	        // required for interface, not used
	        }
	         
	        public void onStatusChanged(String provider, int status,
	        Bundle extras) {
	        // required for interface, not used
	        }
	    };
		
	    locmgr.requestLocationUpdates(LocationManager.GPS_PROVIDER,0,10000.0f,onLocationChange);
		
	}
	
	
//	private void gpsLocation() {
//    	// Starts with GPS, if no GPS then gets network location
//    	
//		LocationManager lm = (LocationManager) getSystemService(Context.LOCATION_SERVICE);  
//		List<String> providers = lm.getProviders(true);
//		Log.d("PANDROID providers count", "" + providers.size());
//
////		/* Loop over the array backwards, and if you get an accurate location, then break out the loop*/
////		Location loc = null;
////
////		for (int i=providers.size()-1; i>=0; i--) {
////			Log.d("PANDROID providers", providers.get(i));
////		    loc = lm.getLastKnownLocation(providers.get(i));
////		    if (loc != null) break;
////		}
////
////		if (loc != null) {
////			Log.d("PANDROID", "loc != null");
////			//if(latitude != loc.getLatitude() || longitude != loc.getLongitude()) {
////				lastGpsContactDateTime = getHumanDateTime(-1);
////			//`}
////			Log.d("LATITUDE",Double.valueOf(loc.getLatitude()).toString());
////			Log.d("LONGITUDE",Double.valueOf(loc.getLongitude()).toString());
////            putSharedData("PANDROID_DATA", "latitude", Double.valueOf(loc.getLatitude()).toString(), "float");
////            putSharedData("PANDROID_DATA", "longitude", Double.valueOf(loc.getLongitude()).toString(), "float");
////		}
////		else {
//			Criteria criteria = new Criteria();
//			criteria.setAccuracy(Criteria.ACCURACY_COARSE);
//			criteria.setPowerRequirement(Criteria.POWER_LOW);
//			criteria.setAltitudeRequired(false);
//			criteria.setBearingRequired(false);
//			criteria.setCostAllowed(true);
//			String bestProvider = lm.getBestProvider(criteria, true);
//			
//			// If not provider found, abort GPS retrieving
//			if (bestProvider == null) {
//				Log.e("LOCATION", "No location provider found!");
//				return;
//			}
//			
//			lm.requestLocationUpdates(bestProvider, Core.defaultInterval, 1000,
//				new LocationListener() {
//					public void onLocationChanged(Location location) {
//						Log.d("Best latitude", Double.valueOf(location.getLatitude()).toString());
//						putSharedData("PANDROID_DATA", "latitude",
//								Double.valueOf(location.getLatitude()).toString(), "float");
//						Log.d("Best longitude", Double.valueOf(location.getLongitude()).toString());
//				        putSharedData("PANDROID_DATA", "longitude",
//				        		Double.valueOf(location.getLongitude()).toString(), "float");
//					}
//					public void onStatusChanged(String s, int i, Bundle bundle) {
//						
//					}
//					public void onProviderEnabled(String s) {
//						// try switching to a different provider
//					}
//					public void onProviderDisabled(String s) {
//						putSharedData("PANDROID_DATA", "enabled_location_provider",
//							"disabled", "string");
//					}
//				});
//		//}
//		
//    }
    
    private void batteryLevel() {
    	
    	Intent batteryIntent = getApplicationContext().registerReceiver(null, new IntentFilter(Intent.ACTION_BATTERY_CHANGED));
    		int rawlevel = batteryIntent.getIntExtra("level", -1);
    		int scale = batteryIntent.getIntExtra("scale", -1);
    		//double level = -1;
    		if (rawlevel >= 0 && scale > 0) {
    			putSharedData("PANDROID_DATA", "batteryLevel", Integer.valueOf((rawlevel * 100) / scale).toString(), "integer");
    		}
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
        String gpsStatus = getSharedData("PANDROID_DATA", "gpsStatus", Core.defaultGpsStatus, "string");
        
        if(gpsStatus.equals("enabled")) {
        	Log.d("PANDROID AGENT", "ENABLED");
			gpsLocation();
        }
        else {
        	Log.d("PANDROID AGENT", "DISABLED");
            putSharedData("PANDROID_DATA", "latitude", "181.0", "float");
            putSharedData("PANDROID_DATA", "longitude", "181.0", "float");
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
		String memoryStatus = getSharedData("PANDROID_DATA", "memoryStatus", Core.defaultMemoryStatus, "string");
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
		
		putSharedData("PANDROID_DATA", "availableRamKb", "" + availableRamKb, "long");
		putSharedData("PANDROID_DATA", "totalRamKb", "" + totalRamKb, "long");
	}// end getMemoryStatus
	
	private void getTaskStatus() {
		String taskStatus = getSharedData("PANDROID_DATA", "taskStatus", Core.defaultTaskStatus, "string");
		String task = getSharedData("PANDROID_DATA", "task", Core.defaultTask, "string");
		String taskHumanName = getSharedData("PANDROID_DATA", "taskHumanName", Core.defaultTaskHumanName, "string");
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
	}//end getTaskStatus
	
	/**
	 * 	Retrieves the simID of the device if available
	 */
	private void getSimID(){
		
			String simID = getSharedData("PANDROID_DATA", "simID", Core.defaultSimID, "string");
		
			String serviceName = Context.TELEPHONY_SERVICE;
			TelephonyManager telephonyManager = (TelephonyManager) getApplicationContext().getSystemService(serviceName);
			simID = telephonyManager.getSimSerialNumber();
			putSharedData("PANDROID_DATA", "simID", simID, "string");
	}
	/**
	 * 	Retrieves the time in seconds since the device was switched on
	 */
	private void getUpTime(){
		long upTime = Core.defaultUpTime;
		upTime = SystemClock.elapsedRealtime()/1000;
		if(upTime != 0)
			putSharedData("PANDROID_DATA", "upTime", ""+upTime, "long");
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
			putSharedData("PANDROID_DATA", "networkOperator", networkOperator, "string");
	}
	/**
	 *  Retrieves the number of sent sms messages using the android messaging app only
	 */
	private void getSMSSent(){
		
		String SMSSent = ""+Core.defaultSMSSent;
		SMSSent = getSharedData("PANDROID_DATA", "SMSSent", ""+Core.defaultSMSSent, "integer");
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
		
		SMSSent =""+ totalMessages;
		
		if(SMSSent != null)
			putSharedData("PANDROID_DATA", "SMSSent", SMSSent, "integer");
		
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
			putSharedData("PANDROID_DATA", "networkType", networkType, "string");
		
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
			putSharedData("PANDROID_DATA", "phoneType", phoneType, "string");
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
            
			putSharedData("PANDROID_DATA", "incomingCalls", ""+incoming, "integer");
            putSharedData("PANDROID_DATA", "missedCalls", ""+missed, "integer");
            putSharedData("PANDROID_DATA", "outgoingCalls", ""+outgoing, "integer");
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
			String signalStrengthValue = ""+Core.defaultSignalStrength;
			if (signalStrength.isGsm()) {
                if (signalStrength.getGsmSignalStrength() != 99)
                    signalStrengthValue =""+ (signalStrength.getGsmSignalStrength() * 2 - 113);
                else
                    signalStrengthValue =""+ (signalStrength.getGsmSignalStrength());
            }
			else{
               signalStrengthValue ="" + (signalStrength.getCdmaDbm());
            }
			putSharedData("PANDROID_DATA", "signalStrength", signalStrengthValue, "integer");
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
			putSharedData("PANDROID_DATA", "receiveBytes", ""+receiveBytes, "long" );
			putSharedData("PANDROID_DATA", "transmitBytes", ""+transmitBytes, "long" );
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
			putSharedData("PANDROID_DATA", "roaming", "1", "integer" );
		else
			putSharedData("PANDROID_DATA", "roaming", "0", "integer" );
	}
	
	
	private void putSharedData(String preferenceName, String tokenName, String data, String type) {
		
		SharedPreferences agentPreferences = this.getSharedPreferences(
    			this.getString(R.string.const_string_preferences),
    			Activity.MODE_PRIVATE);
        
        Editor editor = agentPreferences.edit();
		
		if(type == "boolean") {
			editor.putBoolean(tokenName, Boolean.parseBoolean(data));
			editor.commit();
		}
		else if(type == "float") {
			editor.putFloat(tokenName, Float.parseFloat(data));
			editor.commit();
		}
		else if(type == "integer") {
			editor.putInt(tokenName, Integer.parseInt(data));
			editor.commit();
		}
		else if(type == "long") {
			editor.putLong(tokenName, Long.parseLong(data));
			editor.commit();
		}
		else if(type == "string") {
			editor.putString(tokenName, data);
			editor.commit();
		}
		
		editor.commit();
    }
    
    private String getSharedData(String preferenceName, String tokenName, String defaultValue, String type) {
		
    	SharedPreferences agentPreferences = this.getSharedPreferences(
    			this.getString(R.string.const_string_preferences),
    			Activity.MODE_PRIVATE);
		
		if(type == "boolean") {
			boolean a = agentPreferences.getBoolean(tokenName, Boolean.parseBoolean(defaultValue));
			return Boolean.valueOf(a).toString();
		}
		else if(type == "float") {
			float a = agentPreferences.getFloat(tokenName, Float.parseFloat(defaultValue));
			return Float.valueOf(a).toString();
		}
		else if(type == "integer") {
			int a = agentPreferences.getInt(tokenName, Integer.parseInt(defaultValue));
			return Integer.valueOf(a).toString();
		}
		else if(type == "long") {
			long a = agentPreferences.getLong(tokenName, Long.parseLong(defaultValue));
			return Long.valueOf(a).toString();
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
    
    public static void CancelNotification(Context ctx, int notifyId) {
	    String ns = Context.NOTIFICATION_SERVICE;
	    NotificationManager nMgr = (NotificationManager) ctx.getSystemService(ns);
	    nMgr.cancel(notifyId);
	}
  
    
    
//    ///////////////////////////////////////////
//    // Getting values from device functions
//    ///////////////////////////////////////////
//    
//    public class MyLocationListener implements LocationListener {
//    
//		@Override
//	    public void onLocationChanged(Location loc) {
//            putSharedData("PANDROID_DATA", "latitude", Double.valueOf(loc.getLatitude()).toString(), "float");
//            putSharedData("PANDROID_DATA", "longitude", Double.valueOf(loc.getLongitude()).toString(), "float");
//	    }
//	    
//	    @Override
//	    public void onProviderDisabled(String provider) {
//	    }
//	
//	    @Override
//	    public void onProviderEnabled(String provider) {
//	    }
//	
//	    
//		@Override
//	    public void onStatusChanged(String provider, int status, Bundle extras) {
//	    }
//
//    }/* End of Class MyLocationListener */
}
