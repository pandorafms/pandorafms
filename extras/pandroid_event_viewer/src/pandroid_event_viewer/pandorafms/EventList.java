package pandroid_event_viewer.pandorafms;

import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.HashMap;

import android.app.ListActivity;
import android.content.Context;
import android.content.Intent;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Color;
import android.os.Bundle;
import android.util.Log;
import android.view.LayoutInflater;
import android.view.Menu;
import android.view.MenuInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

public class EventList extends ListActivity {
	private ListView lv;
	private MyAdapter la;
	public PandroidEventviewerActivity object;
	public HashMap<Integer, Boolean> openedItem;
	public HashMap<String, Bitmap> imgGroups;
	public HashMap<String, Bitmap> imgSeverity;
	public HashMap<String, Bitmap> imgType;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        this.openedItem = new HashMap<Integer, Boolean>();
        this.imgGroups = new HashMap<String, Bitmap>();
        this.imgSeverity = new HashMap<String, Bitmap>();
        this.imgType = new HashMap<String, Bitmap>();
        
        Intent i = getIntent();
        this.object = (PandroidEventviewerActivity)i.getSerializableExtra("object");
        
        setContentView(R.layout.list_view_layout);
        
        this.toggleLoadingLayout();
        
        lv = (ListView)findViewById(android.R.id.list);
        
        la = new MyAdapter(getBaseContext(), object);
        
        lv.setAdapter(la);
    }
    
    public void onResume() {
    	super.onResume();
    	
    	this.toggleLoadingLayout();
    }
    
    @Override
    public boolean onCreateOptionsMenu(Menu menu) {
        MenuInflater inflater = getMenuInflater();
        inflater.inflate(R.menu.options_menu, menu);
        return true;
    }
    
    public void toggleLoadingLayout() {
    	LinearLayout loadingLayout = (LinearLayout) findViewById(R.id.loading_layout);
    	
        if (this.object.loadInProgress) {
        	loadingLayout.setVisibility(LinearLayout.VISIBLE);
        }
        else {
        	loadingLayout.setVisibility(LinearLayout.GONE);
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
		
		Boolean opened = new Boolean(false);
		if (this.openedItem.containsKey(new Integer(position))) {
			opened = this.openedItem.get(new Integer(position));
		}
		
		LinearLayout itemLinearLayout = (LinearLayout)l.findViewById(R.id.item_linear_layout);
		
		if (!opened.booleanValue()) {
			EventListItem item = this.object.eventList.get(position);
			View view;
			
			LayoutInflater inflater = (LayoutInflater) getBaseContext().getSystemService(Context.LAYOUT_INFLATER_SERVICE); 
			view = inflater.inflate(R.layout.item_list_event_extended, null);
			
			TextView text;
			if (item.tags.length() != 0) {
				text = (TextView)view.findViewById(R.id.tags_text);
				text.setText(item.tags);
			}
			
			if (item.user_comment.length() != 0) {
				text = (TextView)view.findViewById(R.id.comments_text);
				text.setText(item.user_comment);
			}
			
			if (item.group_name.length() != 0) {
				text = (TextView)view.findViewById(R.id.group_text);
				text.setText(item.group_name);
				this.setImageGroup(view, item.group_icon, R.id.img_group);
			}
			else {
				//ALL
				this.setImageGroup(view, "world", R.id.img_group);
			}
			
			if (item.agent_name.length() != 0) {
				View row = view.findViewById(R.id.row_agent);
				row.setVisibility(View.VISIBLE);
				
				text = (TextView)view.findViewById(R.id.type_text);
				text.setText(item.description_event);
				
				this.setImageType(view, item.description_image, R.id.img_type);
			}
			
			if (item.criticity_name.length() != 0) {
				text = (TextView)view.findViewById(R.id.severity_text);
				text.setText(item.criticity_name);
				
				this.setImageType(view, item.criticity_image, R.id.img_severity);
			}
			Log.e("item", item.criticity_name);
			
			itemLinearLayout.addView(view);
		}
		else {
			itemLinearLayout.removeViewAt(1);
		}
		opened = new Boolean(!opened.booleanValue());
		this.openedItem.put(new Integer(position), opened);
    }
    
	public class MyAdapter extends BaseAdapter
    {
		private Context mContext;
		public PandroidEventviewerActivity object;
		
		public MyAdapter(Context c, PandroidEventviewerActivity object)
		{
			mContext = c;
			
			this.object = object;
		}

		@Override
		public int getCount() {
    		return this.object.eventList.size();
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
			
			EventListItem item = this.object.eventList.get(position);
			
			//OPTIMIZACIÓN PARA NO CREAR convertView
    		if (convertView == null)
    		{
    			LayoutInflater inflater = (LayoutInflater) mContext.getSystemService(Context.LAYOUT_INFLATER_SERVICE); 
				view = inflater.inflate(R.layout.item_list_event_layout, null);
    		}
    		else
    		{
    			view = convertView;
    		}
    		
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
    		
    		return view;
		}
		
    }
    
    
}