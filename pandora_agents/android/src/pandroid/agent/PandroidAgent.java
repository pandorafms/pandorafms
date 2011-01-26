package pandroid.agent;

import android.app.Activity;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.Button;
import android.widget.RadioGroup;
import android.widget.RadioButton;
import android.os.Handler;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.View.OnKeyListener;
import android.view.KeyEvent;
import android.view.inputmethod.InputMethodManager;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.widget.Toast;
import android.graphics.Color;

import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.FileOutputStream;
import java.io.FileInputStream;
import java.util.Date;
import java.util.Calendar;
import java.util.List;
import java.lang.Thread;

public class PandroidAgent extends Activity {
    Handler h = new Handler();
    
    int defaultInterval = 300;
    String defaultServerPort = "41121";
    String defaultServerAddr = "10.0.2.2";
    String defaultAgentName = "pandroidAgent";
    String defaultGpsMode = "last"; // "last" or "current"
    String defaultGpsStatus = "disabled"; // "disabled" or "enabled"
    
    boolean showLastXML = true;
    boolean contactError = false;
    
    String lastGpsContactDateTime = "";
    Thread thread = new Thread();

    @Override
    public void onCreate(Bundle savedInstanceState) {	
        super.onCreate(savedInstanceState);
        this.setContentView(R.layout.main);
        
//        defaultGpsMode = "last";
//        String gpsMode = getSharedData("PANDROID_DATA", "gpsMode", defaultGpsMode, "string");
//
//        if(gpsMode.equals(gpsMode)) {
//		    LocationManager mlocManager = (LocationManager)getSystemService(Context.LOCATION_SERVICE);
//		    LocationListener mlocListener = new MyLocationListener();
//		         //Use the LocationManager class to obtain GPS locations
//	        mlocManager.requestLocationUpdates( 
//	        		LocationManager.GPS_PROVIDER, 
//	        		0, // minTime in ms 
//	        		0, // minDistance in meters
//		        		mlocListener);
//        }
        
        //resetValues();
        
        initViews();
        
        updateConf();
        
        setCleanReturns();
        
        setButtonEvents();
        
        thread = new Thread(null, BGProcess, "Background");
        thread.start();
    }
    
    private Runnable BGProcess = new Runnable() {
    	public void run() {
    		backGroundOpps();
    	}
    };
    
    private void backGroundOpps() {
        h.post(new Runnable() {
        	@Override
        	public void run() {
        		updateLastContact();
        		h.postDelayed(this, 1000);
        	}
        });
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
    
	private void setCleanReturns() {
	    EditText intervalInput = (EditText) findViewById(R.id.intervalInput);
	    intervalInput.setOnKeyListener(new OnKeyListener() {
	        public boolean onKey(View v, int keyCode, KeyEvent event) {
	            if(keyCode == 66) {
	        	    EditText intervalInput = (EditText) findViewById(R.id.intervalInput);
	        	    if(intervalInput.getText().toString().length() > 0) {
	        	    	intervalInput.setText(intervalInput.getText().toString().replaceAll("[\\r\\n]", ""));
	        	    }
	            }
	            return false;
	        }
	    });
	    
	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
	    serverAddrInput.setOnKeyListener(new OnKeyListener() {
	        public boolean onKey(View v, int keyCode, KeyEvent event) {
	            if(keyCode == 66) {
	        	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
	        	    if(serverAddrInput.getText().toString().length() > 0) {
	        	    	serverAddrInput.setText(serverAddrInput.getText().toString().replaceAll("[\\r\\n]", ""));
	        	    }
	            }
	            return false;
	        }
	    });
	   
	    EditText agentNameInput = (EditText) findViewById(R.id.agentNameInput);
	    agentNameInput.setOnKeyListener(new OnKeyListener() {
	        public boolean onKey(View v, int keyCode, KeyEvent event) {
	            if(keyCode == 66) {
	        	    EditText agentNameInput = (EditText) findViewById(R.id.agentNameInput);
	        	    if(agentNameInput.getText().toString().length() > 0) {
	        	    	agentNameInput.setText(agentNameInput.getText().toString().replaceAll("[\\r\\n]", ""));
	        	    }
	            }
	            return false;
	        }
	    });
	}
    
	private void setButtonEvents() {
        // Set update button events
        Button updateButton = (Button) findViewById(R.id.update);
        
        updateButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) { 
        		contactError = false;
        	    updateConf();
        	    contact();
        		updateLastContact();
        		hideKeyboard();
        	}
        });
        
        // Set reset button events
        Button resetButton = (Button) findViewById(R.id.reset);
        
        resetButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        	    resetValues();
        		initViews();
        		updateConf();
        	    hideKeyboard();
        	}
        });
        
        // Set reset button events
