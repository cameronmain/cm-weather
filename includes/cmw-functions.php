<?php

class CM_Weather {

    public function __construct() {
        add_shortcode('weather_card', [$this, 'weather_card_shortcode']);
    }

    public function weather_card_shortcode() {
        $location = get_option('cmw_location', 'Blyth');
        $api_key = get_option('cmw_api_key', '');
        if (!$api_key) {
            return '<div class="cmw-card">Weather API key is not set. Please configure it in the admin settings.</div>';
        }
        $current_url = 'http://api.weatherapi.com/v1/current.json?key=' . urlencode($api_key) . '&q=' . urlencode($location) . '&aqi=no';
        $forecast_url = 'http://api.weatherapi.com/v1/forecast.json?key=' . urlencode($api_key) . '&q=' . urlencode($location) . '&days=3&aqi=no&alerts=no';
        $data = $this->get_response($current_url);
        $forecast_data = $this->get_response($forecast_url);
        error_log('Raw weather API response: ' . $data);
        error_log('Raw forecast API response: ' . $forecast_data);
    
        if ($data && $forecast_data) {
            $weather = json_decode($data, true);
            $forecast = json_decode($forecast_data, true);
    
            if (!empty($weather['current']) && !empty($weather['location']) && !empty($forecast['forecast']['forecastday'])) {
                $temp = esc_html($weather['current']['temp_c']);
                $temp_f = esc_html($weather['current']['temp_f']);
                $feelslike = esc_html($weather['current']['feelslike_c']);
                $feelslike_f = esc_html($weather['current']['feelslike_f']);
                $city = esc_html($weather['location']['name']);
                $localtime = $weather['location']['localtime']; // format: "2025-05-05 11:41"
                $country = esc_html($weather['location']['country']);
                $condition = esc_html($weather['current']['condition']['text']);
                $icon = esc_url('https:' . $weather['current']['condition']['icon']);
                $wind_gust = esc_html($weather['current']['gust_kph']);
                $precip = esc_html($weather['current']['precip_mm']);
                $visibility = esc_html($weather['current']['vis_km']);
                $humidity = esc_html($weather['current']['humidity']);
                $pressure = esc_html($weather['current']['pressure_mb']);
                $wind_speed = esc_html($weather['current']['wind_kph']);
                $wind_dir = esc_html($weather['current']['wind_dir']);
                $uv = esc_html($weather['current']['uv']);

                $today = $forecast['forecast']['forecastday'][0];
                $min_temp = esc_html($today['day']['mintemp_c']);
                $max_temp = esc_html($today['day']['maxtemp_c']);
                $sunrise = esc_html($today['astro']['sunrise']);
                $sunset = esc_html($today['astro']['sunset']);

                // format date and time
                $datetime_parts = explode(' ', $localtime);
                $date = $datetime_parts[0]; // "2025-05-05"
                $time = $datetime_parts[1]; // "11:41"
                $timestamp = strtotime($date);
                $formatted_date = date('F j', $timestamp); // e.g. "May 5"

                // 3 day forecast
                $forecast_html = '';
                foreach ($forecast['forecast']['forecastday'] as $i => $day) {
                    $label = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('l', strtotime($day['date'])));
                    $icon_url = 'https:' . $day['day']['condition']['icon'];
                    $desc = esc_html($day['day']['condition']['text']);
                    $mint = esc_html($day['day']['mintemp_c']);
                    $maxt = esc_html($day['day']['maxtemp_c']);
                    $forecast_html .= '<div class="cmw-forecast-day">'
                        . '<div class="cmw-forecast-day-label">' . $label . '</div>'
                        . '<img class="cmw-forecast-icon" src="' . $icon_url . '" alt="' . $desc . '" />'
                        . '<div class="cmw-forecast-temp">' . $mint . '° / ' . $maxt . '°</div>'
                        . '</div>';
                }

                // Import extra CSS
                $css_url = plugins_url('cm-weather/assets/css/cmw-extended.css');
                wp_enqueue_style('cmw-extended', $css_url);

                // Build forecast HTML for both units
                $forecast_html_c = '';
                $forecast_html_f = '';
                foreach ($forecast['forecast']['forecastday'] as $i => $day) {
                    $label = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('l', strtotime($day['date'])));
                    $icon_url = 'https:' . $day['day']['condition']['icon'];
                    $desc = esc_html($day['day']['condition']['text']);
                    $mint = esc_html($day['day']['mintemp_c']);
                    $maxt = esc_html($day['day']['maxtemp_c']);
                    $mint_f = esc_html($day['day']['mintemp_f']);
                    $maxt_f = esc_html($day['day']['maxtemp_f']);
                    $forecast_html_c .= '<div class="cmw-forecast-day"><div class="cmw-forecast-day-label">' . $label . '</div><img class="cmw-forecast-icon" src="' . $icon_url . '" alt="' . $desc . '" /><div class="cmw-forecast-temp cmw-celsius">' . $mint . '° / ' . $maxt . '°</div><div class="cmw-forecast-temp cmw-fahrenheit" style="display:none">' . $mint_f . '° / ' . $maxt_f . '°</div></div>';
                }
                foreach ($forecast['forecast']['forecastday'] as $i => $day) {
                    $label = $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : date('l', strtotime($day['date'])));
                    $icon_url = 'https:' . $day['day']['condition']['icon'];
                    $desc = esc_html($day['day']['condition']['text']);
                    $mint_f = esc_html($day['day']['mintemp_f']);
                    $maxt_f = esc_html($day['day']['maxtemp_f']);
                    $forecast_html_f .= '<div class="cmw-forecast-day"><div class="cmw-forecast-day-label">' . $label . '</div><img class="cmw-forecast-icon" src="' . $icon_url . '" alt="' . $desc . '" /><div class="cmw-forecast-temp">' . $mint_f . '° / ' . $maxt_f . '°</div></div>';
                }

