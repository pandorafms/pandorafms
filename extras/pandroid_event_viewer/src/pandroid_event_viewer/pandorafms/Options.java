package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

public class Options extends Activity {
	public String url;
	public String user;
	public String password;
	
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.options);
        
        SharedPreferences preferences = getSharedPreferences(
        	this.getString(R.string.const_string_preferences), 
        	Activity.MODE_PRIVATE);
        
        url = preferences.getString("url", "");
        user = preferences.getString("user", "");
        password = preferences.getString("password", "");
        
        EditText text = (EditText) findViewById(R.id.url);
        text.setText(url);
        text = (EditText) findViewById(R.id.user);
        text.setText(user);
        
        final Button buttonSearch = (Button) findViewById(R.id.update_options);
        buttonSearch.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				save_options();
			}
		});
    }
    
    public void save_options() {
    	SharedPreferences preferences = getSharedPreferences(
            this.getString(R.string.const_string_preferences), 
            Activity.MODE_PRIVATE);
    	
    	SharedPreferences.Editor editorPreferences = preferences.edit();
    	
    	EditText text = (EditText) findViewById(R.id.url);
    	
    	editorPreferences.putString("url", text.getText().toString());
    	text = (EditText) findViewById(R.id.user);
    	editorPreferences.putString("user", text.getText().toString());
    	text = (EditText) findViewById(R.id.password);
    	editorPreferences.putString("password", text.getText().toString());
    	
    	Context context = this.getApplicationContext();
    	int duration = Toast.LENGTH_SHORT;
    	
    	if (editorPreferences.commit()) {
    		Toast toast = Toast.makeText(context, this.getString(R.string.config_update_succesful_str), duration);
    		toast.show();
    	}
    	else {
    		Toast toast = Toast.makeText(context, this.getString(R.string.config_update_fail_str), duration);
    		toast.show();
    	}
    }
}
