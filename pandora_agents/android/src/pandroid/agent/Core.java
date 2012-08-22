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

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;

//import android.util.Log;

public class Core {
	
	//																	//
	//								CONSTANTS							//
	//																	//
	
	//The 181 is the first invalid value between 180 and -180 values.
	static final float CONST_INVALID_COORDS = 181;
	static final int CONST_INVALID_BATTERY_LEVEL = -1;
	//The -361 is the first invalid value between 360 and -360 values.
	static final int CONST_INVALID_ORIENTATION = -361;
	static final float CONST_INVALID_PROXIMITY = -1;
	static final long CONST_INVALID_CONTACT = -1;
	static final int CONST_CONTACT_ERROR = 0;
	
	//																	//
	//					  DEFAULT CONFIGURATION MODULES					//
	//																	//
	
    static volatile public String defaultServerAddr = "firefly.artica.es";  //master address
    static volatile public String defaultServerPort = "41121";
    static volatile public int defaultInterval = 300;
    static volatile public String defaultAgentName = "pandroid";
    static volatile public String defaultmobileWebURL = "firefly.artica.es/pandora_demo/mobile";
    static volatile public String defaultGpsStatus = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultBatteryLevelReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultMemoryStatus = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultTaskStatus = "disabled"; // "disabled" or "enabled"
    static volatile public String defaultSimIDReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultPasswordCheck = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultDeviceUpTimeReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultNetworkOperatorReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultNetworkTypeReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultPhoneTypeReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultSignalStrengthReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultReceivedSMSReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultSentSMSReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultIncomingCallsReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultMissedCallsReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultOutgoingCallsReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultBytesReceivedReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultBytesSentReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultHelloSignalReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultRoamingReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultInventoryReport = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultNotificationCheck = "enabled"; // "disabled" or "enabled"
    static volatile public boolean defaultHasSim = false;
    
    //																	//
    //						DEFAULT MODULE VALUES						//
    //																	//
    
    static volatile public String defaultTask = "";
    static volatile public String defaultTaskHumanName = "";
    static volatile public String defaultTaskRun = "false";
    static volatile public long defaultRam = 0;
    static volatile public long defaultContact = 0;
    static volatile public int defaultContactError = 0;
    static volatile public long defaultUpTime = 0;
    static volatile public String defaultSimID = "";
    static volatile public int defaultSMSReceived = 0;
    static volatile public int defaultSMSSent = 0;
    static volatile public String defaultNetworkOperator = "";
    static volatile public String defaultNetworkType = "";
    static volatile public String defaultPhoneType = "";
    static volatile public int defaultSignalStrength = 0;
    static volatile public int defaultIncomingCalls = 0;
    static volatile public int defaultMissedCalls = 0;
    static volatile public int defaultOutgoingCalls = 0;
    static volatile public long defaultReceiveBytes = 0;
    static volatile public long defaultTransmitBytes = 0;
    static volatile public int defaultHelloSignal = 2;
    static volatile public int defaultRoaming = 0;
    
    
    static volatile public String defaultPassword = "";
       
    static volatile public Context con = null;
    static volatile public AlarmManager am = null;
    static volatile public PendingIntent sender = null;
    static volatile public boolean alarmEnabled = false;
    
    //																//
    //                   CONFIGURATION VALUES						//
    //																//
    
    static volatile public String serverAddr = defaultServerAddr;
    static volatile public String serverPort  = defaultServerPort;
    static volatile public int interval = defaultInterval;
    static volatile public String agentName = defaultAgentName;
    static volatile public String mobileWebURL = defaultmobileWebURL;
    static volatile public String gpsStatus = defaultGpsStatus;
    static volatile public String memoryStatus = defaultMemoryStatus;
    static volatile public String taskStatus = defaultTaskStatus;
    static volatile public String task = defaultTask;
    static volatile public String taskHumanName = defaultTaskHumanName;
    static volatile public String simIDReport = defaultSimIDReport;
    static volatile public String passwordCheck = defaultPasswordCheck;
    static volatile public String DeviceUpTimeReport = defaultDeviceUpTimeReport;
    static volatile public String NetworkOperatorReport = defaultNetworkOperatorReport;
    static volatile public String NetworkTypeReport = defaultNetworkTypeReport;
    static volatile public String PhoneTypeReport = defaultPhoneTypeReport;
    static volatile public String SignalStrengthReport = defaultSignalStrengthReport;
    static volatile public String ReceivedSMSReport = defaultReceivedSMSReport;
    static volatile public String SentSMSReport = defaultSentSMSReport;
    static volatile public String IncomingCallsReport = defaultIncomingCallsReport;
    static volatile public String MissedCallsReport = defaultMissedCallsReport;
    static volatile public String OutgoingCallsReport = defaultOutgoingCallsReport;
    static volatile public String BytesReceivedReport = defaultBytesReceivedReport;
    static volatile public String BytesSentReport = defaultBytesSentReport;
    static volatile public String HelloSignalReport = defaultHelloSignalReport;
    static volatile public String BatteryLevelReport = defaultHelloSignalReport;
    static volatile public String RoamingReport = defaultRoamingReport;
    static volatile public String InventoryReport = defaultRoamingReport;
    static volatile public String NotificationCheck = defaultNotificationCheck;
    
