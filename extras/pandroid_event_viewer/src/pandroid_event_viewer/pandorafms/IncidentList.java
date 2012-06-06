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

import java.util.ArrayList;
import java.util.List;

import android.app.ListActivity;
import android.content.Context;
import android.os.AsyncTask;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.RelativeLayout;
import android.widget.TextView;

/**
 * Activity where incidents are displayed.
 * 
 * @author Santiago Munín González
 * 
 */
public class IncidentList extends ListActivity {
	private ListView lv;
	private MyIncidentListAdapter la;
	private boolean loadInProgress;
	private static String TAG = "IncidentList";
	private List<IncidentListItem> incidents = new ArrayList<IncidentListItem>();
	private int newIncidents;
	private int offset, pagination;

	@Override
	protected void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		setContentView(R.layout.list_view_layout);
		offset = 0;
		pagination = 0;
		la = new MyIncidentListAdapter(getBaseContext());
		this.toggleLoadingLayout();
		lv = (ListView) findViewById(android.R.id.list);
		lv.setAdapter(la);
		// TODO broadcast receiver went here
		this.toggleLoadingLayout();
		loadIncidents();
		this.toggleLoadingLayout();
	}

	/**
	 * Shows loading information.
	 */
	private void toggleLoadingLayout() {
		LinearLayout layout;

		layout = (LinearLayout) findViewById(R.id.empty_list_layout);
		layout.setVisibility(LinearLayout.GONE);

		layout = (LinearLayout) findViewById(R.id.loading_layout);

		if (loadInProgress) {
			layout.setVisibility(LinearLayout.VISIBLE);
		} else {
			layout.setVisibility(LinearLayout.GONE);
		}
	}
	/**
	 * Loads incidents.
	 *
	 */
	private void loadIncidents() {
		la.showLoadingEvents = true;
		new GetIncidentsTask().execute((Void)null);
	}
	private class GetIncidentsTask extends AsyncTask<Void, Void, Void> {
		List<IncidentListItem> list = new ArrayList<IncidentListItem>();
		@Override
		protected Void doInBackground(Void... params) {
			incidents = new ArrayList<IncidentListItem>();
			//TODO just a tests
			IncidentListItem i = new IncidentListItem();
			i.title = "Test";
			i.description = "asdadsadads";
			i.timestamp = "ASDD";
			i.statusImage = "http://www.limpiatumundo.com/imagenes/circle_blue.png";
			list.add(i);
			i = new IncidentListItem();
			i.title = "Test2";
			list.add(i);
			
			return null;
		}
		@Override
		protected void onPostExecute(Void result) {
			super.onPostExecute(result);
			incidents.clear();
			incidents.addAll(list);
			la.notifyDataSetChanged();
		}
		
	}
	/**
	 * Private adapter (incident list).
	 * 
	 * @author Santiago Munín González
	 * 
	 */
	private class MyIncidentListAdapter extends BaseAdapter {
		private Context mContext;
		public boolean showLoadingEvents;

		public MyIncidentListAdapter(Context c) {
			super();
			mContext = c;
			showLoadingEvents = false;
			
		}

		@Override
		public int getCount() {
			// TODO +1?
			return incidents.size();
		}

		@Override
		public Object getItem(int position) {
			return null;
		}

		@Override
		public long getItemId(int position) {
			return 0;
		}

		@Override
		public View getView(int position, View convertView, ViewGroup parent) {
			View view;

			LayoutInflater inflater = (LayoutInflater) mContext
					.getSystemService(Context.LAYOUT_INFLATER_SERVICE);
			view = inflater.inflate(R.layout.item_list_incident_layout, null);
			view.setOnClickListener(new OnIncidentClickListener(position));
			// If the end of the list.
			if (incidents.size() == position) {
				// Show button to get more events
				if ((!loadInProgress) && (newIncidents != 0)) {
					if (showLoadingEvents) {
						LinearLayout layout = (LinearLayout) view
								.findViewById(R.id.loading_more_incidents);
						layout.setVisibility(LinearLayout.VISIBLE);

						RelativeLayout layout2 = (RelativeLayout) view
								.findViewById(R.id.content_incident_item);
						layout2.setVisibility(RelativeLayout.VISIBLE);

						Button button = (Button) view
								.findViewById(R.id.button_load_more_incidents);
						button.setVisibility(Button.GONE);
					} else {
						Button button = (Button) view
								.findViewById(R.id.button_load_more_incidents);

			/*			if (incidents.size() == 0) {
							button.setVisibility(Button.GONE);
						} else if (incidents.size() >= newIncidents) {
							button.setVisibility(Button.GONE);
						} else {
							button.setVisibility(Button.VISIBLE);
						}*/

						button.setOnClickListener(new View.OnClickListener() {
							@Override
							public void onClick(View v) {
								offset += pagination;								
								loadIncidents();
							}
						});

						/*RelativeLayout content_event_item = (RelativeLayout) view
								.findViewById(R.id.content_event_item);
						content_event_item.setVisibility(RelativeLayout.GONE);*/
					}
				}
			} else {
				IncidentListItem item = incidents.get(position);

				TextView tv = (TextView) view.findViewById(R.id.incident_name);
				tv.setText(item.title);
				Core.setTextViewLeftImage(tv, item.statusImage);

				tv = (TextView) view.findViewById(R.id.incident_agent);
				tv.setText(item.nameAgent);
				Core.setTextViewLeftImage(tv, item.priorityImage);

				tv = (TextView) view
						.findViewById(R.id.incident_last_update_timestamp);
				tv.setText(item.timestamp);

				// Show extended info
				if (item.opened) {
					((LinearLayout) view.findViewById(R.id.incident_extra_info)).setVisibility(View.VISIBLE);

					TextView text = (TextView) view.findViewById(R.id.incident_description);
					text.setText(item.description);

					view.setOnClickListener(new OnIncidentClickListener(position));

					Button button;
					button = (Button) findViewById(R.id.incident_close_button);
					// TODO if status == closed then
					// button.setVisibility(Visibility.GONE);
					// else
				/*	button.setOnClickListener(new OnClickListener() {

						@Override
						public void onClick(View v) {
							// TODO close event
						}
					});*/

				} else {
					((LinearLayout) view.findViewById(R.id.incident_extra_info)).setVisibility(View.GONE);
				}
			}

			return view;
		}

		/**
		 * Custom click listener (show more information).
		 * 
		 * @author Santiago Munín González
		 * 
		 */
		private class OnIncidentClickListener implements OnClickListener {
			private int mPosition;

			OnIncidentClickListener(int position) {
				mPosition = position;
			}

			@Override
			public void onClick(View arg0) {
				IncidentListItem item = incidents.get(mPosition);
				item.opened = !item.opened;
				incidents.set(mPosition, item);
				la.notifyDataSetChanged();
			}
		}
	}
}