<?php
/**
 * Weight Entry Modal Component
 */
if (!defined('DB_PATH')) die('Direct access not permitted');

$today = date('Y-m-d');
$now_time = date('H:i');
?>
<div id="weightModal" class="modal">
    <div class="modal-overlay" onclick="closeWeightModal()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Weight Entry</h2>
            <button class="modal-close" onclick="closeWeightModal()">×</button>
        </div>
        
        <form id="weightForm" class="modal-body">
            <input type="hidden" id="entry_id" name="id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="entry_date">Date</label>
                    <input type="date" id="entry_date" name="date" 
                           value="<?php echo $today; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="entry_time">Time</label>
                    <input type="time" id="entry_time" name="time" 
                           value="<?php echo $now_time; ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="entry_weight">Weight (<?php echo e($user['weight_unit']); ?>)</label>
                <input type="number" id="entry_weight" name="weight" 
                       step="0.1" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="entry_notes">Notes (optional)</label>
                <textarea id="entry_notes" name="notes" rows="3" 
                          placeholder="Any notes about this entry..."></textarea>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeWeightModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
