// API Helper Functions
const API = {
    async request(endpoint, options = {}) {
        const method = options.method || 'GET';
        const headers = {
            'Content-Type': 'application/json',
            ...options.headers
        };

        const config = {
            method,
            headers,
            ...options
        };

        if (method !== 'GET' && options.body) {
            config.body = JSON.stringify(options.body);
        }

        try {
            const response = await fetch(endpoint, config);
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'API Error');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    auth: {
        login(email, password, remember_me = false) {
            return API.request('/grade-hub-php/public/api/auth.php?action=login', {
                method: 'POST',
                body: { email, password, remember_me }
            });
        },
        logout() {
            return API.request('/grade-hub-php/public/api/auth.php?action=logout', {
                method: 'POST'
            });
        },
        register(data) {
            return API.request('/grade-hub-php/public/api/auth.php?action=register', {
                method: 'POST',
                body: data
            });
        },
        getProfile() {
            return API.request('/grade-hub-php/public/api/auth.php?action=profile');
        }
    },

    grades: {
        list(filters = {}) {
            const params = new URLSearchParams(filters).toString();
            return API.request('/grade-hub-php/public/api/grades.php?action=list' + (params ? '&' + params : ''));
        },
        get(id) {
            return API.request('/grade-hub-php/public/api/grades.php?action=get&id=' + id);
        },
        update(id, data) {
            return API.request('/grade-hub-php/public/api/grades.php?action=update', {
                method: 'POST',
                body: { id, ...data }
            });
        },
        submit(id) {
            return API.request('/grade-hub-php/public/api/grades.php?action=submit', {
                method: 'POST',
                body: { id }
            });
        },
        approve(id) {
            return API.request('/grade-hub-php/public/api/grades.php?action=approve', {
                method: 'POST',
                body: { id }
            });
        },
        reject(id) {
            return API.request('/grade-hub-php/public/api/grades.php?action=reject', {
                method: 'POST',
                body: { id }
            });
        },
        getPending() {
            return API.request('/grade-hub-php/public/api/grades.php?action=pending');
        }
    },

    subjects: {
        list(filters = {}) {
            const params = new URLSearchParams(filters).toString();
            return API.request('/grade-hub-php/public/api/subjects.php?action=list' + (params ? '&' + params : ''));
        },
        get(id) {
            return API.request('/grade-hub-php/public/api/subjects.php?action=get&id=' + id);
        },
        getFaculty() {
            return API.request('/grade-hub-php/public/api/subjects.php?action=faculty');
        },
        create(data) {
            return API.request('/grade-hub-php/public/api/subjects.php?action=create', {
                method: 'POST',
                body: data
            });
        }
    },

    corrections: {
        list(status = null) {
            let url = '/grade-hub-php/public/api/corrections.php?action=list';
            if (status) url += '&status=' + status;
            return API.request(url);
        },
        request(data) {
            return API.request('/grade-hub-php/public/api/corrections.php?action=request', {
                method: 'POST',
                body: data
            });
        },
        approve(id, remarks = '') {
            return API.request('/grade-hub-php/public/api/corrections.php?action=approve', {
                method: 'POST',
                body: { id, remarks }
            });
        },
        reject(id, remarks = '') {
            return API.request('/grade-hub-php/public/api/corrections.php?action=reject', {
                method: 'POST',
                body: { id, remarks }
            });
        }
    },

    dashboard: {
        getStats() {
            return API.request('/grade-hub-php/public/api/dashboard.php?action=dashboard');
        },
        getRecent() {
            return API.request('/grade-hub-php/public/api/dashboard.php?action=recent');
        }
    }
};

// Utility Functions
const Utils = {
    showNotification(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`);
        // You can replace this with a toast library like Sonner
        alert(message);
    },

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString();
    },

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleString();
    },

    redirectTo(url) {
        window.location.href = url;
    },

    getCurrentUser() {
        // This would typically come from a session endpoint
        return API.auth.getProfile();
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { API, Utils };
}
