<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get current user ID
$userId = getCurrentUserId();

// Fetch all lookup data for dropdowns
$vehicle_types = $conn->query("SELECT * FROM vehicle_types ORDER BY name");
$time_of_day = $conn->query("SELECT * FROM time_of_day ORDER BY id");
$surfaces = $conn->query("SELECT * FROM surfaces ORDER BY name");
$road_densities = $conn->query("SELECT * FROM road_densities ORDER BY id");
$road_types = $conn->query("SELECT * FROM road_types ORDER BY name");
$weather_conditions = $conn->query("SELECT * FROM weather_conditions ORDER BY name");

// Get current date and time for default values
$current_date = date('Y-m-d');
$current_time = date('H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Driving Experience</title>
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
                    <li><a href="add-experience.php" class="active">Add</a></li>
                    <li><a href="view-experiences.php">View</a></li>
                    <li><a href="statistics.php">Stats</a></li>
                    <li style="margin-left: auto;"><span style="color: var(--text-light);">👤 <?php echo htmlspecialchars(getCurrentUsername()); ?></span></li>
                    <li><a href="logout.php" style="color: var(--warning-color);">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <section class="container">
            <h2>Add Driving Experience</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <form action="process-experience.php" method="POST" id="experienceForm">
                <h3 class="section-header">📅 Date & Time</h3>
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
                            value="<?php echo $current_date; ?>"
                            max="<?php echo $current_date; ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="start_time">
                            Start Time<span class="required">*</span>
                        </label>
                        <div class="time-selector">
                            <select id="start_hour" name="start_hour" required class="hour">
                                <option value="">Hour</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <input 
                                type="number" 
                                id="start_minute" 
                                name="start_minute" 
                                min="0" 
                                max="59" 
                                placeholder="00" 
                                required 
                                class="minute"
                            >
                            <select id="start_period" name="start_period" required class="period">
                                <option value=""></option>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="end_time">
                            End Time<span class="required">*</span>
                        </label>
                        <div class="time-selector">
                            <select id="end_hour" name="end_hour" required class="hour">
                                <option value="">Hour</option>
                                <?php for($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <input 
                                type="number" 
                                id="end_minute" 
                                name="end_minute" 
                                min="0" 
                                max="59" 
                                placeholder="00" 
                                required 
                                class="minute"
                            >
                            <select id="end_period" name="end_period" required class="period">
                                <option value=""></option>
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                </div>

                <h3 class="section-header spaced">🚗 Vehicle & Conditions</h3>
                <div class="form-grid">
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
                            placeholder="45.50"
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
                                <option value="<?php echo $row['id']; ?>">
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
                                <option value="<?php echo $row['id']; ?>">
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
                                <option value="<?php echo $row['id']; ?>">
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
                                <option value="<?php echo $row['id']; ?>">
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
                                <option value="<?php echo $row['id']; ?>">
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
                                <option value="<?php echo $row['id']; ?>">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <h3 class="section-header spaced">📍 Location & Notes</h3>
                <div class="form-grid">
                    <!-- Location Section -->
                    <div class="form-group">
                        <label for="start_location">
                            Start Location
                        </label>
                        <input 
                            type="text" 
                            id="start_location" 
                            name="start_location" 
                            placeholder="e.g., Downtown"
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
                            placeholder="e.g., Airport"
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
                            placeholder="Any observations, challenges, or achievements during this drive..."
                            rows="4"
                        ></textarea>
                    </div>
                </div>

                <div class="form-submit-area">
                    <button type="submit" class="btn btn-success btn-block btn-large">
                        ✓ Save Experience
                    </button>
                    <a href="index.php" class="btn btn-primary btn-large btn-cancel">
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
        // Auto-select time of day based on start time
        function updateTimeOfDay() {
            const hour = parseInt(document.getElementById('start_hour').value) || 0;
            const period = document.getElementById('start_period').value;
            
            if (!hour || !period) return;
            
            // Convert to 24-hour
            let hour24 = hour;
            if (period === 'PM' && hour !== 12) hour24 += 12;
            if (period === 'AM' && hour === 12) hour24 = 0;
            
            const timeOfDaySelect = document.getElementById('time_of_day_id');
            
            let timeOfDayId = '';
            if (hour24 >= 7 && hour24 < 9) timeOfDayId = '1';        // Morning Rush
            else if (hour24 >= 9 && hour24 < 12) timeOfDayId = '2';  // Late Morning
            else if (hour24 >= 12 && hour24 < 17) timeOfDayId = '3'; // Afternoon
            else if (hour24 >= 17 && hour24 < 19) timeOfDayId = '4'; // Evening Rush
            else if (hour24 >= 19 && hour24 < 23) timeOfDayId = '5'; // Night
            else timeOfDayId = '6';                                   // Late Night
            
            timeOfDaySelect.value = timeOfDayId;
        }
        
        document.getElementById('start_hour').addEventListener('change', updateTimeOfDay);
        document.getElementById('start_period').addEventListener('change', updateTimeOfDay);

        // Validate end time is after start time
        document.getElementById('experienceForm').addEventListener('submit', function(e) {
            const startHour = parseInt(document.getElementById('start_hour').value);
            const startMinute = parseInt(document.getElementById('start_minute').value) || 0;
            const startPeriod = document.getElementById('start_period').value;
            
            const endHour = parseInt(document.getElementById('end_hour').value);
            const endMinute = parseInt(document.getElementById('end_minute').value) || 0;
            const endPeriod = document.getElementById('end_period').value;
            
            if (!startHour || !endHour || !startPeriod || !endPeriod) return;
            
            // Validate minutes
            if (startMinute < 0 || startMinute > 59) {
                e.preventDefault();
                alert('Start minute must be between 0 and 59!');
                return false;
            }
            
            if (endMinute < 0 || endMinute > 59) {
                e.preventDefault();
                alert('End minute must be between 0 and 59!');
                return false;
            }
            
            // Convert to 24-hour format
            let startHour24 = startHour;
            if (startPeriod === 'PM' && startHour !== 12) startHour24 += 12;
            if (startPeriod === 'AM' && startHour === 12) startHour24 = 0;
            
            let endHour24 = endHour;
            if (endPeriod === 'PM' && endHour !== 12) endHour24 += 12;
            if (endPeriod === 'AM' && endHour === 12) endHour24 = 0;
            
            const startMinutes = startHour24 * 60 + startMinute;
            const endMinutes = endHour24 * 60 + endMinute;
            
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
            
            // Maximum 24 hours for a driving session (basically allow any reasonable duration)
            if (duration > 24 * 60) {
                e.preventDefault();
                alert('Driving session cannot exceed 24 hours!\n\n' +
                      'Start: ' + startHour + ':' + String(startMinute).padStart(2, '0') + ' ' + startPeriod + '\n' +
                      'End: ' + endHour + ':' + String(endMinute).padStart(2, '0') + ' ' + endPeriod + '\n' +
                      'Duration: ' + hours + ' hours ' + mins + ' minutes\n\n' +
                      'Please check your times.');
                return false;
            }
        });
    </script>
</body>
</html>
