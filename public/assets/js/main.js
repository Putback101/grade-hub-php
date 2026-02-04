// Main application initialization and helper functions

document.addEventListener('DOMContentLoaded', function() {
    // Initialize application
    initializeApp();
});

function initializeApp() {
    // Setup CSRF token if available
    setupCSRFToken();

    // Add active class to current page nav item
    setActiveNavigation();

    // Setup global event listeners
    setupGlobalListeners();
}

function setupCSRFToken() {
    // Add CSRF token to all fetch requests if available
    const token = document.querySelector('meta[name="csrf-token"]');
    if (token) {
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            if (args[1] && !args[1].headers) {
                args[1].headers = {};
            }
            if (args[1]) {
                args[1].headers['X-CSRF-Token'] = token.content;
            }
            return originalFetch.apply(this, args);
        };
    }
}

function setActiveNavigation() {
    const current = window.location.pathname;
    const navLinks = document.querySelectorAll('.sidebar-nav a');

    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (current === href || current.startsWith(href + '/')) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

function setupGlobalListeners() {
    // Logout link
    const logoutLink = document.querySelector('a[href="/logout"]');
    if (logoutLink) {
        logoutLink.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '/logout';
            }
        });
    }

    // Form submission handlers
    document.addEventListener('submit', handleFormSubmit);
}

function handleFormSubmit(e) {
    // This can be extended for global form handling
}

// Helper function to handle API responses
function handleAPIResponse(response, successCallback, errorCallback) {
    if (response.success) {
        if (successCallback) {
            successCallback(response.data);
        }
    } else {
        if (errorCallback) {
            errorCallback(response.message);
        } else {
            UI.showAlert(response.message || 'An error occurred', 'error');
        }
    }
}

// Helper function for async operations with loading state
async function withLoading(asyncFn, loadingElement = null) {
    try {
        if (loadingElement) {
            UI.showLoading(loadingElement);
        }
        const result = await asyncFn();
        return result;
    } catch (error) {
        UI.showAlert(error.message || 'An error occurred', 'error');
        throw error;
    }
}

// Export for global use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initializeApp, handleAPIResponse, withLoading };
}
