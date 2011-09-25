package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.HashMap;

import android.app.ListActivity;
import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.content.IntentFilter;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.MenuItem;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.RelativeLayout;
import android.widget.TextView;
import android.widget.Toast;

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
        this.core = (Core)i.getParcelableExtra("core");
        
        setContentView(R.layout.list_view_layout);
        
        this.toggleLoadingLayout();
        
        lv = (ListView)findViewById(android.R.id.list);
        
        la = new MyAdapter(getBaseContext(), object);
        
        lv.setAdapter(la);
        
        onBroadcast = new BroadcastReceiver() {
			
			@Override
			public void onReceive(Context context, Intent intent) {
				Log.e("onReceive", "onReceive");
				
				int load_more = intent.getIntExtra("load_more", 0);
				
				if (load_more == 1) {
					la.showLoadingEvents = false;
					la.notifyDataSetChanged();
				}
				else {
					LinearLayout layout = (LinearLayout) findViewById(R.id.loading_layout);
					layout.setVisibility(LinearLayout.GONE);
					
					if (object.count_events == 0) {
						layout = (LinearLayout) findViewById(R.id.empty_list_layout);
						layout.setVisibility(LinearLayout.VISIBLE);	
					}
				}
			}
		};
    }
    
    public void onResume() {
    	super.onResume();
    	
		registerReceiver(onBroadcast, new IntentFilter("eventlist.java"));
    	
    	this.toggleLoadingLayout();
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.options_menu, menu);
        return true;
    }
    @Override
    public boolean onOptionsItemSelected(MenuItem item) {
        switch (item.getItemId()) {
            case R.id.options_button_menu_options:
            	Intent i = new Intent(this, Options.class);
            	//FAIL//i.putExtra("object", object);
            	i.putExtra("core", this.core);
            	
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
    
    public Bitmap downloadFile(String fileUrl) {Log.e("downloadFile", fileUrl);
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
    		img = this.downloadFile(
    			"http://192.168.70.112/pandora_console/images/groups_small/" + group_icon + ".png");
    		
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
		
		Log.e("onListItemClick", new Integer(position).toString());
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
		
		public boolean showLoadingEvents;
		
		public MyAdapter(Context c, PandroidEventviewerActivity object)
		{
			mContext = c;
			
			this.object = object;
			
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
	    				button.setVisibility(Button.VISIBLE);
	    				
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
						setImageGroup(viewEventExtended, item.group_icon, R.id.img_group);
					}
					else {
						//ALL
						setImageGroup(viewEventExtended, "world", R.id.img_group);
					}
					
					if (item.agent_name.length() != 0) {
						View row = viewEventExtended.findViewById(R.id.row_agent);
						row.setVisibility(View.VISIBLE);
						
						text = (TextView)viewEventExtended.findViewById(R.id.type_text);
						text.setText(item.description_event);
						
						setImageType(viewEventExtended, item.description_image, R.id.img_type);
					}
					
					if (item.criticity_name.length() != 0) {
						text = (TextView)viewEventExtended.findViewById(R.id.severity_text);
						text.setText(item.criticity_name);
						
						setImageType(viewEventExtended, item.criticity_image, R.id.img_severity);
					}
					
					LinearLayout itemLinearLayout = (LinearLayout)view.findViewById(R.id.item_linear_layout);
					itemLinearLayout.addView(viewEventExtended);
				}
    		}
    		
    		return view;
		}
		
    }
    
    
}