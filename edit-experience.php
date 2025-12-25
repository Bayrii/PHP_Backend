<?php
session_start();
require_once 'config/database.php';
require_once 'includes/anonymize.php';
require_once 'includes/auth.php';

// Require login
requireLogin();
$userId = getCurrentUserId();

// Check if ID is provided (support both direct ID and anonymized code)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Direct ID approach
    $id = intval($_GET['id']);
} elseif (isset($_GET['code']) && !empty($_GET['code'])) {
    // Anonymized code approach (fallback)
    $id = deanonymizeCode($_GET['code']);
    if ($id === null) {
        $_SESSION['message'] = "Invalid or expired experience code";
        $_SESSION['message_type'] = 'error';
        header('Location: view-experiences.php');
        exit;
    }
} else {
    $_SESSION['message'] = "No experience ID provided";
    $_SESSION['message_type'] = 'error';
    header('Location: view-experiences.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and validate form data
    $experience_date = trim($_POST['experience_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $distance_km = trim($_POST['distance_km'] ?? '');
    $vehicle_type_id = intval($_POST['vehicle_type_id'] ?? 0);
    $time_of_day_id = intval($_POST['time_of_day_id'] ?? 0);
    $weather_id = intval($_POST['weather_id'] ?? 0);
    $road_type_id = intval($_POST['road_type_id'] ?? 0);
    $surface_id = intval($_POST['surface_id'] ?? 0);
    $road_density_id = intval($_POST['road_density_id'] ?? 0);
    $start_location = trim($_POST['start_location'] ?? '');
    $end_location = trim($_POST['end_location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($experience_date)) $errors[] = "Experience date is required";
    if (empty($start_time)) $errors[] = "Start time is required";
    if (empty($end_time)) $errors[] = "End time is required";
    if ($start_time && $end_time) {
        // Parse times
        list($start_hour, $start_min) = explode(':', $start_time);
        list($end_hour, $end_min) = explode(':', $end_time);
        
        $start_minutes = ($start_hour * 60) + $start_min;
        $end_minutes = ($end_hour * 60) + $end_min;
        
        // Check if same time
        if ($start_minutes === $end_minutes) {
            $errors[] = "End time must be different from start time";
        }
        
        // Calculate duration (allow midnight crossing)
        $duration = $end_minutes - $start_minutes;
        if ($duration < 0) {
            $duration += (24 * 60); // Add 24 hours if next day
        }
        
        // Don't allow sessions longer than 24 hours (sanity check only)
        if ($duration > (24 * 60)) {
            $errors[] = "Driving session cannot exceed 24 hours. Please check your times.";
        }
    }
    if (empty($distance_km) || floatval($distance_km) <= 0) {
        $errors[] = "Valid distance_km traveled is required";
    }
    if ($vehicle_type_id <= 0) $errors[] = "Vehicle type is required";
    if ($time_of_day_id <= 0) $errors[] = "Time of day is required";
    if ($weather_id <= 0) $errors[] = "Weather condition is required";
    if ($road_type_id <= 0) $errors[] = "Road type is required";
    if ($surface_id <= 0) $errors[] = "Road surface is required";
    if ($road_density_id <= 0) $errors[] = "Traffic density is required";
    
    if (!empty($errors)) {
        $_SESSION['message'] = implode(', ', $errors);
        $_SESSION['message_type'] = 'error';
    } else {
        // Update database with user_id verification for security
        $sql = "UPDATE driving_experiences 
                SET date = :date, start_time = :start_time, 
                    end_time = :end_time, distance_km = :distance_km,
                    start_location = :start_location, end_location = :end_location, 
                    vehicle_type_id = :vehicle_type_id, time_of_day_id = :time_of_day_id, 
                    surface_id = :surface_id, road_density_id = :road_density_id, 
                    road_type_id = :road_type_id, weather_id = :weather_id, notes = :notes
                WHERE id = :id AND user_id = :user_id";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':date', $experience_date, PDO::PARAM_STR);
            $stmt->bindValue(':start_time', $start_time, PDO::PARAM_STR);
            $stmt->bindValue(':end_time', $end_time, PDO::PARAM_STR);
            $stmt->bindValue(':distance_km', $distance_km, PDO::PARAM_STR);
            $stmt->bindValue(':start_location', $start_location, PDO::PARAM_STR);
            $stmt->bindValue(':end_location', $end_location, PDO::PARAM_STR);
            $stmt->bindValue(':vehicle_type_id', $vehicle_type_id, PDO::PARAM_INT);
            $stmt->bindValue(':time_of_day_id', $time_of_day_id, PDO::PARAM_INT);
            $stmt->bindValue(':surface_id', $surface_id, PDO::PARAM_INT);
            $stmt->bindValue(':road_density_id', $road_density_id, PDO::PARAM_INT);
            $stmt->bindValue(':road_type_id', $road_type_id, PDO::PARAM_INT);
            $stmt->bindValue(':weather_id', $weather_id, PDO::PARAM_INT);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    $_SESSION['message'] = "Experience updated successfully!";
                    $_SESSION['message_type'] = 'success';
                    header('Location: view-experiences.php');
                    exit;
                } else {
                    $_SESSION['message'] = "You don't have permission to edit this experience";
                    $_SESSION['message_type'] = 'error';
                    header('Location: view-experiences.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error updating experience: " . $e->getMessage();
            $_SESSION['message_type'] = 'error';
        }
    }
}

// Fetch existing experience data with ownership verification
$stmt = $conn->prepare("SELECT * FROM driving_experiences WHERE id = :id AND user_id = :user_id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$experience = $stmt->fetch();

if (!$experience) {
    $_SESSION['message'] = "Experience not found or you don't have permission to edit it";
    $_SESSION['message_type'] = 'error';
    header('Location: view-experiences.php');
    exit;
}

// Fetch all lookup data for dropdowns
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name");
$time_of_day = $conn->query("SELECT * FROM time_of_day ORDER BY id");
$surfaces = $conn->query("SELECT * FROM surfaces ORDER BY name");
$road_densities = $conn->query("SELECT * FROM road_densities ORDER BY id");
$road_types = $conn->query("SELECT * FROM road_types ORDER BY name");
$weather_conditions = $conn->query("SELECT * FROM weather_conditions ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Driving Experience</title>
    <link rel="stylesheet" href="css/style.css?v=9.0">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1>Driving Tracker</h1>
            </div>
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="add-experience.php">Add</a></li>
                    <li><a href="view-experiences.php" class="active">View</a></li>
                    <li><a href="statistics.php">Stats</a></li>
                    <li><span class="user-info">User: <?php echo htmlspecialchars(getCurrentUsername()); ?></span></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="container">
            <h2>Edit Experience</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="edit-experience.php?id=<?php echo $id; ?>" method="POST" id="experienceForm">
                <div class="form-grid">
                    <!-- Date and Time Section -->
                    <div class="form-group">
                        <label for="experience_date">
                            Date of Experience<span class="required">*</span>
                        </label>
                        <input 
                            type="date" 
                            id="experience_date" 
                            name="experience_date" 
                            value="<?php echo htmlspecialchars($experience['date']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="start_time">
                            Start Time<span class="required">*</span>
                        </label>
                        <input 
                            type="time" 
                            id="start_time" 
                            name="start_time" 
                            value="<?php echo htmlspecialchars($experience['start_time']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_time">
                            End Time<span class="required">*</span>
                        </label>
                        <input 
                            type="time" 
                            id="end_time" 
                            name="end_time" 
                            value="<?php echo htmlspecialchars($experience['end_time']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="distance_km">
                            Kilometers Traveled<span class="required">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="distance_km" 
                            name="distance_km" 
                            min="0.01" 
                            step="0.01" 
                            value="<?php echo htmlspecialchars($experience['distance_km']); ?>"
                            inputmode="decimal"
                            required
                        >
                    </div>

                    <!-- Vehicle and Conditions Section -->
                    <div class="form-group">
                        <label for="vehicle_type_id">
                            Vehicle Type<span class="required">*</span>
                        </label>
                        <select id="vehicle_type_id" name="vehicle_type_id" required>
                            <option value="">Select vehicle type</option>
                            <?php while ($row = $vehicle_types->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>" 
                                    <?php echo $row['id'] == $experience['vehicle_type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="time_of_day_id">
                            Time of Day<span class="required">*</span>
                        </label>
                        <select id="time_of_day_id" name="time_of_day_id" required>
                            <option value="">Select time period</option>
                            <?php while ($row = $time_of_day->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $row['id'] == $experience['time_of_day_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="weather_id">
                            Weather Condition<span class="required">*</span>
                        </label>
                        <select id="weather_id" name="weather_id" required>
                            <option value="">Select weather</option>
                            <?php while ($row = $weather_conditions->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $row['id'] == $experience['weather_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Road Conditions Section -->
                    <div class="form-group">
                        <label for="road_type_id">
                            Road Type<span class="required">*</span>
                        </label>
                        <select id="road_type_id" name="road_type_id" required>
                            <option value="">Select road type</option>
                            <?php while ($row = $road_types->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $row['id'] == $experience['road_type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="surface_id">
                            Road Surface<span class="required">*</span>
                        </label>
                        <select id="surface_id" name="surface_id" required>
                            <option value="">Select surface type</option>
                            <?php while ($row = $surfaces->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $row['id'] == $experience['surface_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="road_density_id">
                            Traffic Density<span class="required">*</span>
                        </label>
                        <select id="road_density_id" name="road_density_id" required>
                            <option value="">Select traffic density</option>
                            <?php while ($row = $road_densities->fetch()): ?>
                                <option value="<?php echo $row['id']; ?>"
                                    <?php echo $row['id'] == $experience['road_density_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Location Section -->
                    <div class="form-group">
                        <label for="start_location">
                            Start Location
                        </label>
                        <input 
                            type="text" 
                            id="start_location" 
                            name="start_location" 
                            value="<?php echo htmlspecialchars($experience['start_location']); ?>"
                            maxlength="255"
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_location">
                            End Location
                        </label>
                        <input 
                            type="text" 
                            id="end_location" 
                            name="end_location" 
                            value="<?php echo htmlspecialchars($experience['end_location']); ?>"
                            maxlength="255"
                        >
                    </div>

                    <!-- Notes Section -->
                    <div class="form-group full-width">
                        <label for="notes">
                            Additional Notes
                        </label>
                        <textarea 
                            id="notes" 
                            name="notes" 
                            rows="4"
                        ><?php echo htmlspecialchars($experience['notes']); ?></textarea>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success btn-block">
                        💾 Update Experience
                    </button>
                    <a href="view-experiences.php" class="btn btn-primary" style="flex: 0 0 150px;">
                        Cancel
                    </a>
                </div>
            </form>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>

    <script>
        // Validate end time is after start time
        document.getElementById('experienceForm').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (endTime && startTime) {
                const [startHour, startMin] = startTime.split(':').map(Number);
                const [endHour, endMin] = endTime.split(':').map(Number);
                
                const startMinutes = startHour * 60 + startMin;
                const endMinutes = endHour * 60 + endMin;
                
                // Same time check
                if (startMinutes === endMinutes) {
                    e.preventDefault();
                    alert('End time must be different from start time!');
                    return false;
                }
                
                // Calculate duration (positive = same day, negative = next day)
                let duration = endMinutes - startMinutes;
                
                // If negative, it crosses midnight into next day
                if (duration < 0) {
                    duration += 24 * 60; // Add 24 hours
                }
                
                const hours = Math.floor(duration / 60);
                const mins = duration % 60;
                
                // Maximum 24 hours for a driving session (sanity check only)
                if (duration > 24 * 60) {
                    e.preventDefault();
                    alert('Driving session cannot exceed 24 hours!\n\nYour session: ' + hours + ' hours ' + mins + ' minutes\n\nPlease check your times.');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
