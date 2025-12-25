<?php
session_start();
require_once 'config/database.php';
require_once 'classes/DrivingExperience.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add-experience.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get current user ID
$userId = getCurrentUserId();

// Convert 12-hour time to 24-hour format
function convertTo24Hour($hour, $minute, $period) {
    $hour = intval($hour);
    $minute = intval($minute);
    
    if ($minute < 0 || $minute > 59) {
        return null;
    }
    
    if ($period === 'AM') {
        if ($hour === 12) $hour = 0;
    } else {
        if ($hour !== 12) $hour += 12;
    }
    
    return sprintf('%02d:%02d:00', $hour, $minute);
}

// Create DrivingExperience object
$experience = new DrivingExperience();

// Set properties from POST data
$experience->setExperienceDate(trim($_POST['experience_date'] ?? ''));

// Build time from dropdowns
$startHour = trim($_POST['start_hour'] ?? '');
$startMinute = intval(trim($_POST['start_minute'] ?? '0'));
$startPeriod = trim($_POST['start_period'] ?? '');
$startTime = '';
if ($startHour && $startPeriod) {
    $startTime = convertTo24Hour($startHour, $startMinute, $startPeriod);
}
$experience->setStartTime($startTime);

$endHour = trim($_POST['end_hour'] ?? '');
$endMinute = intval(trim($_POST['end_minute'] ?? '0'));
$endPeriod = trim($_POST['end_period'] ?? '');
$endTime = '';
if ($endHour && $endPeriod) {
    $endTime = convertTo24Hour($endHour, $endMinute, $endPeriod);
}
$experience->setEndTime($endTime);

$experience->setKilometers(trim($_POST['distance_km'] ?? ''));
$experience->setVehicleTypeId(intval($_POST['vehicle_type_id'] ?? 0));
$experience->setTimeOfDayId(intval($_POST['time_of_day_id'] ?? 0));
$experience->setWeatherId(intval($_POST['weather_id'] ?? 0));
$experience->setRoadTypeId(intval($_POST['road_type_id'] ?? 0));
$experience->setSurfaceId(intval($_POST['surface_id'] ?? 0));
$experience->setRoadDensityId(intval($_POST['road_density_id'] ?? 0));
$experience->setStartLocation(trim($_POST['start_location'] ?? ''));
$experience->setEndLocation(trim($_POST['end_location'] ?? ''));
$experience->setNotes(trim($_POST['notes'] ?? ''));

// Validate using OOP method
$errors = $experience->validate();

// Additional validation for time
if (!$experience->getStartTime()) {
    $errors[] = "Invalid start time";
}
if (!$experience->getEndTime()) {
    $errors[] = "Invalid end time";
}

// Duration validation
$duration = $experience->getDuration();
if ($duration > 1440) {
    $errors[] = "Driving duration cannot exceed 24 hours";
}

if (!empty($errors)) {
    $_SESSION['message'] = implode(', ', $errors);
    $_SESSION['message_type'] = 'error';
    header('Location: add-experience.php');
    exit;
}

// Prepare PDO statement with named parameters
$sql = "INSERT INTO driving_experiences 
        (user_id, date, start_time, end_time, distance_km, start_location, end_location,
         vehicle_type_id, time_of_day_id, surface_id, road_density_id, road_type_id, weather_id, notes)
        VALUES (:user_id, :date, :start_time, :end_time, :distance_km, :start_location, :end_location,
                :vehicle_type_id, :time_of_day_id, :surface_id, :road_density_id, :road_type_id, :weather_id, :notes)";

try {
    $stmt = $conn->prepare($sql);
    
    // Bind user_id first
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    
    // Bind parameters using bindValue
    $stmt->bindValue(':date', $experience->getExperienceDate(), PDO::PARAM_STR);
    $stmt->bindValue(':start_time', $experience->getStartTime(), PDO::PARAM_STR);
    $stmt->bindValue(':end_time', $experience->getEndTime(), PDO::PARAM_STR);
    $stmt->bindValue(':distance_km', $experience->getKilometers(), PDO::PARAM_STR);
    $stmt->bindValue(':start_location', $experience->getStartLocation(), PDO::PARAM_STR);
    $stmt->bindValue(':end_location', $experience->getEndLocation(), PDO::PARAM_STR);
    $stmt->bindValue(':vehicle_type_id', $experience->getVehicleTypeId(), PDO::PARAM_INT);
    $stmt->bindValue(':time_of_day_id', $experience->getTimeOfDayId(), PDO::PARAM_INT);
    $stmt->bindValue(':surface_id', $experience->getSurfaceId(), PDO::PARAM_INT);
    $stmt->bindValue(':road_density_id', $experience->getRoadDensityId(), PDO::PARAM_INT);
    $stmt->bindValue(':road_type_id', $experience->getRoadTypeId(), PDO::PARAM_INT);
    $stmt->bindValue(':weather_id', $experience->getWeatherId(), PDO::PARAM_INT);
    $stmt->bindValue(':notes', $experience->getNotes(), PDO::PARAM_STR);
    
    // Execute
    if ($stmt->execute()) {
        $_SESSION['message'] = "Driving experience added successfully! Total distance_km: " . number_format($experience->getKilometers(), 2);
        $_SESSION['message_type'] = 'success';
        header('Location: view-experiences.php');
    } else {
        throw new Exception("Failed to insert record");
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Error adding experience: " . $e->getMessage();
    $_SESSION['message_type'] = 'error';
    header('Location: add-experience.php');
}
exit;
?>
