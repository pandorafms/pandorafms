package pandroid.agent;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;

import android.app.Activity;
import android.app.ActivityManager;
import android.app.ActivityManager.RunningAppProcessInfo;
import android.app.Dialog;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.pm.PackageManager.NameNotFoundException;
import android.os.AsyncTask;
import android.os.Bundle;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.Toast;
import android.view.WindowManager;
import android.view.inputmethod.InputMethodManager;

public class Setup extends Activity {
	
	private HashMap<String, String> listProcesses;
	
	
	/** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        listProcesses = new HashMap<String, String>();
        
        Core.loadConf(getApplicationContext());
		
        setContentView(R.layout.setup);
        loadViews();
		loadInBackgroundProcessInExecution();
		setButtonEvents();
        
    }
    
    public void onResume() {
        super.onResume();
        if(Core.password.equals(Core.defaultPassword))
        {
        	createpass();
        }
        else{
    	enterpass();
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
    
	private void setButtonEvents() {
        // Set update button events
        Button updateButton = (Button) findViewById(R.id.update);
        
        updateButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		getDataFromView();
        		boolean result = Core.updateConf(getApplicationContext());
        		
        		if (result) {
        			Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.correct_update),
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
    
	
	
    private void loadInBackgroundProcessInExecution() {
    	new GetProcessInExecutionAsyncTask().execute();
    }
    
    public class GetProcessInExecutionAsyncTask extends AsyncTask<Void, Void, Void> {

		@Override
		protected Void doInBackground(Void... params) {
			getProcess();
			
			return null;
		}
		
		private void getProcess() {
			listProcesses.clear();
			
			ActivityManager activityManager = (ActivityManager)getApplication().getSystemService(ACTIVITY_SERVICE);
			List<RunningAppProcessInfo> runningAppProcessInfos = activityManager.getRunningAppProcesses();
			PackageManager pm = getApplication().getPackageManager();
			RunningAppProcessInfo runningAppProcessInfo;
			
			for (int i = 0; i < runningAppProcessInfos.size(); i++) {
				runningAppProcessInfo = runningAppProcessInfos.get(i);
				
				try {
					CharSequence human_name = pm.getApplicationLabel(
						pm.getApplicationInfo(runningAppProcessInfo.processName, PackageManager.GET_META_DATA));
					
					listProcesses.put(runningAppProcessInfo.processName, human_name + "");
				}
				catch (NameNotFoundException e) {
					listProcesses.put(runningAppProcessInfo.processName, runningAppProcessInfo.processName);
				}
			}
		}
		
		
		@Override
		protected void onPostExecute(Void unused)
		{
			
			Spinner combo = (Spinner)findViewById(R.id.processes_combo);
			ArrayList<String> listProcess = new ArrayList<String>(listProcesses.keySet());
			ArrayList<String> listProcessHuman = new ArrayList<String>(listProcesses.values());
			int position = 0;
			
		    if (Core.task.length() != 0) {
		    	position = listProcess.indexOf(Core.task);
		    	
		    	String text = Core.task;
		    	if (Core.taskHumanName.length() != 0) {
		    		text = Core.taskHumanName;
		    	}
		    	
		    	//If the process is not running, add in the list at the end
		    	if (position == -1) {
		    		listProcesses.put(Core.task, text);
		    		
		    		//The asociative array is reordened, and need to extract th subarrays again.
		    		listProcess = new ArrayList<String>(listProcesses.keySet());
					listProcessHuman = new ArrayList<String>(listProcesses.values());
		    		
		    		position = listProcess.indexOf(Core.task);	
		    	}
		    }
		    
			
	    	ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(getApplicationContext(),
		    	android.R.layout.simple_spinner_item, listProcessHuman);
		    combo.setAdapter(spinnerArrayAdapter);
		    
		    combo.setSelection(position);
		    
		    ProgressBar progressBar = (ProgressBar) findViewById(R.id.loading_task_anim);
		    progressBar.setVisibility(ProgressBar.GONE);
		    
		    combo.setVisibility(Spinner.VISIBLE);
		    
		    CheckBox checkbox = (CheckBox)findViewById(R.id.checkTaskReport);
		    checkbox.setEnabled(true);
		    
		    Button button = (Button)findViewById(R.id.update);
		    button.setEnabled(true);
		    
		}
    	
    	
    	
    }
	


    private void getDataFromView() {
        // Init form values
		EditText editText;
		CheckBox checkBox;
		Spinner combo;
		
		editText = (EditText) findViewById(R.id.serverAddrInput);
		Core.serverAddr = editText.getText().toString();
        
		editText = (EditText) findViewById(R.id.serverPortInput);
		Core.serverPort = editText.getText().toString();
        
		editText = (EditText) findViewById(R.id.intervalInput);
		Core.interval = new Integer(editText.getText().toString()).intValue();
        
		editText = (EditText) findViewById(R.id.agentNameInput);
		Core.agentName = editText.getText().toString();
        
        checkBox = (CheckBox) findViewById(R.id.checkGpsReport);
        if (checkBox.isChecked())
        	Core.gpsStatus = "enabled";
        else
        	Core.gpsStatus = "disabled";
        
        checkBox = (CheckBox) findViewById(R.id.checkMemoryReport);
        if (checkBox.isChecked())
        	Core.memoryStatus = "enabled";
        else
        	Core.memoryStatus = "disabled";
        checkBox = (CheckBox) findViewById(R.id.checkSimIDReport);
        if (checkBox.isChecked())
        	Core.simIDStatus = "enabled";
        else
        	Core.simIDStatus = "disabled";
        
        
        checkBox = (CheckBox) findViewById(R.id.checkTaskReport);
        if (checkBox.isChecked()) {
        	Core.taskStatus = "enabled";
        	
        	combo = (Spinner)findViewById(R.id.processes_combo);
        	int position = combo.getSelectedItemPosition();
        	ArrayList<String> listProcess = new ArrayList<String>(listProcesses.keySet());
			ArrayList<String> listProcessHuman = new ArrayList<String>(listProcesses.values());
        	Core.task = listProcess.get(position);
        	Core.taskHumanName = listProcessHuman.get(position);
        }
        else {
        	Core.taskStatus = "disabled";
        	Core.task = "";
        	Core.taskHumanName = "";
        }
        
    }
    
	private void loadViews(){
        // Init form values
		EditText editText;
		CheckBox checkBox;
		
		editText = (EditText) findViewById(R.id.serverAddrInput);
		editText.setText(Core.serverAddr);
        
		editText = (EditText) findViewById(R.id.serverPortInput);
		editText.setText(Core.serverPort);
        
		editText = (EditText) findViewById(R.id.intervalInput);
		editText.setText(Integer.toString(Core.interval));
        
		editText = (EditText) findViewById(R.id.agentNameInput);
		editText.setText(Core.agentName);
        
        checkBox = (CheckBox) findViewById(R.id.checkGpsReport);
        checkBox.setChecked(Core.gpsStatus.equals("enabled"));
        
        checkBox = (CheckBox) findViewById(R.id.checkMemoryReport);
        checkBox.setChecked(Core.memoryStatus.equals("enabled"));
        
        checkBox = (CheckBox) findViewById(R.id.checkSimIDReport);
        checkBox.setChecked(Core.simIDStatus.equals("enabled"));
        
        checkBox = (CheckBox) findViewById(R.id.checkTaskReport);
        checkBox.setChecked(Core.taskStatus.equals("enabled"));
        
	}
	public void createpass() {
		//set up dialog
		final Dialog dialog = new Dialog(this);
		dialog.setContentView(R.layout.password_create);
		dialog.setTitle("Set Password");
		dialog.setCancelable(false);
		dialog.getWindow().setSoftInputMode (WindowManager.LayoutParams.SOFT_INPUT_STATE_ALWAYS_VISIBLE);
		//there are a lot of settings, for dialog, check them all out!

		//set up text
		final EditText text = (EditText) dialog.findViewById(R.id.password_create_field);
		text.setText("");
		//set up text
		final EditText text2 = (EditText) dialog.findViewById(R.id.password_create_field_2);
		text2.setText("");


		//set up button
		Button button = (Button) dialog.findViewById(R.id.password_create_button);
		button.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {

		    String createpass_password = text.getText().toString().trim();
			String createpass_password2 = text2.getText().toString().trim();
			
		    try
		    {
		        if(createpass_password.equals(createpass_password2))
		        {
		        	Core.password = createpass_password;
		        	Core.updateConf(getApplicationContext());
		        	//Core.restartAgentListener(getApplicationContext());
		        	dialog.dismiss();
		        }
		       else 
		        {
		        	Toast toast = Toast.makeText(getApplicationContext(), getString(R.string.password_no_match), Toast.LENGTH_SHORT);
	        	    	toast.show();
		        }
		    }
		    catch(Exception x)
		    {       
		    	Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.password_error),
        	       		Toast.LENGTH_SHORT);
        	    	toast.show();
		        finish();
		    }

		}
		});
		//now that the dialog is set up, it's time to show it   

		    dialog.show();

	}// end createpass
	
	public void enterpass() {
		//set up dialog
		final Dialog dialog = new Dialog(this);
		dialog.setContentView(R.layout.password_entry);
		dialog.setTitle("Enter Password");
		dialog.setCancelable(false);
		dialog.getWindow().setSoftInputMode (WindowManager.LayoutParams.SOFT_INPUT_STATE_ALWAYS_VISIBLE);
		//there are a lot of settings, for dialog, check them all out!

		//set up text
		final EditText text = (EditText) dialog.findViewById(R.id.password_entry_input);
		text.setText("");
		

		//set up button
		Button button = (Button) dialog.findViewById(R.id.password_entry_button);
		button.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {
			
			String password = text.getText().toString().trim();
				
			
		    try
		    {
		        if(password.equals(Core.password))
		        {
		        	InputMethodManager im = (InputMethodManager)getSystemService(getApplicationContext().INPUT_METHOD_SERVICE);
                    im.hideSoftInputFromWindow(text.getWindowToken(), 0);
		        	dialog.dismiss();
		        }
		        else
		        {
		        	Toast toast = Toast.makeText(getApplicationContext(), getString(R.string.password_no_match), Toast.LENGTH_SHORT);
        	    	toast.show();
        	    	//finish();
		        }
		      
		    }
		    catch(Exception x)
		    {       
		    	Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.password_error),
        	       		Toast.LENGTH_SHORT);
        	    	toast.show();
		        finish();
		    }

		}
		});
		
		//now that the dialog is set up, it's time to show it   
		Button backButton = (Button) dialog.findViewById(R.id.password_back_button);
		backButton.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {
				dialog.dismiss();
				switchTabInActivity(0);
			
			}
		});
		   
		dialog.show();
	
	}// end enterpass
	
	public void switchTabInActivity(int indexTabToSwitchTo){
        PandroidAgent ParentActivity;
        ParentActivity = (PandroidAgent) this.getParent();
        ParentActivity.switchTab(indexTabToSwitchTo);
}
}
