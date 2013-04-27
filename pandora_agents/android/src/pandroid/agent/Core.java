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
    static volatile public String defaultInventoryReport = "disabled"; // "disabled" or "enabled"
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
    static volatile public long defaultBufferSize = 256;
    
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
    static volatile public long bufferSize = defaultBufferSize;
    
    static volatile public long lastContact = CONST_INVALID_CONTACT;
    static volatile public int contactError = CONST_CONTACT_ERROR;
    
    
    
    
    
    public Core() {
    	
    }
    
    static synchronized public void startAgentListener(Context context) {
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
    
    static synchronized public void stopAgentListener() {
    	if (am != null){
    		am.cancel(sender);
    		alarmEnabled = false;
    	}
    }
    
	static synchronized public void restartAgentListener(Context context) {
		stopAgentListener();
		startAgentListener(context);
	}
	
	static synchronized public void loadLastValues(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	
    		
    	latitude = HelperSharedPreferences.getSharedPreferencesFloat(con, "latitude", CONST_INVALID_COORDS);
    	longitude = HelperSharedPreferences.getSharedPreferencesFloat(con, "longitude", CONST_INVALID_COORDS);
    	batteryLevel = HelperSharedPreferences.getSharedPreferencesInt(con, "batteryLevel", CONST_INVALID_BATTERY_LEVEL);
    	orientation = HelperSharedPreferences.getSharedPreferencesFloat(con, "orientation", CONST_INVALID_ORIENTATION);
    	proximity = HelperSharedPreferences.getSharedPreferencesFloat(con, "proximity", CONST_INVALID_PROXIMITY);
    	taskStatus = HelperSharedPreferences.getSharedPreferencesString(con, "taskStatus", defaultTaskStatus);
		task = HelperSharedPreferences.getSharedPreferencesString(con, "task", defaultTask);
		taskHumanName = HelperSharedPreferences.getSharedPreferencesString(con, "taskHumanName", defaultTaskHumanName);
		taskRun = HelperSharedPreferences.getSharedPreferencesString(con, "taskRun", defaultTaskRun);
		memoryStatus = HelperSharedPreferences.getSharedPreferencesString(con, "memoryStatus", defaultMemoryStatus);
		availableRamKb = HelperSharedPreferences.getSharedPreferencesLong(con, "availableRamKb", defaultRam);
		totalRamKb = HelperSharedPreferences.getSharedPreferencesLong(con, "totalRamKb", defaultRam);
		lastContact = HelperSharedPreferences.getSharedPreferencesLong(con, "lastContact", defaultContact);
		contactError = HelperSharedPreferences.getSharedPreferencesInt(con, "contactError", defaultContactError);
		simID = HelperSharedPreferences.getSharedPreferencesString(con, "simID", defaultSimID);
		upTime = HelperSharedPreferences.getSharedPreferencesLong(con, "upTime", Core.defaultUpTime);
		SMSReceived = HelperSharedPreferences.getSharedPreferencesInt(con, "SMSReceived", defaultSMSReceived);
		SMSSent = HelperSharedPreferences.getSharedPreferencesInt(con, "SMSSent", defaultSMSSent);
		networkOperator = HelperSharedPreferences.getSharedPreferencesString(con, "networkOperator", defaultNetworkOperator);
		networkType = HelperSharedPreferences.getSharedPreferencesString(con, "networkType", defaultNetworkType);
		phoneType = HelperSharedPreferences.getSharedPreferencesString(con, "phoneType", defaultPhoneType);
		signalStrength = HelperSharedPreferences.getSharedPreferencesInt(con, "signalStrength", defaultSignalStrength);
		incomingCalls = HelperSharedPreferences.getSharedPreferencesInt(con, "incomingCalls", defaultIncomingCalls);
		missedCalls = HelperSharedPreferences.getSharedPreferencesInt(con, "missedCalls", defaultMissedCalls);
		outgoingCalls = HelperSharedPreferences.getSharedPreferencesInt(con, "outgoingCalls", defaultOutgoingCalls);
		receiveBytes = HelperSharedPreferences.getSharedPreferencesLong(con, "receiveBytes", defaultReceiveBytes);
		transmitBytes = HelperSharedPreferences.getSharedPreferencesLong(con, "transmitBytes", defaultTransmitBytes);
		helloSignal = HelperSharedPreferences.getSharedPreferencesInt(con, "helloSignal", defaultHelloSignal);
		roaming = HelperSharedPreferences.getSharedPreferencesInt(con, "roaming", defaultRoaming);
		bufferSize = HelperSharedPreferences.getSharedPreferencesLong(con, "bufferSize", defaultBufferSize);
		
    }// end loadLastValues
    
    static synchronized public void loadConf(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	
    		
		serverAddr = HelperSharedPreferences.getSharedPreferencesString(con, "serverAddr", defaultServerAddr);
		serverPort = HelperSharedPreferences.getSharedPreferencesString(con, "serverPort", defaultServerPort);
		interval = HelperSharedPreferences.getSharedPreferencesInt(con, "interval", defaultInterval);
		agentName = HelperSharedPreferences.getSharedPreferencesString(con, "agentName", defaultAgentName+"_"+Installation.id(context));
		mobileWebURL = HelperSharedPreferences.getSharedPreferencesString(con, "mobileWebURL", defaultmobileWebURL);
		gpsStatus = HelperSharedPreferences.getSharedPreferencesString(con, "gpsStatus", defaultGpsStatus);
		memoryStatus = HelperSharedPreferences.getSharedPreferencesString(con, "memoryStatus", defaultMemoryStatus);
		taskStatus = HelperSharedPreferences.getSharedPreferencesString(con, "taskStatus", defaultTaskStatus);
		task = HelperSharedPreferences.getSharedPreferencesString(con, "task", defaultTask);
		taskHumanName = HelperSharedPreferences.getSharedPreferencesString(con, "taskHumanName", defaultTaskHumanName);
		taskRun = HelperSharedPreferences.getSharedPreferencesString(con, "taskRun", defaultTaskRun);
		password = HelperSharedPreferences.getSharedPreferencesString(con, "password", defaultPassword);
		passwordCheck = HelperSharedPreferences.getSharedPreferencesString(con, "passwordCheck", defaultPasswordCheck);
		simIDReport = HelperSharedPreferences.getSharedPreferencesString(con, "simIDReport", defaultSimIDReport);
		DeviceUpTimeReport = HelperSharedPreferences.getSharedPreferencesString(con, "DeviceUpTimeReport", defaultDeviceUpTimeReport);
	    NetworkOperatorReport = HelperSharedPreferences.getSharedPreferencesString(con, "NetworkOperatorReport", defaultNetworkOperatorReport);
	    NetworkTypeReport = HelperSharedPreferences.getSharedPreferencesString(con, "NetworkTypeReport", defaultNetworkTypeReport);
	    PhoneTypeReport = HelperSharedPreferences.getSharedPreferencesString(con, "PhoneTypeReport", defaultPhoneTypeReport);
	    SignalStrengthReport = HelperSharedPreferences.getSharedPreferencesString(con, "SignalStrengthReport", defaultSignalStrengthReport);
	    ReceivedSMSReport = HelperSharedPreferences.getSharedPreferencesString(con, "ReceivedSMSReport", defaultReceivedSMSReport);
	    SentSMSReport = HelperSharedPreferences.getSharedPreferencesString(con, "SentSMSReport", defaultSentSMSReport);
	    IncomingCallsReport = HelperSharedPreferences.getSharedPreferencesString(con, "IncomingCallsReport", defaultIncomingCallsReport);
	    MissedCallsReport = HelperSharedPreferences.getSharedPreferencesString(con, "MissedCallsReport", defaultMissedCallsReport);
	    OutgoingCallsReport = HelperSharedPreferences.getSharedPreferencesString(con, "OutgoingCallsReport", defaultOutgoingCallsReport);
	    BytesReceivedReport = HelperSharedPreferences.getSharedPreferencesString(con, "BytesReceivedReport", defaultBytesReceivedReport);
	    BytesSentReport = HelperSharedPreferences.getSharedPreferencesString(con, "BytesSentReport", defaultBytesSentReport);
	    HelloSignalReport = HelperSharedPreferences.getSharedPreferencesString(con, "HelloSignalReport", defaultHelloSignalReport);
	    BatteryLevelReport = HelperSharedPreferences.getSharedPreferencesString(con, "BatteryLevelReport", defaultBatteryLevelReport);
	    RoamingReport = HelperSharedPreferences.getSharedPreferencesString(con, "RoamingReport", defaultRoamingReport);
	    InventoryReport = HelperSharedPreferences.getSharedPreferencesString(con, "InventoryReport", defaultInventoryReport);
	    NotificationCheck = HelperSharedPreferences.getSharedPreferencesString(con, "NotificationCheck", defaultNotificationCheck);
	    bufferSize = HelperSharedPreferences.getSharedPreferencesLong(con, "bufferSize", defaultBufferSize);
    }// end loadConf
    
    static synchronized public boolean updateConf(Context context) {
    	return updateConf(context, serverAddr, serverPort, interval, agentName,
    		gpsStatus, memoryStatus, taskStatus, task, taskHumanName, simID, simIDReport, upTime,
    		networkOperator, SMSReceived, SMSSent, networkType, phoneType, signalStrength,
    		incomingCalls, missedCalls, outgoingCalls, receiveBytes, transmitBytes, password, helloSignal,
    		passwordCheck, DeviceUpTimeReport, NetworkOperatorReport, NetworkTypeReport, PhoneTypeReport,
    		SignalStrengthReport, ReceivedSMSReport, SentSMSReport, IncomingCallsReport, MissedCallsReport,
    		OutgoingCallsReport, BytesReceivedReport, BytesSentReport, HelloSignalReport, BatteryLevelReport,
    		RoamingReport, roaming, mobileWebURL, InventoryReport, NotificationCheck, bufferSize
    		);
    	
    }// end updateConf
    
    static synchronized public boolean updateConf(Context context, String _serverAddr,
    	String _serverPort, int _interval, String _agentName, String _gpsStatus,
    	String _memoryStatus, String _taskStatus, String _task,
    	String _taskHumanName, String _simID, String _simIDReport, long _upTime, String _networkOperator,
    	int _smsReceived, int _smsSent, String _networkType, String _phoneType, int _signalStrength,
    	int _incomingCalls, int _missedCalls, int _outgoingCalls, long _receiveBytes, long _transmitBytes,
    	String _password, int _helloSignal, String _passwordCheck, String _DeviceUpTimeReport, String _NetworkOperatorReport,
    	String _NetworkTypeReport, String _PhoneTypeReport, String _SignalStrengthReport, String _ReceivedSMSReport,
    	String _SentSMSReport, String _IncomingCallsReport, String _MissedCallsReport, String _OutgoingCallsReport, String _BytesReceivedReport,
    	String _BytesSentReport, String _HelloSignalReport, String _BatteryLevelReport, String _RoamingReport, int _roaming, String _mobileWebURL,
    	String _InventoryReport, String _NotificationCheck, long _bufferSize) {
    	
    	if (con == null) {
    		con = context;
    	}
    	
		
		
    	HelperSharedPreferences.putSharedPreferencesString(con, "serverAddr", _serverAddr);
		HelperSharedPreferences.putSharedPreferencesString(con, "serverPort", _serverPort);
		HelperSharedPreferences.putSharedPreferencesInt(con, "interval", _interval);
		HelperSharedPreferences.putSharedPreferencesString(con, "agentName", _agentName);
		HelperSharedPreferences.putSharedPreferencesString(con, "gpsStatus", _gpsStatus);
		HelperSharedPreferences.putSharedPreferencesString(con, "memoryStatus", _memoryStatus);
		HelperSharedPreferences.putSharedPreferencesString(con, "taskStatus", _taskStatus);
		HelperSharedPreferences.putSharedPreferencesString(con, "task", _task);
		HelperSharedPreferences.putSharedPreferencesString(con, "taskHumanName", _taskHumanName);
		HelperSharedPreferences.putSharedPreferencesString(con, "simID", _simID);
		HelperSharedPreferences.putSharedPreferencesString(con, "simIDReport", _simIDReport);
		HelperSharedPreferences.putSharedPreferencesLong(con, "upTime", _upTime);
		HelperSharedPreferences.putSharedPreferencesString(con, "networkOperator", _networkOperator);
		HelperSharedPreferences.putSharedPreferencesInt(con, "SMSReceived", _smsReceived);
		HelperSharedPreferences.putSharedPreferencesInt(con, "SMSSent", _smsSent);
		HelperSharedPreferences.putSharedPreferencesString(con, "networkType", _networkType);
		HelperSharedPreferences.putSharedPreferencesString(con, "phoneType", _phoneType);
		HelperSharedPreferences.putSharedPreferencesInt(con, "signalStrength", _signalStrength);
		HelperSharedPreferences.putSharedPreferencesInt(con, "incomingCalls", _incomingCalls);
		HelperSharedPreferences.putSharedPreferencesInt(con, "missedCalls", _missedCalls);
		HelperSharedPreferences.putSharedPreferencesInt(con, "outgoingCalls", _outgoingCalls);
		HelperSharedPreferences.putSharedPreferencesLong(con,  "receiveBytes", _receiveBytes);
		HelperSharedPreferences.putSharedPreferencesLong(con,  "transmitBytes", _transmitBytes);
		HelperSharedPreferences.putSharedPreferencesString(con, "password", _password);
		HelperSharedPreferences.putSharedPreferencesString(con, "passwordCheck", _passwordCheck);
		HelperSharedPreferences.putSharedPreferencesInt(con, "helloSignal", _helloSignal);
		HelperSharedPreferences.putSharedPreferencesInt(con, "roaming", _roaming);
		HelperSharedPreferences.putSharedPreferencesString(con, "DeviceUpTimeReport", _DeviceUpTimeReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "NetworkOperatorReport", _NetworkOperatorReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "NetworkTypeReport", _NetworkTypeReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "PhoneTypeReport", _PhoneTypeReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "SignalStrengthReport", _SignalStrengthReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "ReceivedSMSReport", _ReceivedSMSReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "SentSMSReport", _SentSMSReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "IncomingCallsReport", _IncomingCallsReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "MissedCallsReport", _MissedCallsReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "OutgoingCallsReport", _OutgoingCallsReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "BytesReceivedReport", _BytesReceivedReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "BytesSentReport", _BytesSentReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "HelloSignalReport", _HelloSignalReport); 
		HelperSharedPreferences.putSharedPreferencesString(con, "BatteryLevelReport", _BatteryLevelReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "RoamingReport", _RoamingReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "InventoryReport", _InventoryReport);
		HelperSharedPreferences.putSharedPreferencesString(con, "NotificationCheck", _NotificationCheck);
		HelperSharedPreferences.putSharedPreferencesString(con, "mobileWebURL", _mobileWebURL);
		HelperSharedPreferences.putSharedPreferencesLong(con,  "bufferSize", _bufferSize);
		
		return true;
	}// end updateConf
    
    
   
    
    
//    //database//
//    
//    static synchronized public void loadLastValuesDatabase(Context context) {
//    	if (con == null) {
//    		con = context;
//    	}
//    	
//    	
//    	
//    		
//    	latitude = Float.parseFloat(getDatabaseValue(con, "latitude"));
//    	longitude = Float.parseFloat(getDatabaseValue(con, "longitude"));
//    	batteryLevel = Integer.parseInt(getDatabaseValue(con, "batteryLevel"));
//    	orientation = Float.parseFloat(getDatabaseValue(con, "orientation"));
//    	proximity = Float.parseFloat(getDatabaseValue(con, "proximity"));
//    	taskStatus = getDatabaseValue(con, "taskStatus");
//		task = getDatabaseValue(con, "task");
//		taskHumanName = getDatabaseValue(con, "taskHumanName");
//		taskRun = getDatabaseValue(con, "taskRun");
//		memoryStatus = getDatabaseValue(con, "memoryStatus");
//		availableRamKb = Long.parseLong(getDatabaseValue(con, "availableRamKb"));
//		totalRamKb = Long.parseLong(getDatabaseValue(con, "totalRamKb"));
//		lastContact = Long.parseLong(getDatabaseValue(con, "lastContact"));
//		contactError = Integer.parseInt(getDatabaseValue(con, "contactError"));
//		simID = getDatabaseValue(con, "simID");
//		upTime = Long.parseLong(getDatabaseValue(con, "upTime"));
//		SMSReceived = Integer.parseInt(getDatabaseValue(con, "SMSReceived"));
//		SMSSent = Integer.parseInt(getDatabaseValue(con, "SMSSent"));
//		networkOperator = getDatabaseValue(con, "networkOperator");
//		networkType = getDatabaseValue(con, "networkType");
//		phoneType = getDatabaseValue(con, "phoneType");
//		signalStrength = Integer.parseInt(getDatabaseValue(con, "signalStrength"));
//		incomingCalls = Integer.parseInt(getDatabaseValue(con, "incomingCalls"));
//		missedCalls = Integer.parseInt(getDatabaseValue(con, "missedCalls"));
//		outgoingCalls = Integer.parseInt(getDatabaseValue(con, "outgoingCalls"));
//		receiveBytes = Long.parseLong(getDatabaseValue(con, "receiveBytes"));
//		transmitBytes = Long.parseLong(getDatabaseValue(con, "transmitBytes"));
//		helloSignal = Integer.parseInt(getDatabaseValue(con, "helloSignal"));
//		roaming = Integer.parseInt(getDatabaseValue(con, "roaming"));
//		
//    }// end loadLastValues
//    
//    static synchronized public void loadConfDatabase(Context context) {
//    	if (con == null) {
//    		con = context;
//    	}
//    	
//    	
//		serverAddr = getDatabaseValue(con, "serverAddr");
//		serverPort = getDatabaseValue(con, "serverPort");
//		interval = Integer.parseInt(getDatabaseValue(con, "interval"));
//		agentName = getDatabaseValue(con, "agentName");
//		mobileWebURL = getDatabaseValue(con, "mobileWebURL");
//		gpsStatus = getDatabaseValue(con, "gpsStatus");
//		memoryStatus = getDatabaseValue(con, "memoryStatus");
//		taskStatus = getDatabaseValue(con, "taskStatus");
//		task = getDatabaseValue(con, "task");
//		taskHumanName = getDatabaseValue(con, "taskHumanName");
//		taskRun = getDatabaseValue(con, "taskRun");
//		password = getDatabaseValue(con, "password");
//		passwordCheck = getDatabaseValue(con, "passwordCheck");
//		simIDReport = getDatabaseValue(con, "simIDReport");
//		DeviceUpTimeReport  = getDatabaseValue(con, "DeviceUpTimeReport");
//	    NetworkOperatorReport = getDatabaseValue(con, "NetworkOperatorReport");
//	    NetworkTypeReport = getDatabaseValue(con, "NetworkTypeReport");
//	    PhoneTypeReport = getDatabaseValue(con, "PhoneTypeReport");
//	    SignalStrengthReport = getDatabaseValue(con, "SignalStrengthReport");
//	    ReceivedSMSReport = getDatabaseValue(con, "ReceivedSMSReport");
//	    SentSMSReport = getDatabaseValue(con, "SentSMSReport");
//	    IncomingCallsReport = getDatabaseValue(con, "IncomingCallsReport");
//	    MissedCallsReport = getDatabaseValue(con, "MissedCallsReport");
//	    OutgoingCallsReport = getDatabaseValue(con, "OutgoingCallsReport");
//	    BytesReceivedReport = getDatabaseValue(con, "BytesReceivedReport");
//	    BytesSentReport = getDatabaseValue(con, "BytesSentReport");
//	    HelloSignalReport = getDatabaseValue(con, "HelloSignalReport");
//	    BatteryLevelReport = getDatabaseValue(con, "BatteryLevelReport");
//	    RoamingReport = getDatabaseValue(con, "RoamingReport");
//	    InventoryReport = getDatabaseValue(con, "InventoryReport");
//	    NotificationCheck = getDatabaseValue(con, "NotificationCheck");
//    }// end loadConf
//    
//    static synchronized public boolean updateDatabase(Context context) {
//    	return updateDatabase(context, serverAddr, serverPort, interval, agentName,
//    		gpsStatus, memoryStatus, taskStatus, task, taskHumanName, simID, simIDReport, upTime,
//    		networkOperator, SMSReceived, SMSSent, networkType, phoneType, signalStrength,
//    		incomingCalls, missedCalls, outgoingCalls, receiveBytes, transmitBytes, password, helloSignal,
//    		passwordCheck, DeviceUpTimeReport, NetworkOperatorReport, NetworkTypeReport, PhoneTypeReport,
//    		SignalStrengthReport, ReceivedSMSReport, SentSMSReport, IncomingCallsReport, MissedCallsReport,
//    		OutgoingCallsReport, BytesReceivedReport, BytesSentReport, HelloSignalReport, BatteryLevelReport,
//    		RoamingReport, roaming, mobileWebURL, InventoryReport , NotificationCheck
//    		);
//    	
//    }// end updateConf
//    
//    static synchronized public boolean updateDatabase(Context context, String _serverAddr,
//    	String _serverPort, int _interval, String _agentName, String _gpsStatus,
//    	String _memoryStatus, String _taskStatus, String _task,
//    	String _taskHumanName, String _simID, String _simIDReport, long _upTime, String _networkOperator,
//    	int _smsReceived, int _smsSent, String _networkType, String _phoneType, int _signalStrength,
//    	int _incomingCalls, int _missedCalls, int _outgoingCalls, long _receiveBytes, long _transmitBytes,
//    	String _password, int _helloSignal, String _passwordCheck, String _DeviceUpTimeReport, String _NetworkOperatorReport,
//    	String _NetworkTypeReport, String _PhoneTypeReport, String _SignalStrengthReport, String _ReceivedSMSReport,
//    	String _SentSMSReport, String _IncomingCallsReport, String _MissedCallsReport, String _OutgoingCallsReport, String _BytesReceivedReport,
//    	String _BytesSentReport, String _HelloSignalReport, String _BatteryLevelReport, String _RoamingReport, int _roaming, String _mobileWebURL,
//    	String _InventoryReport, String _NotificationCheck) {
//    	
//    	if (con == null) {
//    		con = context;
//    	}
//    	
//				
//		
//		updateDatabaseValue(con,"serverAddr", _serverAddr);
//		updateDatabaseValue(con,"serverPort", _serverPort);
//		updateDatabaseValue(con,"interval", ""+_interval);
//		updateDatabaseValue(con,"agentName", _agentName);
//		updateDatabaseValue(con,"gpsStatus", _gpsStatus);
//		updateDatabaseValue(con,"memoryStatus", _memoryStatus);
//		updateDatabaseValue(con,"taskStatus", _taskStatus);
//		updateDatabaseValue(con,"task", _task);
//		updateDatabaseValue(con,"taskHumanName", _taskHumanName);
//		updateDatabaseValue(con,"simID", _simID);
//		updateDatabaseValue(con,"simIDReport", _simIDReport);
//		updateDatabaseValue(con,"upTime", ""+_upTime);
//		updateDatabaseValue(con,"networkOperator", _networkOperator);
//		updateDatabaseValue(con,"SMSReceived", ""+_smsReceived);
//		updateDatabaseValue(con,"SMSSent", ""+_smsSent);
//		updateDatabaseValue(con,"networkType", _networkType);
//		updateDatabaseValue(con,"phoneType", _phoneType);
//		updateDatabaseValue(con,"signalStrength", ""+_signalStrength);
//		updateDatabaseValue(con,"incomingCalls", ""+_incomingCalls);
//		updateDatabaseValue(con,"missedCalls", ""+_missedCalls);
//		updateDatabaseValue(con,"outgoingCalls", ""+_outgoingCalls);
//		updateDatabaseValue(con,"receiveBytes", ""+_receiveBytes);
//		updateDatabaseValue(con,"transmitBytes", ""+_transmitBytes);
//		updateDatabaseValue(con,"password", _password);
//		updateDatabaseValue(con,"passwordCheck", _passwordCheck);
//		updateDatabaseValue(con,"helloSignal", ""+_helloSignal);
//		updateDatabaseValue(con,"roaming", ""+_roaming);
//		updateDatabaseValue(con,"DeviceUpTimeReport", _DeviceUpTimeReport); 
//		updateDatabaseValue(con,"NetworkOperatorReport", _NetworkOperatorReport); 
//		updateDatabaseValue(con,"NetworkTypeReport", _NetworkTypeReport); 
//		updateDatabaseValue(con,"PhoneTypeReport", _PhoneTypeReport); 
//		updateDatabaseValue(con,"SignalStrengthReport", _SignalStrengthReport);
//		updateDatabaseValue(con,"ReceivedSMSReport", _ReceivedSMSReport);
//		updateDatabaseValue(con,"SentSMSReport", _SentSMSReport);
//		updateDatabaseValue(con,"IncomingCallsReport", _IncomingCallsReport); 
//		updateDatabaseValue(con,"MissedCallsReport", _MissedCallsReport); 
//		updateDatabaseValue(con,"OutgoingCallsReport", _OutgoingCallsReport); 
//		updateDatabaseValue(con,"BytesReceivedReport", _BytesReceivedReport); 
//		updateDatabaseValue(con,"BytesSentReport", _BytesSentReport); 
//		updateDatabaseValue(con,"HelloSignalReport", _HelloSignalReport); 
//		updateDatabaseValue(con,"BatteryLevelReport", _BatteryLevelReport);
//		updateDatabaseValue(con,"RoamingReport", _RoamingReport);
//		updateDatabaseValue(con,"InventoryReport", _InventoryReport);
//		updateDatabaseValue(con,"NotificationCheck", _NotificationCheck);
//		updateDatabaseValue(con,"mobileWebURL", _mobileWebURL);
//		
//		
//		return true;
//	}// end updateConf
//    
//    
//    
////  //Adds a new row "name" with the value "value"
////  	public static void addValue(Context context, String name, String value){
////  		db = new DataBaseHandler(con);
////  		
////  		DataHandler dh = new DataHandler(name, value);
////  		
////  		db.addValue(dh);
////  		
////  	}
//  	
//  	//Updates a given row "name" with a "value"
//  	public static synchronized void updateDatabaseValue(Context context, String name, String value){
//  		db = new DataBaseHandler(con, "pandroid", null, 1);
//  		//Retrieve id of row to update
//  		int id = getDataHandler(con, name).get_id();
//  		
//  		DataHandler dh = new DataHandler(id, name, value);
//  		
//  		db.updateValue(dh);
//  	}
//  	
//  	//Returns the DataHandler object of the given row "name"
//  	public static synchronized DataHandler getDataHandler(Context context, String name){
//  		db = new DataBaseHandler(con, "pandroid", null, 1);
//  		
//  		return db.getValue(name);
//  	}
//  	
//  	//Returns the value of the given row "name"
//  	public static synchronized String getDatabaseValue(Context context, String name){
//  		db = new DataBaseHandler(con, "pandroid", null, 1);
//  		
//  		return db.getValue(name).get_value();
//  		
//  	}
}