                // import extra CSS
                $css_url = plugins_url('cm-weather/assets/css/cmw-extended.css');
                wp_enqueue_style('cmw-extended', $css_url);

                // inline JS for unit toggle
                $toggle_js = "<script>document.addEventListener('DOMContentLoaded',function(){\n  var card = document.querySelector('.cmw-card');\n  var cUnit=document.querySelector('.cmw-unit-c');\n  var fUnit=document.querySelector('.cmw-unit-f');\n  if(!cUnit||!fUnit)return;\n  var defaultUnit = card ? card.getAttribute('data-default-unit') : 'C';\n  if(defaultUnit==='F'){\n    document.querySelectorAll('.cmw-celsius').forEach(function(e){e.style.display='none';});\n    document.querySelectorAll('.cmw-fahrenheit').forEach(function(e){e.style.display='';});\n  }\n  function showF(){\n    document.querySelectorAll('.cmw-celsius').forEach(function(e){e.style.display='none';});\n    document.querySelectorAll('.cmw-fahrenheit').forEach(function(e){e.style.display='';});\n  }\n  function showC(){\n    document.querySelectorAll('.cmw-celsius').forEach(function(e){e.style.display='';});\n    document.querySelectorAll('.cmw-fahrenheit').forEach(function(e){e.style.display='none';});\n  }\n  cUnit.addEventListener('click',showF);\n  fUnit.addEventListener('click',showC);\n});</script>";

                $default_unit = get_option('cmw_temperature_unit', 'C');
                return '
                    <div class="cmw-card" data-default-unit="' . esc_attr($default_unit) . '">
                        <div class="card-header">
                            <div class="card-top-row">
                                <span class="card-time">' . $time . '</span>
                                <span class="card-date">' . $formatted_date . '</span>
                            </div>
                            <span class="card-location">' . $city . '</span>
                        </div>
                        <div class="card-temp">
                            <img src="' . $icon . '" alt="' . $condition . '" />
                            <span class="temp-val cmw-celsius"><span class="cmw-temp-num">' . round($temp) . '</span><span class="cmw-unit cmw-unit-c" style="cursor:pointer;font-weight:bold;">°C</span></span>
                            <span class="temp-val cmw-fahrenheit" style="display:none"><span class="cmw-temp-num">' . round($temp_f) . '</span><span class="cmw-unit cmw-unit-f" style="cursor:pointer;font-weight:bold;">°F</span></span>
                            <span class="temp-range cmw-celsius" style="display:block;line-height:1.2;">
                                <span class="temp-low">L: ' . round($min_temp) . '°</span><br/>
                                <span class="temp-high">H: ' . round($max_temp) . '°</span>
                            </span>
                            <span class="temp-range cmw-fahrenheit" style="display:none;line-height:1.2;">
                                <span class="temp-low">L: ' . round($today['day']['mintemp_f']) . '°</span><br/>
                                <span class="temp-high">H: ' . round($today['day']['maxtemp_f']) . '°</span>
                            </span>
                        </div>
                        <div class="cmw-extra-info">
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Feels Like</span> <span class="cmw-extra-info-value cmw-celsius">' . round($feelslike) . '°C</span><span class="cmw-extra-info-value cmw-fahrenheit" style="display:none">' . round($feelslike_f) . '°F</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">' . $condition . '</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Wind Gust</span> <span class="cmw-extra-info-value">' . $wind_gust . ' km/h</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Precipitation</span> <span class="cmw-extra-info-value">' . $precip . ' mm</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Visibility</span> <span class="cmw-extra-info-value">' . $visibility . ' km</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Humidity</span> <span class="cmw-extra-info-value">' . $humidity . ' %</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Pressure</span> <span class="cmw-extra-info-value">' . $pressure . ' hPa</span></div>
                            <div class="cmw-extra-info-row"><span class="cmw-extra-info-label">Wind</span> <span class="cmw-extra-info-value">' . $wind_speed . ' km/h ' . $wind_dir . '</span></div>
                        </div>
                        <div class="cmw-sun-row">
                            <div><span class="cmw-sun-label">Sunrise</span><br><span class="cmw-sun-time">' . $sunrise . '</span></div>
                            <div><span class="cmw-sun-label">Sunset</span><br><span class="cmw-sun-time">' . $sunset . '</span></div>
                        </div>
                        <div class="cmw-forecast">
                            <div class="cmw-forecast-title">Daily Forecast</div>
                            <div class="cmw-forecast-row">' . $forecast_html_c . '</div>
                        </div>
                    </div>' . $toggle_js;

            }
        }
    
        return '<div class="cmw-card">Weather data unavailable</div>';
    }
    

    private function get_response($url) {
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            $response = @file_get_contents($url);
            if ($response === false) {
                $response = $this->curl($url);
            }
        } elseif (is_array($response) && isset($response['body'])) {
            $response = $response['body'];
        }

        return $response ?: null;
    }

    private function curl($url) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => false,
            CURLOPT_USERAGENT      => 'CMWeatherPlugin/1.0',
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $error = curl_errno($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $code !== 200) {
            return null;
        }

        return $response;
    }
}

