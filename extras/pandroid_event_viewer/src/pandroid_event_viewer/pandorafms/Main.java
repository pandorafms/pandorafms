package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.app.Notification;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.TabActivity;
import android.content.Context;
import android.content.Intent;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.DatePicker;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TimePicker;
import android.widget.Toast;

public class Main extends Activity {
	public PandroidEventviewerActivity object;
	public HashMap<Integer, String> pandoraGroups;
	public Spinner comboSeverity;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        Intent i = getIntent();
        this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        
        this.pandoraGroups = new HashMap<Integer, String>();
        
        setContentView(R.layout.main);
        
        final Button buttonReset = (Button) findViewById(R.id.button_reset);
        final Button buttonSearch = (Button) findViewById(R.id.button_send);
        
        //Check if the user preferences it is set.
        if ((object.user.length() == 0) && (object.password.length() == 0)
        	&& (object.url.length() == 0)) {
        	Toast toast = Toast.makeText(this.getApplicationContext(),
        		this.getString(R.string.please_set_preferences_str),
        		Toast.LENGTH_SHORT);
    		toast.show();
    		
    		buttonReset.setEnabled(false);
    		buttonSearch.setEnabled(false);
        }
        else {
            Spinner combo;
            
            buttonSearch.setEnabled(false);
            buttonReset.setEnabled(false);
            
            new GetGroupsAsyncTask().execute();
        }
        
        comboSeverity = (Spinner) findViewById(R.id.severity_combo);
        ArrayAdapter adapter = ArrayAdapter.createFromResource(
                this, R.array.severity_array_values, android.R.layout.simple_spinner_item);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        comboSeverity.setAdapter(adapter);
        
        buttonReset.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				reset_form();
			}
		});
        
        buttonSearch.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				search_form();
			}
		});
    }
    
    public ArrayList<String> getGroups() {
    	ArrayList<String> array = new ArrayList<String>();
    	
		try {
            DefaultHttpClient httpClient = new DefaultHttpClient();
    		
	    	HttpPost httpPost = new HttpPost(this.object.url);
	    	
	    	List<NameValuePair> parameters = new ArrayList<NameValuePair>();
	    	parameters.add(new BasicNameValuePair("user", this.object.user));
	    	parameters.add(new BasicNameValuePair("pass", this.object.password));
	    	parameters.add(new BasicNameValuePair("op", "get"));
	    	parameters.add(new BasicNameValuePair("op2", "groups"));
	    	parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
	    	parameters.add(new BasicNameValuePair("return_type", "csv"));
	    	parameters.add(new BasicNameValuePair("other", ";"));
	    	
	    	UrlEncodedFormEntity entity = new UrlEncodedFormEntity(parameters);
	    	
	    	httpPost.setEntity(entity);
	    	
	    	HttpResponse response = httpClient.execute(httpPost);
	    	HttpEntity entityResponse = response.getEntity();
	    	
	    	String return_api = this.object.convertStreamToString(entityResponse.getContent());
	    	Log.e("getGroups", return_api);
	    	
	    	String[] lines = return_api.split("\n");
	    	
	    	for (int i= 0; i < lines.length; i++) {
	    		String[] groups = lines[i].split(";", 21);
	    		
	    		this.pandoraGroups.put(new Integer(groups[0]), groups[1]);
	    		
	    		array.add(groups[1]);
	    	}
    	}
    	catch (Exception e) {
    		Log.e("ERROR THE ", e.getMessage());
    	}
		
		return array;
    }
    
    public class GetGroupsAsyncTask extends AsyncTask<Void, Void, Void> {
    	public ArrayList<String> lista;
    	
		@Override
		protected Void doInBackground(Void... params) {
			lista = getGroups();
			
			return null;
		}
		
		@Override
		protected void onPostExecute(Void unused)
		{
			Spinner combo = (Spinner)findViewById(R.id.group_combo);
	    	
	    	ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(getApplicationContext(),
		    	android.R.layout.simple_spinner_item,
		    	lista);
		    combo.setAdapter(spinnerArrayAdapter);
		    combo.setSelection(0);
		    
		    ProgressBar loadingGroup = (ProgressBar) findViewById(R.id.loading_group);
		    
		    loadingGroup.setVisibility(ProgressBar.GONE);
		    combo.setVisibility(Spinner.VISIBLE);
		    
		    Button buttonReset = (Button) findViewById(R.id.button_reset);
	        Button buttonSearch = (Button) findViewById(R.id.button_send);
	        
	        buttonReset.setEnabled(true);
	        buttonSearch.setEnabled(true);
		}
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
        switch (item.getItemId()) {
            case R.id.options_button_menu_options:
            	Intent i = new Intent(this, Options.class);
            	//FAIL//i.putExtra("object", object);
            	
            	startActivity(i);
            	break;
        }
        
        return true;
    }
    
    public void search_form() {
    	//Clean the EventList
    	this.object.eventList = new ArrayList<EventListItem>();
    	this.object.loadInProgress = true;
    	
    	//Get form data
    	DatePicker datePicker = (DatePicker)findViewById(R.id.date);
    	TimePicker timePicker = (TimePicker)findViewById(R.id.time);
    	int day = datePicker.getDayOfMonth();
    	int month = datePicker.getMonth() + 1;
    	int year = datePicker.getYear();
    	int hour = timePicker.getCurrentHour();
    	int minute = timePicker.getCurrentMinute();
    	Date date = new Date(year, month, day, minute, hour);
    	this.object.timestamp = date.getTime() / 1000;
    	
    	EditText agentName = (EditText) findViewById(R.id.agent_name);
    	String agentNameStr = agentName.getText().toString();
    	
    	this.object.id_group = 0;
    	
    	Spinner combo;
    	int sel;
    	combo = (Spinner) findViewById(R.id.group_combo);
    	String selectedGroup = combo.getSelectedItem().toString();
    	
    	Iterator it = pandoraGroups.entrySet().iterator();
    	while (it.hasNext()) {
    		Map.Entry<Integer, String> e = (Map.Entry<Integer, String>)it.next();
    		
    		if (e.getValue().equals(selectedGroup)) {
    			this.object.id_group = e.getKey();
    		}
    	}
    	
    	combo = (Spinner) findViewById(R.id.severity_combo);
    	this.object.severity = combo.getSelectedItemPosition();
    	
    	
    	this.object.executeBackgroundGetEvents();
    	
    	TabActivity ta = (TabActivity) this.getParent();
    	ta.getTabHost().setCurrentTab(1);
    }
    
    public void reset_form() {
    	EditText agentEditText = (EditText)findViewById(R.id.agent_name);
    	agentEditText.setText("");
    	
    	Spinner combo = (Spinner) findViewById(R.id.group_combo);
    	combo.setSelection(0);
    	
    	combo = (Spinner) findViewById(R.id.severity_combo);
    	combo.setSelection(0);
    	
    	Calendar c = Calendar.getInstance();
    	DatePicker datePicker = (DatePicker)findViewById(R.id.date);
    	datePicker.updateDate(c.get(Calendar.YEAR),
    		c.get(Calendar.MONTH),
    		c.get(Calendar.DAY_OF_MONTH));
    	
    	TimePicker timePicker = (TimePicker)findViewById(R.id.time);
    	timePicker.setCurrentHour(c.get(Calendar.HOUR_OF_DAY));
    	timePicker.setCurrentMinute(c.get(Calendar.MINUTE));
    }
}
