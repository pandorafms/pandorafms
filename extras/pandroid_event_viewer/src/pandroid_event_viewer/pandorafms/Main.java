package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
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
import android.app.TabActivity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.Toast;

public class Main extends Activity {
	public PandroidEventviewerActivity object;
	public HashMap<Integer, String> pandoraGroups;
	public Spinner comboSeverity;
	public Core core;
	
	/** Called when the activity is first created. */
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		Intent i = getIntent();
		this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
		this.core = (Core)i.getSerializableExtra("core");
		
		this.pandoraGroups = new HashMap<Integer, String>();
		
		setContentView(R.layout.main);
		
		final Button buttonReset = (Button) findViewById(R.id.button_reset);
		final Button buttonSearch = (Button) findViewById(R.id.button_send);
		final Button buttonbuttonSetAsFilterWatcher = (Button) findViewById(R.id.button_set_as_filter_watcher);
		
		//Check if the user preferences it is set.
		if (object.user.length() == 0 || object.password.length() == 0 ||
				object.url.length() == 0) {
			Toast toast = Toast.makeText(this.getApplicationContext(),
				this.getString(R.string.please_set_preferences_str),
				Toast.LENGTH_SHORT);
			toast.show();
			
			buttonReset.setEnabled(false);
			buttonSearch.setEnabled(false);
			buttonbuttonSetAsFilterWatcher.setEnabled(false);
		}
		else if (object.user.equals("demo") || object.password.equals("demo")) {
			Toast toast = Toast.makeText(this.getApplicationContext(),
					this.getString(R.string.preferences_set_demo_pandora_str),
					Toast.LENGTH_LONG);
				toast.show();
		}
		else {
			buttonSearch.setEnabled(false);
			buttonReset.setEnabled(false);
			buttonbuttonSetAsFilterWatcher.setEnabled(false);
			
			new GetGroupsAsyncTask().execute();
		}
		
		SharedPreferences preferences = getSharedPreferences(
				this.getString(R.string.const_string_preferences), 
				Activity.MODE_PRIVATE);
		
		comboSeverity = (Spinner) findViewById(R.id.severity_combo);
		ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
			this, R.array.severity_array_values, android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		comboSeverity.setAdapter(adapter);
		
		
		Spinner combo;
		combo = (Spinner) findViewById(R.id.status_combo);
		adapter = ArrayAdapter.createFromResource(
			this, R.array.event_status_values, android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(3);
		
		combo = (Spinner) findViewById(R.id.max_time_old_event_combo);
		adapter = ArrayAdapter.createFromResource(
			this, R.array.max_time_old_event_values, android.R.layout.simple_spinner_item);
		adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
		combo.setAdapter(adapter);
		combo.setSelection(preferences.getInt("filterLastTime", 6));
		
		
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
		
		buttonbuttonSetAsFilterWatcher.setOnClickListener(new View.OnClickListener() {
			
			@Override
			public void onClick(View v) {
				save_filter_watcher();
			}
		});
		
    	if (this.object.show_popup_info) {
    		this.object.show_popup_info = false;
    		i = new Intent(this, About.class);
    		startActivity(i);
    	}
	}
	
	public void onRestart() {
		super.onRestart();
		
		if (this.pandoraGroups.size() == 0) {
			Log.e("Main onRestart", "Main onRestart");
			new GetGroupsAsyncTask().execute();
		}
	}
	
