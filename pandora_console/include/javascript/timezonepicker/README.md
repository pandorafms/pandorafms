# timezonepicker

A jQuery and ImageMap based timezone picker.

This library only works with pre-built imagemaps generated from
http://timezonepicker.com.

## Features

- Simple implementation, lightweight footprint (160KB, 40KB gzipped).
- Includes 440+ clickable areas.
- HTML5 Geolocation to identify timezone.
- Islands include padding to increase ease of selection.
- Country mapping can be used to set timezone and country at the same time.
- Timezone highlighting on rollover (thanks to [jQuery maphilight](http://davidlynch.org/projects/maphilight/docs/))

## Usage

Basic call using all defaults:

```javascript
$("#img-with-usemap-attr").timezonePicker();
```

A few simple options:

```javascript
$("#img-with-usemap-attr").timezonePicker({
  pin: ".timezone-pin",
  fillColor: "FFCCCC"
});
```

## Options

As pulled from the set of defaults.

```javascript
$.fn.timezonePicker.defaults = {
  // Selector for the pin that should be used. This selector only works in the
  // immediate parent of the image map img tag.
  pin: ".timezone-pin",
  // Specify a URL for the pin image instead of using a DOM element.
  pinUrl: null,
  // Preselect a particular timezone.
  timezone: null,
  // Pass through options to the jQuery maphilight plugin.
  maphilight: true,
  // Selector for the select list, textfield, or hidden to update upon click.
  target: null,
  // Selector for the select list, textfield, or hidden to update upon click
  // with the specified country.
  countryTarget: null,
  // If changing the country should use the first timezone within that country.
  countryGuess: true,
  // A list of country guess exceptions. These should only be needed if a
  // country spans multiple timezones.
  countryGuesses: {
    AU: "Australia/Sydney",
    BR: "America/Sao_Paulo",
    CA: "America/Toronto",
    CN: "Asia/Shanghai",
    ES: "Europe/Madrid",
    MX: "America/Mexico_City",
    RU: "Europe/Moscow",
    US: "America/New_York"
  },
  // If this map should automatically adjust its size if scaled. Note that
  // this can be very expensive computationally and will likely have a delay
  // on resize. The maphilight library also is incompatible with this setting
  // and will be disabled.
  responsive: false,
  // A function to be called upon timezone change
  // timezoneName, countryName, and offset will be passed as arguments
  changeHandler: null,

  // Default options passed along to the maphilight plugin.
  fade: false,
  stroke: true,
  strokeColor: "FFFFFF",
  strokeOpacity: 0.4,
  fillColor: "FFFFFF",
  fillOpacity: 0.4,
  groupBy: "data-offset"
};
```

## Additional methods

After creating a timezone picker from an image tag, you can execute additional
commands on the image map with these methods:

```javascript
// Query the user's browser for the current location and set timezone from that.
$("#img-with-usemap-attr").timezonePicker("detectTimezone");

// The detectTimezone method may also provide event callbacks.
$("#img-with-usemap-attr").timezonePicker("detectTimezone", {
  success: successCallback,
  error: errorCallback,
  complete: completeCallback // Called on both success or failure.
});

// Set the active timezone to some value programatically.
$("#img-with-usemap-attr").timezonePicker("updateTimezone", "America/New_York");

// Resize the image map coordinates to match an adjusted size of the image.
// Note that this option does not work well and is very slow. Not recommended.
$("#img-with-usemap-attr").timezonePicker("resize");
```

## Building new definition files

The definition files are used to determine the polygons and rectangles used to
generate the resulting imagemap. Note this should rarely be necessary for normal
users as the timezone picker project will rebuild the shape files after updates
to the timezone database.

1. Download latest shape file "tz_world" from
   http://efele.net/maps/tz/world/.

   wget http://efele.net/maps/tz/world/tz_world.zip
   unzip tz_world.zip

2. Install PostGIS, which provides the shp2pgsql executable.
   http://postgis.refractions.net/download/

   For Mac OS X, I installed PostGres, GDAL Complete, and PostGIS binaries from
   http://www.kyngchaos.com/software:postgres

   Then add psql and shp2pgsql to your $PATH variable in your shell profile.
   export PATH=/usr/local/pgsql-9.1/bin:$PATH

3. Convert the tz_world.shp file into SQL:

   ```
   cd world
   shp2pgsql tz_world.shp timezones > tz_world.sql
   ```

4. Create a temporary database and import the SQL file.

   ```
   psql -U postgres -c "CREATE DATABASE timezones" -d template1
   ```

   And import the PostGIS functions into the database.

   ```
   psql -U postgres -d timezones -f /usr/local/pgsql-9.1/share/contrib/postgis-2.0/postgis.sql

   psql -U postgres -d timezones < tz_world.sql
   ```

5. Export the data as text in a simplified format.

   ```
   psql -U postgres -d timezones -t -A -c "

      SELECT tzid, ST_AsText(ST_Simplify(ST_SnapToGrid(geom, 0.001), 0.3)) FROM timezones

      WHERE (ST_Area(geom) > 3 OR (gid IN (

      SELECT MAX(gid) FROM timezones WHERE ST_Area(geom) <= 3 AND tzid NOT IN (

      SELECT tzid FROM timezones WHERE ST_Area(geom) > 3

      ) group by tzid ORDER BY MAX(ST_AREA(geom))

      ))) AND tzid != 'uninhabited';

   " > tz_world.txt
   ```

   And a special export for Islands that are hard to select otherwise.

   ```
   psql -U postgres -d timezones -t -A -c "
      SELECT tzid, ST_Expand(ST_Extent(geom), GREATEST(3 - ST_Area(ST_Extent(geom)), 0)) FROM timezones

      WHERE ST_Area(geom) < 3 AND (tzid LIKE 'Pacific/%' OR tzid LIKE 'Indian/%' OR tzid LIKE 'Atlantic/%') GROUP BY tzid ORDER BY tzid;
   " > tz_islands.txt
   ```

## LICENSE

Copyright 2011-2013 Nathan Haug

Released under the MIT License.
