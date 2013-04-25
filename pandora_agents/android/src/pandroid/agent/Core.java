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

import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;

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
    
    
    static DataBaseHandler db;
    
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
    	
    	
    	
    		
    	latitude = Float.parseFloat(getValue(con, "latitude"));
    	longitude = Float.parseFloat(getValue(con, "longitude"));
    	batteryLevel = Integer.parseInt(getValue(con, "batteryLevel"));
    	orientation = Float.parseFloat(getValue(con, "orientation"));
    	proximity = Float.parseFloat(getValue(con, "proximity"));
    	taskStatus = getValue(con, "taskStatus");
		task = getValue(con, "task");
		taskHumanName = getValue(con, "taskHumanName");
		taskRun = getValue(con, "taskRun");
		memoryStatus = getValue(con, "memoryStatus");
		availableRamKb = Long.parseLong(getValue(con, "availableRamKb"));
		totalRamKb = Long.parseLong(getValue(con, "totalRamKb"));
		lastContact = Long.parseLong(getValue(con, "lastContact"));
		contactError = Integer.parseInt(getValue(con, "contactError"));
		simID = getValue(con, "simID");
		upTime = Long.parseLong(getValue(con, "upTime"));
		SMSReceived = Integer.parseInt(getValue(con, "SMSReceived"));
		SMSSent = Integer.parseInt(getValue(con, "SMSSent"));
		networkOperator = getValue(con, "networkOperator");
		networkType = getValue(con, "networkType");
		phoneType = getValue(con, "phoneType");
		signalStrength = Integer.parseInt(getValue(con, "signalStrength"));
		incomingCalls = Integer.parseInt(getValue(con, "incomingCalls"));
		missedCalls = Integer.parseInt(getValue(con, "missedCalls"));
		outgoingCalls = Integer.parseInt(getValue(con, "outgoingCalls"));
		receiveBytes = Long.parseLong(getValue(con, "receiveBytes"));
		transmitBytes = Long.parseLong(getValue(con, "transmitBytes"));
		helloSignal = Integer.parseInt(getValue(con, "helloSignal"));
		roaming = Integer.parseInt(getValue(con, "roaming"));
		
    }// end loadLastValues
    
    static public void loadConf(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	
		serverAddr = getValue(con, "serverAddr");
		serverPort = getValue(con, "serverPort");
		interval = Integer.parseInt(getValue(con, "interval"));
		agentName = getValue(con, "agentName");
		mobileWebURL = getValue(con, "mobileWebURL");
		gpsStatus = getValue(con, "gpsStatus");
		memoryStatus = getValue(con, "memoryStatus");
		taskStatus = getValue(con, "taskStatus");
		task = getValue(con, "task");
		taskHumanName = getValue(con, "taskHumanName");
		taskRun = getValue(con, "taskRun");
		password = getValue(con, "password");
		passwordCheck = getValue(con, "passwordCheck");
		simIDReport = getValue(con, "simIDReport");
		DeviceUpTimeReport  = getValue(con, "DeviceUpTimeReport");
	    NetworkOperatorReport = getValue(con, "NetworkOperatorReport");
	    NetworkTypeReport = getValue(con, "NetworkTypeReport");
	    PhoneTypeReport = getValue(con, "PhoneTypeReport");
	    SignalStrengthReport = getValue(con, "SignalStrengthReport");
	    ReceivedSMSReport = getValue(con, "ReceivedSMSReport");
	    SentSMSReport = getValue(con, "SentSMSReport");
	    IncomingCallsReport = getValue(con, "IncomingCallsReport");
	    MissedCallsReport = getValue(con, "MissedCallsReport");
	    OutgoingCallsReport = getValue(con, "OutgoingCallsReport");
	    BytesReceivedReport = getValue(con, "BytesReceivedReport");
	    BytesSentReport = getValue(con, "BytesSentReport");
	    HelloSignalReport = getValue(con, "HelloSignalReport");
	    BatteryLevelReport = getValue(con, "BatteryLevelReport");
	    RoamingReport = getValue(con, "RoamingReport");
	    InventoryReport = getValue(con, "InventoryReport");
	    NotificationCheck = getValue(con, "NotificationCheck");
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
    	
				
		
		updateValue(con,"serverAddr", _serverAddr);
		updateValue(con,"serverPort", _serverPort);
		updateValue(con,"interval", ""+_interval);
		updateValue(con,"agentName", _agentName);
		updateValue(con,"gpsStatus", _gpsStatus);
		updateValue(con,"memoryStatus", _memoryStatus);
		updateValue(con,"taskStatus", _taskStatus);
		updateValue(con,"task", _task);
		updateValue(con,"taskHumanName", _taskHumanName);
		updateValue(con,"simID", _simID);
		updateValue(con,"simIDReport", _simIDReport);
		updateValue(con,"upTime", ""+_upTime);
		updateValue(con,"networkOperator", _networkOperator);
		updateValue(con,"SMSReceived", ""+_smsReceived);
		updateValue(con,"SMSSent", ""+_smsSent);
		updateValue(con,"networkType", _networkType);
		updateValue(con,"phoneType", _phoneType);
		updateValue(con,"signalStrength", ""+_signalStrength);
		updateValue(con,"incomingCalls", ""+_incomingCalls);
		updateValue(con,"missedCalls", ""+_missedCalls);
		updateValue(con,"outgoingCalls", ""+_outgoingCalls);
		updateValue(con,"receiveBytes", ""+_receiveBytes);
		updateValue(con,"transmitBytes", ""+_transmitBytes);
		updateValue(con,"password", _password);
		updateValue(con,"passwordCheck", _passwordCheck);
		updateValue(con,"helloSignal", ""+_helloSignal);
		updateValue(con,"roaming", ""+_roaming);
		updateValue(con,"DeviceUpTimeReport", _DeviceUpTimeReport); 
		updateValue(con,"NetworkOperatorReport", _NetworkOperatorReport); 
		updateValue(con,"NetworkTypeReport", _NetworkTypeReport); 
		updateValue(con,"PhoneTypeReport", _PhoneTypeReport); 
		updateValue(con,"SignalStrengthReport", _SignalStrengthReport);
		updateValue(con,"ReceivedSMSReport", _ReceivedSMSReport);
		updateValue(con,"SentSMSReport", _SentSMSReport);
		updateValue(con,"IncomingCallsReport", _IncomingCallsReport); 
		updateValue(con,"MissedCallsReport", _MissedCallsReport); 
		updateValue(con,"OutgoingCallsReport", _OutgoingCallsReport); 
		updateValue(con,"BytesReceivedReport", _BytesReceivedReport); 
		updateValue(con,"BytesSentReport", _BytesSentReport); 
		updateValue(con,"HelloSignalReport", _HelloSignalReport); 
		updateValue(con,"BatteryLevelReport", _BatteryLevelReport);
		updateValue(con,"RoamingReport", _RoamingReport);
		updateValue(con,"InventoryReport", _InventoryReport);
		updateValue(con,"NotificationCheck", _NotificationCheck);
		updateValue(con,"mobileWebURL", _mobileWebURL);
		
		
		return true;
	}// end updateConf
    
    
    
    /*
  //Initialize database
  	public static void initDatabase(Context context){
  		if (con == null) {
      		con = context;
      	}
  	db = new DataBaseHandler(con);
  	
  	addValue(con,"serverAddr",defaultServerAddr);
  	addValue(con,"serverPort",defaultServerPort);
  	addValue(con,"interval",""+defaultInterval);
  	addValue(con,"agentName",defaultAgentName+"_"+Installation.id(context));
  	addValue(con,"mobileWebURL",defaultmobileWebURL);
  	addValue(con,"gpsStatus",defaultGpsStatus);
  	addValue(con,"memoryStatus",defaultMemoryStatus);
  	addValue(con,"taskStatus",defaultTaskStatus);
  	addValue(con,"task",defaultTask);
  	addValue(con,"taskHumanName",defaultTaskHumanName);
  	addValue(con,"simIDReport",defaultSimIDReport);
  	addValue(con,"passwordCheck",defaultPasswordCheck);
  	addValue(con,"DeviceUpTimeReport",defaultDeviceUpTimeReport);
  	addValue(con,"NetworkOperatorReport",defaultNetworkOperatorReport);
  	addValue(con,"NetworkTypeReport",defaultNetworkTypeReport);
  	addValue(con,"PhoneTypeReport",defaultPhoneTypeReport);
  	addValue(con,"SignalStrengthReport",defaultSignalStrengthReport);
  	addValue(con,"ReceivedSMSReport",defaultReceivedSMSReport);
  	addValue(con,"SentSMSReport",defaultSentSMSReport);
  	addValue(con,"IncomingCallsReport",defaultIncomingCallsReport);
  	addValue(con,"MissedCallsReport",defaultMissedCallsReport);
  	addValue(con,"OutgoingCallsReport",defaultOutgoingCallsReport);
  	addValue(con,"BytesReceivedReport",defaultBytesReceivedReport);
  	addValue(con,"BytesSentReport",defaultBytesSentReport);
  	addValue(con,"HelloSignalReport",defaultHelloSignalReport);
  	addValue(con,"BatteryLevelReport",defaultHelloSignalReport);
  	addValue(con,"RoamingReport",defaultRoamingReport);
  	addValue(con,"InventoryReport",defaultRoamingReport);
  	addValue(con,"NotificationCheck",defaultNotificationCheck);
  	
  	addValue(con,"hasSim", ""+defaultHasSim);
      
  	addValue(con,"password",defaultPassword);
     
  	addValue(con,"latitude",""+CONST_INVALID_COORDS);
  	addValue(con,"longitude",""+CONST_INVALID_COORDS);
  	addValue(con,"batteryLevel",""+CONST_INVALID_BATTERY_LEVEL);
  	addValue(con,"orientation",""+CONST_INVALID_ORIENTATION);
  	addValue(con,"proximity",""+CONST_INVALID_PROXIMITY);
  	addValue(con,"taskRun",defaultTaskRun);
  	addValue(con,"availableRamKb",""+defaultRam);
  	addValue(con,"totalRamKb",""+defaultRam);
  	addValue(con,"simID",defaultSimID);
  	addValue(con,"upTime",""+defaultUpTime);
  	addValue(con,"SMSReceived",""+defaultSMSReceived);
  	addValue(con,"SMSSent",""+defaultSMSSent);
  	addValue(con,"networkOperator",defaultNetworkOperator);
  	addValue(con,"networkType",defaultNetworkType);
  	addValue(con,"phoneType",defaultPhoneType);
  	addValue(con,"signalStrength",""+defaultSignalStrength);
  	addValue(con,"incomingCalls",""+defaultIncomingCalls);
  	addValue(con,"missedCalls",""+defaultMissedCalls);
  	addValue(con,"outgoingCalls",""+defaultOutgoingCalls);
  	addValue(con,"receiveBytes",""+defaultReceiveBytes);
  	addValue(con,"transmitBytes",""+defaultTransmitBytes);
  	addValue(con,"helloSignal",""+defaultHelloSignal);
  	addValue(con,"roaming",""+defaultRoaming);
      
  	addValue(con,"lastContact",""+CONST_INVALID_CONTACT);
  	addValue(con,"contactError",""+CONST_CONTACT_ERROR);
      
  	}
  	
  	//Adds a new row "name" with the value "value"
  	public static void addValue(Context context, String name, String value){
  		db = new DataBaseHandler(con);
  		
  		DataHandler dh = new DataHandler(name, value);
  		
  		db.addValue(dh);
  		
  	}
  	*/
  	//Updates a given row "name" with a "value"
  	public static synchronized void updateValue(Context context, String name, String value){
  		db = new DataBaseHandler(con, "pandroid", null, 1);
  		//Retrieve id of row to update
  		int id = getDataHandler(con, name).get_id();
  		
  		DataHandler dh = new DataHandler(id, name, value);
  		
  		db.updateValue(dh);
  	}
  	
  	//Returns the DataHandler object of the given row "name"
  	public static synchronized DataHandler getDataHandler(Context context, String name){
  		db = new DataBaseHandler(con, "pandroid", null, 1);
  		
  		return db.getValue(name);
  	}
  	
  	//Returns the value of the given row "name"
  	public static synchronized String getValue(Context context, String name){
  		db = new DataBaseHandler(con, "pandroid", null, 1);
  		
  		return db.getValue(name).get_value();
  		
  	}
  	
  	
  	
}
