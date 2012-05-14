package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.ArrayList;
import java.util.HashMap;

import android.app.Activity;
import android.app.ListActivity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.content.SharedPreferences;
import android.content.res.Configuration;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.View.OnClickListener;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.RelativeLayout;
import android.widget.TextView;

public class EventList extends ListActivity {
	private ListView lv;
	private MyAdapter la;
	
	public PandroidEventviewerActivity object;
	public Core core;
	
	public HashMap<Integer, Boolean> openedItem;
	public HashMap<String, Bitmap> imgGroups;
	public HashMap<String, Bitmap> imgSeverity;
	public HashMap<String, Bitmap> imgType;
	
	private BroadcastReceiver onBroadcast;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
       // this.openedItem = new HashMap<Integer, Boolean>();
        this.imgGroups = new HashMap<String, Bitmap>();
        this.imgSeverity = new HashMap<String, Bitmap>();
        this.imgType = new HashMap<String, Bitmap>();
        
        Intent i = getIntent();
        this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        this.core = (Core)i.getSerializableExtra("core");
        
        setContentView(R.layout.list_view_layout);
        
        this.toggleLoadingLayout();
        
        lv = (ListView)findViewById(android.R.id.list);
        
        la = new MyAdapter(getBaseContext(), object, core);
        
        lv.setAdapter(la);
        
