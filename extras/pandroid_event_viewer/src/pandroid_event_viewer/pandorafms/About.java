/*
Pandora FMS - http://pandorafms.com

==================================================
Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
Please see http://pandorafms.org for full contribution list

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public License
as published by the Free Software Foundation; version 2

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 */
package pandroid_event_viewer.pandorafms;

import android.app.Activity;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Html;
import android.text.method.LinkMovementMethod;
import android.view.View;
import android.widget.CheckBox;
import android.widget.TextView;

/**
 * This class contains basic instructions to describe the About popup.
 * 
 * @author Miguel de Dios Matías
 * 
 */
public class About extends Activity {

	@Override
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);

		setContentView(R.layout.info);

		TextView text = (TextView) findViewById(R.id.url_pandora);
		text.setText(Html
				.fromHtml("<a href='http://pandorafms.org/'>PandoraFMS.org</a>"));
		text.setMovementMethod(LinkMovementMethod.getInstance());

		CheckBox check_show = (CheckBox) findViewById(R.id.dont_show_again_extended);

		check_show.setOnClickListener(new View.OnClickListener() {
			@Override
			public void onClick(View v) {
				CheckBox check_show = (CheckBox) v;
				if (check_show.isChecked()) {
					SharedPreferences preferences = getSharedPreferences(
							getString(R.string.const_string_preferences),
							Activity.MODE_PRIVATE);
					SharedPreferences.Editor editorPreferences = preferences
							.edit();
					editorPreferences.putBoolean("show_popup_info", false);
					editorPreferences.commit();
				} else {
					SharedPreferences preferences = getSharedPreferences(
							getString(R.string.const_string_preferences),
							Activity.MODE_PRIVATE);
					SharedPreferences.Editor editorPreferences = preferences
							.edit();
					editorPreferences.putBoolean("show_popup_info", true);
					editorPreferences.commit();
				}
			}
		});
	}
}
