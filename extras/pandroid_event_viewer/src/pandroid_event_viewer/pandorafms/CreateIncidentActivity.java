package pandroid_event_viewer.pandorafms;

import java.util.ArrayList;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;

import org.apache.http.NameValuePair;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.AdapterView;
import android.widget.AdapterView.OnItemSelectedListener;
import android.widget.ArrayAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Spinner;

/**
 * Allows user to create an incident.
 * 
 * @author Santiago Munín González
 * 
 */
public class CreateIncidentActivity extends Activity {
	private static String TAG = "CreateIncidentActivity";
	EditText title, description;
	Spinner source, priority, group, status;
	int priority_code, status_code;
	Map<Integer, String> groups;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.create_incident);
		setViews();
	}

	private void setViews() {
		title = (EditText) findViewById(R.id.incident_title);
		title.setText("");
		description = (EditText) findViewById(R.id.incident_description);
		description.setText("");
		priority = (Spinner) findViewById(R.id.incident_priority);
		priority.setOnItemSelectedListener(new OnItemSelectedListener() {

			@Override
			public void onItemSelected(AdapterView<?> arg0, View arg1,
					int arg2, long arg3) {
				priority_code = arg0.getSelectedItemPosition();
				if (priority_code == 5) {
					priority_code = 10;
				}
			}

			@Override
			public void onNothingSelected(AdapterView<?> arg0) {
			}
		});
		group = (Spinner) findViewById(R.id.incident_group);
		source = (Spinner) findViewById(R.id.incident_source);
		status = (Spinner) findViewById(R.id.incident_status);
		status.setOnItemSelectedListener(new OnItemSelectedListener() {

			@Override
			public void onItemSelected(AdapterView<?> arg0, View arg1,
					int arg2, long arg3) {
				status_code = arg0.getSelectedItemPosition();
				if (status_code == 4) {
					status_code = 13;
				}
			}

			@Override
			public void onNothingSelected(AdapterView<?> arg0) {
			}
		});
		new GetGroupsAsyncTask().execute((Void) null);

		((Button) findViewById(R.id.incident_create_button))
				.setOnClickListener(new OnClickListener() {

					@Override
					public void onClick(View v) {
						sendNewIncident();
					}
				});
	}

	/**
	 * Performs the create incident petition.
	 */
	private void sendNewIncident() {
		Log.i(TAG, "Sending new incident");
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "set"));
		parameters.add(new BasicNameValuePair("op2", "new_incident"));
		/*
		 * 
		 * op=set&op2=new_incident&other=titulo|descripcion%20texto|Logfiles|2|10
		 * |12&other_mode=url_encode_separator_|
		 */
		parameters.add(new BasicNameValuePair("other_mode",
				"url_encode_separator_|"));
		String incidentParams[] = new String[6];
		incidentParams[0] = title.getText().toString();
		incidentParams[1] = description.getText().toString();
		incidentParams[2] = String.valueOf(source.getSelectedItem().toString());
		incidentParams[3] = String.valueOf(priority_code);
		incidentParams[4] = String.valueOf(status_code);
		incidentParams[5] = String.valueOf(groups.get(group
				.getSelectedItemPosition()));
		parameters.add(new BasicNameValuePair("other", Core
				.serializeParams2Api(incidentParams)));
		Core.httpGet(getApplicationContext(), parameters);

	}

	/**
	 * Async task which get groups.
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class GetGroupsAsyncTask extends
			AsyncTask<Void, Void, Map<Integer, String>> {

		@Override
		protected Map<Integer, String> doInBackground(Void... params) {
			return Core.getGroups(getApplicationContext());
		}

		@Override
		protected void onPostExecute(Map<Integer, String> result) {
			groups = result;
			List<String> list = new LinkedList<String>();
			for (Entry<Integer, String> entry : result.entrySet()) {
				list.add(entry.getValue());
			}
			ArrayAdapter<String> spinnerArrayAdapter = new ArrayAdapter<String>(
					getApplicationContext(),
					android.R.layout.simple_spinner_item, list);
			group.setAdapter(spinnerArrayAdapter);
			group.setSelection(0);
			/*
			 * ProgressBar loadingGroup = (ProgressBar)
			 * findViewById(R.id.loading_group);
			 * 
			 * loadingGroup.setVisibility(ProgressBar.GONE);
			 * combo.setVisibility(Spinner.VISIBLE);
			 */
		}
	}

	/*
	 * private class GetAgentsAsyncTask extends AsyncTask<Void, Void,
	 * Map<Integer, String>> {
	 * 
	 * @Override protected Map<Integer, String> doInBackground(Void... params) {
	 * return Core.getAgents(getApplicationContext()); }
	 * 
	 * @Override protected void onPostExecute(Map<Integer, String> result) {
	 * agents = result; List<String> list = new LinkedList<String>(); for
	 * (Entry<Integer, String> entry : result.entrySet()) {
	 * list.add(entry.getValue()); } ArrayAdapter<String> spinnerArrayAdapter =
	 * new ArrayAdapter<String>( getApplicationContext(),
	 * android.R.layout.simple_spinner_item, list);
	 * agent.setAdapter(spinnerArrayAdapter); agent.setSelection(0); /*
	 * ProgressBar loadingGroup = (ProgressBar)
	 * findViewById(R.id.loading_group);
	 * 
	 * loadingGroup.setVisibility(ProgressBar.GONE);
	 * combo.setVisibility(Spinner.VISIBLE);
	 */
	// }
	// }
}
