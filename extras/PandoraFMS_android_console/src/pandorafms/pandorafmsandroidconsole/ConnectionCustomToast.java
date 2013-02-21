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
 * Activity of help view.
 * 
 * @author Miguel de Dios Matías
 * 
 */

package pandorafms.pandorafmsandroidconsole;

import android.app.Activity;
import android.graphics.drawable.AnimationDrawable;
import android.os.Bundle;
import android.widget.ImageView;

public class ConnectionCustomToast extends Activity {
	private AnimationDrawable loadAnimation;
	public static Activity activity;
	
	public void onCreate(Bundle savedInstanceState) {
		super.onCreate(savedInstanceState);
		
		//Hack to close the activity from other activity.
		activity = this;
		
		setContentView(R.layout.connection_custom_toast);
		
		ImageView loading_anim = (ImageView)findViewById(R.id.loading_anim);
		loading_anim.setImageResource(R.drawable.loading);
		loadAnimation = (AnimationDrawable)loading_anim.getDrawable();
	}
	
	@Override
	public void onWindowFocusChanged(boolean hasFocus){
		loadAnimation.start();
	}
}
