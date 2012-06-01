package pandroid_event_viewer.pandorafms;

import java.util.List;

/**
 * Represents an incident.
 * 
 * @author Santiago Munín González
 * 
 */
public class IncidentListItem {
	public int id;
	public int idAgent;
	public String nameAgent;
	public String title;
	public String description;
	public int priority;
	public String priorityImage;
	public int idGroup;
	public String nameGroup;
	public List<String> notes;
	public int status;
	public String statusImage;
	public String timestamp;
	public boolean opened = false;
	//TODO attachments too?
}
