package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.http.NameValuePair;
import org.apache.http.message.BasicNameValuePair;

import android.content.Context;
import android.util.Log;

public class API {

	private static String TAG = "API";

	/**
	 * Get groups through an api call.
	 * 
	 * @param context
	 *            Application context.
	 * @return Map containing id -> group.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	public static Map<Integer, String> getGroups(Context context)
			throws IOException {
		Map<Integer, String> result = new HashMap<Integer, String>();
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "groups"));
		parameters.add(new BasicNameValuePair("other_mode",
				"url_encode_separator_|"));
		parameters.add(new BasicNameValuePair("return_type", "csv"));
		parameters.add(new BasicNameValuePair("other", ";"));

		String return_api = Core.httpGet(context, parameters);
		String[] lines = return_api.split("\n");
		try {
			for (int i = 0; i < lines.length; i++) {
				String[] groups = lines[i].split(";", 21);
				result.put(Integer.valueOf(groups[0]), groups[1]);
			}
		} catch (NumberFormatException e) {
			Log.e(TAG, "Problem parsing number in response");
		}
		return result;
	}

	/**
	 * Get agents through an api call.
	 * 
	 * @param context
	 * @return Map containing id -> agent.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	public static Map<Integer, String> getAgents(Context context)
			throws IOException {
		Map<Integer, String> result = new HashMap<Integer, String>();
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "all_agents"));
		parameters.add(new BasicNameValuePair("return_type", "csv"));

		String return_api = Core.httpGet(context, parameters);
		String[] lines = return_api.split("\n");

		for (int i = 0; i < lines.length; i++) {
			String[] agents = lines[i].split(";");
			result.put(Integer.valueOf(agents[0]), agents[1]);
		}
		return result;
	}

	/**
	 * Get API version.
	 * 
	 * @param context
	 *            Application context.
	 * @return API version or empty string if fails.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	public static String getVersion(Context context) throws IOException {
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "test"));
		String return_api;
		return_api = Core.httpGet(context, parameters);
		// TODO wait version
		if (return_api.contains("OK")) {
			String[] lines = return_api.split(",");
			if (lines.length == 3) {
				return lines[1];
			} else {
				return context.getString(R.string.unknown_version);
			}
		} else {
			return "";
		}
	}

	/**
	 * Get events from pandora console.
	 * 
	 * @param newEvents
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	public static String getEvents(Context context, String other)
			throws IOException {
		// Get total count.
		ArrayList<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "events"));
		parameters.add(new BasicNameValuePair("other_mode",
				"url_encode_separator_|"));
		parameters.add(new BasicNameValuePair("return_type", "csv"));
		parameters.add(new BasicNameValuePair("other", other));
		return Core.httpGet(context, parameters);
	}

	/**
	 * Get tags through an api call.
	 * 
	 * @return A list of groups.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 * 
	 */
	public static List<String> getTags(Context context) throws IOException {
		ArrayList<String> array = new ArrayList<String>();
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "tags"));
		parameters.add(new BasicNameValuePair("return_type", "csv"));
		String return_api = Core.httpGet(context, parameters);
		String[] lines = return_api.split("\n");
		array.add("");
		try {
			for (int i = 0; i < lines.length; i++) {
				String[] tags = lines[i].split(";", 2);
				array.add(tags[1]);
			}
		} catch (ArrayIndexOutOfBoundsException e) {
			Log.e(TAG, "There was a problem getting tags: " + e.getMessage());
		}
		return array;
	}

	/**
	 * Creates new incident in console.
	 * 
	 * @param context
	 *            Application context
	 * @param incidentParameters
	 *            Incident data
	 * @throws IOException
	 *             If there is any problem with the connection.
	 * 
	 */
	public static void createNewIncident(Context context,
			String[] incidentParameters) throws IOException {
		Log.i(TAG, "Sending new incident");
		List<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "set"));
		parameters.add(new BasicNameValuePair("op2", "new_incident"));
		parameters.add(new BasicNameValuePair("other_mode",
				"url_encode_separator_|"));
		parameters.add(new BasicNameValuePair("other", Core
				.serializeParams2Api(incidentParameters)));
		Core.httpGet(context, parameters);
	}

	/**
	 * Validates an event.
	 * 
	 * @param context
	 *            Application context.
	 * @param idEvent
	 *            Id of event.
	 * @param comment
	 *            Validation comment.
	 * @return <b>true</b> if validation was done.
	 * @throws IOException
	 *             If here is any connection problem.
	 */
	public static boolean validateEvent(Context context, int idEvent,
			String comment) throws IOException {
		List<NameValuePair> parameters;
		// Set event validation.
		parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "set"));
		parameters.add(new BasicNameValuePair("op2", "validate_events"));
		parameters.add(new BasicNameValuePair("id", Integer.valueOf(idEvent)
				.toString()));
		parameters.add(new BasicNameValuePair("other", comment));
		String return_api = Core.httpGet(context, parameters);

		if (return_api.startsWith("Correct validation")) {
			return true;
		} else {
			return false;
		}
	}
}