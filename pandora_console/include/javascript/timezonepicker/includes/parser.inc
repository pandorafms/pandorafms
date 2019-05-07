<?php
/**
 * Parses two PostGIS export files, as formatted by the README.md file.
 *
 * This converts given export files from using longitude and latitude
 * coordinates into X/Y coordinates on a specified map with a particular width
 * and height. Because image maps are generally not scalable (though this is not
 * impossible), each set of returned coordinates is size-specific.
 */
function timezone_picker_parse_files($map_width, $map_height, $tz_world, $tz_islands = NULL) {
  // Variable to store all the timezone information. Each timezone should include:
  // - offset: The UTC offset. Represented in hours, rounded to one decimal.
  // - pin: An array of X/Y coordinates representing the primary city in the
  //        timezone.
  // - country: The two-character country code.
  // Each timezone is keyed by its Timezone Database name, as retrieved by PHP.
  // See http://us.php.net/manual/en/timezones.php.
  $timezones = array();

  // By using a winter date, daylight savings will not be active. This makes
  // timezone offsets consistent year-round. i.e. Arizona is always -4 UTC
  // instead of -5 UTC in the summer. It also helps increase consistency in the
  // vertical display of timezones, since any kind of daylight savings is hit or
  // miss between different countries. As an added bonus, it distinguishes
  // Pacific/Marjuro and Pacific/Kwaljalein which have overlapping borders but
  // separate timezones.
  // Also note that the TZ Database has different offsets for countries based on
  // year, so using the current year refects current offsets.
  $empty_date = date_create('2012-01-01T00:00:00Z');

  // Parse the main timezone export file.
  $contents = file_get_contents($tz_world);
  $rows = array_filter(explode("\n", $contents));
  foreach ($rows as $row) {
    if (empty($row)) {
      continue;
    }

    $array_1 = explode('|', $row);
    $timezone_name = $array_1[0];
    $timezone_data = $array_1[1];

    // Determine offset for grouping.
    if (!isset($timezones[$timezone_name])) {
      // Not all timezones may be in every version of PHP.
      if ($timezone = @timezone_open($timezone_name)) {
        $timezone_location = timezone_location_get($timezone);
        $timezones[$timezone_name]['offset'] = round(timezone_offset_get($timezone, $empty_date) / 3600, 1);
        $timezones[$timezone_name]['pin'] = timezone_picker_convert_xy($timezone_location['latitude'], $timezone_location['longitude'], $map_width, $map_height);
        $timezones[$timezone_name]['country'] = $timezone_location['country_code'] !== '??' ? $timezone_location['country_code'] : NULL;
      }
      else {
        $timezones[$timezone_name]['offset'] = NULL;
        $timezones[$timezone_name]['pin'] = array();
        $timezones[$timezone_name]['country'] = NULL;
      }
      $timezones[$timezone_name]['polys'] = array();
      $timezones[$timezone_name]['rects'] = array();
    }

    // Remove MULTIPOLYGON() from the data.
    $timezone_data = substr($timezone_data, 13);
    $timezone_data = substr($timezone_data, 0, strlen($timezone_data) - 1);

    $polys = explode(')),((', $timezone_data);

    foreach ($polys as $poly) {
      // Remove leading or trailing parethesis.
      $poly = trim($poly, ')(');

      // Most of our polygons will only have an outer polygon, though its possible
      // that we need to cutout an inner polygon. Since ImageMaps don't support
      // this, we discard the cutout.
      $array_2 = explode('),(', $poly);
      $outer_poly = $array_2[0]; 

      $area_poly = array();
      $longlats = explode(',', $outer_poly);
      foreach ($longlats as $longlat) {
        $array_3 = explode(' ', $longlat);
        $longitude = $array_3[0];
        $latitude = $array_3[1];
        $array_4 = timezone_picker_convert_xy($latitude, $longitude, $map_width, $map_height);
        $x = $array_4[0];
        $y = $array_4[1];
        $area_poly[] = $x;
        $area_poly[] = $y;
      }
      $timezones[$timezone_name]['polys'][] = $area_poly;
    }
  }

  // Optionally make islands easier to select by using bounding boxes.
  if (file_exists($tz_islands)) {
    $contents = file_get_contents('include/javascript/timezonepicker/tz_islands.txt');
    $rows = array_filter(explode("\n", $contents));

    foreach ($rows as $row) {
      $array_5 = explode('|', $row);
      $timezone_name = $array_5[0]; 
      $timezone_data = $array_5[1];

      // Don't allow wrapping across the seam of the map.
      if ($timezone_name === 'Pacific/Fiji' || $timezone_name === 'Pacific/Auckland') {
        continue;
      }

      // Remove BOX() from the data.
      $timezone_data = substr($timezone_data, 4);
      $timezone_data = substr($timezone_data, 0, strlen($timezone_data) - 1);

      $area_poly = array();
      $longlats = explode(',', trim($timezone_data, ')('));

      $array_6 = explode(' ', $longlats[0]);
      $longitude = $array_6[0]; 
      $latitude = $array_6[1];
      
      $array_7 = timezone_picker_convert_xy($latitude, $longitude, $map_width, $map_height);
      $x1 = $array_7[0];
      $y1 = $array_7[1];
      
      $array_8 = explode(' ', $longlats[1]);
      $longitude = $array_8[0];
      $latitude = $array_8[1];
      
      $array_9 = timezone_picker_convert_xy($latitude, $longitude, $map_width, $map_height);
      $x2 = $array_9[0];
      $y2 = $array_9[1];

      // Ensure minimum areas.
      if ($x2 - $x1 < 10) {
        $x1 -= 5;
        $x2 += 5;
      }
      if ($y1 - $y2 < 10) {
        $y2 -= 5;
        $y1 += 5;
      }

      if (isset($timezones[$timezone_name])) {
        $timezones[$timezone_name]['rects'] = array(array($x1, $y1, $x2, $y2));
        if (count($timezones[$timezone_name]['polys']) === 1) {
          $timezones[$timezone_name]['polys'] = array();
        }
      }
    }
  }
  return $timezones;
}

/**
 * Converts latitude and longitude into X,Y coodinates on a Equirectangular map.
 *
 * Latitude and longitude are intended to be placed on a sphere, so when making
 * a 2D representation we need to convert these to X,Y coordinates. This
 * is intended to be used with the "Equirectangular" map projection and adjusts
 * the X,Y coodinates based on the height and width of the map to be displayed.
 *
 * @see http://en.wikipedia.org/wiki/Equirectangular_projection
 * @see http://en.wikipedia.org/wiki/File:BlankMap-World6-Equirectangular.svg
 */
function timezone_picker_convert_xy($latitude, $longitude, $map_width, $map_height) {
  $x = round(($longitude + 180) * ($map_width / 360));
  $y = round((($latitude * -1) + 90) * ($map_height / 180));
  return array($x, $y);
}
