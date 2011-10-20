package pandroid.agent;

import android.app.Activity;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.widget.TextView;

public class Help extends Activity {
	@Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        
        setContentView(R.layout.help);
        
        
        TextView text = (TextView) findViewById(R.id.help_text);
        text.setText(Html.fromHtml(getString(R.string.help_text_str)));
        text.setMovementMethod(LinkMovementMethod.getInstance());
	}
}
