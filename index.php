<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();

// Get current user ID
$userId = getCurrentUserId();

// Fetch all lookup data for the dashboard
$stats = [];

// Get total distance_km for this user
$stmt = $conn->prepare("SELECT SUM(distance_km) as total_km, COUNT(*) as total_trips FROM driving_experiences WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
if ($row = $stmt->fetch()) {
    $stats['total_km'] = number_format($row['total_km'] ?? 0, 2);
    $stats['total_trips'] = $row['total_trips'] ?? 0;
}

// Get unique locations count for this user
$stmt = $conn->prepare("SELECT COUNT(DISTINCT start_location) + COUNT(DISTINCT end_location) as locations FROM driving_experiences WHERE user_id = :user_id");
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
if ($row = $stmt->fetch()) {
    $stats['locations'] = $row['locations'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Supervised Driving Experience Management System">
    <title>Dashboard - Driving Experience Tracker</title>
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
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="add-experience.php">Add</a></li>
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
            <h2>Dashboard</h2>
            
            <!-- Statistics Cards -->
            <div class="dashboard-grid">
                <div class="card stat-card">
                    <span class="stat-number"><?php echo $stats['total_km']; ?></span>
                    <span class="stat-label">Total Kilometers</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-number"><?php echo $stats['total_trips']; ?></span>
                    <span class="stat-label">Total Trips</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-number"><?php echo $stats['locations']; ?></span>
                    <span class="stat-label">Unique Locations</span>
                </div>
            </div>
        </section>

        <section class="container">
            <h2>Quick Actions</h2>
            <div class="dashboard-grid">
                <a href="add-experience.php" class="card">
                    <div class="card-icon">+</div>
                    <h3 class="card-title">Add Experience</h3>
                    <p class="card-description">Record new driving session</p>
                </a>
                
                <a href="view-experiences.php" class="card">
                    <div class="card-icon">☰</div>
                    <h3 class="card-title">View Records</h3>
                    <p class="card-description">Browse driving history</p>
                </a>
                
                <a href="statistics.php" class="card">
                    <div class="card-icon">▬</div>
                    <h3 class="card-title">Statistics</h3>
                    <p class="card-description">View charts and analysis</p>
                </a>
            </div>
        </section>

        <?php
        // Display recent experiences for this user
        $stmt = $conn->prepare("
            SELECT de.*, vt.name as vehicle, w.name as weather, rt.name as road
            FROM driving_experiences de
            JOIN vehicle_types vt ON de.vehicle_type_id = vt.id
            JOIN weather_conditions w ON de.weather_id = w.id
            JOIN road_types rt ON de.road_type_id = rt.id
            WHERE de.user_id = :user_id
            ORDER BY de.created_at DESC
            LIMIT 5
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $recent_rows = $stmt->fetchAll();
        if (!empty($recent_rows)):
        ?>
        <section class="container">
            <h2>Recent Activity</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Distance (km)</th>
                            <th>Vehicle</th>
                            <th>Weather</th>
                            <th>Road Type</th>
                            <th>From → To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_rows as $row): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['experience_date'])); ?></td>
                            <td><?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?></td>
                            <td><?php echo number_format($row['distance_km'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['vehicle']); ?></td>
                            <td><?php echo htmlspecialchars($row['weather']); ?></td>
                            <td><?php echo htmlspecialchars($row['road']); ?></td>
                            <td>
                                <?php 
                                $from = htmlspecialchars($row['start_location'] ?? 'N/A');
                                $to = htmlspecialchars($row['end_location'] ?? 'N/A');
                                echo "$from → $to";
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 1rem;">
                <a href="view-experiences.php" class="btn btn-primary">View All</a>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>
</body>
</html>
