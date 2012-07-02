/*
Pandora FMS - http://pandorafms.com

==================================================
Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
Please see http://pandorafms.org for full contribution list

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation; version 2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 */
package pandroid_event_viewer.pandorafms;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLEncoder;
import java.security.KeyManagementException;
import java.security.NoSuchAlgorithmException;
import java.security.SecureRandom;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.KeyManager;
import javax.net.ssl.SSLContext;
import javax.net.ssl.SSLSession;
import javax.net.ssl.SSLSocketFactory;
import javax.net.ssl.TrustManager;

import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.NameValuePair;
import org.apache.http.client.entity.UrlEncodedFormEntity;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.message.BasicNameValuePair;

import android.app.Activity;
import android.app.AlarmManager;
import android.app.PendingIntent;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.drawable.BitmapDrawable;
import android.graphics.drawable.Drawable;
import android.util.Log;
import android.widget.TextView;
import android.widget.Toast;

/**
 * This class provides basic functions to manage services and some received
 * data.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class Core {
	private static String TAG = "Core";
	private static Map<String, Bitmap> imgCache = new HashMap<String, Bitmap>();
	// Don't use this variable, just call getSocketFactory
	private static SSLSocketFactory sslSocketFactory;

	/**
	 * Reads from the input stream and returns a string.
	 * 
	 * @param is
	 * @return A string with all data read.
	 */
	public static String convertStreamToString(InputStream is) {
		BufferedReader reader = new BufferedReader(new InputStreamReader(is),
				8 * 1024);
		StringBuilder sb = new StringBuilder();

		String line = null;
		try {
			while ((line = reader.readLine()) != null) {
				sb.append(line + "\n");
			}
		} catch (IOException e) {
			e.printStackTrace();
		} finally {
			try {
				is.close();
			} catch (IOException e) {
				e.printStackTrace();
			}
		}

		return sb.toString();
	}

	/**
	 * Sets fetch frequency.
	 * 
	 * @param ctx
	 *            Application context.
	 */
	public static void setFetchFrequency(Context ctx) {
		Log.i(TAG, "Setting events fetching frequency");
		// Stops the service (if it's running)
		ctx.stopService(new Intent(ctx, PandroidEventviewerService.class));
		// Sets the launch frequency
		AlarmManager alarmM = (AlarmManager) ctx
				.getSystemService(Context.ALARM_SERVICE);

		PendingIntent pandroidService = PendingIntent.getService(ctx, 0,
				new Intent(ctx, PandroidEventviewerService.class), 0);

		int sleepTimeAlarm = convertRefreshTimeKeyToTime(ctx);

		Log.i(TAG, "sleepTimeAlarm = " + sleepTimeAlarm);

		alarmM.setRepeating(AlarmManager.RTC_WAKEUP,
				System.currentTimeMillis(), sleepTimeAlarm, pandroidService);
	}

	/**
	 * Converts the maximum time setted to filter events to a timestamp.
	 * 
	 * @param timestamp
	 * @param arrayKey
	 * @return Time in milliseconds.
	 */
	public static long convertMaxTimeOldEventValuesToTimestamp(long time,
			int arrayKey) {
		long return_var = 0;

		if (time == 0) {
			Calendar c = Calendar.getInstance();
			time = c.getTimeInMillis() / 1000;
		}

		switch (arrayKey) {
		case 0:
			return_var = time - 30 * 60;
			break;
		case 1:
			return_var = time - 60 * 60;
			break;
		case 2:
			return_var = time - 2 * (60 * 60);
			break;
		case 3:
			return_var = time - 3 * (60 * 60);
			break;
		case 4:
			return_var = time - 4 * (60 * 60);
			break;
		case 5:
			return_var = time - 5 * (60 * 60);
			break;
		case 6:
			return_var = time - 8 * (60 * 60);
			break;
		case 7:
			return_var = time - 10 * (60 * 60);
			break;
		case 8:
			return_var = time - 12 * (60 * 60);
			break;
		case 9:
			return_var = time - 24 * (60 * 60);
			break;
		case 10:
			return_var = time - 2 * (24 * 60 * 60);
			break;
		case 11:
			return_var = time - 3 * (24 * 60 * 60);
			break;
		case 12:
			return_var = time - 4 * (24 * 60 * 60);
			break;
		case 13:
			return_var = time - 5 * (24 * 60 * 60);
			break;
		case 14:
			return_var = time - 7 * (24 * 60 * 60);
			break;
		case 15:
			return_var = time - 2 * (7 * 24 * 60 * 60);
			break;
		case 16:
			return_var = time - 30 * (24 * 60 * 60);
			break;
		}

		return return_var;
	}

	/**
	 * Converts max event age chosen from spinner to seconds (either are seconds
	 * or not)
	 * 
	 * @return Time in milliseconds.
	 */
	private static int convertRefreshTimeKeyToTime(Context ctx) {
		int returnvar = 60 * 10;

		SharedPreferences preferences = ctx.getSharedPreferences(
				ctx.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		int refreshTimeKey = preferences.getInt("refreshTimeKey", 3);

		switch (refreshTimeKey) {
		case 0:
			returnvar = 30; // 30 seconds
			break;
		case 1:
			returnvar = 60; // 1 minute
			break;
		case 2:
			returnvar = 60 * 5; // 5 minutes
			break;
		case 3:
			returnvar = 60 * 10; // 10 minutes
			break;
		case 4:
			returnvar = 60 * 15; // 15 minutes
			break;
		case 5:
			returnvar = 60 * 30; // 30 minutes
			break;
		case 6:
			returnvar = 60 * 45; // 45 minutes
			break;
		case 7:
			returnvar = 3600; // 1 hour
			break;
		case 8:
			returnvar = 3600 + (60 * 30); // 1 hour and 30 minutes
			break;
		case 9:
			returnvar = 3600 * 2; // 2 hours
			break;
		case 10:
			returnvar = 3600 * 3; // 3 hours
			break;
		case 11:
			returnvar = 3600 * 4; // 4 hours
			break;
		case 12:
			returnvar = 3600 * 6; // 6 hours
			break;
		case 13:
			returnvar = 3600 * 8; // 8 hours
			break;
		case 14:
			returnvar = 3600 * 10; // 10 hours
			break;
		case 15:
			returnvar = 3600 * 12; // 12 hours
			break;
		case 16:
			returnvar = 3600 * 24; // 24 hours
			break;
		case 17:
			returnvar = 3600 * 36; // 36 hours
			break;
		case 18:
			returnvar = 3600 * 48; // 48 hours
			break;
		}

		return returnvar * 1000;
	}

	/**
	 * Converts params to string.
	 * 
	 * @param params
	 * @return All params in a single string.
	 */
	public static String serializeParams2Api(String[] params) {
		String return_var = params[0];

		for (int i = 1; i < params.length; i++) {
			return_var += "|" + params[i];
		}
		Log.i(TAG + " serializeParams2Api", return_var);
		return return_var;
	}

	/**
	 * Performs an http get petition.
	 * 
	 * @param context
	 *            Application context.
	 * @param additionalParameters
	 *            Petition additional parameters
	 * @return Petition result.
	 * @throws IOException
	 *             If there is any problem with the connection.
	 */
	public static String httpGet(Context context,
			List<NameValuePair> additionalParameters) throws IOException {
		SharedPreferences preferences = context.getSharedPreferences(
				context.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);

		String url = preferences.getString("url", "") + "/include/api.php";
		String user = preferences.getString("user", "");
		String password = preferences.getString("password", "");
		String apiPassword = preferences.getString("api_password", "");
		if (url.length() == 0 || user.length() == 0) {
			return "";
		}
		ArrayList<NameValuePair> parameters = new ArrayList<NameValuePair>();
		parameters.add(new BasicNameValuePair("user", user));
		parameters.add(new BasicNameValuePair("pass", password));
		if (apiPassword.length() > 0) {
			parameters.add(new BasicNameValuePair("apipass", apiPassword));
		}
		parameters.addAll(additionalParameters);
		if (url.toLowerCase().contains("https")) {
			// Secure connection
			return Core.httpsGet(url, parameters);
		} else {
			DefaultHttpClient httpClient = new DefaultHttpClient();
			UrlEncodedFormEntity entity;
			HttpPost httpPost;
			HttpResponse response;
			HttpEntity entityResponse;
			String return_api;
			httpPost = new HttpPost(url);
			entity = new UrlEncodedFormEntity(parameters);
			httpPost.setEntity(entity);
			response = httpClient.execute(httpPost);
			entityResponse = response.getEntity();
			return_api = Core
					.convertStreamToString(entityResponse.getContent());
			return return_api;
		}
	}

	/**
	 * Downloads an image
	 * 
	 * @param fileUrl
	 *            Image's url.
	 * @return A bitmap of that image.
	 */
	public static Bitmap downloadImage(String fileUrl) {
		if (imgCache.containsKey(fileUrl)) {
			Log.i(TAG, "Fetched from cache: " + fileUrl);
			return imgCache.get(fileUrl);
		}
		Log.i(TAG, "Downloading image: " + fileUrl);
		URL myFileUrl = null;

		try {
			myFileUrl = new URL(fileUrl);
			if (fileUrl.toLowerCase().contains("https")) {
				HttpsURLConnection con = (HttpsURLConnection) new URL(fileUrl)
						.openConnection();
				con.setHostnameVerifier(new HostnameVerifier() {
					public boolean verify(String hostname, SSLSession session) {
						return true;
					}
				});
				con.setSSLSocketFactory(getSocketFactory());
				Bitmap img = BitmapFactory.decodeStream(con.getInputStream());
				imgCache.put(fileUrl, img);
				return img;
			} else {
				HttpURLConnection conn = (HttpURLConnection) myFileUrl
						.openConnection();
				conn.setDoInput(true);
				conn.connect();
				InputStream is = conn.getInputStream();
				Bitmap img = BitmapFactory.decodeStream(is);
				imgCache.put(fileUrl, img);
				return img;
			}
		} catch (IOException e) {
			Log.e(TAG, "Downloading image " + fileUrl + ": error");
		}
		return null;
	}

	/**
	 * Puts the image to the left of theTextView.
	 * 
	 * @param view
	 *            TextView.
	 * @param image
	 *            Bitmap.
	 */
	public static void setTextViewLeftImage(TextView view, Bitmap image) {
		Drawable d = new BitmapDrawable(image);
		setTextViewLeftImage(view, d, 16);
	}

	/**
	 * Puts the image to the left of theTextView.
	 * 
	 * @param view
	 *            TextView.
	 * @param url
	 *            Image's url.
	 */
	public static void setTextViewLeftImage(TextView view, String url) {
		Drawable d = new BitmapDrawable(Core.downloadImage(url));
		setTextViewLeftImage(view, d, 16);
	}

	/**
	 * Puts the image to the left of theTextView.
	 * 
	 * @param view
	 *            TextView.
	 * @param image
	 *            Image.
	 * @param size
	 *            Image size
	 */
	public static void setTextViewLeftImage(TextView view, Drawable image,
			int size) {
		image.setBounds(0, 0, size, size);
		view.setCompoundDrawables(image, null, null, null);
	}

	/**
	 * Finds out if the given url has a CA signed certificate.
	 * 
	 * @param url
	 * @return boolean
	 * @throws IOException
	 *             If the given url is not accessible.
	 */
	public static boolean isValidCertificate(URL url) {
		HttpsURLConnection con;
		try {
			con = (HttpsURLConnection) url.openConnection();
			con.connect();
			con.disconnect();
			return true;
		} catch (IOException e) {
			return false;
		}
	}

	/**
	 * Checks if a url is online.
	 * 
	 * @param url
	 * @return boolean
	 */
	public static boolean isOnline(URL url) {
		try {
			HttpsURLConnection con = (HttpsURLConnection) url.openConnection();
			con.setHostnameVerifier(new HostnameVerifier() {
				public boolean verify(String hostname, SSLSession session) {
					return true;
				}
			});
			con.setSSLSocketFactory(getSocketFactory());
			con.setDoOutput(true);
			con.getInputStream();
			return true;
		} catch (IOException e) {
			return false;
		}
	}

	/**
	 * Performs an secure http petition
	 * 
	 * @param url
	 *            Target
	 * @param parameters
	 *            Petition parameters
	 * @return Result of the petition.
	 * @throws IOException
	 *             If there is any problem with connection.
	 * 
	 */
	private static String httpsGet(String url, List<NameValuePair> parameters)
			throws IOException {
		String result = "";
		HttpsURLConnection con;
		try {
			con = (HttpsURLConnection) new URL(url).openConnection();
			con.setHostnameVerifier(new HostnameVerifier() {
				public boolean verify(String hostname, SSLSession session) {
					return true;
				}
			});
			con.setSSLSocketFactory(getSocketFactory());
			con.setDoOutput(true);
			String postData = "";
			boolean first = true;
			for (NameValuePair nameValuePair : parameters) {
				postData = first ? postData : postData + "&";
				first = false;
				postData += URLEncoder.encode(nameValuePair.getName()) + "="
						+ URLEncoder.encode(nameValuePair.getValue());
			}
			if (postData.length() > 0) {
				OutputStreamWriter wr = new OutputStreamWriter(
						con.getOutputStream());
				wr.write(postData);
				wr.flush();
			}

			InputStream inputStream;
			inputStream = con.getInputStream();
			BufferedReader bufferedReader = new BufferedReader(
					new InputStreamReader(inputStream));
			String temp;
			while ((temp = bufferedReader.readLine()) != null) {
				Log.d("CONTENT", temp);
				result += temp + "\n";
			}
		} catch (MalformedURLException e) {
			// Can't reach here because the given url is checked when is
			// inserted in Options activity.
		}
		return result;
	}

	/**
	 * Returns a SSL Factory instance that accepts all server certificates.
	 * 
	 * @return An SSL-specific socket factory.
	 **/
	private static final SSLSocketFactory getSocketFactory() {
		if (sslSocketFactory == null) {
			try {
				TrustManager[] tm = new TrustManager[] { new NaiveTrustManager() };
				SSLContext context = SSLContext.getInstance("TLS");
				context.init(new KeyManager[0], tm, new SecureRandom());

				sslSocketFactory = (SSLSocketFactory) context
						.getSocketFactory();

			} catch (KeyManagementException e) {
				Log.e("No SSL algorithm support: " + e.getMessage(),
						e.toString());
			} catch (NoSuchAlgorithmException e) {
				Log.e("Exception when setting up the Naive key management.",
						e.toString());
			}
		}
		return sslSocketFactory;
	}

	/**
	 * Shows a toast which will show the connection problem message. Do not call
	 * outside the UI's thread.
	 * 
	 * @param context
	 *            Application context.
	 * @param ignoreConnectionCheck
	 *            If true it shows the toast even if the connection is not
	 *            correctly configured.
	 */
	public static void showConnectionProblemToast(Context context,
			boolean ignoreConnectionCheck) {
		SharedPreferences preferences = context.getSharedPreferences(
				context.getString(R.string.const_string_preferences),
				Activity.MODE_PRIVATE);
		if (preferences.getBoolean("online", false)) {
			Toast.makeText(context, R.string.connection_problem,
					Toast.LENGTH_SHORT).show();
		} else {
			if (ignoreConnectionCheck) {
				Toast.makeText(context, R.string.connection_settings_problem,
						Toast.LENGTH_SHORT).show();
			}
		}
	}

	/**
	 * Checks if a string is inside the list (case insensitive).
	 * 
	 * @param list
	 *            List of strings.
	 * @param string
	 *            Given value.
	 * @return If the string is inside the list.
	 */
	public static boolean containsIgnoreCase(List<String> list, String string) {
		Iterator<String> it = list.iterator();
		while (it.hasNext()) {
			if (it.next().equalsIgnoreCase(string))
				return true;
		}
		return false;
	}

	/**
	 * Returns the corresponding image to the given severity code.
	 * 
	 * @param context
	 *            Application context.
	 * @param severityCode
	 *            Severity code.
	 * @return a Drawable item.
	 */
	public static Drawable getSeverityImage(Context context, int severityCode) {
		switch (severityCode) {
		case 0:
			return context.getResources().getDrawable(
					R.drawable.severity_maintenance);
		case 1:
			return context.getResources().getDrawable(
					R.drawable.severity_informational);

		case 2:
			return context.getResources().getDrawable(
					R.drawable.severity_normal);

		case 3:
			return context.getResources().getDrawable(
					R.drawable.severity_warning);

		case 4:
			return context.getResources().getDrawable(
					R.drawable.severity_critical);
		}
		return null;
	}

	/**
	 * Returns the corresponding image to the given event type.
	 * 
	 * @param context
	 *            Application context.
	 * @param eventType
	 *            Event type.
	 * @return Drawable
	 */
	public static Drawable getEventTypeImage(Context context, String eventType) {
		eventType = eventType.toLowerCase();
		Map<String, Integer> images = new HashMap<String, Integer>();
		images.put("alert_recovered", R.drawable.error);
		images.put("alert_manual_validation", R.drawable.eye);
		images.put("going_up_warning", R.drawable.b_yellow);
		images.put("going_up_critical", R.drawable.b_red);
		images.put("going_down_critical", R.drawable.b_red);
		images.put("going_up_normal", R.drawable.b_green);
		images.put("going_down_normal", R.drawable.b_green);
		images.put("going_down_warning", R.drawable.b_yellow);
		images.put("alert_fired", R.drawable.bell);
		images.put("system", R.drawable.cog);
		images.put("recon_host_detected", R.drawable.network);
		images.put("new_agent", R.drawable.wand);
		images.put("unknown", R.drawable.err);

		Integer code = images.get(eventType.toLowerCase());
		if (code != null) {
			return context.getResources().getDrawable(code);
		} else {
			return null;
		}
	}
}
