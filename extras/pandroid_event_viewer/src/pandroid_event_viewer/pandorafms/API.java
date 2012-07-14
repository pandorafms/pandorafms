package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.http.NameValuePair;
import org.apache.http.message.BasicNameValuePair;

import android.annotation.SuppressLint;
import android.content.Context;
import android.util.Log;

@SuppressLint("UseSparseArrays")
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
	 * Performs a get_events API call.
	 * 
	 * @param context
	 *            Application context.
	 * @param filterAgentName
	 *            Agent name.
	 * @param idGroup
	 *            Group id.
	 * @param filterSeverity
	 *            Severity.
	 * @param filterStatus
	 *            Status.
	 * @param filterEventSearch
	 *            Text in event title.
	 * @param filterTag
	 *            Tag.
	 * @param filterTimestamp
	 *            Events after this time.
	 * @param itemsPerPage
	 *            Number of items retrieved per list in each call.
	 * @param offset
	 *            List offset.
	 * @param total
	 *            Retrieve number of events instead of events info.
	 * @param more_criticity
	 *            Retrieve maximum criticity instead of events info.
	 * @return API call result.
	 * @throws IOException
	 *             if there was any problem.
	 */
	public static String getEvents(Context context, String filterAgentName,
			int idGroup, int filterSeverity, int filterStatus,
			String filterEventSearch, String filterTag, long filterTimestamp,
			long itemsPerPage, long offset, boolean total,
			boolean more_criticity) throws IOException {
		ArrayList<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("op", "get"));
		parameters.add(new BasicNameValuePair("op2", "events"));
		parameters.add(new BasicNameValuePair("other_mode",
				"url_encode_separator_|"));
		parameters.add(new BasicNameValuePair("return_type", "csv"));
		parameters.add(new BasicNameValuePair("other",
				serializeEventsParamsToAPI(filterAgentName, idGroup,
						filterSeverity, filterStatus, filterEventSearch,
						filterTag, filterTimestamp, itemsPerPage, offset,
						total, more_criticity)));
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

	/**
	 * Serialize parameters to api.
	 * 
	 * @param filterAgentName
	 *            Agent name.
	 * @param idGroup
	 *            Group id.
	 * @param filterSeverity
	 *            Severity.
	 * @param filterStatus
	 *            Status.
	 * @param filterEventSearch
	 *            Text in event title.
	 * @param filterTag
	 *            Tag.
	 * @param filterTimestamp
	 *            Events after this time.
	 * @param itemsPerPage
	 *            Number of items retrieved per list in each call.
	 * @param offset
	 *            List offset.
	 * @param total
	 *            Retrieve number of events instead of events info.
	 * @param more_criticity
	 *            Retrieve maximum criticity instead of events info.
	 * @return Serialized parameters.
	 */
	private static String serializeEventsParamsToAPI(String filterAgentName,
			int idGroup, int filterSeverity, int filterStatus,
			String filterEventSearch, String filterTag, long filterTimestamp,
			long itemsPerPage, long offset, boolean total,
			boolean more_criticity) {

		String totalStr = (total) ? "total" : "-1";
		if (more_criticity) {
			totalStr = "more_criticity";
		}
		return Core.serializeParams2Api(new String[] { ";", // Separator for the
															// csv
				Integer.toString(filterSeverity), // criticity or severity
				filterAgentName, // The agent name
				"", // Name of module
				"", // Name of alert template
				"", // Id user
				Long.toString(filterTimestamp), // The minimum timestamp
				"", // The maximum timestamp
				String.valueOf(filterStatus), // The status
				filterEventSearch, // The free search in the text event
									// description.
				Integer.toString(20), // The pagination of list events
				Long.toString(0), // The offset of list events
				totalStr, // Count or show
				Integer.toString(idGroup), // Group ID
				filterTag }); // Tag
	}
}