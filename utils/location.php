<?php
class LocationUtil {
    // Nominatim configuration
    private static $nominatimEndpoint = "https://nominatim.openstreetmap.org/search";
    private static $userAgent = "TkshaDatingApp/1.0";
    
    /**
     * Get coordinates from address using OpenStreetMap Nominatim
     * @param string $address The address to geocode
     * @return array|false Array with 'lat' and 'lng' or false on failure
     */
    public static function getCoordinatesFromAddress($address) {
        if (empty($address)) {
            return false;
        }

        $params = [
            'q' => urlencode($address),
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1
        ];
        
        $url = self::$nominatimEndpoint . '?' . http_build_query($params);
        
        $options = [
            'http' => [
                'header' => "User-Agent: " . self::$userAgent . "\r\n",
                'timeout' => 5 // 5 second timeout
            ]
        ];
        
        try {
            $context = stream_context_create($options);
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                error_log("Nominatim API request failed for address: $address");
                return false;
            }
            
            $data = json_decode($response, true);
            
            if (empty($data) || !isset($data[0]['lat'], $data[0]['lon'])) {
                error_log("No valid coordinates found for address: $address");
                return false;
            }
            
            $coordinates = [
                'lat' => (float)$data[0]['lat'],
                'lng' => (float)$data[0]['lon']
            ];
            
            // Validate the coordinates before returning
            if (!self::validateCoordinates($coordinates['lat'], $coordinates['lng'])) {
                error_log("Nominatim returned invalid coordinates for address: $address");
                return false;
            }
            
            return $coordinates;
        } catch (Exception $e) {
            error_log("Geocoding error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate geographic coordinates
     * @param float $lat Latitude
     * @param float $lng Longitude
     * @return bool True if valid
     */
    public static function validateCoordinates($lat, $lng) {
        return is_numeric($lat) && is_numeric($lng) && 
               $lat >= -90 && $lat <= 90 && 
               $lng >= -180 && $lng <= 180;
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        // Validate all coordinates first
        if (!self::validateCoordinates($lat1, $lon1) || 
            !self::validateCoordinates($lat2, $lon2)) {
            throw new InvalidArgumentException("Invalid coordinates provided");
        }
        
        $earthRadius = 6371; // Radius of the earth in km
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLon/2) * sin($dLon/2);
             
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        
        return $distance;
    }
    
    /**
     * Check if a location is within a given radius of another location
     * @param float $centerLat Center point latitude
     * @param float $centerLng Center point longitude
     * @param float $pointLat Point to check latitude
     * @param float $pointLng Point to check longitude
     * @param float $radiusKm Radius in kilometers
     * @return bool True if within radius
     */
    public static function isWithinRadius($centerLat, $centerLng, $pointLat, $pointLng, $radiusKm) {
        $distance = self::calculateDistance($centerLat, $centerLng, $pointLat, $pointLng);
        return $distance <= $radiusKm;
    }
}