    static volatile public boolean hasSim = defaultHasSim;
    
    static volatile public String password = defaultPassword;
   
    //																//
    //						 MODULES VALUES							//
    //																//
    
    static volatile public float latitude = CONST_INVALID_COORDS;
    static volatile public float longitude = CONST_INVALID_COORDS;
    static volatile public int batteryLevel = CONST_INVALID_BATTERY_LEVEL;
    static volatile public float orientation = CONST_INVALID_ORIENTATION;
    static volatile public float proximity = CONST_INVALID_PROXIMITY;
    static volatile public String taskRun = defaultTaskRun;
    static volatile public long availableRamKb = defaultRam;
    static volatile public long totalRamKb = defaultRam;
    static volatile public String simID = defaultSimID;
    static volatile public long upTime = defaultUpTime;
    static volatile public int SMSReceived = defaultSMSReceived;
    static volatile public int SMSSent = defaultSMSSent;
    static volatile public String networkOperator = defaultNetworkOperator;
    static volatile public String networkType = defaultNetworkType;
    static volatile public String phoneType = defaultPhoneType;
    static volatile public int signalStrength = defaultSignalStrength;
    static volatile public int incomingCalls = defaultIncomingCalls;
    static volatile public int missedCalls = defaultMissedCalls;
    static volatile public int outgoingCalls = defaultOutgoingCalls;
    static volatile public long receiveBytes = defaultReceiveBytes;
    static volatile public long transmitBytes = defaultTransmitBytes;
    static volatile public int helloSignal = defaultHelloSignal;
    static volatile public int roaming = defaultRoaming;
    
    static volatile public long lastContact = CONST_INVALID_CONTACT;
    static volatile public int contactError = CONST_CONTACT_ERROR;
        
    public Core() {
    	
    }
    
    static public void startAgentListener(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	loadConf(con);
    	
		Intent intentReceiver = new Intent(con, EventReceiver.class);
		sender = PendingIntent.getBroadcast(con, 0, intentReceiver, 0);
	        
		am = (AlarmManager)con.getSystemService(Context.ALARM_SERVICE);
    	
    	alarmEnabled = true;
    	am.setRepeating(AlarmManager.RTC_WAKEUP, System.currentTimeMillis(), (interval * 1000), sender);
    	
    }// end startAgentListener
    
    static public void stopAgentListener() {
    	if (am != null){
    		am.cancel(sender);
    		alarmEnabled = false;
    	}
    }
    
	static public void restartAgentListener(Context context) {
		stopAgentListener();
		startAgentListener(context);
	}
	
	static public void loadLastValues(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	SharedPreferences agentPreferences = con.getSharedPreferences(
			con.getString(R.string.const_string_preferences),
			Activity.MODE_PRIVATE);
    		
    	latitude = agentPreferences.getFloat("latitude", CONST_INVALID_COORDS);
    	longitude = agentPreferences.getFloat("longitude", CONST_INVALID_COORDS);
    	batteryLevel = agentPreferences.getInt("batteryLevel", CONST_INVALID_BATTERY_LEVEL);
    	orientation = agentPreferences.getFloat("orientation", CONST_INVALID_ORIENTATION);
    	proximity = agentPreferences.getFloat("proximity", CONST_INVALID_PROXIMITY);
    	taskStatus = agentPreferences.getString("taskStatus", defaultTaskStatus);
		task = agentPreferences.getString("task", defaultTask);
		taskHumanName = agentPreferences.getString("taskHumanName", defaultTaskHumanName);
		taskRun = agentPreferences.getString("taskRun", defaultTaskRun);
		memoryStatus = agentPreferences.getString("memoryStatus", defaultMemoryStatus);
		availableRamKb = agentPreferences.getLong("availableRamKb", defaultRam);
		totalRamKb = agentPreferences.getLong("totalRamKb", defaultRam);
		lastContact = agentPreferences.getLong("lastContact", defaultContact);
		contactError = agentPreferences.getInt("contactError", defaultContactError);
		simID = agentPreferences.getString("simID", defaultSimID);
		upTime = agentPreferences.getLong("upTime", Core.defaultUpTime);
		SMSReceived = agentPreferences.getInt("SMSReceived", defaultSMSReceived);
		SMSSent = agentPreferences.getInt("SMSSent", defaultSMSSent);
		networkOperator = agentPreferences.getString("networkOperator", defaultNetworkOperator);
		networkType = agentPreferences.getString("networkType", defaultNetworkType);
		phoneType = agentPreferences.getString("phoneType", defaultPhoneType);
		signalStrength = agentPreferences.getInt("signalStrength", defaultSignalStrength);
		incomingCalls = agentPreferences.getInt("incomingCalls", defaultIncomingCalls);
		missedCalls = agentPreferences.getInt("missedCalls", defaultMissedCalls);
		outgoingCalls = agentPreferences.getInt("outgoingCalls", defaultOutgoingCalls);
		receiveBytes = agentPreferences.getLong("receiveBytes", defaultReceiveBytes);
		transmitBytes = agentPreferences.getLong("transmitBytes", defaultTransmitBytes);
		helloSignal = agentPreferences.getInt("helloSignal", defaultHelloSignal);
		roaming = agentPreferences.getInt("roaming", defaultRoaming);
		
    }// end loadLastValues
    
