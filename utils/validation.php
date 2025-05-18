<?php
class ValidationUtil {
    public static $lastError = "";
    
    public static function validateRegistrationData($data) {
        if(empty($data->name)) {
            self::$lastError = "Name is required";
            return false;
        }
        
        if(empty($data->email) || !filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = "Valid email is required";
            return false;
        }
        
        if(empty($data->password) || strlen($data->password) < 8) {
            self::$lastError = "Password must be at least 8 characters";
            return false;
        }
        
        if(empty($data->gender) || !in_array($data->gender, ['male', 'female'])) {
            self::$lastError = "Valid gender is required";
            return false;
        }
        
        return true;
    }
    
    public static function validateCoordinates($lat, $lng) {
        if(!is_numeric($lat) || !is_numeric($lng)) {
            self::$lastError = "Coordinates must be numeric";
            return false;
        }
        
        if($lat < -90 || $lat > 90) {
            self::$lastError = "Latitude must be between -90 and 90";
            return false;
        }
        
        if($lng < -180 || $lng > 180) {
            self::$lastError = "Longitude must be between -180 and 180";
            return false;
        }
        
        return true;
    }
    
    public static function validateRadius($radius) {
        return is_numeric($radius) && $radius >= 1 && $radius <= 1000;
    }
}