	public ArrayList<String> getGroups() {
		ArrayList<String> array = new ArrayList<String>();
		
		SharedPreferences preferences = getSharedPreferences(
			this.getString(R.string.const_string_preferences), 
			Activity.MODE_PRIVATE);
		
		String url = preferences.getString("url", "");
		String user = preferences.getString("user", "");
		String password = preferences.getString("password", "");
		
		try {
			DefaultHttpClient httpClient = new DefaultHttpClient();
			
			HttpPost httpPost = new HttpPost(url + "/include/api.php");
			
			List<NameValuePair> parameters = new ArrayList<NameValuePair>();
			parameters.add(new BasicNameValuePair("user", user));
			parameters.add(new BasicNameValuePair("pass", password));
			parameters.add(new BasicNameValuePair("op", "get"));
			parameters.add(new BasicNameValuePair("op2", "groups"));
			parameters.add(new BasicNameValuePair("other_mode", "url_encode_separator_|"));
			parameters.add(new BasicNameValuePair("return_type", "csv"));
			parameters.add(new BasicNameValuePair("other", ";"));
			
			UrlEncodedFormEntity entity = new UrlEncodedFormEntity(parameters);
			
			httpPost.setEntity(entity);
			
			HttpResponse response = httpClient.execute(httpPost);
			HttpEntity entityResponse = response.getEntity();
			
			String return_api = Core.convertStreamToString(entityResponse.getContent());
			
			String[] lines = return_api.split("\n");
			
			for (int i= 0; i < lines.length; i++) {
				String[] groups = lines[i].split(";", 21);
				
				this.pandoraGroups.put(new Integer(groups[0]), groups[1]);
				
				array.add(groups[1]);
			}
		}
		catch (Exception e) {
			Log.e("EXCEPTION ArrayList", e.getMessage());
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
			Button buttonbuttonSetAsFilterWatcher = (Button) findViewById(R.id.button_set_as_filter_watcher);
			
			buttonReset.setEnabled(true);
			buttonSearch.setEnabled(true);
			buttonbuttonSetAsFilterWatcher.setEnabled(true);
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
		Intent i;
		switch (item.getItemId()) {
			case R.id.options_button_menu_options:
				i = new Intent(this, Options.class);
				//i.putExtra("object", object);
				i.putExtra("core", new Core());
				
				startActivity(i);
				break;
			case R.id.about_button_menu_options:
				i = new Intent(this, About.class);
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
		/*
		DatePicker datePicker = (DatePicker)findViewById(R.id.date);
		TimePicker timePicker = (TimePicker)findViewById(R.id.time);
		int day = datePicker.getDayOfMonth();
		int month = datePicker.getMonth();
		int year = datePicker.getYear();
		int hour = timePicker.getCurrentHour();
		int minute = timePicker.getCurrentMinute();
		Calendar c = Calendar.getInstance();
		c.set(year, month, day, hour, minute);
		
		this.object.timestamp = c.getTimeInMillis() / 1000;
		*/
		
		
		int timeKey = 0;
		Spinner combo = (Spinner)findViewById(R.id.max_time_old_event_combo);
		timeKey = combo.getSelectedItemPosition();
		
		this.object.timestamp = this.core.convertMaxTimeOldEventValuesToTimestamp(0, timeKey);
		
		EditText text = (EditText) findViewById(R.id.agent_name);
		this.object.agentNameStr = text.getText().toString();
		
		this.object.id_group = 0;
		
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
		this.object.severity = combo.getSelectedItemPosition() - 1;
		
		combo = (Spinner)findViewById(R.id.status_combo);
		Log.e("Main search_form", "status_combo = " + combo.getSelectedItemPosition());
		//this.object.status = combo.getSelectedItemPosition() - 1;//The next version re-add please
		this.object.status = combo.getSelectedItemPosition() - 0;
		
		text = (EditText)findViewById(R.id.event_search_text);
		this.object.eventSearch = text.getText().toString();
		
		this.object.getNewListEvents = true;
		this.object.executeBackgroundGetEvents();
		
		TabActivity ta = (TabActivity) this.getParent();
		ta.getTabHost().setCurrentTab(1);
	}
	
	public void save_filter_watcher() {
		String filterAgentName = "";
		int filterIDGroup = 0;
		int filterSeverity = -1;
		int filterStatus = -1;
		int filterLastTime = 0;
		String filterEventSearch = "";
		
		
		EditText text = (EditText) findViewById(R.id.agent_name);
		filterAgentName = text.getText().toString();
		
		Spinner combo;
		combo = (Spinner) findViewById(R.id.group_combo);
		if ((combo != null) && (combo.getSelectedItem() != null)) {
			String selectedGroup = combo.getSelectedItem().toString();
			
			Iterator it = pandoraGroups.entrySet().iterator();
			while (it.hasNext()) {
				Map.Entry<Integer, String> e = (Map.Entry<Integer, String>)it.next();
				
				if (e.getValue().equals(selectedGroup)) {
					filterIDGroup = e.getKey();
				}
			}
		}
		
		combo = (Spinner) findViewById(R.id.severity_combo);
		filterSeverity = combo.getSelectedItemPosition() - 1;
		
		combo = (Spinner)findViewById(R.id.status_combo);
		//filterStatus = combo.getSelectedItemPosition() - 1;//The next version re-add please
		filterStatus = combo.getSelectedItemPosition() - 0;
		
		combo = (Spinner)findViewById(R.id.max_time_old_event_combo);
		filterLastTime = combo.getSelectedItemPosition();
		
		text = (EditText)findViewById(R.id.event_search_text);
		filterEventSearch = text.getText().toString();
		
		
		SharedPreferences preferences = getSharedPreferences(
			this.getString(R.string.const_string_preferences), 
			Activity.MODE_PRIVATE);
		SharedPreferences.Editor editorPreferences = preferences.edit();
			
		editorPreferences.putString("filterAgentName", filterAgentName);
		editorPreferences.putInt("filterIDGroup", filterIDGroup);
		editorPreferences.putInt("filterSeverity", filterSeverity);
		editorPreferences.putInt("filterStatus", filterStatus);
		editorPreferences.putString("filterEventSearch", filterEventSearch);
		editorPreferences.putInt("filterLastTime", filterLastTime);
		
		if (editorPreferences.commit()) {
			this.core.stopServiceEventWatcher(getApplicationContext());
			this.core.startServiceEventWatcher(getApplicationContext());
			
			Toast toast = Toast.makeText(getApplicationContext(),
				this.getString(R.string.filter_update_succesful_str), Toast.LENGTH_SHORT);
			toast.show();
		}
		else {
			Toast toast = Toast.makeText(getApplicationContext(),
				this.getString(R.string.filter_update_fail_str), Toast.LENGTH_SHORT);
			toast.show();
		}
	}
	
	public void reset_form() {
		EditText text = (EditText)findViewById(R.id.agent_name);
		text.setText("");
		
		Spinner combo = (Spinner) findViewById(R.id.group_combo);
		combo.setSelection(0);
		
		combo = (Spinner) findViewById(R.id.severity_combo);
		combo.setSelection(0);
		
		combo = (Spinner)findViewById(R.id.max_time_old_event_combo);
		combo.setSelection(6);
		
		combo = (Spinner)findViewById(R.id.status_combo);
		combo.setSelection(4);
		
		text = (EditText)findViewById(R.id.event_search_text);
		text.setText("");
		
		/*
		Calendar c = Calendar.getInstance();
		DatePicker datePicker = (DatePicker)findViewById(R.id.date);
		datePicker.updateDate(c.get(Calendar.YEAR),
			c.get(Calendar.MONTH),
			c.get(Calendar.DAY_OF_MONTH));
		
		TimePicker timePicker = (TimePicker)findViewById(R.id.time);
		timePicker.setCurrentHour(c.get(Calendar.HOUR_OF_DAY));
		timePicker.setCurrentMinute(c.get(Calendar.MINUTE));
		*/
	}
	}
