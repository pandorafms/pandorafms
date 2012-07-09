package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.util.Map.Entry;

import android.app.Activity;
import android.app.ProgressDialog;
import android.os.AsyncTask;
import android.os.Bundle;
import android.util.Log;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.EditText;
import android.widget.Toast;

/**
 * Allows user to create an incident.
 * 
 * @author Santiago Munín González
 * 
 */
public class CreateIncidentActivity extends Activity {
	private static String TAG = "CreateIncidentActivity";
	private static int DEFAULT_STATUS_CODE = 0;
	private static int DEFAULT_PRIORITY_CODE = 0;
	private static String DEFAULT_SOURCE = "Pandora FMS Event";
	private EditText title, description;
	private ProgressDialog dialog;
	private String eventTitle, eventDescription, eventGroup;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		Bundle extras = getIntent().getExtras();
		if (extras != null) {
			eventTitle = extras.getString("title");
			eventDescription = extras.getString("description");
			eventGroup = extras.getString("group");
		}
		setContentView(R.layout.create_incident);
		initializeViews();
		resetViews();
	}

	/**
	 * Initializes views.
	 */
	private void initializeViews() {
		title = (EditText) findViewById(R.id.incident_title);
		description = (EditText) findViewById(R.id.incident_description);

		((Button) findViewById(R.id.incident_create_button))
				.setOnClickListener(new OnClickListener() {
					
					public void onClick(View v) {
						if (title != null && title.length() > 0) {
							dialog = ProgressDialog
									.show(CreateIncidentActivity.this,
											"",
											getString(R.string.creating_incident),
											true);
							new SetNewIncidentAsyncTask().execute((Void) null);
						} else {
							Toast.makeText(getApplicationContext(),
									R.string.title_empty, Toast.LENGTH_SHORT)
									.show();
						}
					}
				});
	}

	/**
	 * Resets views.
	 */
	private void resetViews() {

		title.setText(eventTitle);
		description.setText(eventDescription);
	}

	/**
	 * Performs the create incident petition.
	 * 
	 * @return <b>true</b> if it is created.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	private void sendNewIncident() throws IOException {
		Log.i(TAG, "Sending new incident");
		String incidentParams[] = new String[6];
		incidentParams[0] = title.getText().toString();
		incidentParams[1] = description.getText().toString();
		incidentParams[2] = String.valueOf(DEFAULT_SOURCE);
		incidentParams[3] = String.valueOf(DEFAULT_PRIORITY_CODE);
		incidentParams[4] = String.valueOf(DEFAULT_STATUS_CODE);
		int groupCode = -1;
		for (Entry<Integer, String> entry : API.getGroups(
				getApplicationContext()).entrySet()) {
			if (entry.getValue().equals(eventGroup)) {
				groupCode = entry.getKey();
			}
		}
		if (groupCode >= 0) {
			incidentParams[5] = String.valueOf(groupCode);
		}
		API.createNewIncident(getApplicationContext(), incidentParams);
	}

	/**
	 * Performs the api call to add the new incident
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class SetNewIncidentAsyncTask extends
			AsyncTask<Void, Void, Boolean> {

		@Override
		protected Boolean doInBackground(Void... params) {
			try {
				sendNewIncident();
				return true;
			} catch (IOException e) {
				return false;
			}
		}

		@Override
		protected void onPostExecute(Boolean result) {
			if (result) {
				Toast.makeText(getApplicationContext(),
						R.string.incident_created, Toast.LENGTH_SHORT).show();
				dialog.dismiss();
				finish();
			} else {
				Toast.makeText(getApplicationContext(),
						R.string.create_incident_group_error,
						Toast.LENGTH_SHORT).show();
				dialog.dismiss();
			}
		}
	}
}