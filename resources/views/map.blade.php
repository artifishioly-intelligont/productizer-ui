@extends('layouts.master')

@section('body')

<div class="container">
    <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-8">
            <h3>Map</h3>
            <hr>
            <div id="map"></div>
        </div>
		<div id="toggleMode">
			<h4 style="display:inline-block" id="learnMode">Learn Mode</h4>
			<label style="display:inline-block" class="switch">
			  <input type="checkbox" id="toggle" onclick="toggleMode()">
			  <div class="slider round"></div>
			</label>
			<h4 style="display:inline-block" id="guessMode">Guess Mode</h4>
		</div>
		<hr>
		<div id="learnInterface" class="col-xs-12 col-sm-12 col-md-4">		
			<label for="themes">1)Select a theme:</label>
			<select name="themes" id="themes" onchange="updateSelectedTheme()">
			  <option value="pond">Pond</option>
			  <option value="tree">Tree</option>
			  <option value="road">Road</option>
			  <option value="roundabout">Roundabout</option>
			  <option value="building">Building</option>
			</select>
			<label for="map-selected-learn">2) On the map to the left, draw boxes around 5 features of your selected theme.</label>
            <hr>
            <div id="map-selected-learn"></div>
			<hr>

			<input type="hidden" id="selectedTheme" value="default"/>
			<input type="file" name="learn_img" id="learn_img" accept="image/*" multiple="multiple"/>

			<button onclick="sendLearnData()">LEARN</button>
        </div>
		<div id="guessInterface" class="col-xs-12 col-sm-12 col-md-4">
			<label for="map-selected-guess">On the map to the left, draw a box around a feature, and we will predict it's theme!</label>
            <hr>
            <div id="map-selected-guess"></div>
			<hr>

			<input type="hidden" id="guessUrl" value="default"/>
			<input type="file" name="guess_img" id="guess_img" accept="image/*" multiple="multiple"/>			

			<button onclick="sendGuessData()">GUESS</button>
		</div>
		
    </div>
</div>

@endsection

