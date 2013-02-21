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

/**
 * Activity of option view.
 * 
 * @author Miguel de Dios Matías
 * 
 */

package pandorafms.pandorafmsandroidconsole;

import android.app.Activity;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;

public class Options extends Activity {
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		setContentView(R.layout.options);
		
		Button save_button = (Button)findViewById(R.id.save_options);
		Button cancel_button = (Button)findViewById(R.id.cancel_options);
		
		SharedPreferences preferences = getSharedPreferences(
			getString(R.string.const_string_preferences),
			Activity.MODE_PRIVATE);
		
		EditText field;
		
		field = (EditText)findViewById(R.id.url_option);
		String url_pandora = preferences.getString("url_pandora", "");
		field.setText(url_pandora);
		field = (EditText)findViewById(R.id.user_option);
		String user = preferences.getString("user", "");
		field.setText(user);
		field = (EditText)findViewById(R.id.password_option);
		String password = preferences.getString("password", "");
		field.setText(password);
		
		//Check if not empty the data of connection
		if (!url_pandora.equals("") && !user.equals("") && !password.equals("")) {
			//And set the label of button as update
			save_button.setText(getString(R.string.update_button_str));
		}
		
		//Add the listener for the save button.
		save_button.setOnClickListener(new View.OnClickListener() {
			
			@Override
			public void onClick(View v) {
				SharedPreferences preferences = getSharedPreferences(
						getString(R.string.const_string_preferences),
						Activity.MODE_PRIVATE);
				SharedPreferences.Editor editorPreferences = preferences
						.edit();
				
				EditText field;
				
				field = (EditText)findViewById(R.id.url_option);
				editorPreferences.putString("url_pandora", field.getText().toString());
				field = (EditText)findViewById(R.id.user_option);
				editorPreferences.putString("user", field.getText().toString());
				field = (EditText)findViewById(R.id.password_option);
				editorPreferences.putString("password", field.getText().toString());
				editorPreferences.commit();
				
				setResult(666);
				finish();
			}
		});
		
		cancel_button.setOnClickListener(new View.OnClickListener() {
			
			@Override
			public void onClick(View v) {
				finish();
			}
		});
		
	}
}
