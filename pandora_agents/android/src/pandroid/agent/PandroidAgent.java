package pandroid.agent;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.os.Bundle;
import android.widget.TextView;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.Button;
import android.os.Handler;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.View.OnKeyListener;
import android.view.KeyEvent;
import android.view.inputmethod.InputMethodManager;
import android.location.Location;
import android.location.LocationListener;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.ComponentName;
import android.graphics.Color;

import java.util.Date;
import java.lang.Thread;

public class PandroidAgent extends Activity {
    Handler h = new Handler();
    
    int defaultInterval = 300;
    String defaultServerPort = "41121";
    String defaultServerAddr = "10.0.2.2";
    String defaultAgentName = "pandroidAgent";
    String defaultGpsStatus = "disabled"; // "disabled" or "enabled"
    
    boolean showLastXML = true;
    
    String lastGpsContactDateTime = "";
    Thread thread = new Thread();
    ComponentName service = null;
    PendingIntent sender = null;
    AlarmManager am = null;

    @Override
    public void onCreate(Bundle savedInstanceState) {	
        super.onCreate(savedInstanceState);
        this.setContentView(R.layout.main);
        
        //resetValues();
        
        // Load the stored data into views
        loadViews();
                
        // Set the cleaning returns listener
        setCleanReturns();
        
        // Set the button events listener
        setButtonEvents();
        
        // Start the agent listener service
		//ComponentName service = startService(new Intent(this, PandroidAgentListener.class));
		
		// Setting an alarm to call service
        Intent intentReceiver = new Intent(this, EventReceiver.class);
        sender = PendingIntent.getBroadcast(this, 0, intentReceiver, 0);
        
        am = (AlarmManager) getSystemService(ALARM_SERVICE);
        
        // Start the alert listener
		startAgentListener();

		// Update the UI each second
        h.post(new Runnable() {
        	@Override
        	public void run() {
        		updateUI();
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
	    
	    EditText serverPortInput = (EditText) findViewById(R.id.serverPortInput);
	    serverPortInput.setOnKeyListener(new OnKeyListener() {
	        public boolean onKey(View v, int keyCode, KeyEvent event) {
	            if(keyCode == 66) {
	        	    EditText serverPortInput = (EditText) findViewById(R.id.serverPortInput);
	        	    if(serverPortInput.getText().toString().length() > 0) {
	        	    	serverPortInput.setText(serverPortInput.getText().toString().replaceAll("[\\r\\n]", ""));
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
        	    updateConf();
        	    updateUI();
				restartAgentListener();
        		hideKeyboard();
        	}
        });
        
        // Set reset button events
        Button resetButton = (Button) findViewById(R.id.reset);
        
        resetButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        	    resetValues();
        		loadViews();
        		updateConf();
        		restartAgentListener();
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
                
	}
	
	private void resetValues() {
		putSharedData("PANDROID_DATA", "serverAddr", defaultServerAddr, "string");
		putSharedData("PANDROID_DATA", "serverPort", defaultServerPort, "string");
		putSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer");
		putSharedData("PANDROID_DATA", "agentName", defaultAgentName, "string");
		putSharedData("PANDROID_DATA", "latitude", "181", "float");
		putSharedData("PANDROID_DATA", "longitude", "181", "float");
		putSharedData("PANDROID_DATA", "gpsStatus", defaultGpsStatus, "string");
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
	    
	    if(checkGpsReport.isChecked()) {
	        putSharedData("PANDROID_DATA", "gpsStatus", "enabled", "string");
	    }
	    else {
	        putSharedData("PANDROID_DATA", "gpsStatus", "disabled", "string");
	    }
        
	}
	
	private void hideKeyboard() {
	    EditText serverAddrInput = (EditText) findViewById(R.id.serverAddrInput);
		InputMethodManager imm = (InputMethodManager)getSystemService(Context.INPUT_METHOD_SERVICE);
		imm.hideSoftInputFromWindow(serverAddrInput.getWindowToken(), 0);
	}
	
	private void updateUI() {
		// Update connection data summary
		updateSummary();
		
		// Update the last contact info
		updateLastContactInfo();
		
		// Update the last XML sended info
		updateLastXML();
	}
	
	private void updateLastXML() {
		TextView xml = (TextView) this.findViewById(R.id.xml);
		if(showLastXML) {
			String lastXML = getSharedData("PANDROID_DATA", "lastXML", "[no data]", "string");
			xml.setText("Last XML builded: \n\n" + lastXML);
		}
		else {
			xml.setText("");
		}
	}
	
	private void stopAgentListener() {
	    am.cancel(sender);
	}
	
	private void startAgentListener() {
		int interval = Integer.parseInt(getSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer"));

        // Set the alarm with the interval frequency
        am.setRepeating(AlarmManager.RTC, System.currentTimeMillis(), (interval * 1000), sender);
	}
	
	private void restartAgentListener() {
		stopAgentListener();
		startAgentListener();
	}
	
	private void updateLastContactInfo() {
		//putSharedData("PANDROID_DATA", "lastContact", "-1", "long");
		long lastContact = Long.parseLong(getSharedData("PANDROID_DATA", "lastContact", "-1", "long"));
		int contactError = Integer.parseInt(getSharedData("PANDROID_DATA", "contactError", "0", "integer"));

        Date date = new Date();
        long timestamp = date.getTime() / 1000;
        long timeAgo = -1;
        if(lastContact != -1){
            timeAgo = timestamp - lastContact;
        }
        
		int interval = Integer.parseInt(getSharedData("PANDROID_DATA", "interval", Integer.toString(defaultInterval), "integer"));

        if(timeAgo >= interval) {
        	timeAgo = 0;
        }
        
    	String stringAgo = "";
    	
    	if(lastContact == -1) {
    		stringAgo = "Never.";
    	}
    	else if(timeAgo == 0) {
    		stringAgo = "Now.";
    	}
    	else {
        	stringAgo = timeAgo + " seconds ago.";
    	}
    	
        if(contactError == 1) {
        	changeContactInfo("Contact error", "#FF0000");
        }
        else {
        	changeContactInfo("Last Contact: " + stringAgo, "#00FF00");
        }
	}
	
	private void changeContactInfo(String msg, String colorCode) {
		TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo);
		lastContactInfo.setTextColor(Color.parseColor(colorCode));
		lastContactInfo.setText(msg);
		
	}
	
	private void updateSummary() {
		String serverAddr = getSharedData("PANDROID_DATA", "serverAddr", "[no data]", "string");
		String serverPort = getSharedData("PANDROID_DATA", "serverPort", "[no data]", "string");
		String interval = getSharedData("PANDROID_DATA", "interval", "300", "integer");
		String agentName = getSharedData("PANDROID_DATA", "agentName", "[no data]", "string");
		String gpsStatus = getSharedData("PANDROID_DATA", "gpsStatus", "[no data]", "string");
	
		// Update the connection summary
		TextView summary = (TextView) this.findViewById(R.id.fieldSummary);
		summary.setText("Server: " + serverAddr + "\nPort: " + serverPort + "\nInterval: " + interval + " seconds\nAgent name: " + agentName + "\nGPS Report: " + gpsStatus);	
	}
	
	private void loadViews(){
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

	}
	
}