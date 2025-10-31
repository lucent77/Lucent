/**
 * Swissturn Tool Management System
 * Main JavaScript Functions
 */

// API base URL (from PHP constant)
const API_URL = window.location.origin + '/api';

// Utility: Show loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="flex justify-center items-center py-8"><div class="spinner"></div></div>';
    }
}

// Utility: Show error message
function showError(elementId, message) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = `<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-sm text-red-700">${message}</p></div>`;
    }
}

// Utility: Format date
function formatDate(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Utility: Format datetime
function formatDateTime(dateStr) {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Utility: Make API request
async function apiRequest(endpoint, options = {}) {
    try {
        const response = await fetch(API_URL + endpoint, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'API request failed');
        }

        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Utility: Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 fade-in ${
        type === 'success' ? 'bg-green-600' :
        type === 'error' ? 'bg-red-600' :
        'bg-blue-600'
    }`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Utility: Confirm dialog
function confirmAction(message) {
    return confirm(message);
}

// Utility: Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Utility: Get status badge HTML
function getStatusBadge(status) {
    const badges = {
        'active': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>',
        'near_expiry': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">Near Expiry</span>',
        'expired': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Expired</span>',
        'needs_reorder': '<span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Needs Reorder</span>'
    };
    return badges[status] || `<span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">${status}</span>`;
}

// Utility: Get priority badge HTML
function getPriorityBadge(priority) {
    const badges = {
        'low': '<span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">Low</span>',
        'medium': '<span class="px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800">Medium</span>',
        'high': '<span class="px-2 py-1 text-xs font-semibold rounded bg-orange-100 text-orange-800">High</span>',
        'urgent': '<span class="px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-800">Urgent</span>'
    };
    return badges[priority] || priority;
}

// Auto-dismiss alerts
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        const alerts = document.querySelectorAll('[id$="-alert"]');
        alerts.forEach(alert => {
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        });
    }, 5000);
});

// Mobile menu toggle (if needed)
function toggleMobileMenu() {
    const sidebar = document.querySelector('aside');
    if (sidebar) {
        sidebar.classList.toggle('hidden');
    }
}

// Export functions for use in other scripts
window.app = {
    showLoading,
    showError,
    formatDate,
    formatDateTime,
    apiRequest,
    showToast,
    confirmAction,
    escapeHtml,
    getStatusBadge,
    getPriorityBadge
};
