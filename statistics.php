<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Require login
requireLogin();
$userId = getCurrentUserId();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics data
$stats = [];

// Weather statistics
$weather_stats = $conn->prepare("
    SELECT w.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN weather_conditions w ON de.weather_id = w.id
    WHERE de.user_id = :user_id
    GROUP BY w.id, w.name
    ORDER BY count DESC
");
$weather_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$weather_stats->execute();

// Vehicle type statistics
$vehicle_stats = $conn->prepare("
    SELECT vt.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN vehicle_types vt ON de.vehicle_type_id = vt.id
    WHERE de.user_id = :user_id
    GROUP BY vt.id, vt.name
    ORDER BY count DESC
");
$vehicle_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$vehicle_stats->execute();

// Road type statistics
$road_stats = $conn->prepare("
    SELECT rt.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN road_types rt ON de.road_type_id = rt.id
    WHERE de.user_id = :user_id
    GROUP BY rt.id, rt.name
    ORDER BY count DESC
");
$road_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$road_stats->execute();

// Time of day statistics
$time_stats = $conn->prepare("
    SELECT tod.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN time_of_day tod ON de.time_of_day_id = tod.id
    WHERE de.user_id = :user_id
    GROUP BY tod.id, tod.name
    ORDER BY tod.id
");
$time_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$time_stats->execute();

// Monthly statistics
$monthly_stats = $conn->prepare("
    SELECT 
        DATE_FORMAT(date, '%Y-%m') as month,
        COUNT(*) as trips,
        SUM(distance_km) as total_km
    FROM driving_experiences
    WHERE user_id = :user_id
    GROUP BY DATE_FORMAT(date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
$monthly_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$monthly_stats->execute();

// Surface statistics
$surface_stats = $conn->prepare("
    SELECT s.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN surfaces s ON de.surface_id = s.id
    WHERE de.user_id = :user_id
    GROUP BY s.id, s.name
    ORDER BY count DESC
");
$surface_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$surface_stats->execute();

// Traffic density statistics
$traffic_stats = $conn->prepare("
    SELECT rd.name, COUNT(*) as count, SUM(de.distance_km) as total_km
    FROM driving_experiences de
    JOIN road_densities rd ON de.road_density_id = rd.id
    WHERE de.user_id = :user_id
    GROUP BY rd.id, rd.name
    ORDER BY rd.id
");
$traffic_stats->bindValue(':user_id', $userId, PDO::PARAM_INT);
$traffic_stats->execute();

// Overall statistics
$overall_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_trips,
        SUM(distance_km) as total_km,
        AVG(distance_km) as avg_km,
        MIN(date) as first_trip,
        MAX(date) as last_trip
    FROM driving_experiences
    WHERE user_id = :user_id
");
$overall_stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$overall_stmt->execute();
$overall = $overall_stmt->fetch();

// Prepare data for JavaScript charts
$weather_data = ['labels' => [], 'counts' => [], 'km' => []];
while ($row = $weather_stats->fetch()) {
    $weather_data['labels'][] = $row['name'];
    $weather_data['counts'][] = $row['count'];
    $weather_data['km'][] = round($row['total_km'], 2);
}

$vehicle_data = ['labels' => [], 'counts' => [], 'km' => []];
while ($row = $vehicle_stats->fetch()) {
    $vehicle_data['labels'][] = $row['name'];
    $vehicle_data['counts'][] = $row['count'];
    $vehicle_data['km'][] = round($row['total_km'], 2);
}

$road_data = ['labels' => [], 'counts' => [], 'km' => []];
while ($row = $road_stats->fetch()) {
    $road_data['labels'][] = $row['name'];
    $road_data['counts'][] = $row['count'];
    $road_data['km'][] = round($row['total_km'], 2);
}

$time_data = ['labels' => [], 'counts' => [], 'km' => []];
while ($row = $time_stats->fetch()) {
    $time_data['labels'][] = $row['name'];
    $time_data['counts'][] = $row['count'];
    $time_data['km'][] = round($row['total_km'], 2);
}

$monthly_data = ['labels' => [], 'trips' => [], 'km' => []];
while ($row = $monthly_stats->fetch()) {
    $monthly_data['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_data['trips'][] = $row['trips'];
    $monthly_data['km'][] = round($row['total_km'], 2);
}
$monthly_data['labels'] = array_reverse($monthly_data['labels']);
$monthly_data['trips'] = array_reverse($monthly_data['trips']);
$monthly_data['km'] = array_reverse($monthly_data['km']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics - Driving Experience</title>
    <link rel="stylesheet" href="css/style.css?v=9.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin: 2rem 0;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        @media (max-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
    </style>
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
                    <li><a href="view-experiences.php">View</a></li>
                    <li><a href="statistics.php" class="active">Stats</a></li>
                    <li><span class="user-info">User: <?php echo htmlspecialchars(getCurrentUsername()); ?></span></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <!-- Overall Statistics -->
        <section class="container">
            <h2>Overall Statistics</h2>
            <div class="stats-grid">
                <div class="card stat-card">
                    <span class="stat-number"><?php echo number_format($overall['total_km'], 2); ?></span>
                    <span class="stat-label">Total Kilometers</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-number"><?php echo $overall['total_trips']; ?></span>
                    <span class="stat-label">Total Trips</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-number"><?php echo number_format($overall['avg_km'], 2); ?></span>
                    <span class="stat-label">Average KM/Trip</span>
                </div>
                <div class="card stat-card">
                    <span class="stat-number"><?php echo $overall['first_trip'] ? date('M d, Y', strtotime($overall['first_trip'])) : 'N/A'; ?></span>
                    <span class="stat-label">First Trip</span>
                </div>
            </div>
        </section>

        <!-- Weather Statistics -->
        <section class="container">
            <h2>🌤️ Weather Conditions</h2>
            <div class="chart-container">
                <canvas id="weatherChart"></canvas>
            </div>
        </section>

        <!-- Vehicle Statistics -->
        <section class="container">
            <h2>🚙 Vehicle Types</h2>
            <div class="chart-container">
                <canvas id="vehicleChart"></canvas>
            </div>
        </section>

        <!-- Road Type Statistics -->
        <section class="container">
            <h2>🛣️ Road Types</h2>
            <?php if (empty($road_data['labels'])): ?>
                <p style="text-align: center; color: var(--text-light); padding: 2rem;">No data available. Add some driving experiences first.</p>
            <?php else: ?>
            <div class="chart-container">
                <canvas id="roadChart"></canvas>
            </div>
            <?php endif; ?>
        </section>

        <!-- Time of Day Statistics -->
        <section class="container">
            <h2>🕐 Time of Day Distribution</h2>
            <?php if (empty($time_data['labels'])): ?>
                <p style="text-align: center; color: var(--text-light); padding: 2rem;">No data available. Add some driving experiences first.</p>
            <?php else: ?>
            <div class="chart-container">
                <canvas id="timeChart"></canvas>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>

    <script>
        // Chart.js default configuration
        Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        Chart.defaults.color = '#2c3e50';

        // Color schemes
        const colors = {
            primary: '#3498db',
            success: '#27ae60',
            warning: '#f39c12',
            danger: '#e74c3c',
            info: '#16a085',
            purple: '#9b59b6',
            pink: '#e91e63'
        };

        const chartColors = [
            colors.primary,
            colors.success,
            colors.warning,
            colors.danger,
            colors.info,
            colors.purple,
            colors.pink,
            '#34495e',
            '#1abc9c',
            '#d35400'
        ];

        // Weather Chart
        new Chart(document.getElementById('weatherChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($weather_data['labels']); ?>,
                datasets: [{
                    label: 'Trips',
                    data: <?php echo json_encode($weather_data['counts']); ?>,
                    backgroundColor: chartColors
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Vehicle Chart
        new Chart(document.getElementById('vehicleChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($vehicle_data['labels']); ?>,
                datasets: [{
                    label: 'Number of Trips',
                    data: <?php echo json_encode($vehicle_data['counts']); ?>,
                    backgroundColor: colors.primary
                }, {
                    label: 'Total Kilometers',
                    data: <?php echo json_encode($vehicle_data['km']); ?>,
                    backgroundColor: colors.success
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Road Type Chart
        <?php if (!empty($road_data['labels'])): ?>
        new Chart(document.getElementById('roadChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($road_data['labels']); ?>,
                datasets: [{
                    label: 'Kilometers',
                    data: <?php echo json_encode($road_data['km']); ?>,
                    backgroundColor: chartColors
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
        <?php endif; ?>

        // Time of Day Chart
        <?php if (!empty($time_data['labels'])): ?>
        new Chart(document.getElementById('timeChart'), {
            type: 'radar',
            data: {
                labels: <?php echo json_encode($time_data['labels']); ?>,
                datasets: [{
                    label: 'Number of Trips',
                    data: <?php echo json_encode($time_data['counts']); ?>,
                    borderColor: colors.primary,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
