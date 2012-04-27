package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.widget.TextView;

public class Info extends Activity {
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.about);
        
        TextView text = (TextView) findViewById(R.id.url_pandora);
        text.setText(Html.fromHtml("<a href='http://pandorafms.org/'>PandoraFMS.org</a>"));
        text.setMovementMethod(LinkMovementMethod.getInstance());
	}
}
