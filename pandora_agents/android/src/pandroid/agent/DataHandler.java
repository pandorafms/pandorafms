package pandroid.agent;

public class DataHandler {
	int _id;
	String _name;
	String _value;
	
	public DataHandler(){
		
	}
	
	public DataHandler(String name, String value){
		this._name = name;
		this._value = value;
	}
	
	public DataHandler(int id, String name, String value){
		this._id = id;
		this._name = name;
		this._value = value;
	}

	public int get_id() {
		return _id;
	}

	public void set_id(int _id) {
		this._id = _id;
	}

	public String get_name() {
		return _name;
	}

	public void set_name(String _name) {
		this._name = _name;
	}

	public String get_value() {
		return _value;
	}

	public void set_value(String _value) {
		this._value = _value;
	}
	
	
	
}
