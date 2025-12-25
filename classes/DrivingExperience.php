<?php
/**
 * DrivingExperience Class
 * Represents a single driving experience with all its properties
 */
class DrivingExperience {
    // Properties
    private $id;
    private $experienceDate;
    private $startTime;
    private $endTime;
    private $distance_km;
    private $startLocation;
    private $endLocation;
    private $vehicleTypeId;
    private $timeOfDayId;
    private $surfaceId;
    private $roadDensityId;
    private $roadTypeId;
    private $weatherId;
    private $notes;
    private $createdAt;
    
    /**
     * Constructor
     */
    public function __construct($data = []) {
        if (!empty($data)) {
            $this->hydrate($data);
        }
    }
    
    /**
     * Hydrate object from array
     */
    public function hydrate($data) {
        foreach ($data as $key => $value) {
            $method = 'set' . str_replace('_', '', ucwords($key, '_'));
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
    
    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }
    
    public function getExperienceDate() { return $this->experienceDate; }
    public function setExperienceDate($date) { $this->experienceDate = $date; }
    
    public function getStartTime() { return $this->startTime; }
    public function setStartTime($time) { $this->startTime = $time; }
    
    public function getEndTime() { return $this->endTime; }
    public function setEndTime($time) { $this->endTime = $time; }
    
    public function getKilometers() { return $this->distance_km; }
    public function setKilometers($km) { $this->distance_km = $km; }
    
    public function getStartLocation() { return $this->startLocation; }
    public function setStartLocation($location) { $this->startLocation = $location; }
    
    public function getEndLocation() { return $this->endLocation; }
    public function setEndLocation($location) { $this->endLocation = $location; }
    
    public function getVehicleTypeId() { return $this->vehicleTypeId; }
    public function setVehicleTypeId($id) { $this->vehicleTypeId = $id; }
    
    public function getTimeOfDayId() { return $this->timeOfDayId; }
    public function setTimeOfDayId($id) { $this->timeOfDayId = $id; }
    
    public function getSurfaceId() { return $this->surfaceId; }
    public function setSurfaceId($id) { $this->surfaceId = $id; }
    
    public function getRoadDensityId() { return $this->roadDensityId; }
    public function setRoadDensityId($id) { $this->roadDensityId = $id; }
    
    public function getRoadTypeId() { return $this->roadTypeId; }
    public function setRoadTypeId($id) { $this->roadTypeId = $id; }
    
    public function getWeatherId() { return $this->weatherId; }
    public function setWeatherId($id) { $this->weatherId = $id; }
    
    public function getNotes() { return $this->notes; }
    public function setNotes($notes) { $this->notes = $notes; }
    
    public function getCreatedAt() { return $this->createdAt; }
    public function setCreatedAt($date) { $this->createdAt = $date; }
    
    /**
     * Calculate duration in minutes
     */
    public function getDuration() {
        if (!$this->startTime || !$this->endTime) {
            return 0;
        }
        $start = strtotime($this->startTime);
        $end = strtotime($this->endTime);
        if ($end < $start) {
            $end += 86400; // Add 24 hours for overnight trips
        }
        return ($end - $start) / 60;
    }
    
    /**
     * Format date for display
     */
    public function getFormattedDate() {
        return date('M d, Y', strtotime($this->experienceDate));
    }
    
    /**
     * Validate the experience data
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->experienceDate)) {
            $errors[] = "Experience date is required";
        }
        if (empty($this->startTime)) {
            $errors[] = "Start time is required";
        }
        if (empty($this->endTime)) {
            $errors[] = "End time is required";
        }
        if (empty($this->distance_km) || $this->distance_km <= 0) {
            $errors[] = "Kilometers must be greater than 0";
        }
        if ($this->vehicleTypeId <= 0) {
            $errors[] = "Vehicle type is required";
        }
        if ($this->timeOfDayId <= 0) {
            $errors[] = "Time of day is required";
        }
        if ($this->weatherId <= 0) {
            $errors[] = "Weather condition is required";
        }
        if ($this->roadTypeId <= 0) {
            $errors[] = "Road type is required";
        }
        if ($this->surfaceId <= 0) {
            $errors[] = "Road surface is required";
        }
        if ($this->roadDensityId <= 0) {
            $errors[] = "Traffic density is required";
        }
        
        return $errors;
    }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'experience_date' => $this->experienceDate,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'distance_km' => $this->distance_km,
            'start_location' => $this->startLocation,
            'end_location' => $this->endLocation,
            'vehicle_type_id' => $this->vehicleTypeId,
            'time_of_day_id' => $this->timeOfDayId,
            'surface_id' => $this->surfaceId,
            'road_density_id' => $this->roadDensityId,
            'road_type_id' => $this->roadTypeId,
            'weather_id' => $this->weatherId,
            'notes' => $this->notes,
            'created_at' => $this->createdAt
        ];
    }
}
?>
