<?php
/**
 * Swissturn Tool Management System
 * Alert Messages Component
 */

if (!defined('SYSTEM_INIT')) {
    die('Direct access not permitted');
}

// Display success message
if (isset($_SESSION['success_message'])):
?>
<div id="success-alert" class="bg-green-50 border-l-4 border-green-400 p-4 mb-4 rounded">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm text-green-700"><?php echo e($_SESSION['success_message']); ?></p>
        </div>
        <div class="ml-3">
            <button onclick="closeAlert('success-alert')" class="text-green-500 hover:text-green-700">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
<?php
    unset($_SESSION['success_message']);
endif;

// Display error message
if (isset($_SESSION['error_message'])):
?>
<div id="error-alert" class="bg-red-50 border-l-4 border-red-400 p-4 mb-4 rounded">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm text-red-700"><?php echo e($_SESSION['error_message']); ?></p>
        </div>
        <div class="ml-3">
            <button onclick="closeAlert('error-alert')" class="text-red-500 hover:text-red-700">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
<?php
    unset($_SESSION['error_message']);
endif;

// Display warning message
if (isset($_SESSION['warning_message'])):
?>
<div id="warning-alert" class="bg-orange-50 border-l-4 border-orange-400 p-4 mb-4 rounded">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"/>
            </svg>
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm text-orange-700"><?php echo e($_SESSION['warning_message']); ?></p>
        </div>
        <div class="ml-3">
            <button onclick="closeAlert('warning-alert')" class="text-orange-500 hover:text-orange-700">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/>
                </svg>
            </button>
        </div>
    </div>
</div>
<?php
    unset($_SESSION['warning_message']);
endif;
?>

<script>
function closeAlert(id) {
    const alert = document.getElementById(id);
    if (alert) {
        alert.style.display = 'none';
    }
}

// Auto-dismiss alerts after 5 seconds
setTimeout(() => {
    ['success-alert', 'error-alert', 'warning-alert'].forEach(id => {
        closeAlert(id);
    });
}, 5000);
</script>
