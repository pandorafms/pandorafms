package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;
import android.widget.Toast;

public class Options extends Activity {
	public String url;
	public String user;
	public String password;
	public int refreshTimeKey;
	
	public Core core;
	public PandroidEventviewerActivity object;
	
	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
        
        Intent i = getIntent();
        this.core = (Core)i.getSerializableExtra("core");
        //this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        //this.core = this.object.core;
        
        
        
        setContentView(R.layout.options);
        
        SharedPreferences preferences = getSharedPreferences(
        	this.getString(R.string.const_string_preferences), 
        	Activity.MODE_PRIVATE);
        
        url = preferences.getString("url", "http://firefly.artica.es/pandora_demo");
        user = preferences.getString("user", "demo");
        password = preferences.getString("password", "demo");
        refreshTimeKey = preferences.getInt("refreshTimeKey", 3);
        
        EditText text = (EditText) findViewById(R.id.url);
        text.setText(url);
        text = (EditText) findViewById(R.id.user);
        text.setText(user);
        text = (EditText) findViewById(R.id.password);
        text.setText(password);
        
        Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
        ArrayAdapter<CharSequence> adapter = ArrayAdapter.createFromResource(
                this, R.array.refresh_combo, android.R.layout.simple_spinner_item);
        adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        combo.setAdapter(adapter);
        combo.setSelection(refreshTimeKey);
        
        final Button buttonSearch = (Button) findViewById(R.id.update_options);
        buttonSearch.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				save_options();
			}
		});
        
        if (this.object != null && this.object.show_popup_info) {
    		this.object.show_popup_info = false;
    		i = new Intent(this, About.class);
    		startActivity(i);
    	}
    }
    
    public void save_options() {
    	SharedPreferences preferences = getSharedPreferences(
            this.getString(R.string.const_string_preferences), 
            Activity.MODE_PRIVATE);
    	SharedPreferences.Editor editorPreferences = preferences.edit();
    	
    	EditText text = (EditText) findViewById(R.id.url);
    	String url = text.getText().toString();
    	if (url.charAt(url.length() - 1) == '/') {
    		url = url.substring(0, url.length() - 1);
    	}
    	
    	editorPreferences.putString("url", url);
    	//this.object.url = url;
    	text = (EditText) findViewById(R.id.user);
    	editorPreferences.putString("user", text.getText().toString());
    	//this.object.user = text.getText().toString();
    	text = (EditText) findViewById(R.id.password);
    	editorPreferences.putString("password", text.getText().toString());
    	//this.object.password = text.getText().toString();
    	
    	Spinner combo = (Spinner) findViewById(R.id.refresh_combo);
    	editorPreferences.putInt("refreshTimeKey", combo.getSelectedItemPosition());
    	
    	Context context = this.getApplicationContext();
    	int duration = Toast.LENGTH_SHORT;
    	
    	if (editorPreferences.commit()) {
    		if (this.core != null) {
    			this.core.stopServiceEventWatcher(getApplicationContext());
    			this.core.startServiceEventWatcher(getApplicationContext());
    		}
    		
    		Toast toast = Toast.makeText(context, this.getString(R.string.config_update_succesful_str), duration);
    		toast.show();
    	}
    	else {
    		Toast toast = Toast.makeText(context, this.getString(R.string.config_update_fail_str), duration);
    		toast.show();
    	}
    }
}
