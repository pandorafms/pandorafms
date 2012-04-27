package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
import java.util.List;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Toast;

public class PopupValidationEvent extends Activity {
	public int id_event;
	public String comment;
	
	public PandroidEventviewerActivity object;
	public Core core;
	
	public String url;
	public String user;
	public String password;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        Intent i = getIntent();
        this.id_event = i.getIntExtra("id_event", -1);
    	//this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        this.core = (Core)i.getSerializableExtra("core");
        
        setContentView(R.layout.popup_validation_event);
        
        final Button button = (Button) findViewById(R.id.button_validate_event);
        
        button.setOnClickListener(new View.OnClickListener() {		
    		@Override
    		public void onClick(View v) {
    			validateEvent();
    		}
    	});
    }
    
    public void validateEvent() {
    	EditText textbox = (EditText) findViewById(R.id.comment);
    	String comment = textbox.getText().toString();
    	Button button = (Button) findViewById(R.id.button_validate_event);
    	ProgressBar pb = (ProgressBar) findViewById(R.id.send_progress);
    	
    	button.setVisibility(Button.GONE);
    	pb.setVisibility(ProgressBar.VISIBLE);
    	
    	this.comment = "Validate from Pandroid Eventviewer Mobile: " + comment;
    	
    	new SendValidationAsyncTask().execute();
    }
    
    public void destroyPopup() {
    	finish();
    }
    
    public boolean sendValidation() {
    	boolean return_var = false;
    	
		if (this.url == null) {
	        SharedPreferences preferences = getApplicationContext().getSharedPreferences(
	        	getApplicationContext().getString(R.string.const_string_preferences), 
	        	Activity.MODE_PRIVATE);
	            
	        this.url = preferences.getString("url", "");
	        this.user = preferences.getString("user", "");
	        this.password = preferences.getString("password", "");
		}
		try {
	        DefaultHttpClient httpClient = new DefaultHttpClient();
	        UrlEncodedFormEntity entity;
	        HttpPost httpPost;
	        List<NameValuePair> parameters;
	        HttpResponse response;
	        HttpEntity entityResponse;
	        String return_api;
			
	    	httpPost = new HttpPost(this.url + "/include/api.php");
	    	
	    	//Set event validation.
	    	parameters = new ArrayList<NameValuePair>();
	    	parameters.add(new BasicNameValuePair("user", this.user));
	    	parameters.add(new BasicNameValuePair("pass", this.password));
	        parameters.add(new BasicNameValuePair("op", "set"));
	        parameters.add(new BasicNameValuePair("op2", "validate_events"));
	        parameters.add(new BasicNameValuePair("id", new Integer(this.id_event).toString()));
	        parameters.add(new BasicNameValuePair("other", this.comment));
	        entity = new UrlEncodedFormEntity(parameters);
	        httpPost.setEntity(entity);
	    	response = httpClient.execute(httpPost);
	    	entityResponse = response.getEntity();
	    	return_api = Core.convertStreamToString(entityResponse.getContent());
	    	return_api = return_api.replace("\n", "");
	    	
	    	if (return_api.startsWith("Correct validation")) {
	    		return_var = true;
	    	}
    	}
    	catch (Exception e) {
    		Log.e("EXCEPTION sendValidation", e.getMessage());
    	}
		
		return return_var;
    }
    
    public class SendValidationAsyncTask extends AsyncTask<Void, Void, Void> {
    	public ArrayList<String> lista;
    	public boolean result;
    	
		@Override
		protected Void doInBackground(Void... params) {
			result = sendValidation();
			
			return null;
		}
		
		@Override
		protected void onPostExecute(Void unused)
		{
			String text;
			
			if (result) {
				text = getApplicationContext().getString(R.string.successful_validate_event_str);
			}
			else {
				text = getApplicationContext().getString(R.string.fail_validate_event_str);
			}
			
			Toast toast = Toast.makeText(getApplicationContext(), text, Toast.LENGTH_SHORT);
    		toast.show();
    		
    		destroyPopup();
		}
    }
}
