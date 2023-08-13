<?php
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

function my_theme_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
}

// Read CSV file and store data in an array
$csvFile = dirname(__FILE__) . '/WebsiteSpreadsheet.csv';
$csvData = array_map('str_getcsv', file($csvFile));
$variables = array();  // Initialize the $variables array, though you haven't used it yet.
global $altTexts;
$altTexts = array();


if (($handle = fopen($csvFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $key = $data[0];
        $fullText = $data[3];  // Getting the Full Text column

        // Store only if the key contains the word 'Alt'
        if (strpos($key, 'Alt') !== false) {
            $altTexts[$key] = $fullText;
        }
    }
    fclose($handle);
}


// Convert CSV data to associative array
foreach ($csvData as $row) {
    $key = str_replace(' ', '', ucwords(strtolower($row[0]))); // Convert key to variable name format
    $variables[$key] = isset($row[3]) ? $row[3] : '';  // default value is an empty string
}

$cityNames = array("Westchase", "Wesley-Chapel", "Lutz", "Trinity", "Brandon", "Valrico", 
                   "Apollo-Beach", "Sun-City-Center", "Sun-City",  "Keystone", "Odessa", 
                   "St-Petersburg", "Clearwater", "Dunedin", "Zephyrhills", "Land-o-Lakes", 
                   "Palm-Harbor", "Largo", "Oldsmar", "Safety-Harbor", "Pinellas-Park", 
                   "New-Port-Richey", "Tarpon-Springs", "Holiday", "Bloomingdale", "Fish-Hawk", 
                   "East-Lake", "The-Eagles", "Town-N-Country", "Tierra-Verde",  
                   "Belleair-Bluffs", "Belleair-Shores", "Indian-Rocks", "Indian-Shores", 
                   "Redington-Shores", "Mandeira-Beach", "Belleair", "Bay-Pines", "Kenneth-City", "Gulfport", 
                   "South-Pasadena", "Carrollwood", "Lake-Magdalene", "Cheval", "Avila", 
                   "Tampa-Palms", "Hunters-Green", "Lexington-Oaks", "Cory-Lake-Isles", "Pebble-Creek", 
                   "Northdale", "Lealman", "Seminole", "Town-N-Country", "Citrus-Park", "Thonotosassa", 
                   "Lithia", "Turkey-Creek", "Durant", "Hopewell", "Seven-Springs", "San-Antonio", 
                   "Dade-City", "Tampa");

foreach ($cityNames as $cityName) {
    if (strpos($cityName, '-') !== false) { // If city name contains a dash
        $newCityName = str_replace('-', '.', $cityName); // Replace dash with dot
        $cityNames[] = $newCityName; // Add new city name to array
    }
}

$cities = array();

foreach ($cityNames as $city) {
    $cities[strtolower($city)] = "$city";
}

//Functions that render variables declared to the website.
function display_variable_shortcode($atts) {
    global $variables, $cities;
    
    $variableName = str_replace(' ', '', ucwords(strtolower($atts['variable']))); // Convert attribute to variable name format

    // If the variable is 'Links', run the display_links functionality
    if ($variableName === 'Links') {
        // Get current URL
        $currentUrl = $_SERVER['REQUEST_URI'];

        // Array of service types
        $services = array("kitchen-cabinets", "cabinet-refacing", "cabinet-doors");

        // Identify the current service type and location from the URL
        $currentService = "";
        $location = "";
        
        foreach ($services as $service) {
            if (strpos($currentUrl, $service . "-in-") !== false) {
                $currentService = $service;
                $location = substr($currentUrl, strpos($currentUrl, $service . "-in-") + strlen($service . "-in-"));
                break;
            }
        }

        // Check if we successfully identified the service type and location
        if ($currentService == "" || $location == "") {
            return "";
        }

        // Identify the other two services
        $otherServices = array_diff($services, array($currentService));

        // Create the response string
        $response = "Our services also include ";
        $i = 0;

        foreach ($otherServices as $otherService) {
            // Convert the service type to title case for the link text
            $linkText = ucwords(str_replace("-", " ", $otherService));

            // Create the URL for the link
            $url = "https://www.re-a-door-cabinets.com/" . $otherService . "-in-" . $location;

            // Add the link to the response string
            $response .= "<a href=\"" . $url . "\" target=\"_blank\" rel=\"noreferrer noopener\">" . $linkText . "</a>";

            // Add " and " after the first link, without trailing space.
            if ($i == 0) {
                $response .= " and ";
            }

            $i++;
        }

        $response .= " design and installation in your location.";

        return $response;
    } else {
        // For other variables, proceed as usual
        // Check if the variable exists
        if (isset($variables[$variableName])) {
            $variableValue = $variables[$variableName];
            
            // Search for '(Location)' in the variable value
            if (strpos($variableValue, '(Location)') !== false) {
                // Get current URL
                $currentUrl = $_SERVER['REQUEST_URI'];
                $location = "Tampa Bay"; // Default value

                // Check each city name in the global cities array
                foreach ($cities as $city => $value) {
                    // If city name is found in the URL, update the location string
                    if (stripos($currentUrl, $city) !== false) {
                        // Replace dash with space in the city name
                        $value = str_replace('-', ' ', $value);
                        // Replace dot with space in the city name
                        $value = str_replace('.', ' ', $value);
                        $location = $value;
                        break; // Once we found a city in the URL, we can break the loop
                    }
                }

                // Replace '(Location)' in the variable value with the location string
                $variableValue = str_replace('(Location)', $location, $variableValue);
            }

            // Return the variable value
            return $variableValue;
        }

        // Return empty if the provided variable is not found
        return '';
    }
}


//function that displays yext information on the website
function display_yext_shortcode($atts) {
    global $variables;

    // Get attribute (note: this should be in the format 'Service in Location')
    $serviceInLocation = $atts['serviceinlocation'];

    // Convert attribute to variable name format
    $variableName = str_replace(' ', '', ucwords(strtolower($serviceInLocation)));

    // Return variable value if it exists, else return empty string
    return isset($variables[$variableName]) ? $variables[$variableName] : '';
}

//function that handles image creation and alt text modifications

function custom_image_shortcode($atts) {
    $defaultAltText = 'Modern high quality Kitchen Cabinet, Cabinet Doors, and Cabinet refacing done in Florida'; 
    global $altTexts, $cities;

    // Extracting attributes from the shortcode
    $atts = shortcode_atts(array(
        'src' => '',
        'type' => '', 
        'alttext' => '1' 
    ), $atts, 'custom_image');

    $currentUrl = $_SERVER['REQUEST_URI'];

    // Default location
    $location = "Tampa";

    // Extract location from the URL if possible
    foreach ($cities as $city => $value) {
        if (stripos($currentUrl, $city) !== false) {
            $location = str_replace(array('-', '.'), ' ', $value);
            break;
        }
    }

    // Construct the variable name based on type and alttext number
    $typeCamelCase = implode("", array_map('ucfirst', explode('-', $atts['type'])));
    $altVariableName = $typeCamelCase . 'AltText' . $atts['alttext'];

    // If the constructed variable name exists in the $altTexts array, fetch its value
    if (isset($altTexts[$altVariableName])) {
        $altText = str_replace('(Location)', $location, $altTexts[$altVariableName]);
    } else {
        $altText = $defaultAltText;
    }

    return '<img src="' . esc_url($atts['src']) . '" alt="' . esc_attr($altText) . '" class="wp-image-677"/>';
}




add_shortcode('custom_image', 'custom_image_shortcode');
add_shortcode('display_variable', 'display_variable_shortcode');
add_shortcode('display_yext', 'display_yext_shortcode');