        onBroadcast = new BroadcastReceiver() {
			
			@Override
			public void onReceive(Context context, Intent intent) {
				int load_more = intent.getIntExtra("load_more", 0);
				
				Button button = (Button) findViewById(R.id.button_load_more_events);
				
				if (object.eventList.size() == 0) {
					button.setVisibility(Button.GONE);
				}
				else if (((long)object.eventList.size()) >= object.count_events) {
					button.setVisibility(Button.GONE);
				}
				else {
					button.setVisibility(Button.VISIBLE);
				}
				
				if (load_more == 1) {
					LinearLayout layout = (LinearLayout) findViewById(R.id.loading_layout);
					layout.setVisibility(LinearLayout.GONE);
					la.showLoadingEvents = false;
				}
				else {
					LinearLayout layout = (LinearLayout) findViewById(R.id.loading_layout);
					layout.setVisibility(LinearLayout.GONE);
					
					if (((int)object.count_events) == 0) {
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
    		this.object.executeBackgroundGetEvents();
    	}
    }
    
    public void onResume() {
    	super.onResume();
    	
		registerReceiver(onBroadcast, new IntentFilter("eventlist.java"));
    	
    	this.toggleLoadingLayout();
    	
    	if (!this.object.loadInProgress) {
    		if (((int)object.count_events) == 0) {
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
            	i = new Intent(this, Options.class);
            	//i.putExtra("object", object);
            	i.putExtra("core", this.core);
            	
            	startActivity(i);
            	break;
            case R.id.refresh_button_menu_options:
            	this.object.loadInProgress = true;
            	this.object.getNewListEvents = true;
            	this.object.eventList = new ArrayList<EventListItem>();
            	this.toggleLoadingLayout();
            	this.object.executeBackgroundGetEvents();
            	break;
            case R.id.about_button_menu_options:
            	i = new Intent(this, About.class);
            	startActivity(i);
            	break;
        }
        
        return true;
    }
    
    public void toggleLoadingLayout() {
    	LinearLayout layout;
    	
    	layout = (LinearLayout) findViewById(R.id.empty_list_layout);
		layout.setVisibility(LinearLayout.GONE);
		
		layout = (LinearLayout) findViewById(R.id.loading_layout);
    	
        if (this.object.loadInProgress) {
        	layout.setVisibility(LinearLayout.VISIBLE);
        }
        else {
        	layout.setVisibility(LinearLayout.GONE);
        }
    }
    
    public Bitmap downloadFile(String fileUrl) {
          URL myFileUrl =null;          
          try {
               myFileUrl= new URL(fileUrl);
          } catch (MalformedURLException e) {
               // TODO Auto-generated catch block
               e.printStackTrace();
          }
          try {
               HttpURLConnection conn= (HttpURLConnection)myFileUrl.openConnection();
               conn.setDoInput(true);
               conn.connect();
               InputStream is = conn.getInputStream();
               
               return BitmapFactory.decodeStream(is);
               //imView.setImageBitmap(bmImg);
          } catch (IOException e) {
               // TODO Auto-generated catch block
               e.printStackTrace();
          }
        
          return null;
    }
    
    public void setImageGroup(View view, String group_icon, int id) {
    	ImageView imgview = (ImageView)view.findViewById(id);
    	Bitmap img;
    	
    	if (this.imgGroups.containsKey(group_icon)) {
    		img = this.imgGroups.get(group_icon);
    	}
    	else {
    		 SharedPreferences preferences = getApplicationContext().getSharedPreferences(
    			getApplicationContext().getString(R.string.const_string_preferences), 
	        	Activity.MODE_PRIVATE);
    		            
    		String url = preferences.getString("url", "");
    		
    		img = this.downloadFile(
    			url + "/images/groups_small/" + group_icon + ".png");
    		
    		if (img != null) {
    			this.imgGroups.put(group_icon, img);
    		}
    	}
    	
    	if (img != null) {
    		imgview.setImageBitmap(img);
    	}
    }
    
    public void setImageType(View view, String url, int id) {
    	ImageView imgview = (ImageView)view.findViewById(id);
    	Bitmap img = null;
    	
    	if (this.imgType.containsKey(url)) {
    		img = this.imgType.get(url);
    	}
    	else {
    		img = this.downloadFile(url);
    		
    		if (img != null) {
    			this.imgType.put(url, img);
    		}
    	}
    	
    	if (img != null) {
    		imgview.setImageBitmap(img);
    	}
    }
    
    public void setImageSeverity(View view, String url, int id) {
    	ImageView imgview = (ImageView)view.findViewById(id);
    	Bitmap img = null;
    	
    	if (this.imgSeverity.containsKey(url)) {
    		img = this.imgSeverity.get(url);
    	}
    	else {
    		img = this.downloadFile(url);
    		
    		if (img != null) {
    			this.imgSeverity.put(url, img);
    		}
    	}
    	
    	if (img != null) {
    		imgview.setImageBitmap(img);
    	}
    }
    
	@Override
    protected void onListItemClick(ListView l, View v, int position, long id)
    {
		super.onListItemClick(l, v, position, id);
		
		EventListItem item = this.object.eventList.get(position);
		
		item.opened = !item.opened;
		this.object.eventList.set(position, item);
		la.notifyDataSetChanged();
    }
	
	public void loadMoreEvents(View v) {
		la.showLoadingEvents = true;
		la.notifyDataSetChanged();
		
		object.executeBackgroundGetEvents();
	}
    
	public class MyAdapter extends BaseAdapter
    {
		private Context mContext;
		public PandroidEventviewerActivity object;
		public Core core;
		
		public boolean showLoadingEvents;
		
		public MyAdapter(Context c, PandroidEventviewerActivity object, Core core)
		{
			mContext = c;
			
			this.object = object;
			this.core = core;
			
			showLoadingEvents = false;
		}

		@Override
		public int getCount() {
    		return this.object.eventList.size() + 1;
		}

		@Override
		public Object getItem(int position) {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		public long getItemId(int position) {
			// TODO Auto-generated method stub
			return 0;
		}
		
		@Override
		public View getView(int position, View convertView, ViewGroup parent) {
			View view;
			
			//The comment lines don't run fine, opened wrong the rows.
			
			//Optimization for not create convertView all times
    		//if (convertView == null)
    		//{
    			LayoutInflater inflater = (LayoutInflater) mContext.getSystemService(Context.LAYOUT_INFLATER_SERVICE); 
				view = inflater.inflate(R.layout.item_list_event_layout, null);
    		/*}
    		else
    		{
    			view = convertView;
    		}*/
    		
			//If the end of the list.
    		if (this.object.eventList.size() == position) {
    			//Show button to get more events
    			if ((!object.loadInProgress) && (object.count_events != 0)) {
    				if (showLoadingEvents) {
    					LinearLayout layout = (LinearLayout) view.findViewById(R.id.loading_more_events);
    					layout.setVisibility(LinearLayout.VISIBLE);
    					
    					RelativeLayout layout2 = (RelativeLayout) view.findViewById(R.id.content_event_item);
    					layout2.setVisibility(RelativeLayout.GONE);
    					
    					Button button = (Button)view.findViewById(R.id.button_load_more_events);
	    				button.setVisibility(Button.GONE);
    				}
    				else {
	    				Button button = (Button)view.findViewById(R.id.button_load_more_events);
	    				
	    				if (object.eventList.size() == 0) {
	    					button.setVisibility(Button.GONE);
	    				}
	    				else if (((long)object.eventList.size()) >= object.count_events) {
	    					button.setVisibility(Button.GONE);
	    				}
	    				else {
	    					button.setVisibility(Button.VISIBLE);
	    				}
	    				
	    				button.setOnClickListener(new View.OnClickListener() {		
	    					@Override
	    					public void onClick(View v) {
	    						object.offset += object.pagination;
	    						loadMoreEvents(v);
	    					}
	    				});
	    				
	    				RelativeLayout content_event_item = (RelativeLayout)view.findViewById(R.id.content_event_item);
	    				content_event_item.setVisibility(RelativeLayout.GONE);
    				}
    			}
    		}
    		else {
    			EventListItem item = this.object.eventList.get(position);
    			
	    		switch (item.criticity) {
	  
	    			case 0://Blue
	    				view.setBackgroundColor(Color.parseColor("#CDE2EA"));
	    				break;
	    			case 1://Grey
	    				view.setBackgroundColor(Color.parseColor("#E4E4E4"));
	    				break;
	    			case 2://Green
	    				view.setBackgroundColor(Color.parseColor("#BBFFA4"));
	    				break;
	    			case 3://Yellow
	    				view.setBackgroundColor(Color.parseColor("#F4FFBF"));
	    				break;
	    			case 4://Red
	    				view.setBackgroundColor(Color.parseColor("#FFC0B5"));
	    				break;
	    			default://Grey
	    				view.setBackgroundColor(Color.parseColor("#E4E4E4"));
	    				break;
	    		}
				
				TextView titulo = (TextView)view.findViewById(R.id.agent_name);
				
				if (item.event_type.equals("system")) {
					titulo.setText(R.string.system_str);
				}
				else {
					titulo.setText(item.agent_name);
				}
				
				TextView descripcion = (TextView)view.findViewById(R.id.event_name);
				descripcion.setText(item.event);
				
				TextView timestamp = (TextView)view.findViewById(R.id.timestamp);
				timestamp.setText(item.timestamp);
				
				if (item.criticity_image.length() != 0) 
					setImageType(view, item.criticity_image, R.id.img_severity_colapse_item);
				if (item.group_icon.length() != 0)
					setImageGroup(view, item.group_icon, R.id.img_group_colapse_item);
				
				ImageView imgValidate = (ImageView)view.findViewById(R.id.img_validate_colapse_item);
				if (item.status == 1) {
					imgValidate.setImageResource(R.drawable.tick);
				}
				else {
					imgValidate.setImageResource(R.drawable.tick_off);
				}
				
				
				//Show extended info
				if (item.opened) {
					View viewEventExtended;
					viewEventExtended = inflater.inflate(R.layout.item_list_event_extended, null);
					
					TextView text;
					if (item.tags.length() != 0) {
						text = (TextView)viewEventExtended.findViewById(R.id.tags_text);
						text.setText(item.tags);
					}
					
					if (item.user_comment.length() != 0) {
						text = (TextView)viewEventExtended.findViewById(R.id.comments_text);
						text.setText(item.user_comment);
					}
					
					if (item.group_name.length() != 0) {
						text = (TextView)viewEventExtended.findViewById(R.id.group_text);
						text.setText(item.group_name);
						if (item.group_icon.length() != 0)
							setImageGroup(viewEventExtended, item.group_icon, R.id.img_group);
					}
					else {
						//ALL
						setImageGroup(viewEventExtended, "world", R.id.img_group);
					}
					
					if (item.agent_name.length() != 0) {
						View row = viewEventExtended.findViewById(R.id.row_agent);
						row.setVisibility(View.VISIBLE);
						
						text = (TextView)viewEventExtended.findViewById(R.id.agent_text);
						text.setText(Html.fromHtml(
							"<a href='" + this.object.url +
							"/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente="
							//"/mobile/index.php?page=agent&id=" //The link to Pandora Console Mobile
							+ item.id_agent
							+ "'>" + item.agent_name + "</a>"));
						text.setMovementMethod(LinkMovementMethod.getInstance());
					}
					
					if (item.description_image.length() != 0)
						setImageType(viewEventExtended, item.description_image, R.id.img_type);
					text = (TextView)viewEventExtended.findViewById(R.id.type_text);
					text.setText(eventType2Text(item.event_type));
					
					if (item.criticity_name.length() != 0) {
						text = (TextView)viewEventExtended.findViewById(R.id.severity_text);
						text.setText(item.criticity_name);
						
						if (item.criticity_image.length() != 0)
							setImageType(viewEventExtended, item.criticity_image, R.id.img_severity);
					}
					
					//Set the open and close the extended info event row action.
					view.setOnClickListener(new OnItemClickListener(position, this.object));
					
					Button button;
			        button = (Button)viewEventExtended.findViewById(R.id.validate_button_extended);
			        if (item.status == -1) {
			        	//For unknow events
			        	button.setVisibility(Button.GONE);
			        	text = (TextView)viewEventExtended.findViewById(R.id.validate_event_label);
			        	text.setText("");
			        	text.setVisibility(TextView.VISIBLE);
			        }
			        else if (item.status != 1) {
				        OnClickListenerButtonValidate clickListener = new OnClickListenerButtonValidate();
				        clickListener.id_event = item.id_event;
				        clickListener.core = this.core;
				        button.setOnClickListener(clickListener);
				        
				        text = (TextView)viewEventExtended.findViewById(R.id.validate_event_label);
				        text.setVisibility(TextView.GONE);
			        }
			        else {
			        	button.setVisibility(Button.GONE);
			        	text = (TextView)viewEventExtended.findViewById(R.id.validate_event_label);
			        	text.setVisibility(TextView.VISIBLE);
			        }
					
					LinearLayout itemLinearLayout = (LinearLayout)view.findViewById(R.id.item_linear_layout);
					itemLinearLayout.addView(viewEventExtended);
				}
    		}
    		
    		return view;
		}
		
		private String eventType2Text(String type) {
			String return_var;
			
			if (type.equals("alert_recovered")) {
				return_var = getApplicationContext().getString(R.string.alert_recovered_str);
			}
			else if (type.equals("alert_manual_validation")) {
				return_var = getApplicationContext().getString(R.string.alert_manual_validation_str);
			}
			else if (type.equals("going_up_warning")) {
				return_var = getApplicationContext().getString(R.string.going_up_warning_str);
			}
			else if (type.equals("going_down_critical")) {
				return_var = getApplicationContext().getString(R.string.going_down_critical_str);
			}
			else if (type.equals("going_up_critical")) {
				return_var = getApplicationContext().getString(R.string.going_down_critical_str);
			}
			else if (type.equals("going_up_normal")) {
				return_var = getApplicationContext().getString(R.string.going_up_normal_str);
			}
			else if (type.equals("going_down_normal")) {
				return_var = getApplicationContext().getString(R.string.going_up_normal_str);
			}
			else if (type.equals("going_down_warning")) {
				return_var = getApplicationContext().getString(R.string.going_down_warning_str);
			}
			else if (type.equals("alert_fired")) {
				return_var = getApplicationContext().getString(R.string.alert_fired_str);
			}
			else if (type.equals("system")) {
				return_var = getApplicationContext().getString(R.string.system_str);
			}
			else if (type.equals("recon_host_detected")) {
				return_var = getApplicationContext().getString(R.string.system_str);
			}
			else if (type.equals("new_agent")) {
				return_var = getApplicationContext().getString(R.string.new_agent_str);
			}
			else {
				return_var = getApplicationContext().getString(R.string.unknown_str) + " " + type;
			}
			
			return return_var;
		}
		
		private class OnItemClickListener implements OnClickListener{
	    	private int mPosition;
	    	private PandroidEventviewerActivity object;
	    	OnItemClickListener(int position, PandroidEventviewerActivity object){
	    		mPosition = position;
	    		this.object = object;
	    	}
	    	@Override
	    	public void onClick(View arg0) {
	    		EventListItem item = this.object.eventList.get(mPosition);
	    		item.opened = !item.opened;
	    		this.object.eventList.set(mPosition, item);
	    		la.notifyDataSetChanged();
	    	}
	    }
		
		public class OnClickListenerButtonValidate implements OnClickListener {
			public int id_event;
			//public PandroidEventviewerActivity object;
			public Core core;
			
			@Override
			public void onClick(View v) {
				Intent i = new Intent(getApplicationContext(), PopupValidationEvent.class);
            	i.putExtra("id_event", id_event);
                //i.putExtra("object", this.object);
                i.putExtra("core", this.core);
            	
            	startActivity(i);
			}
			
		}
		
    }
    
    
}