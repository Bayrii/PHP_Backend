<?php
session_start();
require_once 'config/database.php';
require_once 'includes/anonymize.php';
require_once 'includes/auth.php';

// Require login
requireLogin();

$db = Database::getInstance();
$conn = $db->getConnection();

// Get current user ID
$userId = getCurrentUserId();

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Filters
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_vehicle = $_GET['vehicle'] ?? '';
$filter_weather = $_GET['weather'] ?? '';
$filter_road_type = $_GET['road_type'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

// Always filter by user_id
$where_conditions[] = "de.user_id = :user_id";
$params[':user_id'] = $userId;

if ($filter_date_from) {
    $where_conditions[] = "de.date >= :date_from";
    $params[':date_from'] = $filter_date_from;
}

if ($filter_date_to) {
    $where_conditions[] = "de.date <= :date_to";
    $params[':date_to'] = $filter_date_to;
}

if ($filter_vehicle) {
    $where_conditions[] = "de.vehicle_type_id = :vehicle_id";
    $params[':vehicle_id'] = intval($filter_vehicle);
}

if ($filter_weather) {
    $where_conditions[] = "de.weather_id = :weather_id";
    $params[':weather_id'] = intval($filter_weather);
}

if ($filter_road_type) {
    $where_conditions[] = "de.road_type_id = :road_type_id";
    $params[':road_type_id'] = intval($filter_road_type);
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Get total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM driving_experiences de $where_sql";
$count_stmt = $conn->prepare($count_sql);
foreach ($params as $key => $value) {
    $count_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$count_stmt->execute();
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get experiences with joins
$sql = "SELECT 
            de.*,
            vt.name as vehicle_type,
            tod.name as time_of_day,
            s.name as surface,
            rd.name as road_density,
            rt.name as road_type,
            w.name as weather
        FROM driving_experiences de
        JOIN vehicle_types vt ON de.vehicle_type_id = vt.id
        JOIN time_of_day tod ON de.time_of_day_id = tod.id
        JOIN surfaces s ON de.surface_id = s.id
        JOIN road_densities rd ON de.road_density_id = rd.id
        JOIN road_types rt ON de.road_type_id = rt.id
        JOIN weather_conditions w ON de.weather_id = w.id
        $where_sql
        ORDER BY de.date DESC, de.start_time DESC
        LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$result = $stmt;

// Get total distance_km with filters
$total_km_sql = "SELECT SUM(distance_km) as total_km FROM driving_experiences de $where_sql";
$total_km_stmt = $conn->prepare($total_km_sql);
foreach ($params as $key => $value) {
    $total_km_stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$total_km_stmt->execute();
$total_km = $total_km_stmt->fetch()['total_km'] ?? 0;

// Get filter options
$vehicles = $conn->query("SELECT * FROM vehicle_types ORDER BY name");
$weathers = $conn->query("SELECT * FROM weather_conditions ORDER BY name");
$road_types_list = $conn->query("SELECT * FROM road_types ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Experiences</title>
    <link rel="stylesheet" href="css/style.css?v=9.0">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <style>
        .filters {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text-color);
            background: white;
        }
        .pagination a:hover {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        .pagination .active {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn-small {
            padding: 0.4rem 0.8rem;
            font-size: 0.875rem;
            white-space: nowrap;
        }
        /* Make table more compact and responsive */
        table {
            font-size: 0.9rem;
        }
        th, td {
            padding: 0.75rem 0.6rem;
        }
        td:nth-child(5),
        td:nth-child(6) {
            white-space: normal;
            line-height: 1.4;
        }
        td:last-child {
            min-width: 110px;
        }
        /* Make action buttons stack vertically in table */
        .action-buttons {
            flex-direction: column;
            gap: 0.3rem;
        }
        .action-buttons .btn-small {
            width: 100%;
            text-align: center;
            padding: 0.35rem 0.6rem;
            font-size: 0.8rem;
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
            <h2>All Driving Experiences</h2>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo htmlspecialchars($_SESSION['message']); 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="filters">
                <h3>Filter Experiences</h3>
                <form method="GET" action="view-experiences.php">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="date_from">From Date</label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to">To Date</label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                        </div>
                        <div class="form-group">
                            <label for="vehicle">Vehicle Type</label>
                            <select id="vehicle" name="vehicle">
                                <option value="">All Vehicles</option>
                                <?php while ($v = $vehicles->fetch()): ?>
                                    <option value="<?php echo $v['id']; ?>" <?php echo $filter_vehicle == $v['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($v['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="weather">Weather</label>
                            <select id="weather" name="weather">
                                <option value="">All Weather</option>
                                <?php while ($w = $weathers->fetch()): ?>
                                    <option value="<?php echo $w['id']; ?>" <?php echo $filter_weather == $w['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($w['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="road_type">Road Type</label>
                            <select id="road_type" name="road_type">
                                <option value="">All Road Types</option>
                                <?php while ($r = $road_types_list->fetch()): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo $filter_road_type == $r['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($r['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="filter-buttons">
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                        <a href="view-experiences.php" class="btn btn-primary">Clear Filters</a>
                    </div>
                </form>
            </div>

            <!-- Summary Stats -->
            <div class="alert alert-info">
                <strong>Total Kilometers:</strong> <?php echo number_format($total_km, 2); ?> km | 
                <strong>Total Records:</strong> <?php echo $total_records; ?>
            </div>

            <!-- Data Table -->
            <?php if ($total_records > 0): ?>
                <div class="table-container">
                    <table id="experiencesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>KM</th>
                                <th>Vehicle</th>
                                <th>Conditions</th>
                                <th>Route</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                <td>
                                    <?php 
                                    echo date('H:i', strtotime($row['start_time'])) . '<br>↓<br>' . 
                                         date('H:i', strtotime($row['end_time'])); 
                                    ?>
                                </td>
                                <td><strong><?php echo number_format($row['distance_km'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['vehicle_type']); ?></td>
                                <td class="conditions-cell">
                                    <strong><?php echo htmlspecialchars($row['time_of_day']); ?></strong><br>
                                    <?php echo htmlspecialchars($row['weather']); ?><br>
                                    <?php echo htmlspecialchars($row['road_type']); ?> / <?php echo htmlspecialchars($row['surface']); ?><br>
                                    <?php echo htmlspecialchars($row['road_density']); ?>
                                </td>
                                <td class="route-cell">
                                    <?php 
                                    $from = htmlspecialchars($row['start_location'] ?: 'N/A');
                                    $to = htmlspecialchars($row['end_location'] ?: 'N/A');
                                    echo "$from<br>→<br>$to";
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit-experience.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-primary btn-small">Edit</a>
                                        <a href="delete-experience.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-danger btn-small"
                                           onclick="return confirm('Are you sure you want to delete this experience?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>No driving experiences found. <a href="add-experience.php">Add your first experience</a>.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Driving Tracker</p>
    </footer>
    
    <!-- jQuery and DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#experiencesTable').DataTable({
                "pageLength": 10,
                "order": [[0, "desc"]], // Sort by date descending
                "responsive": true,
                "searching": false, // Disable search box (we have custom filters)
                "language": {
                    "lengthMenu": "Show _MENU_ experiences per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ experiences",
                    "infoEmpty": "No experiences available",
                    "infoFiltered": "(filtered from _MAX_ total experiences)",
                    "zeroRecords": "No matching experiences found"
                }
            });
        });
    </script>
</body>
</html>
