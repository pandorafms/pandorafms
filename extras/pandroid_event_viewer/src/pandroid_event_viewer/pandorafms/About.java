package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.util.Log;
import android.view.View;
import android.widget.CheckBox;
import android.widget.TextView;

public class About extends Activity {
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.info);
        
        TextView text = (TextView) findViewById(R.id.url_pandora);
        text.setText(Html.fromHtml("<a href='http://pandorafms.org/'>PandoraFMS.org</a>"));
        text.setMovementMethod(LinkMovementMethod.getInstance());
        
        CheckBox check_show = (CheckBox)findViewById(R.id.dont_show_again_extended);
        
        check_show.setOnClickListener(new View.OnClickListener() {		
			@Override
			public void onClick(View v) {
				CheckBox check_show = (CheckBox)v;
				if (check_show.isChecked()) {
					SharedPreferences preferences = getSharedPreferences(
				            getString(R.string.const_string_preferences), 
				            Activity.MODE_PRIVATE);
				    SharedPreferences.Editor editorPreferences = preferences.edit();
				    editorPreferences.putBoolean("show_popup_info", false);
				    editorPreferences.commit();
				}
				else {
					SharedPreferences preferences = getSharedPreferences(
				            getString(R.string.const_string_preferences), 
				            Activity.MODE_PRIVATE);
				    SharedPreferences.Editor editorPreferences = preferences.edit();
				    editorPreferences.putBoolean("show_popup_info", true);
				    editorPreferences.commit();
				}
			}
		});
	}
}
