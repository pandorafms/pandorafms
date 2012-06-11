package pandroid_event_viewer.pandorafms;

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
	 */
	public static Map<Integer, String> getGroups(Context context) {
		Map<Integer, String> result = new HashMap<Integer, String>();
		try {
			List<NameValuePair> parameters = new ArrayList<NameValuePair>();
			parameters.add(new BasicNameValuePair("op", "get"));
			parameters.add(new BasicNameValuePair("op2", "groups"));
			parameters.add(new BasicNameValuePair("other_mode",
					"url_encode_separator_|"));
			parameters.add(new BasicNameValuePair("return_type", "csv"));
			parameters.add(new BasicNameValuePair("other", ";"));

			String return_api = Core.httpGet(context, parameters);
			String[] lines = return_api.split("\n");

			for (int i = 0; i < lines.length; i++) {
				String[] groups = lines[i].split(";", 21);
				result.put(Integer.valueOf(groups[0]), groups[1]);
			}
		} catch (Exception e) {
			Log.e(TAG + ": getting groups", e.getMessage());
		}
		return result;
	}

	/**
	 * Get agents through an api call.
	 * 
	 * @param context
	 * @return Map containing id -> agent.
	 */
	public static Map<Integer, String> getAgents(Context context) {
		Map<Integer, String> result = new HashMap<Integer, String>();
		try {
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
		} catch (Exception e) {
			Log.e(TAG + ": getting groups", e.getMessage());
		}
		return result;
	}

	/**
	 * Get API version.
	 * 
	 * @param context
	 *            Application context.
	 * @return API version or empty string if fails.
	 */
	public static String getVersion(Context context) {
		try {
			List<NameValuePair> parameters = new ArrayList<NameValuePair>();
			parameters.add(new BasicNameValuePair("op", "get"));
			parameters.add(new BasicNameValuePair("op2", "test"));
			String return_api = Core.httpGet(context, parameters);
			// TODO wait version
			if (return_api.equals("OK\n")) {
				return "4.0.2";
			} else {
				return "";
			}
		} catch (Exception e) {
			return "";
		}
	}
}