//        Button startStopButton = (Button) findViewById(R.id.starStop);
//        
//        startStopButton.setOnClickListener(new OnClickListener() {
//        	public void onClick(View view) {
//        	    thread.stop();
//        	}
//        });
        
        CheckBox checkGpsReport = (CheckBox) findViewById(R.id.checkGpsReport);
        
        checkGpsReport.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
                CheckBox checkGpsReport = (CheckBox) findViewById(R.id.checkGpsReport);
	            RadioGroup gpsRadio = (RadioGroup) findViewById(R.id.groupRadioGpsReport);

    	        if(checkGpsReport.isChecked()) {
    	        	gpsRadio.setVisibility(gpsRadio.VISIBLE);
    	        	putSharedData("PANDROID_DATA", "gpsStatus", "enabled", "string");
    	        }
    	        else {
    	        	gpsRadio.setVisibility(gpsRadio.GONE);
    	        	putSharedData("PANDROID_DATA", "gpsStatus", "disabled", "string");
    	        }
        	}
        });
        
	}
	
	private void resetValues() {
		putSharedData("PANDROID_DATA", "serverAddr", defaultServerAddr, "string");
		putSharedData("PANDROID_DATA", "serverPort", defaultServerPort, "string");
		putSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer");
		putSharedData("PANDROID_DATA", "agentName", defaultAgentName, "string");
		putSharedData("PANDROID_DATA", "latitude", "181", "float");
		putSharedData("PANDROID_DATA", "longitude", "181", "float");
		putSharedData("PANDROID_DATA", "gpsStatus", defaultGpsStatus, "string");
		putSharedData("PANDROID_DATA", "gpsMode", defaultGpsMode, "string");
		putSharedData("PANDROID_DATA", "lastContact", "-1", "long");
	}
	
	private void updateConf() {
	    EditText intervalInput = (EditText) findViewById(R.id.intervalInput);
	    String interval = intervalInput.getText().toString().replace("\n", "");
	    putSharedData("PANDROID_DATA", "interval", interval, "integer");
	    
	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
		String serverAddr = serverAddrInput.getText().toString();
	    putSharedData("PANDROID_DATA", "serverAddr", serverAddr, "string");

	    EditText serverPortInput = (EditText) findViewById(R.id.serverPortInput);
	    String serverPort = serverPortInput.getText().toString();
	    putSharedData("PANDROID_DATA", "serverPort", serverPort, "string");

	    EditText agentNameInput = (EditText) findViewById(R.id.agentNameInput);
	    String agentName = agentNameInput.getText().toString();
	    putSharedData("PANDROID_DATA", "agentName", agentName, "string");

	    CheckBox checkGpsReport = (CheckBox) findViewById(R.id.checkGpsReport);
	    
	    String gpsMode = "Disabled";
	    if(checkGpsReport.isChecked()) {
	        RadioButton radioGpsLast = (RadioButton) findViewById(R.id.radioGpsLast);
	        if(radioGpsLast.isChecked()) {
	        	putSharedData("PANDROID_DATA", "gpsMode", "last", "string");
	        	gpsMode = "Last position";
	        }
	        else {
	        	putSharedData("PANDROID_DATA", "gpsMode", "current", "string");
	        	gpsMode = "Current position";
	        }
	    }
        
		TextView summary = (TextView) this.findViewById(R.id.fieldSummary);
		summary.setText("Server: " + serverAddr + "\nPort: " + serverPort + "\nInterval: " + interval + " seconds\nAgent name: " + agentName + "\nGPS Mode: " + gpsMode);
	}
	
	private void hideKeyboard() {
	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
		InputMethodManager imm = (InputMethodManager)getSystemService(Context.INPUT_METHOD_SERVICE);
		imm.hideSoftInputFromWindow(serverAddrInput.getWindowToken(), 0);
	}
	
	private void updateLastContact(){
		//long lastContact = Long.parseLong("5");
		long lastContact = Long.parseLong(getSharedData("PANDROID_DATA", "lastContact", "-1", "long"));

		if(lastContact == -1) {
	        contact();
		}
		
		updateValues();
		
        Date date = new Date();
        long timestamp = date.getTime() / 1000;
        long timeAgo = timestamp - lastContact;
        
		int interval = Integer.parseInt(getSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer"));

        if(timeAgo >= interval) {
        	contact();
        	timeAgo = 0;
        }
        
        if(contactError) {
        	changeContactInfo("Contact error", "#FF0000");
        }
        else {
        	String stringAgo = timeAgo + " seconds ago.";
        	if(timeAgo == 0) {
        		stringAgo = "Now.";
        	}
        	changeContactInfo("Last Contact: " + stringAgo, "#00FF00");
        }
        
		TextView xml = (TextView) this.findViewById(R.id.xml);
		if(showLastXML) {
			String lastXML = getSharedData("PANDROID_DATA", "lastXML", "[no data]", "string");
			xml.setText("Last XML builded: \n\n" + lastXML);
		}
		else {
			xml.setText("");
		}
	}
	
	private void changeContactInfo(String msg, String colorCode) {
		TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo);
		lastContactInfo.setTextColor(Color.parseColor(colorCode));
		lastContactInfo.setText(msg);
		
	}
	
	private void updateSharedData() {
		TextView summary = (TextView) this.findViewById(R.id.fieldSummary);
		String serverAddr = getSharedData("PANDROID_DATA", "serverAddr", "[no data]", "string");
		String serverPort = getSharedData("PANDROID_DATA", "serverPort", "[no data]", "string");
		String interval = getSharedData("PANDROID_DATA", "interval", "300", "integer");
		String agentName = getSharedData("PANDROID_DATA", "agentName", "[no data]", "string");
		summary.setText("Server: " + serverAddr + "\nPort: " + serverPort + "\nInterval: " + interval + "\nAgent name: " + agentName);
	}
	
	private void contact(){
        Date date = new Date();
        
        putSharedData("PANDROID_DATA", "lastContact", Long.toString(date.getTime() / 1000), "long");
        
        // Keep lastXML sended if is not empty (empty means error sending it)
        String lastXML = buildXML();

        if(!lastXML.equals("")) {
            putSharedData("PANDROID_DATA", "lastXML", lastXML, "string");
        }
        
		updateValues();
	}
	
	private void initViews(){
        // Init form values
	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
        serverAddrInput.setText(getSharedData("PANDROID_DATA", "serverAddr", defaultServerAddr, "string"));
        
	    EditText serverPortInput = (EditText) findViewById(R.id.serverPortInput);
        serverPortInput.setText(getSharedData("PANDROID_DATA", "serverPort", defaultServerPort, "string"));
        
	    EditText intervalInput = (EditText) findViewById(R.id.intervalInput);
	    int interval = Integer.parseInt(getSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer"));
        intervalInput.setText(Integer.toString(interval));
        
	    EditText agentNameInput = (EditText) findViewById(R.id.agentNameInput);
        agentNameInput.setText(getSharedData("PANDROID_DATA", "agentName", defaultAgentName, "string"));
        
        CheckBox checkGpsReport = (CheckBox) findViewById(R.id.checkGpsReport);
        
        String gpsStatus = getSharedData("PANDROID_DATA", "gpsStatus", defaultGpsStatus, "string");
        boolean gpsEnabled = gpsStatus.equals("enabled");
        checkGpsReport.setChecked(gpsEnabled);
        
        RadioGroup gpsRadio = (RadioGroup) findViewById(R.id.groupRadioGpsReport);

        if(gpsEnabled) {
	        gpsRadio.setVisibility(gpsRadio.VISIBLE);
        }
        else {
	        gpsRadio.setVisibility(gpsRadio.GONE);
        }
        
        RadioButton radioGpsCurrent = (RadioButton) findViewById(R.id.radioGpsCurrent);
        RadioButton radioGpsLast = (RadioButton) findViewById(R.id.radioGpsLast);

	        	
        if(getSharedData("PANDROID_DATA", "gpsMode", defaultGpsMode, "string").equals("current")) {
        	radioGpsCurrent.setChecked(true);
        }
        else {
        	radioGpsLast.setChecked(true);
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
		
		buffer += "<agent_data description='' group='' os_name='android' os_version='2.1' interval='"+ interval +"' version='3.2RC1(Build 101103)' timestamp='" + getHumanDateTime(-1) + "' agent_name='" + agentName + "' timezone_offset='0'" + gpsData +">\n";
		
		// Modules
		buffer += buildmoduleXML("battery_level", "The actually device battery level", "generic_data", getSharedData("PANDROID_DATA", "batteryLevel", "-1", "integer"));		
		//buffer += buildmoduleXML("last_gps_contact", "Datetime of the last geo-location contact", "generic_data", lastGpsContactDateTime);
		
		// End_Modules
		
		buffer += "</agent_data>";
		
		String destFileName = agentName + "." + System.currentTimeMillis() + ".data";
		
		writeFile(destFileName, buffer);

		String[] tentacleData = {
				  "-a",
				  getSharedData("PANDROID_DATA", "serverAddr", "", "string"),
				  "-p",
				  defaultServerPort,
				  "-v",
				  "/data/data/pandroid.agent/files/" + destFileName
	    		  };

		int tentacleRet = new tentacle_client().tentacle_client(tentacleData);
		
		if(tentacleRet == 0) {
			contactError = false;
		}
		else {
			contactError = true;
			buffer = "";
		}
		
		return buffer;
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
	
	////////////////////////////////////////////////////////////
	// Get human date time from unixtime in milliseconds. 
	// If unixtime = -1 is returned the current datetime
	////////////////////////////////////////////////////////////
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
    
    
    private void showToast (String msg) {
    	Context context = getApplicationContext();
    	int duration = Toast.LENGTH_SHORT;

    	Toast toast = Toast.makeText(context, msg, duration);
    	toast.show();
    }
    
    ///////////////////////////////////////////
    // Getting values from device functions
    ///////////////////////////////////////////
    
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
        String gpsMode = getSharedData("PANDROID_DATA", "gpsMode", defaultGpsMode, "string");
        
        if(gpsMode.equals("last")) {
			gpsLocation();
        }
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
    
    private String readFile(String fileName) {
    	String readString = "";
    	try { // catches IOException below
    		      FileInputStream fIn = openFileInput(fileName);
                  InputStreamReader isr = new InputStreamReader(fIn);
                  /* Prepare a char-Array that will
                   * hold the chars we read back in. */
                  char[] inputBuffer = new char[100];
                  // Fill the Buffer with data from the file
                  isr.read(inputBuffer);
                  // Transform the chars to a String
                  readString = new String(inputBuffer);

	          } catch (IOException ioe) {
	          }
	          
	    return readString;
    }

    public class MyLocationListener implements LocationListener {

	    @Override
	    public void onLocationChanged(Location loc) {
            putSharedData("PANDROID_DATA", "latitude", new Double(loc.getLatitude()).toString(), "float");
            putSharedData("PANDROID_DATA", "longitude", new Double(loc.getLongitude()).toString(), "float");
	    }
	    
	    @Override	
	    public void onProviderDisabled(String provider) {
            putSharedData("PANDROID_DATA", "latitude", "181", "float");
            putSharedData("PANDROID_DATA", "longitude", "181", "float");
	    }
	
	    @Override
	    public void onProviderEnabled(String provider) {
	    }
	
	    @Override
	
	    public void onStatusChanged(String provider, int status, Bundle extras) {
	    }

    }/* End of Class MyLocationListener */
}