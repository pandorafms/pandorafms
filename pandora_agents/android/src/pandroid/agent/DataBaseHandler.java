package pandroid.agent;
import java.util.ArrayList;
import java.util.List;
 
import android.content.ContentValues;
import android.content.Context;
import android.database.Cursor;
import android.database.sqlite.SQLiteDatabase;
import android.database.sqlite.SQLiteOpenHelper;
 
public class DataBaseHandler extends SQLiteOpenHelper {
 
    // All Static variables
    // Database Version
    private static final int DATABASE_VERSION = 1;
 
    // Database Name
    private static final String DATABASE_NAME = "PANDROID_DATA";
 
    // Contacts table name
    private static final String TABLE_DATA = "data"; //"values" is a reserved sqllite word
 
    // Contacts Table Columns names
    private static final String KEY_ID = "id";
    private static final String KEY_NAME = "name";
    private static final String KEY_VALUE = "value";
 
    public DataBaseHandler(Context context) {
        super(context, DATABASE_NAME, null, DATABASE_VERSION);
    }
 
    // Creating Tables
    @Override
    public void onCreate(SQLiteDatabase db) {
        String CREATE_CONTACTS_TABLE = "CREATE TABLE " + TABLE_DATA + "("
                + KEY_ID + " INTEGER PRIMARY KEY," + KEY_NAME + " TEXT,"
                + KEY_VALUE + " TEXT" + ")";
        db.execSQL(CREATE_CONTACTS_TABLE);
    }
 
    // Upgrading database
    @Override
    public void onUpgrade(SQLiteDatabase db, int oldVersion, int newVersion) {
        // Drop older table if existed
        db.execSQL("DROP TABLE IF EXISTS " + TABLE_DATA);
 
        // Create tables again
        onCreate(db);
    }
 
    /**
     * All CRUD(Create, Read, Update, Delete) Operations
     */
 
    // Adding new contact
    void addValue(DataHandler dh) {
        SQLiteDatabase db = this.getWritableDatabase();
 
        ContentValues values = new ContentValues();
        values.put(KEY_NAME, dh.get_name()); 
        values.put(KEY_VALUE, dh.get_value()); 
 
        // Inserting Row
        db.insert(TABLE_DATA, null, values);
        db.close(); // Closing database connection
    }
 
 // Getting single contact
    DataHandler getValue(String value) {
        SQLiteDatabase db = this.getReadableDatabase();
 
        Cursor cursor = db.query(TABLE_DATA, new String[] { KEY_ID,
                KEY_NAME, KEY_VALUE }, KEY_NAME + "=?",
                new String[] { value }, null, null, null, null);
        if (cursor != null)
            cursor.moveToFirst();
 
        DataHandler dh = new DataHandler(
                cursor.getString(1), cursor.getString(2));
        // return contact
        return dh;
    }
	
	// Getting all values
	public List<DataHandler> getAllValues(){
		List<DataHandler> valueList = new ArrayList<DataHandler>();
		// Select All Query
		String selectQuery = "SELECT * FROM " + TABLE_DATA;
		
		SQLiteDatabase db = this.getWritableDatabase();
		Cursor cursor = db.rawQuery(selectQuery, null);
		
		// looping through all rows adding to the list
		if(cursor.moveToFirst()){
			do{
				DataHandler dh = new DataHandler();
				dh.set_id(Integer.parseInt(cursor.getString(0)));
				dh.set_name(cursor.getString(1));
				dh.set_value(cursor.getString(2));
				valueList.add(dh);
			} while (cursor.moveToNext());
		}
		
		// return value list
		return valueList;
	}
	
	// Update single value
	public int updateValue(DataHandler dh){
		SQLiteDatabase db = this.getWritableDatabase();
		
		ContentValues values = new ContentValues();
		values.put(KEY_NAME, dh.get_name());
		values.put(KEY_VALUE, dh.get_value());
		
		// updating row
		return db.update(TABLE_DATA, values, KEY_ID + " = ?",
				new String[] { String.valueOf(dh.get_id())});
	}
	
	// Getting contacts Count
    public boolean isEmpty() {
        String countQuery = "SELECT  * FROM " + TABLE_DATA;
        SQLiteDatabase db = this.getReadableDatabase();
        Cursor cursor = db.rawQuery(countQuery, null);
        cursor.close();
 
        // return count
        if(cursor.getCount() == 0)
        	return true;
        else
        	return false;
    }
 
	
	
	
	
	
	
	
}























