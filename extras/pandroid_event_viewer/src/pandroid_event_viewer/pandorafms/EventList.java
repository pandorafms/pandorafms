package pandroid_event_viewer.pandorafms;

import java.util.Random;

import android.app.Activity;
import android.content.Context;
import android.graphics.Color;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.BaseAdapter;
import android.widget.ListView;
import android.widget.TextView;

public class EventList extends Activity {
	private ListView lv1;
	private MyAdapter la1;
	
    /** Called when the activity is first created. */
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.list_view_layout);
        
        //enganchamos el view con el layout de lista de main.xml
        lv1 = (ListView)findViewById(android.R.id.list);
        
        la1 = new MyAdapter(getBaseContext());
        
        lv1.setAdapter(la1);
    }
    
    
	public class MyAdapter extends BaseAdapter
    {
		private Context mContext;
		
		public MyAdapter(Context c)
		{
			mContext = c;
		}

		@Override
		public int getCount() {
			//A FUEGO ¿¿PORQUE?? Porque rellenamos con 4 datos falsos
    		return 40;
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
    		
    		Random rand = new Random();
    		rand.setSeed(System.currentTimeMillis());
    		
    		int status = rand.nextInt(5);
    		switch (status) {
    			case 0://Green
    				view.setBackgroundColor(Color.parseColor("#BBFFA4"));
    				break;
    			case 1://Red
    				view.setBackgroundColor(Color.parseColor("#FFC0B5"));
    				break;
    			case 2://Yellow
    				view.setBackgroundColor(Color.parseColor("#F4FFBF"));
    				break;
    			case 3://Blue
    				view.setBackgroundColor(Color.parseColor("#CDE2EA"));
    				break;
    			case 4://Grey
    				view.setBackgroundColor(Color.parseColor("#E4E4E4"));
    				break;
    		}
			
			TextView titulo = (TextView)view.findViewById(R.id.agent_name);
			titulo.setText("Agent " + position);
			
			TextView descripcion = (TextView)view.findViewById(R.id.event_name);
			descripcion.setText("Module Host Alive (0.00) is going to CRITICAL");
			
			TextView timestamp = (TextView)view.findViewById(R.id.timestamp);
			timestamp.setText("13:12:11");
    		
    		return view;
		}
		
    }
    
    
}