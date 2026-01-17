<?php
/**
 * Home Page - Dashboard with chart and statistics
 */
if (!defined('DB_PATH')) die('Direct access not permitted');

$has_entered_today = hasEnteredWeightToday($user['id'], $user['timezone']);

// Get statistics
$now = time();
$seven_days_ago = strtotime('-7 days', $now);
$thirty_days_ago = strtotime('-30 days', $now);

// Get weight data
$last_7_days = getDailyAverages($user['id'], $user['timezone'], $seven_days_ago, $now);
$last_30_days = getDailyAverages($user['id'], $user['timezone'], $thirty_days_ago, $now);
$all_entries = getDailyAverages($user['id'], $user['timezone']);

// Calculate statistics
$current_weight = !empty($all_entries) ? end($all_entries)['weight'] : $settings['starting_weight'];
$starting_weight = $settings['starting_weight'];
$target_weight = $settings['target_weight'];

// 7-day change (weight from 7 days ago vs current)
$change_7_day = 0;
if (count($last_7_days) >= 2) {
    $weight_7_days_ago = reset($last_7_days)['weight'];
    $change_7_day = $weight_7_days_ago - $current_weight;
}

// 30-day change (weight from 30 days ago vs current)
$change_30_day = 0;
if (count($last_30_days) >= 2) {
    $weight_30_days_ago = reset($last_30_days)['weight'];
    $change_30_day = $weight_30_days_ago - $current_weight;
}

// Total weight change
$total_change = $starting_weight - $current_weight;
$progress_percent = 0;
if ($starting_weight != $target_weight) {
    $progress_percent = min(100, max(0, ($total_change / ($starting_weight - $target_weight)) * 100));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="LibreScale - Personal weight tracking application">
    <meta name="theme-color" content="#5e35b1">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="icon-192.png">
    <title>Home - LibreScale</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
</head>
<body class="<?php echo getThemeClasses($user); ?>">
    <div class="app-container">
        <header class="app-header">
            <h1>Hi <?php echo e($user['display_name']); ?>! <span class="material-symbols-outlined">waving_hand</span></h1>
        </header>
        
        <main class="app-content">
            <?php if (!$has_entered_today): ?>
                <div class="reminder-banner">
                    <span class="material-symbols-outlined banner-icon">scale</span>
                    <div class="banner-content">
                        <strong>Don't forget!</strong>
                        <p>You haven't logged your weight today</p>
                    </div>
                    <button class="banner-action" onclick="openAddWeightModal()">Add Now</button>
                </div>
            <?php endif; ?>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h2>Weight Progress</h2>
                    <div class="chart-tabs">
                        <button class="tab-btn active" data-period="week">Week</button>
                        <button class="tab-btn" data-period="month">Month</button>
                        <button class="tab-btn" data-period="year">Year</button>
                        <button class="tab-btn" data-period="all">All</button>
                    </div>
                </div>
                <div class="chart-container">
                    <canvas id="weightChart"></canvas>
                </div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="material-symbols-outlined stat-icon">bar_chart</span>
                    <div class="stat-content">
                        <div class="stat-label">7-Day Change</div>
                        <div class="stat-value <?php echo $change_7_day > 0 ? 'positive' : ($change_7_day < 0 ? 'negative' : ''); ?>">
                            <?php echo $change_7_day > 0 ? '-' : ($change_7_day < 0 ? '+' : ''); ?><?php echo formatWeight(abs($change_7_day), $user['weight_unit']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <span class="material-symbols-outlined stat-icon">trending_up</span>
                    <div class="stat-content">
                        <div class="stat-label">30-Day Change</div>
                        <div class="stat-value <?php echo $change_30_day > 0 ? 'positive' : ($change_30_day < 0 ? 'negative' : ''); ?>">
                            <?php echo $change_30_day > 0 ? '-' : ($change_30_day < 0 ? '+' : ''); ?><?php echo formatWeight(abs($change_30_day), $user['weight_unit']); ?>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <span class="material-symbols-outlined stat-icon">target</span>
                    <div class="stat-content">
                        <div class="stat-label">Total Progress</div>
                        <div class="stat-value <?php echo $total_change >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo formatWeight($total_change, $user['weight_unit']); ?>
                        </div>
                        <div class="stat-sublabel"><?php echo number_format($progress_percent, 1); ?>% to goal</div>
                    </div>
                </div>
            </div>
        </main>
        
        <?php include 'nav.php'; ?>
        <?php include 'modal.php'; ?>
        
        <button class="fab" onclick="openAddWeightModal()" title="Add weight entry">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
    
    <script src="app.js"></script>
    <script>
        // Initialize chart with week data by default
        let currentChart = null;
        
        function loadChartData(period = 'week') {
            fetch(`index.php?action=get_chart_data&period=${period}`)
                .then(r => r.json())
                .then(data => {
                    renderChart(data);
                    
                    // Update active tab
                    document.querySelectorAll('.tab-btn').forEach(btn => {
                        btn.classList.toggle('active', btn.dataset.period === period);
                    });
                });
        }
        
        function renderChart(data) {
            const ctx = document.getElementById('weightChart').getContext('2d');
            
            // Destroy existing chart
            if (currentChart) {
                currentChart.destroy();
            }
            
            const labels = data.data.map(d => d.date);
            const weights = data.data.map(d => d.weight);
            const targetLine = new Array(labels.length).fill(data.target_weight);
            
            // Get theme colors from CSS variables (read from body element)
            const styles = getComputedStyle(document.body);
            const primaryColor = styles.getPropertyValue('--primary').trim();
            const primaryBg = styles.getPropertyValue('--primary-bg').trim();
            
            currentChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Weight (' + data.unit + ')',
                            data: weights,
                            borderColor: primaryColor,
                            backgroundColor: primaryBg,
                            tension: 0.4,
                            fill: true,
                            pointRadius: 4,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Target',
                            data: targetLine,
                            borderColor: '#00897b',
                            borderDash: [5, 5],
                            borderWidth: 2,
                            pointRadius: 0,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return value + ' ' + data.unit;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Tab click handlers
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                loadChartData(btn.dataset.period);
            });
        });
        
        // Load initial data
        loadChartData('week');
    </script>
</body>
</html>
