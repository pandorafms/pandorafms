// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details. 

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
import android.net.Uri;
import android.os.AsyncTask;
import android.os.Bundle;
import android.text.TextUtils;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.WindowManager;
import android.view.WindowManager.LayoutParams;
import android.view.inputmethod.InputMethodManager;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.CheckBox;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.RelativeLayout;
import android.widget.Spinner;
import android.widget.Toast;

public class Setup extends Activity {
	
	private HashMap<String, String> listProcesses;
	
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        listProcesses = new HashMap<String, String>();
        Core.loadConf(getApplicationContext());
    }
    
    public void onResume() {
        super.onResume();
        
        if(Core.hasSim)
        	setContentView(R.layout.setup);
        else
        	setContentView(R.layout.setupnosim);
        
     	loadViews();
        loadInBackgroundProcessInExecution();
		setButtonEvents();
       
        if(Core.password.equals(Core.defaultPassword))
        {
        	if(Core.passwordCheck.equals("enabled"))
        		passwordChoose();
        }
        else{
        	LayoutInflater inflater=(LayoutInflater)getSystemService(LAYOUT_INFLATER_SERVICE);
        	View view=inflater.inflate(R.layout.setup, null);
        	RelativeLayout setup = (RelativeLayout)view.findViewById(R.id.setup);
        	setContentView(setup);
        	setup.setVisibility(RelativeLayout.INVISIBLE);
        	enterPass();
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
        Button updateButton = (Button) findViewById(R.id.update);
        
        updateButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		getDataFromView();
        		boolean result = Core.updateConf(getApplicationContext());
        		
        		if (result) {
        			Toast toast = Toast.makeText(getApplicationContext(),
        	       		getString(R.string.config_saved),
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
        
        Button passwordButton = (Button) findViewById(R.id.set_password);
        passwordButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		createPass();
        	}
        });
        
        Button webButton = (Button) findViewById(R.id.goToWebButton);
        webButton.setOnClickListener(new OnClickListener() {
        	public void onClick(View view) {
        		 getDataFromView();
        		 Core.updateConf(getApplicationContext());
        		 Uri uri = Uri.parse(Core.mobileWebURL);
        		 Intent intent = new Intent(Intent.ACTION_VIEW, uri);
        		 startActivity(intent);
        	}
        });
        
	}// end setButtonEvents
    
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
		    		
		    		//The asociative array is reordened, and need to extract the sub arrays again.
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
		    		    
		    Button button = (Button)findViewById(R.id.update);
		    button.setEnabled(true);
		    
		    button = (Button)findViewById(R.id.set_password);
		    button.setEnabled(true);
		    
		    button = (Button)findViewById(R.id.goToWebButton);
		    button.setEnabled(true);
		}
    }// end onPostExecute
	
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
		Core.interval = Integer.valueOf(editText.getText().toString()).intValue();
        
		editText = (EditText) findViewById(R.id.agentNameInput);
		Core.agentName = editText.getText().toString();
		
		editText = (EditText) findViewById(R.id.mobileWebURLInput);
		Core.mobileWebURL = "http://"+editText.getText().toString();
        
        checkBox = (CheckBox) findViewById(R.id.checkGpsReport);
        if (checkBox.isChecked())
        	Core.gpsStatus = "enabled";
        else
        	Core.gpsStatus = "disabled";
        
        checkBox = (CheckBox) findViewById(R.id.checkBatteryLevelReport);
        if (checkBox.isChecked())
        	Core.BatteryLevelReport = "enabled";
        else
        	Core.BatteryLevelReport = "disabled";
        
        checkBox = (CheckBox) findViewById(R.id.checkMemoryReport);
        if (checkBox.isChecked())
        	Core.memoryStatus = "enabled";
        else
        	Core.memoryStatus = "disabled";
        
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
        
        checkBox = (CheckBox) findViewById(R.id.checkDeviceUpTimeReport);
        if (checkBox.isChecked())
        	Core.DeviceUpTimeReport = "enabled";
        else
        	Core.DeviceUpTimeReport = "disabled";

        if (Core.hasSim) {
        	
        	checkBox = (CheckBox) findViewById(R.id.checkSimIDReport);
            if (checkBox.isChecked())
            	Core.simIDReport = "enabled";
            else
            	Core.simIDReport = "disabled";
            
        	checkBox = (CheckBox) findViewById(R.id.checkNetworkOperatorReport);
        	if (checkBox.isChecked())
        		Core.NetworkOperatorReport = "enabled";
        	else
        		Core.NetworkOperatorReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkNetworkTypeReport);
        	if (checkBox.isChecked())
        		Core.NetworkTypeReport = "enabled";
        	else
        		Core.NetworkTypeReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkPhoneTypeReport);
        	if (checkBox.isChecked())
        		Core.PhoneTypeReport = "enabled";
        	else
        		Core.PhoneTypeReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkSignalStrengthReport);
        	if (checkBox.isChecked())
        		Core.SignalStrengthReport = "enabled";
        	else
        		Core.SignalStrengthReport = "disabled";
        	        
        	checkBox = (CheckBox) findViewById(R.id.checkReceivedSMSReport);
        	if (checkBox.isChecked())
        		Core.ReceivedSMSReport = "enabled";
        	else
        		Core.ReceivedSMSReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkSentSMSReport);
        	if (checkBox.isChecked())
        		Core.SentSMSReport = "enabled";
        	else
        		Core.SentSMSReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkIncomingCallsReport);
        	if (checkBox.isChecked())
        		Core.IncomingCallsReport = "enabled";
        	else
        		Core.IncomingCallsReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkMissedCallsReport);
        	if (checkBox.isChecked())
        		Core.MissedCallsReport = "enabled";
        	else
        		Core.MissedCallsReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkOutgoingCallsReport);
        	if (checkBox.isChecked())
        		Core.OutgoingCallsReport = "enabled";
        	else
        		Core.OutgoingCallsReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkBytesReceivedReport);
        	if (checkBox.isChecked())
        		Core.BytesReceivedReport = "enabled";
        	else
        		Core.BytesReceivedReport = "disabled";
        
        	checkBox = (CheckBox) findViewById(R.id.checkBytesSentReport);
        	if (checkBox.isChecked())
        		Core.BytesSentReport = "enabled";
        	else
        		Core.BytesSentReport = "disabled";
        	
        	checkBox = (CheckBox) findViewById(R.id.checkRoamingReport);
        	if (checkBox.isChecked())
        		Core.RoamingReport = "enabled";
        	else
        		Core.RoamingReport = "disabled";
        }// end if sim card
        
        checkBox = (CheckBox) findViewById(R.id.checkHelloSignalReport);
        if (checkBox.isChecked())
        	Core.HelloSignalReport = "enabled";
        else
        	Core.HelloSignalReport = "disabled";
        
        Core.updateConf(getApplicationContext());
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
		
		editText = (EditText) findViewById(R.id.mobileWebURLInput);
		editText.setText(Core.mobileWebURL);
        
        checkBox = (CheckBox) findViewById(R.id.checkGpsReport);
        checkBox.setChecked(Core.gpsStatus.equals("enabled"));
        
        checkBox = (CheckBox) findViewById(R.id.checkBatteryLevelReport);
        checkBox.setChecked(Core.BatteryLevelReport.equals("enabled"));
       
        checkBox = (CheckBox) findViewById(R.id.checkMemoryReport);
        checkBox.setChecked(Core.memoryStatus.equals("enabled"));   
        
        checkBox = (CheckBox) findViewById(R.id.checkTaskReport);
        checkBox.setChecked(Core.taskStatus.equals("enabled"));
        
        checkBox = (CheckBox) findViewById(R.id.checkDeviceUpTimeReport);
        checkBox.setChecked(Core.DeviceUpTimeReport.equals("enabled"));

        if (Core.hasSim) {
        	
        	checkBox = (CheckBox) findViewById(R.id.checkSimIDReport);
            checkBox.setChecked(Core.simIDReport.equals("enabled"));
            
        	checkBox = (CheckBox) findViewById(R.id.checkNetworkOperatorReport);
        	checkBox.setChecked(Core.NetworkOperatorReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkNetworkTypeReport);
        	checkBox.setChecked(Core.NetworkTypeReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkPhoneTypeReport);
        	checkBox.setChecked(Core.PhoneTypeReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkSignalStrengthReport);
        	checkBox.setChecked(Core.SignalStrengthReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkReceivedSMSReport);
        	checkBox.setChecked(Core.ReceivedSMSReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkSentSMSReport);
        	checkBox.setChecked(Core.SentSMSReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkIncomingCallsReport);
        	checkBox.setChecked(Core.IncomingCallsReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkMissedCallsReport);
        	checkBox.setChecked(Core.MissedCallsReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkOutgoingCallsReport);
        	checkBox.setChecked(Core.OutgoingCallsReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkBytesReceivedReport);
        	checkBox.setChecked(Core.BytesReceivedReport.equals("enabled"));
        
        	checkBox = (CheckBox) findViewById(R.id.checkBytesSentReport);
        	checkBox.setChecked(Core.BytesSentReport.equals("enabled"));
        	
        	checkBox = (CheckBox) findViewById(R.id.checkRoamingReport);
        	checkBox.setChecked(Core.RoamingReport.equals("enabled"));
        }//end if sim card
        
        checkBox = (CheckBox) findViewById(R.id.checkHelloSignalReport);
        checkBox.setChecked(Core.HelloSignalReport.equals("enabled"));
        
    }
	
	public void passwordChoose() {
		final Dialog dialog = new Dialog(this);
		dialog.setContentView(R.layout.password_choose);
		dialog.setTitle(getString(R.string.password_choose_text));
		dialog.setCancelable(false);
		
        Button yes = (Button) dialog.findViewById(R.id.yes_button);
		yes.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {
				Core.passwordCheck = "disabled";
				Core.updateConf(getApplicationContext());
				dialog.dismiss();
				createPass();
			} // end onClick
		});//end clickListener
		
		Button no = (Button) dialog.findViewById(R.id.no_button);
		no.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {
			
				CheckBox cB = (CheckBox) dialog.findViewById(R.id.password_checkbox);
				if (cB.isChecked())
					Core.passwordCheck = "disabled";
				else
					Core.passwordCheck = "enabled";	
				Core.updateConf(getApplicationContext());
				dialog.dismiss();
			} // end onClick
		});//end clickListener
		
		dialog.show();
	}
	
	public void createPass() {
		
		final Dialog dialog = new Dialog(this);
		dialog.setContentView(R.layout.password_create);
		dialog.setCancelable(false);
		dialog.getWindow().setSoftInputMode (WindowManager.LayoutParams.SOFT_INPUT_STATE_ALWAYS_VISIBLE);
		
		final EditText text = (EditText) dialog.findViewById(R.id.password_create_field);
		text.setText("");
		
		final EditText text2 = (EditText) dialog.findViewById(R.id.password_create_field_2);
		text2.setText("");

		Button button = (Button) dialog.findViewById(R.id.password_create_button);
		button.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {

		    String createpass_password = text.getText().toString().trim();
			String createpass_password2 = text2.getText().toString().trim();
			
		    try
		    {
		    	if(TextUtils.isEmpty(createpass_password))
		    	{
		    		Core.password = Core.defaultPassword;
		    		Core.updateConf(getApplicationContext());
		    		InputMethodManager im = (InputMethodManager)getSystemService(getApplicationContext().INPUT_METHOD_SERVICE);
                    im.hideSoftInputFromWindow(text.getWindowToken(), 0);
		    		dialog.dismiss();
		    		
		    		Toast toast = Toast.makeText(getApplicationContext(),
	        	       		getString(R.string.password_removed),
	        	       		Toast.LENGTH_SHORT);
	        	    	toast.show();
	        	    	
		    		return;
		    	}
		    	else if(createpass_password.length() < 6) 
				{ 
					text.setError(getString(R.string.password_length)); 
					text2.setError(getString(R.string.password_length)); 
					return; 
				}
		    	
		    	else if(createpass_password.equals(createpass_password2))
		        {
		        	Core.password = createpass_password;
		        	Core.updateConf(getApplicationContext());
		        	//Core.restartAgentListener(getApplicationContext());
		        	InputMethodManager im = (InputMethodManager)getSystemService(getApplicationContext().INPUT_METHOD_SERVICE);
                    im.hideSoftInputFromWindow(text.getWindowToken(), 0);
                  
		        	dialog.dismiss();
		        	
		        	Toast toast = Toast.makeText(getApplicationContext(),
	        	       		getString(R.string.password_created),
	        	       		Toast.LENGTH_SHORT);
	        	    	toast.show();
		        	
		        	return;
		        }
		    	else 
		        {
		    	   text2.setError(getString(R.string.password_no_match));
	        	   return; 	
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
		   } // end onClick
			
		});//end clickListener
			
		dialog.show();

	}// end createPass
	
	public void enterPass() {
		
		final Dialog dialog = new Dialog(this,android.R.style.Theme_Black_NoTitleBar_Fullscreen);
		dialog.setContentView(R.layout.password_entry);
		dialog.setTitle(getString(R.string.password_enter));
		dialog.setCancelable(false);
		dialog.getWindow().setSoftInputMode (WindowManager.LayoutParams.SOFT_INPUT_STATE_ALWAYS_VISIBLE);
		dialog.getWindow().setFlags(LayoutParams.FLAG_FULLSCREEN, LayoutParams.FLAG_FULLSCREEN);
		
		
		final EditText text = (EditText) dialog.findViewById(R.id.password_entry_input);
		text.setText("");
		
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
		        	
		            if(Core.hasSim){
		            	LayoutInflater inflater=(LayoutInflater)getSystemService(LAYOUT_INFLATER_SERVICE);
		            	View view=inflater.inflate(R.layout.setup, null);
		            	RelativeLayout setup = (RelativeLayout)view.findViewById(R.id.setup);
		            	setContentView(setup);
		            	loadViews();
			    		loadInBackgroundProcessInExecution();
			    		setButtonEvents();
			        	setup.setVisibility(RelativeLayout.VISIBLE);
		            }
		            else{
		            	LayoutInflater inflater=(LayoutInflater)getSystemService(LAYOUT_INFLATER_SERVICE);
		            	View view=inflater.inflate(R.layout.setupnosim, null);
		            	RelativeLayout setupnosim = (RelativeLayout)view.findViewById(R.id.setupnosim);
		            	setContentView(setupnosim);
		            	loadViews();
			    		loadInBackgroundProcessInExecution();
			    		setButtonEvents();
			        	setupnosim.setVisibility(RelativeLayout.VISIBLE);
		            }
		        }
		        else
		        {
		        	text.setError(getString(R.string.password_incorrect)); 
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
		
		Button backButton = (Button) dialog.findViewById(R.id.password_back_button);
		backButton.setOnClickListener(new OnClickListener() {
		@Override
		   public void onClick(View v) {
				dialog.dismiss();
				switchTabInActivity(0);
			}
		});
		   
		dialog.show();
		
	}// end enterPass
	
	/**
	 * Allows this activity to switch the parent tab
	 * @param indexTabToSwitchTo
	 */
	public void switchTabInActivity(int indexTabToSwitchTo){
        PandroidAgent ParentActivity;
        ParentActivity = (PandroidAgent) this.getParent();
        ParentActivity.switchTab(indexTabToSwitchTo);
	}
}