    static public void loadConf(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	SharedPreferences agentPreferences = con.getSharedPreferences(
    		con.getString(R.string.const_string_preferences),
    		Activity.MODE_PRIVATE);
    		
		serverAddr = agentPreferences.getString("serverAddr", defaultServerAddr);
		serverPort = agentPreferences.getString("serverPort", defaultServerPort);
		interval = agentPreferences.getInt("interval", defaultInterval);
		agentName = agentPreferences.getString("agentName", defaultAgentName+"_"+Installation.id(context));
		mobileWebURL = agentPreferences.getString("mobileWebURL", defaultmobileWebURL);
		gpsStatus = agentPreferences.getString("gpsStatus", defaultGpsStatus);
		memoryStatus = agentPreferences.getString("memoryStatus", defaultMemoryStatus);
		taskStatus = agentPreferences.getString("taskStatus", defaultTaskStatus);
		task = agentPreferences.getString("task", defaultTask);
		taskHumanName = agentPreferences.getString("taskHumanName", defaultTaskHumanName);
		taskRun = agentPreferences.getString("taskRun", defaultTaskRun);
		password = agentPreferences.getString("password", defaultPassword);
		passwordCheck = agentPreferences.getString("passwordCheck", defaultPasswordCheck);
		simIDReport = agentPreferences.getString("simIDReport", defaultSimIDReport);
		DeviceUpTimeReport = agentPreferences.getString("DeviceUpTimeReport", defaultDeviceUpTimeReport);
	    NetworkOperatorReport = agentPreferences.getString("NetworkOperatorReport", defaultNetworkOperatorReport);
	    NetworkTypeReport = agentPreferences.getString("NetworkTypeReport", defaultNetworkTypeReport);
	    PhoneTypeReport = agentPreferences.getString("PhoneTypeReport", defaultPhoneTypeReport);
	    SignalStrengthReport = agentPreferences.getString("SignalStrengthReport", defaultSignalStrengthReport);
	    ReceivedSMSReport = agentPreferences.getString("ReceivedSMSReport", defaultReceivedSMSReport);
	    SentSMSReport = agentPreferences.getString("SentSMSReport", defaultSentSMSReport);
	    IncomingCallsReport = agentPreferences.getString("IncomingCallsReport", defaultIncomingCallsReport);
	    MissedCallsReport = agentPreferences.getString("MissedCallsReport", defaultMissedCallsReport);
	    OutgoingCallsReport = agentPreferences.getString("OutgoingCallsReport", defaultOutgoingCallsReport);
	    BytesReceivedReport = agentPreferences.getString("BytesReceivedReport", defaultBytesReceivedReport);
	    BytesSentReport = agentPreferences.getString("BytesSentReport", defaultBytesSentReport);
	    HelloSignalReport = agentPreferences.getString("HelloSignalReport", defaultHelloSignalReport);
	    BatteryLevelReport = agentPreferences.getString("BatteryLevelReport", defaultBatteryLevelReport);
	    RoamingReport = agentPreferences.getString("RoamingReport", defaultRoamingReport);
	    InventoryReport = agentPreferences.getString("InventoryReport", defaultInventoryReport);
	    NotificationCheck = agentPreferences.getString("NotificationCheck", defaultNotificationCheck);
    }// end loadConf
    
