package pandroid.agent;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;


//import android.util.Log;

public class Core {
	//The 181 is the first invalid value between 180 and -180 values.
	static final float CONST_INVALID_COORDS = 181;
	static final int CONST_INVALID_BATTERY_LEVEL = -1;
	//The -361 is the first invalid value between 360 and -360 values.
	static final int CONST_INVALID_ORIENTATION = -361;
	static final float CONST_INVALID_PROXIMITY = -1;
	static final long CONST_INVALID_CONTACT = -1;
	static final int CONST_CONTACT_ERROR = 0;
	
    static volatile public String defaultServerAddr = "farscape.artica.es";  //master address
    static volatile public String defaultServerPort = "41121";
    static volatile public int defaultInterval = 300;
    static volatile public String defaultAgentName = "pandroid";
    static volatile public String defaultGpsStatus = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultMemoryStatus = "enabled"; // "disabled" or "enabled"
    static volatile public String defaultTaskStatus = "disabled"; // "disabled" or "enabled"
    static volatile public String defaultTask = "";
    static volatile public String defaultTaskHumanName = "";
    static volatile public String defaultTaskRun = "false";
    static volatile public long defaultRam = 0;
    static volatile public long defaultContact = 0;
    static volatile public int defaultContactError = 0;
    static volatile public String defaultSimID = "";
    static volatile public long defaultUpTime = 0;
    
    static volatile public int defaultSMSReceived = 0;
    static volatile public String defaultMobileOperator = "";
    
    static volatile public Context con = null;
    static volatile public AlarmManager am = null;
    static volatile public PendingIntent sender = null;
    static volatile public boolean alarmEnabled = false;
    
    static volatile public String serverAddr = defaultServerAddr;
    static volatile public String serverPort  = defaultServerPort;
    static volatile public int interval = defaultInterval;
    static volatile public String agentName = defaultAgentName;
    static volatile public String gpsStatus = defaultGpsStatus;
    static volatile public String memoryStatus = defaultMemoryStatus;
    static volatile public String taskStatus = defaultTaskStatus;
    static volatile public String task = defaultTask;
    static volatile public String taskHumanName = defaultTaskHumanName;
    static volatile public String simID = "";  //fix
    static volatile public long upTime = defaultUpTime; //mark device uptime including deep sleep
    static volatile public int SMSReceived = defaultSMSReceived;
    static volatile public String networkOperator = defaultMobileOperator;
    
    static volatile public float latitude = CONST_INVALID_COORDS;
    static volatile public float longitude = CONST_INVALID_COORDS;
    static volatile public int batteryLevel = CONST_INVALID_BATTERY_LEVEL;
    static volatile public float orientation = CONST_INVALID_ORIENTATION;
    static volatile public float proximity = CONST_INVALID_PROXIMITY;
    static volatile public String taskRun = defaultTaskRun;
    static volatile public long availableRamKb = defaultRam;
    static volatile public long totalRamKb = defaultRam;
    
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
	        
		am = (AlarmManager)con.getSystemService(con.ALARM_SERVICE);
    	
    	alarmEnabled = true;
    	am.setRepeating(AlarmManager.RTC_WAKEUP, System.currentTimeMillis(), (interval * 1000), sender);
    	
    }
    
    static public void stopAgentListener() {
    	if (am != null) {
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
    	taskStatus = agentPreferences.getString("taskStatus", Core.defaultTaskStatus);
		task = agentPreferences.getString("task", Core.defaultTask);
		taskHumanName = agentPreferences.getString("taskHumanName", Core.defaultTaskHumanName);
		taskRun = agentPreferences.getString("taskRun", Core.defaultTaskRun);
		memoryStatus = agentPreferences.getString("memoryStatus", Core.defaultMemoryStatus);
		availableRamKb = agentPreferences.getLong("availableRamKb", Core.defaultRam);
		totalRamKb = agentPreferences.getLong("totalRamKb", Core.defaultRam);
		lastContact = agentPreferences.getLong("lastContact", Core.defaultContact);
		contactError = agentPreferences.getInt("contactError", Core.defaultContactError);
		simID = agentPreferences.getString("simID", Core.defaultSimID);
		upTime = agentPreferences.getLong("upTime", Core.defaultUpTime);
		networkOperator = agentPreferences.getString("networkOperator", Core.defaultMobileOperator);
    }
    
    static public void loadConf(Context context) {
    	if (con == null) {
    		con = context;
    	}
    	
    	SharedPreferences agentPreferences = con.getSharedPreferences(
    		con.getString(R.string.const_string_preferences),
    		Activity.MODE_PRIVATE);
    		
		serverAddr = agentPreferences.getString("serverAddr", Core.defaultServerAddr);
		serverPort = agentPreferences.getString("serverPort", Core.defaultServerPort);
		interval = agentPreferences.getInt("interval", Core.defaultInterval);
		//fix agent name to mark
		agentName = agentPreferences.getString("agentName", Core.defaultAgentName + "_mark_v");
		gpsStatus = agentPreferences.getString("gpsStatus", Core.defaultGpsStatus);
		memoryStatus = agentPreferences.getString("memoryStatus", Core.defaultMemoryStatus);
		taskStatus = agentPreferences.getString("taskStatus", Core.defaultTaskStatus);
		task = agentPreferences.getString("task", Core.defaultTask);
		taskHumanName = agentPreferences.getString("taskHumanName", Core.defaultTaskHumanName);
		taskRun = agentPreferences.getString("taskRun", Core.defaultTaskRun);
    }
    
    static public boolean updateConf(Context context) {
    	return updateConf(context, serverAddr, serverPort, interval, agentName,
    		gpsStatus, memoryStatus, taskStatus, task, taskHumanName, simID, upTime, networkOperator);
    }
    
    static public boolean updateConf(Context context, String _serverAddr,
    	String _serverPort, int _interval, String _agentName, String _gpsStatus,
    	String _memoryStatus, String _taskStatus, String _task,
    	String _taskHumanName, String _simID, long _upTime, String _networkOperator) {
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
		editor.putString("SimID", _simID);
		editor.putLong("UpTime", _upTime);
		editor.putString("networkOperator", _networkOperator);
		
		if (editor.commit()) {
			return true;
		}
		return false;
	}
}
