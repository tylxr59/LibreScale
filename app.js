/**
 * LibreScale - Main JavaScript Application
 */

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registered:', registration.scope);
            })
            .catch(err => {
                console.log('ServiceWorker registration failed:', err);
            });
    });
}

// Modal Management
function openAddWeightModal() {
    const modal = document.getElementById('weightModal');
    const form = document.getElementById('weightForm');
    
    // Reset form
    form.reset();
    document.getElementById('entry_id').value = '';
    document.getElementById('modalTitle').textContent = 'Add Weight Entry';
    
    // Set default values
    const now = new Date();
    document.getElementById('entry_date').value = formatDate(now);
    document.getElementById('entry_time').value = formatTime(now);
    
    modal.classList.add('active');
    document.getElementById('entry_weight').focus();
}

function openEditWeightModal(entry) {
    const modal = document.getElementById('weightModal');
    const form = document.getElementById('weightForm');
    
    // Parse the entry data
    const timestamp = entry.timestamp * 1000; // Convert to milliseconds
    const date = new Date(timestamp);
    
    // Populate form
    document.getElementById('entry_id').value = entry.id;
    document.getElementById('entry_date').value = formatDate(date);
    document.getElementById('entry_time').value = formatTime(date);
    document.getElementById('entry_weight').value = entry.weight;
    document.getElementById('entry_notes').value = entry.notes || '';
    document.getElementById('modalTitle').textContent = 'Edit Weight Entry';
    
    modal.classList.add('active');
    document.getElementById('entry_weight').focus();
}

function closeWeightModal() {
    const modal = document.getElementById('weightModal');
    modal.classList.remove('active');
}

// Handle weight form submission
if (document.getElementById('weightForm')) {
    document.getElementById('weightForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const entryId = document.getElementById('entry_id').value;
        const action = entryId ? 'edit_weight' : 'add_weight';
        
        fetch(`index.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                closeWeightModal();
                location.reload();
            } else {
                alert(data.error || 'Failed to save entry');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Failed to save entry');
        });
    });
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeWeightModal();
    }
});

// Date/Time formatting helpers
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatTime(date) {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

// Detect browser timezone
function detectTimezone() {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (e) {
        return 'UTC';
    }
}

// Export these functions globally
window.openAddWeightModal = openAddWeightModal;
window.openEditWeightModal = openEditWeightModal;
window.closeWeightModal = closeWeightModal;
