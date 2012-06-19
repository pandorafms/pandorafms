package pandroid.agent;

import java.util.Date;

import android.app.Activity;
import android.content.*;
import android.graphics.Color;
import android.os.Bundle;
import android.os.Handler;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

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
        
        //connect automatically
        //Core.restartAgentListener(getApplicationContext());
        setButtonEvents();
        // Update the UI each second
        
        
    }
    
    public void onStart(){
    	super.onStart();
    	
    	h.post(new Runnable() {
        	
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
		
		TextView xml = (TextView) this.findViewById(R.id.xml);
		
		//Core.restartAgentListener(getApplicationContext());
		
		SharedPreferences agentPreferences = getSharedPreferences(
    			getString(R.string.const_string_preferences),
    			Activity.MODE_PRIVATE);
		
		String lastXML = agentPreferences.getString("lastXML", "[no data]");
		
		xml.setText("Last XML builded: \n\n" + lastXML);
		
	}
	private void hideLastXML(){
		TextView xml = (TextView) this.findViewById(R.id.xml);
		xml.setText("");
	}
	
	private void updateLastContactInfo() {
		long lastContact = Core.lastContact;
		int contactError = Core.contactError;
		boolean alarmEnabled = Core.alarmEnabled;

        Date date = new Date();
        long timestamp = date.getTime() / 1000;
        long timeAgo = -1;
        
        //loading until error or connects
        TextView lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
		lastContactInfo.setText(getString(R.string.loading));
        
        
        
        if(lastContact != -1){
            timeAgo = timestamp - lastContact;
        }
        
		int interval = Core.interval;

        if(timeAgo >= interval) {
        	timeAgo = 0;
        }
        
    	String stringAgo = "";
    	
    	//Check connection first
    	if(!alarmEnabled){
    		lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText(getString(R.string.contact_stopped_str));
    	}
    	
    	
    	else if(contactError == 1) {
        	lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#FF0000"));
    		lastContactInfo.setText(getString(R.string.conctact_error_str));
        	//stopAgentListener();
        }
    	else if(lastContact == -1) {
    		stringAgo = getString(R.string.never_str);
    	}
    	else if(timeAgo == 0) {
    		stringAgo = getString(R.string.now_str);
    	}
    	else {
        	stringAgo = timeAgo + " " + getString(R.string.seconds_str);
        	lastContactInfo = (TextView) this.findViewById(R.id.lastContactInfo_label_str);
    		lastContactInfo.setTextColor(Color.parseColor("#00FF00"));
    		lastContactInfo.setText(getString(R.string.last_contact_str) + stringAgo);
        }
    		
    	
    	
        
	}
	private void setButtonEvents() {
        // Set update button events
        Button updateButton = (Button) findViewById(R.id.start);
        Button xml = (Button) findViewById(R.id.get_xml);
        Button hidexml = (Button) findViewById(R.id.hide_xml);
        Button stop = (Button) findViewById(R.id.stop);
        
        
        xml.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		updateLastXML();
        	}
        });
        
        hidexml.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		hideLastXML();
        	}
        });
        
        stop.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		Core.stopAgentListener();
        	}
        });
        
        updateButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		boolean result = Core.updateConf(getApplicationContext());
        		
        		if (result) {
        			Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.correct_start),
        	       		Toast.LENGTH_SHORT);
        	    	toast.show();
        		}
        		else {
        			Toast toast = Toast.makeText(getApplicationContext(),
            	       	getString(R.string.incorrect_update),
            	       	Toast.LENGTH_SHORT);
            	    	toast.show();
        		}
        		
        		Core.restartAgentListener(getApplicationContext());
        	}
        });
        
        
        
        
	}
	
	private void showLastValues() {
		// latitude
		TextView textView = (TextView)findViewById(R.id.latitude_value_str);
		textView.setText("");
		if (Core.latitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.latitude);
		}
		// longitude
		textView = (TextView)findViewById(R.id.longitude_value_str);
		textView.setText("");
		if (Core.longitude != Core.CONST_INVALID_COORDS) {
			textView.setText("" + Core.longitude);
		}
		//Battery level
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
		// freeMemory
		textView = (TextView)findViewById(R.id.memory_value_str);
		textView.setText("");
		if (Core.memoryStatus.equals("enabled")) {
			String textMemory = getString(R.string.memory_avaliable_str);
			textMemory = textMemory.replaceFirst("%i", "" + Core.availableRamKb);
			textMemory = textMemory.replaceFirst("%i", "" + Core.totalRamKb);
			textView.setText(textMemory);
		}
		//  simID
		textView = (TextView)findViewById(R.id.sim_id_value);
		textView.setText("");
		if (Core.simID != null) {
			textView.setText("" + Core.simID);
		}
		// upTime
		textView = (TextView)findViewById(R.id.uptime_value);
		textView.setText("");
		if (Core.upTime != 0) {
			textView.setText("" + Core.upTime+" Seconds");
		}
		// mobile operator
		textView = (TextView)findViewById(R.id.networkoperator_value);
		textView.setText("");
		if (Core.networkOperator != null) {
			textView.setText("" + Core.networkOperator);
		}
		// SMSReceived
		/*
		textView = (TextView)findViewById(R.id.sms_received_value);
		textView.setText("");
		textView.setText("" + Core.getSMSReceived(getApplicationContext()));
		 */
	}
}
