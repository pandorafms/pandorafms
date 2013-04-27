package pandroid.agent;

	import android.content.Context;
import android.content.SharedPreferences;
import android.content.SharedPreferences.Editor;
import android.preference.PreferenceManager;

	public class HelperSharedPreferences {		
		

	    public static class SharedPreferencesKeys{
	        public static final String key1="key1";
	        public static final String key2="key2";
	    }

//	    public static void putSharedPreferencesInt(Context context, String key, int value){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        Editor edit=preferences.edit();
//	        edit.putInt(key, value);
//	        edit.commit();
//	    }
//
//	    public static void putSharedPreferencesBoolean(Context context, String key, boolean val){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        Editor edit=preferences.edit();
//	        edit.putBoolean(key, val);
//	        edit.commit();
//	    }
//
//	    public static void putSharedPreferencesString(Context context, String key, String val){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        Editor edit=preferences.edit();
//	        edit.putString(key, val);
//	        edit.commit();
//	    }
//
//	    public static void putSharedPreferencesFloat(Context context, String key, float val){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        Editor edit=preferences.edit();
//	        edit.putFloat(key, val);
//	        edit.commit();
//	    }
//
//	    public static void putSharedPreferencesLong(Context context, String key, long val){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        Editor edit=preferences.edit();
//	        edit.putLong(key, val);
//	        edit.commit();
//	    }
//	    
//	    public static int getSharedPreferencesInt(Context context, String key, int _default){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        return preferences.getInt(key, _default);
//	    }
//
//	    public static long getSharedPreferencesLong(Context context, String key, long _default){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        return preferences.getLong(key, _default);
//	    }
//
//	    public static float getSharedPreferencesFloat(Context context, String key, float _default){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        return preferences.getFloat(key, _default);
//	    }
//
//	    public static String getSharedPreferencesString(Context context, String key, String _default){
//	        SharedPreferences preferences=PreferenceManager.getDefaultSharedPreferences(context);
//	        return preferences.getString(key, _default);
//	    }
	    
	    public synchronized static void putSharedPreferencesInt(Context context, String name, int value){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	int id = getDataHandler(context, name).get_id();
	  		
	  		DataHandler dh = new DataHandler(id, name, ""+value);
	  		
	  		db.updateValue(dh);
	    }
	    
	    public synchronized static void putSharedPreferencesLong(Context context, String name, long value){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	int id = getDataHandler(context, name).get_id();
	  		
	  		DataHandler dh = new DataHandler(id, name, ""+value);
	  		
	  		db.updateValue(dh);
	    }

	    public synchronized static void putSharedPreferencesFloat(Context context, String name, float value){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	int id = getDataHandler(context, name).get_id();
	  		
	  		DataHandler dh = new DataHandler(id, name, ""+value);
	  		
	  		db.updateValue(dh);
	    }
	    
	    public synchronized static void putSharedPreferencesString(Context context, String name, String value){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	int id = getDataHandler(context, name).get_id();
	  		
	  		DataHandler dh = new DataHandler(id, name, value);
	  		
	  		db.updateValue(dh);
	    }
	    
	    public synchronized static int getSharedPreferencesInt(Context context, String name, int i){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	return Integer.parseInt(db.getValue(name).get_value());
	    }

	    public synchronized static long getSharedPreferencesLong(Context context, String name, long l){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	return Long.parseLong(db.getValue(name).get_value());
	    }

	    public synchronized static float getSharedPreferencesFloat(Context context, String name, float f){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	return Float.parseFloat(db.getValue(name).get_value());
	    }
	    
	    public synchronized static String getSharedPreferencesString(Context context, String name, String s){
	    	DataBaseHandler db = new DataBaseHandler(context);
	    	return db.getValue(name).get_value();
	    }
	    
	 
  	
	    //Returns the DataHandler object of the given row "name"
	    public static synchronized DataHandler getDataHandler(Context context, String name){
	    	DataBaseHandler db = new DataBaseHandler(context);
  		
	    	return db.getValue(name);
	    }
  	
  
	    
	    
}