    static public boolean updateConf(Context context) {
    	return updateConf(context, serverAddr, serverPort, interval, agentName,
    		gpsStatus, memoryStatus, taskStatus, task, taskHumanName, simID, simIDReport, upTime,
    		networkOperator, SMSReceived, SMSSent, networkType, phoneType, signalStrength,
    		incomingCalls, missedCalls, outgoingCalls, receiveBytes, transmitBytes, password, helloSignal,
    		passwordCheck, DeviceUpTimeReport, NetworkOperatorReport, NetworkTypeReport, PhoneTypeReport,
    		SignalStrengthReport, ReceivedSMSReport, SentSMSReport, IncomingCallsReport, MissedCallsReport,
    		OutgoingCallsReport, BytesReceivedReport, BytesSentReport, HelloSignalReport, BatteryLevelReport,
    		RoamingReport, roaming, mobileWebURL, InventoryReport , NotificationCheck
    		);
    	
    }// end updateConf
    
    static public boolean updateConf(Context context, String _serverAddr,
    	String _serverPort, int _interval, String _agentName, String _gpsStatus,
    	String _memoryStatus, String _taskStatus, String _task,
    	String _taskHumanName, String _simID, String _simIDReport, long _upTime, String _networkOperator,
    	int _smsReceived, int _smsSent, String _networkType, String _phoneType, int _signalStrength,
    	int _incomingCalls, int _missedCalls, int _outgoingCalls, long _receiveBytes, long _transmitBytes,
    	String _password, int _helloSignal, String _passwordCheck, String _DeviceUpTimeReport, String _NetworkOperatorReport,
    	String _NetworkTypeReport, String _PhoneTypeReport, String _SignalStrengthReport, String _ReceivedSMSReport,
    	String _SentSMSReport, String _IncomingCallsReport, String _MissedCallsReport, String _OutgoingCallsReport, String _BytesReceivedReport,
    	String _BytesSentReport, String _HelloSignalReport, String _BatteryLevelReport, String _RoamingReport, int _roaming, String _mobileWebURL,
    	String _InventoryReport, String _NotificationCheck) {
    	
    	if (con == null) {
    		con = context;
    	}
    	
		SharedPreferences agentPreferences = con.getSharedPreferences(
			con.getString(R.string.const_string_preferences),
			Activity.MODE_PRIVATE);
		SharedPreferences.Editor editor = agentPreferences.edit();
		
		editor.putString("serverAddr", _serverAddr);
		editor.putString("serverPort", _serverPort);
		editor.putInt("interval", _interval);
		editor.putString("agentName", _agentName);
		editor.putString("gpsStatus", _gpsStatus);
		editor.putString("memoryStatus", _memoryStatus);
		editor.putString("taskStatus", _taskStatus);
		editor.putString("task", _task);
		editor.putString("taskHumanName", _taskHumanName);
		editor.putString("simID", _simID);
		editor.putString("simIDReport", _simIDReport);
		editor.putLong("UpTime", _upTime);
		editor.putString("networkOperator", _networkOperator);
		editor.putInt("SMSReceived", _smsReceived);
		editor.putInt("SMSSent", _smsSent);
		editor.putString("networkType", _networkType);
		editor.putString("phoneType", _phoneType);
		editor.putInt("signalStrength", _signalStrength);
		editor.putInt("incomingCalls", _incomingCalls);
		editor.putInt("missedCalls", _missedCalls);
		editor.putInt("outgoingCalls", _outgoingCalls);
		editor.putLong("receiveBytes", _receiveBytes);
		editor.putLong("transmitBytes", _transmitBytes);
		editor.putString("password", _password);
		editor.putString("passwordCheck", _passwordCheck);
		editor.putInt("helloSignal", _helloSignal);
		editor.putInt("roaming", _roaming);
		editor.putString("DeviceUpTimeReport", _DeviceUpTimeReport); 
		editor.putString("NetworkOperatorReport", _NetworkOperatorReport); 
		editor.putString("NetworkTypeReport", _NetworkTypeReport); 
		editor.putString("PhoneTypeReport", _PhoneTypeReport); 
		editor.putString("SignalStrengthReport", _SignalStrengthReport);
		editor.putString("ReceivedSMSReport", _ReceivedSMSReport);
		editor.putString("SentSMSReport", _SentSMSReport);
		editor.putString("IncomingCallsReport", _IncomingCallsReport); 
		editor.putString("MissedCallsReport", _MissedCallsReport); 
		editor.putString("OutgoingCallsReport", _OutgoingCallsReport); 
		editor.putString("BytesReceivedReport", _BytesReceivedReport); 
		editor.putString("BytesSentReport", _BytesSentReport); 
		editor.putString("HelloSignalReport", _HelloSignalReport); 
		editor.putString("BatteryLevelReport", _BatteryLevelReport);
		editor.putString("RoamingReport", _RoamingReport);
		editor.putString("InventoryReport", _InventoryReport);
		editor.putString("NotificationCheck", _NotificationCheck);
		editor.putString("mobileWebURL", _mobileWebURL);
		
		if (editor.commit()) {
			return true;
		}
		return false;
	}// end updateConf
}
