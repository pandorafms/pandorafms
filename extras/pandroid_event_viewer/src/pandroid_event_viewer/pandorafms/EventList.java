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

import java.io.IOException;
import java.util.ArrayList;
import java.util.Map.Entry;

import android.app.Dialog;
import android.app.ListActivity;
import android.app.ProgressDialog;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.res.Configuration;
import android.os.AsyncTask;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.RelativeLayout;
import android.widget.TextView;
import android.widget.Toast;

/**
 * Activity where events are displayed.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class EventList extends ListActivity {
	private static String TAG = "EventList";

	private static int DEFAULT_STATUS_CODE = 0;
	private static int DEFAULT_PRIORITY_CODE = 0;
	private static String DEFAULT_SOURCE = "Pandora FMS Event";
	private static final int CREATE_INCIDENT_DIALOG = 1;
	private static final int VALIDATE_EVENT_ACTIVITY = 0;
	private ListView lv;
	private MyAdapter la;
	private PandroidEventviewerActivity object;
	private BroadcastReceiver onBroadcast;
	private Dialog creatingIncidentDialog;
	private Context context = this;
	private View currentElement;

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		Intent i = getIntent();
		this.object = (PandroidEventviewerActivity) i
				.getSerializableExtra("object");

		setContentView(R.layout.list_view_layout);

		this.toggleLoadingLayout();

		lv = (ListView) findViewById(android.R.id.list);

		la = new MyAdapter(getBaseContext(), object);

		lv.setAdapter(la);

		onBroadcast = new BroadcastReceiver() {

			@Override
			public void onReceive(Context context, Intent intent) {
				int load_more = intent.getIntExtra("load_more", 0);

				Button button = (Button) findViewById(R.id.button_load_more_events);

				if (object.eventList.size() == 0) {
					button.setVisibility(Button.GONE);
				} else if (((long) object.eventList.size()) >= object.count_events) {
					button.setVisibility(Button.GONE);
				} else {
					button.setVisibility(Button.VISIBLE);
				}

				if (load_more == 1) {
					LinearLayout layout = (LinearLayout) findViewById(R.id.loading_layout);
					layout.setVisibility(LinearLayout.GONE);
					la.showLoadingEvents = false;
				} else {
					LinearLayout layout = (LinearLayout) findViewById(R.id.loading_layout);
					layout.setVisibility(LinearLayout.GONE);

					if (((int) object.count_events) == 0) {
						layout = (LinearLayout) findViewById(R.id.empty_list_layout);
						layout.setVisibility(LinearLayout.VISIBLE);
					}
				}

				la.notifyDataSetChanged();
			}
		};

		registerReceiver(onBroadcast, new IntentFilter("eventlist.java"));

		this.toggleLoadingLayout();

		if (this.object.show_popup_info) {
			this.object.show_popup_info = false;
			i = new Intent(this, About.class);
			startActivity(i);
		}
	}

	public void onRestart() {
		super.onRestart();

		if (this.object.showOptionsFirstTime) {
			this.object.loadInProgress = true;
			toggleLoadingLayout();

			this.object.showOptionsFirstTime = false;
			this.object.executeBackgroundGetEvents(false);
		}
	}

	public void onResume() {
		super.onResume();

		registerReceiver(onBroadcast, new IntentFilter("eventlist.java"));

		this.toggleLoadingLayout();

		if (!this.object.loadInProgress) {
			if (((int) object.count_events) == 0) {
				LinearLayout layout = (LinearLayout) findViewById(R.id.empty_list_layout);
				layout.setVisibility(LinearLayout.VISIBLE);
			}
		}
	}

	public void onConfigurationChanged(Configuration newConfig) {
		super.onConfigurationChanged(newConfig);
	}

	@Override
	public boolean onCreateOptionsMenu(Menu menu) {
		MenuInflater inflater = getMenuInflater();
		inflater.inflate(R.menu.options_menu_list_events, menu);
		return true;
	}

	@Override
	public boolean onOptionsItemSelected(MenuItem item) {
		Intent i;
		switch (item.getItemId()) {
		case R.id.options_button_menu_options:
			startActivity(new Intent(this, Options.class));
			break;
		case R.id.refresh_button_menu_options:
			this.object.loadInProgress = true;
			this.object.getNewListEvents = true;
			this.object.eventList = new ArrayList<EventListItem>();
			this.toggleLoadingLayout();
			this.object.executeBackgroundGetEvents(true);
			break;
		case R.id.about_button_menu_options:
			i = new Intent(this, About.class);
			startActivity(i);
			break;
		}

		return true;
	}

	@Override
	protected Dialog onCreateDialog(int id, Bundle args) {
		switch (id) {
		case CREATE_INCIDENT_DIALOG:
			final String group;
			Dialog dialog = new Dialog(this);
			dialog.setContentView(R.layout.create_incident);
			dialog.setTitle(getString(R.string.create_incident));
			final EditText titleEditText = (EditText) dialog
					.findViewById(R.id.incident_title);
			final EditText descriptionEditText = (EditText) dialog
					.findViewById(R.id.incident_description);
			String temp = args.getString("title");

			if (temp != null) {
				titleEditText.setText(temp);
			}

			temp = args.getString("description");
			if (temp != null) {
				descriptionEditText.setText(temp);
			}
			temp = args.getString("group");
			if (temp != null) {
				group = temp;
			} else {
				group = "";
			}
			dialog.findViewById(R.id.incident_create_button)
					.setOnClickListener(new OnClickListener() {

						public void onClick(View v) {
							if (titleEditText != null
									&& titleEditText.length() > 0) {
								creatingIncidentDialog = ProgressDialog.show(
										context, "",
										getString(R.string.creating_incident),
										true);
								String title = titleEditText.getText()
										.toString();
								String description = descriptionEditText
										.getText().toString();
								new SetNewIncidentAsyncTask().execute(title,
										description, group);
							} else {
								Toast.makeText(getApplicationContext(),
										R.string.title_empty,
										Toast.LENGTH_SHORT).show();
							}
						}
					});
			return dialog;
		}
		return null;
	}

	/**
	 * Shows loading information.
	 */
	private void toggleLoadingLayout() {
		LinearLayout layout;

		layout = (LinearLayout) findViewById(R.id.empty_list_layout);
		layout.setVisibility(LinearLayout.GONE);

		layout = (LinearLayout) findViewById(R.id.loading_layout);

		if (this.object.loadInProgress) {
			layout.setVisibility(LinearLayout.VISIBLE);
		} else {
			layout.setVisibility(LinearLayout.GONE);
		}
	}

	@Override
	protected void onListItemClick(ListView l, View v, int position, long id) {
		super.onListItemClick(l, v, position, id);

		try {
			EventListItem item = this.object.eventList.get(position);
			item.opened = !item.opened;
			this.object.eventList.set(position, item);
			la.notifyDataSetChanged();
		} catch (IndexOutOfBoundsException e) {

		}
	}

	/**
	 * Loads more events.
	 * 
	 * @param v
	 */
	private void loadMoreEvents() {
		la.showLoadingEvents = true;
		la.notifyDataSetChanged();

		object.executeBackgroundGetEvents(true);
	}

	protected void onActivityResult(int requestCode, int resultCode, Intent data) {
		if (requestCode == VALIDATE_EVENT_ACTIVITY) {
			if (resultCode == RESULT_OK) {
				String text;
				if (data.getExtras().getBoolean("validated")) {
					currentElement.setBackgroundColor(getResources().getColor(
							R.color.Green));
					text = getApplicationContext().getString(
							R.string.successful_validate_event_str);
				} else {
					text = getApplicationContext().getString(
							R.string.fail_validate_event_str);
				}
				Toast.makeText(getApplicationContext(), text,
						Toast.LENGTH_SHORT).show();
			}
		}
	}

	/**
	 * Private adapter (event list).
	 * 
	 * @author Miguel de Dios Matías
	 * 
	 */
	private class MyAdapter extends BaseAdapter {
		private Context mContext;
		public PandroidEventviewerActivity object;

		public boolean showLoadingEvents;

		public MyAdapter(Context c, PandroidEventviewerActivity object) {
			mContext = c;
			this.object = object;
			showLoadingEvents = false;
		}

		public int getCount() {
			return this.object.eventList.size() + 1;
		}

		public Object getItem(int position) {
			return null;
		}

		public long getItemId(int position) {
			return 0;
		}

		public View getView(int position, View convertView, ViewGroup parent) {
			View view;

			LayoutInflater inflater = (LayoutInflater) mContext
					.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
			view = inflater.inflate(R.layout.item_list_event_layout, null);

			// If the end of the list.
			if (this.object.eventList.size() == position) {
				// Show button to get more events
				if ((!object.loadInProgress) && (object.count_events != 0)) {
					if (showLoadingEvents) {
						LinearLayout layout = (LinearLayout) view
								.findViewById(R.id.loading_more_events);
						layout.setVisibility(LinearLayout.VISIBLE);

						RelativeLayout layout2 = (RelativeLayout) view
								.findViewById(R.id.content_event_item);
						layout2.setVisibility(RelativeLayout.GONE);

						Button button = (Button) view
								.findViewById(R.id.button_load_more_events);
						button.setVisibility(Button.GONE);
					} else {
						Button button = (Button) view
								.findViewById(R.id.button_load_more_events);

						if (object.eventList.size() == 0) {
							button.setVisibility(Button.GONE);
						} else if (((long) object.eventList.size()) >= object.count_events) {
							button.setVisibility(Button.GONE);
						} else {
							button.setVisibility(Button.VISIBLE);
						}

						button.setOnClickListener(new View.OnClickListener() {

							public void onClick(View v) {
								object.offset += object.pagination;
								loadMoreEvents();
							}
						});

						RelativeLayout content_event_item = (RelativeLayout) view
								.findViewById(R.id.content_event_item);
						content_event_item.setVisibility(RelativeLayout.GONE);
					}
				}
			} else {
				final EventListItem item = this.object.eventList.get(position);

				switch (item.criticity) {

				case 0:
					view.setBackgroundColor(getResources().getColor(
							R.color.Blue));
					break;
				case 1:
					view.setBackgroundColor(getResources().getColor(
							R.color.Grey));
					break;
				case 2:
					view.setBackgroundColor(getResources().getColor(
							R.color.Green));
					break;
				case 3:
					view.setBackgroundColor(getResources().getColor(
							R.color.Yellow));
					break;
				case 4:
					view.setBackgroundColor(getResources()
							.getColor(R.color.Red));
					break;
				default:
					view.setBackgroundColor(getResources().getColor(
							R.color.Grey));
					break;
				}

				TextView titulo = (TextView) view.findViewById(R.id.agent_name);

				if (item.event_type.equals("system")) {
					titulo.setText(R.string.system_str);
				} else {
					titulo.setText(item.agent_name);
				}

				TextView descripcion = (TextView) view
						.findViewById(R.id.event_name);
				descripcion.setText(item.event);

				TextView timestamp = (TextView) view
						.findViewById(R.id.timestamp);
				timestamp.setText(item.timestamp);

				if (item.criticity_image.length() != 0)
					Core.setTextViewLeftImage((TextView) view
							.findViewById(R.id.event_name), Core
							.getSeverityImage(getApplicationContext(),
									item.criticity), 16);

				if (item.status == 1) {
					Core.setTextViewLeftImage(timestamp, getResources()
							.getDrawable(R.drawable.tick), 24);
				} else {
					Core.setTextViewLeftImage(timestamp, getResources()
							.getDrawable(R.drawable.tick_off), 24);
				}

				// Show extended info
				if (item.opened) {
					View viewEventExtended;
					viewEventExtended = inflater.inflate(
							R.layout.item_list_event_extended, null);

					TextView text;
					if (item.tags.length() != 0) {
						text = (TextView) viewEventExtended
								.findViewById(R.id.tags_text);
						String[] tags = item.tags.split(",");
						String tagText = "";
						for (int i = 0; i < tags.length; i++) {
							String parts[] = tags[i].split(" ");
							if (i > 0) {
								tagText += ", ";
							}
							if (parts.length == 2) {
								if (!parts[1].startsWith("http://")) {
									parts[1] = "http://" + parts[1];
								}
								tagText += "<a href=\"" + parts[1] + "\">"
										+ parts[0] + "</a>";
							} else {
								tagText += parts[0];
							}
						}
						text.setText(Html.fromHtml(tagText));
						text.setMovementMethod(LinkMovementMethod.getInstance());
					}

					if (item.user_comment.length() != 0) {
						if (item.user_comment.length() > 200) {
							item.user_comment = item.user_comment.substring(0,
									197);
							item.user_comment = item.user_comment.concat("...");
						}
						text = (TextView) viewEventExtended
								.findViewById(R.id.comments_text);
						text.setText(item.user_comment);
					}

					if (item.group_name.length() != 0) {
						text = (TextView) viewEventExtended
								.findViewById(R.id.group_text);
						text.setText(item.group_name);
					}

					if (item.agent_name.length() != 0) {
						View row = viewEventExtended
								.findViewById(R.id.row_agent);
						row.setVisibility(View.VISIBLE);

						text = (TextView) viewEventExtended
								.findViewById(R.id.agent_text);
						text.setText(Html.fromHtml("<a href='"
								+ this.object.url
								+ "/mobile/index.php?page=agent&id="
								+ item.id_agent + " &autologin=1&user="
								+ this.object.user + "&password="
								+ this.object.password + "'>" + item.agent_name
								+ "</a>"));
						text.setMovementMethod(LinkMovementMethod.getInstance());
					}

					Core.setTextViewLeftImage((TextView) viewEventExtended
							.findViewById(R.id.type_text), Core
							.getEventTypeImage(getApplicationContext(),
									item.event_type), 16);

					text = (TextView) viewEventExtended
							.findViewById(R.id.type_text);
					text.setText(eventType2Text(item.event_type));

					if (item.criticity_name.length() != 0) {
						text = (TextView) viewEventExtended
								.findViewById(R.id.severity_text);
						text.setText(item.criticity_name);
						Core.setTextViewLeftImage((TextView) viewEventExtended
								.findViewById(R.id.severity_text), Core
								.getSeverityImage(getApplicationContext(),
										item.criticity), 16);
					}

					// Set the open and close the extended info event row
					// action.
					view.setOnClickListener(new OnItemClickListener(position,
							this.object));

					Button button;
					button = (Button) viewEventExtended
							.findViewById(R.id.validate_button_extended);
					if (item.status == -1) {
						// For unknow events
						button.setVisibility(Button.GONE);
						text = (TextView) viewEventExtended
								.findViewById(R.id.validate_event_label);
						text.setText("");
						text.setVisibility(TextView.VISIBLE);
					} else if (item.status != 1) {
						currentElement = view;
						OnClickListenerButtonValidate clickListener = new OnClickListenerButtonValidate(
								item.id_event);
						button.setOnClickListener(clickListener);
						text = (TextView) viewEventExtended
								.findViewById(R.id.validate_event_label);
						text.setVisibility(TextView.GONE);
						((Button) viewEventExtended
								.findViewById(R.id.create_incident_button))
								.setOnClickListener(new OnClickListener() {

									public void onClick(View v) {
										Bundle b = new Bundle();
										b.putString("group", item.group_name);
										b.putString("title", item.event);
										b.putString("description",
												item.description_event);
										showDialog(CREATE_INCIDENT_DIALOG, b);
									}
								});
					} else {
						button.setVisibility(Button.GONE);
						text = (TextView) viewEventExtended
								.findViewById(R.id.validate_event_label);
						text.setVisibility(TextView.VISIBLE);
					}

					LinearLayout itemLinearLayout = (LinearLayout) view
							.findViewById(R.id.item_linear_layout);
					itemLinearLayout.addView(viewEventExtended);
				}
			}

			return view;
		}

		/**
		 * Returns the event type in the correct format (and system locale).
		 * 
		 * @param type
		 * @return Localized type.
		 */
		private String eventType2Text(String type) {
			String return_var;

			if (type.equals("alert_recovered")) {
				return_var = getApplicationContext().getString(
						R.string.alert_recovered_str);
			} else if (type.equals("alert_manual_validation")) {
				return_var = getApplicationContext().getString(
						R.string.alert_manual_validation_str);
			} else if (type.equals("going_up_warning")) {
				return_var = getApplicationContext().getString(
						R.string.going_up_warning_str);
			} else if (type.equals("going_down_critical")) {
				return_var = getApplicationContext().getString(
						R.string.going_down_critical_str);
			} else if (type.equals("going_up_critical")) {
				return_var = getApplicationContext().getString(
						R.string.going_down_critical_str);
			} else if (type.equals("going_up_normal")) {
				return_var = getApplicationContext().getString(
						R.string.going_up_normal_str);
			} else if (type.equals("going_down_normal")) {
				return_var = getApplicationContext().getString(
						R.string.going_up_normal_str);
			} else if (type.equals("going_down_warning")) {
				return_var = getApplicationContext().getString(
						R.string.going_down_warning_str);
			} else if (type.equals("alert_fired")) {
				return_var = getApplicationContext().getString(
						R.string.alert_fired_str);
			} else if (type.equals("system")) {
				return_var = getApplicationContext().getString(
						R.string.system_str);
			} else if (type.equals("recon_host_detected")) {
				return_var = getApplicationContext().getString(
						R.string.system_str);
			} else if (type.equals("new_agent")) {
				return_var = getApplicationContext().getString(
						R.string.new_agent_str);
			} else {
				return_var = getApplicationContext().getString(
						R.string.unknown_str)
						+ " " + type;
			}

			return return_var;
		}

		/**
		 * Custom click listener (show more information).
		 * 
		 * @author Miguel de Dios Matías
		 * 
		 */
		private class OnItemClickListener implements OnClickListener {
			private int mPosition;
			private PandroidEventviewerActivity object;

			OnItemClickListener(int position, PandroidEventviewerActivity object) {
				mPosition = position;
				this.object = object;
			}

			public void onClick(View arg0) {
				EventListItem item = this.object.eventList.get(mPosition);
				item.opened = !item.opened;
				this.object.eventList.set(mPosition, item);
				la.notifyDataSetChanged();
			}
		}

		/**
		 * Custom click listener (event validation).
		 * 
		 * @author Miguel de Dios Matías
		 * 
		 */
		private class OnClickListenerButtonValidate implements OnClickListener {
			private int idEvent;

			public OnClickListenerButtonValidate(int idEvent) {
				this.idEvent = idEvent;
			}

			public void onClick(View v) {
				Intent i = new Intent(getApplicationContext(),
						PopupValidationEvent.class);
				i.putExtra("id_event", idEvent);
				startActivityForResult(i, VALIDATE_EVENT_ACTIVITY);
			}
		}
	}

	/**
	 * Performs the create incident petition.
	 * 
	 * @return <b>true</b> if it is created.
	 * @throws IOException
	 *             If there is a problem with the connection.
	 */
	private void sendNewIncident(String title, String description, String group)
			throws IOException {
		Log.i(TAG, "Sending new incident");
		String incidentParams[] = new String[6];
		incidentParams[0] = title;
		incidentParams[1] = description;
		incidentParams[2] = String.valueOf(DEFAULT_SOURCE);
		incidentParams[3] = String.valueOf(DEFAULT_PRIORITY_CODE);
		incidentParams[4] = String.valueOf(DEFAULT_STATUS_CODE);
		int groupCode = -1;
		for (Entry<Integer, String> entry : API.getGroups(
				getApplicationContext()).entrySet()) {
			if (entry.getValue().equals(group)) {
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
			AsyncTask<String, Void, Boolean> {

		@Override
		protected Boolean doInBackground(String... params) {
			try {
				sendNewIncident(params[0], params[1], params[2]);
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
				creatingIncidentDialog.dismiss();
				finish();
			} else {
				Toast.makeText(getApplicationContext(),
						R.string.create_incident_group_error,
						Toast.LENGTH_SHORT).show();
				creatingIncidentDialog.dismiss();
			}
		}
	}
}