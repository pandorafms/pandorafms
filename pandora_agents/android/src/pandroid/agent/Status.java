package pandroid.agent;

import java.util.Date;

import android.app.Activity;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.util.Log;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.TextView;

public class Status  extends Activity {
	Handler h = new Handler();
	
	/** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.status);
        Core.loadLastValues(getApplicationContext());
        showLastValues();
        updateLastContactInfo();
        setButtonEvents();
        
        // Update the UI each second
        h.post(new Runnable() {
        	@Override
        	public void run() {
        		Core.loadLastValues(getApplicationContext());
        		showLastValues();
        		updateLastContactInfo();
        		
        		h.postDelayed(this, 1000);
        	}
        });
    }
    
	private void setButtonEvents() {
        // Set update button events
		/*
        Button getButton = (Button) findViewById(R.id.get_xml);
        
        getButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		updateLastXML();
        		showLastValues();
        		updateLastContactInfo();
        	}
        });
        */
	}
    
	private void updateLastXML() {
		/*
		TextView xml = (TextView) this.findViewById(R.id.xml);
		
		Core.restartAgentListener(getApplicationContext());
		
		SharedPreferences agentPreferences = getSharedPreferences(
    			getString(R.string.const_string_preferences),
    			Activity.MODE_PRIVATE);
		
		String lastXML = agentPreferences.getString("lastXML", "[no data]");
		
		xml.setText("Last XML builded: \n\n" + lastXML);
		*/
	}
	
	private void updateLastContactInfo() {
		//putSharedData("PANDROID_DATA", "lastContact", "-1", "long");
		long lastContact = Core.lastContact;
		int contactError = Core.contactError;

        Date date = new Date();
        long timestamp = date.getTime() / 1000;
        long timeAgo = -1;
        if(lastContact != -1){
            timeAgo = timestamp - lastContact;
        }
        
		int interval = Core.interval;

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
        	TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText("Contact error");
        	//stopAgentListener();
        }
        else {
        	TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#00FF00"));
    		lastContactInfo.setText("Last Contact:\n" + stringAgo);
        }
	}
	
	private void showLastValues() {
		TextView textView = (TextView)findViewById(R.id.latitude_value_str);
		textView.setText("");
		if (Core.latitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.latitude);
		}
		
		textView = (TextView)findViewById(R.id.longitude_value_str);
		textView.setText("");
		if (Core.longitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.longitude);
		}
		
		textView = (TextView)findViewById(R.id.battery_value_str);
		textView.setText("");
		if (Core.batteryLevel != Core.CONST_INVALID_BATTERY_LEVEL) {
			textView.setText("" + Core.batteryLevel);
		}
		
		textView = (TextView)findViewById(R.id.orientation_value_str);
		textView.setText("");
		
		if (Core.orientation != Core.CONST_INVALID_ORIENTATION) {
			textView.setText("" + Core.orientation);
		}
		
		textView = (TextView)findViewById(R.id.proximity_value_str);
		textView.setText("");
		if (Core.proximity != Core.CONST_INVALID_PROXIMITY) {
			textView.setText("" + Core.proximity);
		}
		
		textView = (TextView)findViewById(R.id.task_value_str);
		textView.setText("");
		if (Core.taskStatus.equals("enabled") && Core.taskHumanName.length() != 0) {
			String text = Core.taskHumanName + " ( " + Core.task + " ): ";
			if (Core.taskRun.equals("true")) {
				text = text + "running";
			}
			else {
				text = text + "not running";
			}
			textView.setText(text);		
		}
		
		textView = (TextView)findViewById(R.id.memory_value_str);
		textView.setText("");
		if (Core.memoryStatus.equals("enabled")) {
			textView.setText("Avaliable: " + Core.availableRamKb + "Kb Total: " + Core.totalRamKb + "Kb");
		}
		
		textView = (TextView)findViewById(R.id.proximity_value_str);
		if (Core.proximity != Core.CONST_INVALID_PROXIMITY) {
			textView.setText("" + Core.proximity);
		}
	}
}
