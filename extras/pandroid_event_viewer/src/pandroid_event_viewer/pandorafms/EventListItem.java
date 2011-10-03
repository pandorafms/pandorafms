package pandroid_event_viewer.pandorafms;

public class EventListItem {
	public int id_event;
	public int id_agent;
	public String id_user;
	public int id_group;
	public int status;
	public String timestamp;
	public String event;
	public int utimestamp;
	public String event_type;
	public int id_agentmodule;
	public int id_alert_am;
	public int criticity;
	public String user_comment;
	public String tags;
	public String agent_name;
	public String group_name;
	public String group_icon;
	public String description_event;
	public String description_image;
	public String criticity_name;
	public String criticity_image;
	
	public boolean opened;
	
	public EventListItem() {
		id_event = 0;
		id_agent = 0;
		id_user = "";
		id_group = 0;
		status = -1;
		timestamp = "";
		event = "";
		utimestamp = 0;
		event_type = "";
		id_agentmodule = 0;
		id_alert_am = 0;
		criticity = 0;
		user_comment = "";
		tags = "";
		agent_name = "";
		group_name = "";
		group_icon = "";
		description_event = "";
		description_image = "";
		criticity_name = "";
		criticity_image = "";
	}
}
