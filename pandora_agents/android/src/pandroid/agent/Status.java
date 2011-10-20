package pandroid.agent;

import java.util.Date;

import android.app.Activity;
import android.content.Intent;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
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
    
    //For options
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.options_menu, menu);
        return true;
    }
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
    	Intent i;
        switch (item.getItemId()) {
            case R.id.help_button_menu_options:
            	i = new Intent(this, Help.class);
            	startActivity(i);
            	break;
            case R.id.about_button_menu_options:
            	i = new Intent(this, About.class);
            	startActivity(i);
            	break;
        }
        
        return true;
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
    		stringAgo = getString(R.string.never_str);
    	}
    	else if(timeAgo == 0) {
    		stringAgo = getString(R.string.now_str);
    	}
    	else {
        	stringAgo = timeAgo + " " + getString(R.string.seconds_str);
    	}
    	
        if(contactError == 1) {
        	TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText(getString(R.string.conctact_error_str));
        	//stopAgentListener();
        }
        else {
        	TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#00FF00"));
    		lastContactInfo.setText(getString(R.string.last_contact_str) + stringAgo);
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
		/*
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
		*/
		
		/*
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
		*/
		
		textView = (TextView)findViewById(R.id.memory_value_str);
		textView.setText("");
		if (Core.memoryStatus.equals("enabled")) {
			String textMemory = getString(R.string.memory_avaliable_str);
			textMemory = textMemory.replaceFirst("%i", "" + Core.availableRamKb);
			textMemory = textMemory.replaceFirst("%i", "" + Core.totalRamKb);
			textView.setText(textMemory);
		}
	}
}
