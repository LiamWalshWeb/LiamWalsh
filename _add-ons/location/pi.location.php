<?php
/**
 * Plugin_location
 * Display helper for location-based entries
 *
 * @author  Jack McDade <jack@statamic.com>
 * @author  Mubashar Iqbal <mubs@statamic.com>
 * @author  Fred LeBlanc <fred@statamic.com>
 *
 * @copyright  2012
 * @link       http://statamic.com/docs/
 * @license    http://statamic.com/license-agreement
 */
class Plugin_location extends Plugin {

    var $meta = array(
        'name'       => 'Location',
        'version'    => '1.0',
        'author'     => 'Fred LeBlanc',
        'author_url' => 'http://statamic.com'
    );


    /**
     * Initialized maps by adding dependencies to the screen
     *
     * @return string
     */
    public function start_maps() {
        $add_on_path = Path::tidy(Config::getSiteRoot() . Config::getAddOnPath("location"));

        return '
            <!-- leaflet maps -->
            <link rel="stylesheet" href="' . $add_on_path . '/css/leaflet.css" />
            <!--[if lte IE 8]>
                <link rel="stylesheet" href="' . $add_on_path . '/css/leaflet.ie.css" />
            <![endif]-->
            <link rel="stylesheet" href="' . $add_on_path . '/css/override.css" />
            <script type="text/javascript" src="' . $add_on_path . '/js/leaflet.js"></script>
            <script>
                try {
                    if (typeof _location_maps !== "object") {
                        throw ("Out.");
                    }

                    for (var id in _location_maps) {
                        if (!_location_maps.hasOwnProperty(id)) {
                            continue;
                        }

                        try {
                            _location_maps_maps.length;
                        } catch(e) {
                            var _location_maps_maps = {}
                        }

                        _location_maps_maps[id] = L.map(id).setView([_location_maps[id].starting_latitude, _location_maps[id].starting_longitude], _location_maps[id].starting_zoom);

                        L.tileLayer("http://{s}.tile.cloudmade.com/' . $this->fetch('cloudmade_api_key') . '/' . $this->fetch('cloudmade_tile_set_id') . '/256/{z}/{x}/{y}.png", {
                            attribute: "",
                            maxZoom: ' . $this->fetch('max_zoom') . '
                        }).addTo(_location_maps_maps[id]);

                        // markers
                        if (_location_maps[id].markers.length) {
                            // use cluster markers
                            if (_location_maps[id].clusters) {
                                var _marker_clusters = new L.MarkerClusterGroup({
                                    spiderfy_on_max_zoom: _location_maps[id].spiderfy_on_max_zoom,
                                    show_coverage_on_hover: _location_maps[id].show_coverage_on_hover,
                                    zoom_to_bounds_on_click: _location_maps[id].zoom_to_bounds_on_click,
                                    single_marker_mode: _location_maps[id].single_marker_mode,
                                    animate_adding_markers: _location_maps[id].animate_adding_markers,
                                    disable_clustering_at_zoom: _location_maps[id].disable_clustering_at_zoom,
                                    max_cluster_radius: _location_maps[id].max_cluster_radius
                                });

                                for (var i = 0; i < _location_maps[id].markers.length; i++) {
                                    var _marker_data  = _location_maps[id].markers[i],
                                        _local_marker = new L.marker([_marker_data.latitude, _marker_data.longitude]);

                                    if (_marker_data.marker_content) {
                                        _local_marker.bindPopup(_marker_data.marker_content);
                                    }

                                    _marker_clusters.addLayer(_local_marker);
                                }
                                _location_maps_maps[id].addLayer(_marker_clusters);

                            // use regular markers
                            } else {
                                var _local_marker;
                                for (var i = 0; i < _location_maps[id].markers.length; i++) {
                                    var _marker_data = _location_maps[id].markers[i];
                                    _local_marker = L.marker([_marker_data.latitude, _marker_data.longitude]).addTo(_location_maps_maps[id]);

                                    if (_marker_data.marker_content) {
                                        _local_marker.bindPopup(_marker_data.marker_content);
                                    }
                                }
                            }
                        }
                    }
                } catch(e) {}
            </script>
        ';
    }
}