@section('scripts')
<script>

	var learnModeEnabled = true;
	var learnImgs = [];
	var guessUrl = "";

	function learnReqListener (evt) {
		alert(this.responseText);
	}

	function sendLearnData(){
		
		var themesDropdown = document.getElementById("themes");
		var files = document.getElementById("learn_img").files;		
		
		var data = new FormData();
		data.append("feature", themesDropdown.options[themesDropdown.selectedIndex].value);
		for(var i = 0; i < files.length; i++){
			data.append("learn_img_" + i.toString(), files[i]);
		}
		
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.addEventListener("load", learnReqListener);
		xmlHttp.open( "POST", "http://localhost:5000/learn");
		xmlHttp.send(data);
	}
	
	
	function guessReqListener (evt) {
		alert(this.responseText);
	}

	function sendGuessData(){
	
		var files = document.getElementById("guess_img").files;		
		
		var data = new FormData();
		for(var i = 0; i < files.length; i++){
			data.append("guess_img_" + i.toString(), files[i]);
		}
		
		var xmlHttp = new XMLHttpRequest();
		xmlHttp.addEventListener("load", learnReqListener);
		xmlHttp.open( "POST", "http://localhost:5000/guess");
		xmlHttp.send(data);
	}

	function updateSelectedTheme() {
		var themesDropdown = document.getElementById("themes");
		var selectedTheme = themesDropdown.options[themesDropdown.selectedIndex].value;
		document.getElementById("selectedTheme").value=selectedTheme;
	}
	updateSelectedTheme();
	
	function toggleMode() {
		
		var learnInterface = document.getElementById("learnInterface");
		var guessInterface = document.getElementById("guessInterface");     
	 
		var toggle = document.getElementById("toggle");
		if(toggle.checked){
			learnModeEnabled = false;
			
			learnInterface.style.display = 'none';
			guessInterface.style.display = 'block';
			
			document.getElementById("learnMode").style.color = "grey";
			document.getElementById("learnMode").style.fontWeight = "normal";
			
			document.getElementById("guessMode").style.color = "blue";
			document.getElementById("guessMode").style.fontWeight = "bold";
		}
		else{
			learnModeEnabled = true;
			
			guessInterface.style.display = 'none';
			learnInterface.style.display = 'block';
			
			document.getElementById("guessMode").style.color = "grey";
			document.getElementById("guessMode").style.fontWeight = "normal";
			
			document.getElementById("learnMode").style.color = "blue";
			document.getElementById("learnMode").style.fontWeight = "bold";
		}
	}
	toggleMode();

  function initMap() {

    var TILE_SIZE = 256;
	
    if(document.getElementById('map')) {
        var map = new google.maps.Map(document.getElementById('map'), {
          center: {lat:-89.6, lng: -0},
          zoom: 0,

          styles: [
            {elementType: 'geometry', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.stroke', stylers: [{color: '#242f3e'}]},
            {elementType: 'labels.text.fill', stylers: [{color: '#746855'}]},
          ],
          streetViewControl: false,
          mapTypeControlOptions: {
            mapTypeIds: ['os']
          }
        });
		
		//console.log("***********");
		//console.log("<?php echo serialize($map)?>");
		//console.log("***********");

        var osMapType = new google.maps.ImageMapType({
          getTileUrl: function(coord, zoom) {
              var normalizedCoord = getNormalizedCoord(coord, zoom);
              if (!normalizedCoord) {
                return null;
              }
              //var bound = Math.pow(2, zoom);
              var cols = 7 - 1;
              var rows = 4 - 1;
              if(normalizedCoord.x > cols || normalizedCoord.x < 0) {
                return null;
              }
              if(normalizedCoord.y - 1 > rows || normalizedCoord.y - 1 < 0) {
                return null;
              }
              var folder = (6 - 2 + (zoom - 2));
              folder = (folder < 0) ? 0 : folder;
              return '{{ url('/') }}' +
                  '/maps/{{ $map->id }}/actual/actual_files/' + folder + '/' + normalizedCoord.x + '_' +
                  (normalizedCoord.y - 1) + '.jpg';
          },
          tileSize: new google.maps.Size(TILE_SIZE, TILE_SIZE),
          maxZoom: 4,
          minZoom: 0,
          radius: 1,
          name: 'OS',

        });

        map.mapTypes.set('OS', osMapType);
        map.setMapTypeId('OS');

        var drawingManager = new google.maps.drawing.DrawingManager({
          //drawingMode: google.maps.drawing.OverlayType.MARKER,
          drawingControl: true,
          drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_LEFT,
            drawingModes: ['rectangle']
          },

          rectangleOptions: {
            fillColor: '#ffff00',
            fillOpacity: 0.2,
            strokeWeight: 1,
            clickable: false,
            editable: true,
            zIndex: 1
          }
        });
        drawingManager.setMap(map);
		
		var counter = 0;
		
        google.maps.event.addListener(drawingManager, 'rectanglecomplete', function (rectangle) {
            //var coordinates = (rectangle.getBounds().getArray());
		
		counter++;

        var scale = 1 << map.getZoom();

        var worldCoordinate = project(rectangle.getBounds().getNorthEast());

        var pixelCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale),
            Math.floor(worldCoordinate.y * scale));

        var tileCoordinate = new google.maps.Point(
            Math.floor(worldCoordinate.x * scale / TILE_SIZE),
            Math.floor(worldCoordinate.y * scale / TILE_SIZE));

        var worldCoordinate2 = project(rectangle.getBounds().getSouthWest());

        var pixelCoordinate2 = new google.maps.Point(
            Math.floor(worldCoordinate2.x * scale),
            Math.floor(worldCoordinate2.y * scale));

        var tileCoordinate2 = new google.maps.Point(
            Math.floor(worldCoordinate2.x * scale / TILE_SIZE),
            Math.floor(worldCoordinate2.y * scale / TILE_SIZE));

            console.log(tileCoordinate);
            var tileimg = '{{ url('/') }}' +
                  '/maps/{{ $map->id }}/actual/actual_files/12/' + tileCoordinate.x + '_' +
                  (tileCoordinate.y - 1) + '.jpg';

			if(learnModeEnabled){
				$('#map-selected-learn').append('<label for="learn+'+counter+'">'+counter+'</learn>');
				$('#map-selected-learn').append('<img id="learn'+counter+'" src="'+tileimg+'" height="75" width="75"/>'  + "<br /><p></p>");
				var img_name = "windmill.jpg"; //TODO - must be unique, and must be uploaded first
				//learnImgs.push(tileCoordinate.x + '_' + (tileCoordinate.y - 1) + '.jpg');
				learnImgs.push(img_name);
			}
			else{
				$('#map-selected-guess').append('<img id="guessImg" src="'+tileimg+'" height="75" width="75"/>'  + "<br /><p></p>");
				guessUrl = tileCoordinate.x + '_' + (tileCoordinate.y - 1) + '.jpg';
			}
        });

        google.maps.event.addListener(map, 'bounds_changed', function() {
            // Bounds for map calculated
            var strictBounds = new google.maps.LatLngBounds(
                map.getBounds().getSouthWest(),
                map.getBounds().getNorthEast()
            );
            // Listen for the dragend event
            google.maps.event.addListener(map, 'dragend', function() {
                if (strictBounds.contains(map.getCenter())) return;

             // We're out of bounds - Move the map back within the bounds

            var c = map.getCenter(),
                x = c.lng(),
                y = c.lat(),
                maxX = strictBounds.getNorthEast().lng(),
                maxY = strictBounds.getNorthEast().lat(),
                minX = strictBounds.getSouthWest().lng(),
                minY = strictBounds.getSouthWest().lat();

            if (x < minX) x = minX;
            if (x > maxX) x = maxX;
            if (y < minY) y = minY;
            if (y > maxY) y = maxY;

            //map.setCenter(new google.maps.LatLng(y, x));
        });
    });

    }

      // The mapping between latitude, longitude and pixels is defined by the web
      // mercator projection.
      function project(latLng) {
        var siny = Math.sin(latLng.lat() * Math.PI / 180);

        // Truncating to 0.9999 effectively limits latitude to 89.189. This is
        // about a third of a tile past the edge of the world tile.
        siny = Math.min(Math.max(siny, -0.9999), 0.9999);

        return new google.maps.Point(
            TILE_SIZE * (0.5 + latLng.lng() / 360),
            TILE_SIZE * (0.5 - Math.log((1 + siny) / (1 - siny)) / (4 * Math.PI)));
      }
  }

  // Normalizes the coords that tiles repeat across the x axis (horizontally)
  // like the standard Google map tiles.
  function getNormalizedCoord(coord, zoom) {
    var y = coord.y;
    var x = coord.x;

    // tile range in one direction range is dependent on zoom level
    // 0 = 1 tile, 1 = 2 tiles, 2 = 4 tiles, 3 = 8 tiles, etc
    var tileRange = 1 << zoom;

    // don't repeat across y-axis (vertically)
    if (y < 0) { // y >= tileRange) {
      return null;
    }
    var cols = 7;
    // DONT repeat across x-axis
    if (x < 0 || x >= cols) {//3 || x >= tileRange) {
        return null;
        x = (x % cols + cols) % cols;
    }

    return {x: x, y: y};
  }
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBUAtkOq17fvrO06CtpNZ8UjJFFWAsFhKY&callback=initMap&libraries=drawing">
</script>
@endsection