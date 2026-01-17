<?php
/**
 * Entries Page - List and manage all weight entries
 */
if (!defined('DB_PATH')) die('Direct access not permitted');

$entries = getWeightEntries($user['id']);
$starting_weight = $settings['starting_weight'];
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
    <title>Entries - LibreScale</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="<?php echo getThemeClasses($user); ?>">
    <div class="app-container">
        <header class="app-header">
            <h1>Weight Entries</h1>
        </header>
        
        <main class="app-content">
            <?php if (empty($entries)): ?>
                <div class="empty-state">
                    <span class="material-symbols-outlined empty-icon">edit_note</span>
                    <h2>No entries yet</h2>
                    <p>Start tracking your weight by adding your first entry!</p>
                    <button class="btn-primary" onclick="openAddWeightModal()">Add Entry</button>
                </div>
            <?php else: ?>
                <div class="entries-list">
                    <?php
                    $current_date = null;
                    $entry_count = count($entries);
                    for ($i = 0; $i < $entry_count; $i++):
                        $entry = $entries[$i];
                        $entry_date = formatDateTime($entry['timestamp'], $user['timezone'], 'Y-m-d');
                        $diff_from_start = $starting_weight - $entry['weight'];
                        $diff_from_start_class = $diff_from_start > 0 ? 'positive' : ($diff_from_start < 0 ? 'negative' : 'neutral');
                        
                        // Calculate diff from chronologically previous entry (next in DESC array)
                        $diff_from_last = null;
                        $diff_from_last_class = 'neutral';
                        if ($i + 1 < $entry_count) {
                            $previous_entry = $entries[$i + 1];
                            $diff_from_last = $entry['weight'] - $previous_entry['weight'];
                            $diff_from_last_class = $diff_from_last < 0 ? 'positive' : ($diff_from_last > 0 ? 'negative' : 'neutral');
                        }
                        
                        // Show date header if different from previous
                        if ($entry_date !== $current_date):
                            $current_date = $entry_date;
                            $display_date = formatDateTime($entry['timestamp'], $user['timezone'], 'l, F j, Y');
                    ?>
                        <div class="entry-date-header"><?php echo e($display_date); ?></div>
                    <?php endif; ?>
                    
                    <div class="entry-item" data-id="<?php echo $entry['id']; ?>">
                        <div class="entry-time">
                            <?php echo formatDateTime($entry['timestamp'], $user['timezone'], 'H:i'); ?>
                        </div>
                        <div class="entry-weight">
                            <strong><?php echo formatWeight($entry['weight'], $user['weight_unit']); ?></strong>
                            <div class="entry-diffs">
                                <?php if ($diff_from_last !== null): ?>
                                    <span class="entry-diff <?php echo $diff_from_last_class; ?>" title="Change from last entry">
                                        <?php echo $diff_from_last > 0 ? '+' : ($diff_from_last < 0 ? '-' : ''); ?><?php echo abs($diff_from_last) ? number_format(abs($diff_from_last), 1) : '0.0'; ?> <?php echo e($user['weight_unit']); ?>
                                    </span>
                                    <span class="diff-separator">/</span>
                                <?php endif; ?>
                                <span class="entry-diff <?php echo $diff_from_start_class; ?>" title="Change from starting weight">
                                    <?php echo $diff_from_start > 0 ? '-' : '+'; ?><?php echo abs($diff_from_start) ? number_format(abs($diff_from_start), 1) : '0.0'; ?> <?php echo e($user['weight_unit']); ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($entry['notes']): ?>
                            <div class="entry-notes"><?php echo e($entry['notes']); ?></div>
                        <?php endif; ?>
                        <div class="entry-actions">
                            <button class="btn-icon" onclick="editEntry(<?php echo $entry['id']; ?>)" title="Edit">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <button class="btn-icon" onclick="deleteEntry(<?php echo $entry['id']; ?>)" title="Delete">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </main>
        
        <?php include 'nav.php'; ?>
        <?php include 'modal.php'; ?>
        
        <button class="fab" onclick="openAddWeightModal()" title="Add weight entry">
            <span class="material-symbols-outlined">add</span>
        </button>
    </div>
    
    <script src="app.js"></script>
    <script>
        function editEntry(id) {
            // Get entry data
            fetch(`index.php?action=get_entry&id=${id}`)
                .then(r => r.json())
                .then(entry => {
                    openEditWeightModal(entry);
                })
                .catch(err => {
                    alert('Failed to load entry');
                });
        }
        
        function deleteEntry(id) {
            if (!confirm('Are you sure you want to delete this entry?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('index.php?action=delete_weight', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to delete entry');
                }
            })
            .catch(err => {
                alert('Failed to delete entry');
            });
        }
    </script>
</body>
</html>
