package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.app.TabActivity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Region.Op;
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
import android.widget.Spinner;
import android.widget.TimePicker;

public class Main extends Activity {
	public PandroidEventviewerActivity object;
	public HashMap<Integer, String> pandoraGroups;
	public String url;
	public String user;
	public String password;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        SharedPreferences preferences = getSharedPreferences(
            this.getString(R.string.const_string_preferences), 
            Activity.MODE_PRIVATE);
        
        this.url = preferences.getString("url", "");
        this.user = preferences.getString("user", "");
        this.password = preferences.getString("password", "");
        
        Intent i = getIntent();
        this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        
        this.pandoraGroups = new HashMap<Integer, String>();
        
        setContentView(R.layout.main);
        
        Spinner combo = (Spinner) findViewById(R.id.group_combo);
        
        this.setGroups(combo);
        
        combo = (Spinner) findViewById(R.id.severity_combo);
        ArrayAdapter adapter = ArrayAdapter.createFromResource(
                this, R.array.severity_array_values, android.R.layout.simple_spinner_item);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        combo.setAdapter(adapter);
        
        final Button buttonReset = (Button) findViewById(R.id.button_reset);
        buttonReset.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				reset_form();
			}
		});
        
        final Button buttonSearch = (Button) findViewById(R.id.button_send);
        buttonSearch.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				search_form();
			}
		});
    }
    
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
            	
            	startActivity(i);
            	break;
        }
        
        return true;
    }
    
    public void search_form() {
    	//Clean the EventList
    	this.object.eventList = new ArrayList<EventListItem>();
    	this.object.loadInProgress = true;
    	
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
    
    public void setGroups(Spinner combo) {
    	
    	try {
            DefaultHttpClient httpClient = new DefaultHttpClient();
    		
	    	HttpPost httpPost = new HttpPost(this.url);
	    	
	    	List<NameValuePair> parameters = new ArrayList<NameValuePair>(2);
	    	parameters.add(new BasicNameValuePair("user", this.user));
	    	parameters.add(new BasicNameValuePair("pass", this.password));
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
	    	
	    	String[] lines = return_api.split("\n");
	    	
	    	ArrayList<String> array = new ArrayList<String>();
	    	
	    	for (int i= 0; i < lines.length; i++) {
	    		String[] groups = lines[i].split(";", 21);
	    		
	    		this.pandoraGroups.put(new Integer(groups[0]), groups[1]);
	    		
	    		array.add(groups[1]);
	    	}
	    	
	    	ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(this,
	    		android.R.layout.simple_spinner_item,
	    		array);
	    	combo.setAdapter(spinnerArrayAdapter);
    	}
    	catch (Exception e) {
    		Log.e("ERROR THE ", e.getMessage());
    		
    		return;
    	}
    }
}
