// UI Helper Functions

const UI = {
    showAlert(message, type = 'info', container = 'alerts') {
        const alertContainer = document.getElementById(container) || document.body;
        
        const alertTypeClasses = {
            'info': 'alert-info',
            'success': 'alert-success',
            'warning': 'alert-warning',
            'error': 'alert-danger'
        };

        const alert = document.createElement('div');
        alert.className = `alert ${alertTypeClasses[type] || alertTypeClasses['info']}`;
        alert.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="text-lg font-bold cursor-pointer">×</button>
            </div>
        `;

        alertContainer.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 5000);
    },

    showConfirm(message, onConfirm, onCancel) {
        if (confirm(message)) {
            onConfirm();
        } else if (onCancel) {
            onCancel();
        }
    },

    showLoading(element) {
        element.innerHTML = '<p class="text-center text-gray-500 py-4">Loading...</p>';
    },

    hideElement(element) {
        element.style.display = 'none';
    },

    showElement(element) {
        element.style.display = '';
    },

    addClass(element, className) {
        element.classList.add(className);
    },

    removeClass(element, className) {
        element.classList.remove(className);
    },

    toggleClass(element, className) {
        element.classList.toggle(className);
    },

    setHTML(element, html) {
        element.innerHTML = html;
    },

    setText(element, text) {
        element.textContent = text;
    },

    getValue(elementId) {
        const element = document.getElementById(elementId);
        return element ? element.value : '';
    },

    setValue(elementId, value) {
        const element = document.getElementById(elementId);
        if (element) {
            element.value = value;
        }
    },

    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return null;

        const formData = new FormData(form);
        const data = {};

        formData.forEach((value, key) => {
            data[key] = value;
        });

        return data;
    },

    clearForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    },

    disableButton(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            button.style.opacity = '0.5';
        }
    },

    enableButton(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = false;
            button.style.opacity = '1';
        }
    },

    createTable(data, columns) {
        if (!data || data.length === 0) {
            return '<p class="text-center text-gray-500 py-4">No data available</p>';
        }

        let html = '<table class="w-full"><thead class="bg-gray-50"><tr>';

        columns.forEach(column => {
            html += `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">${column.label}</th>`;
        });

        html += '</tr></thead><tbody class="divide-y divide-gray-200">';

        data.forEach(row => {
            html += '<tr>';
            columns.forEach(column => {
                const value = column.render ? column.render(row[column.field]) : row[column.field];
                html += `<td class="px-6 py-4 text-sm text-gray-900">${value}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';

        return html;
    },

    formatGradeStatus(status) {
        const statusMap = {
            'draft': { label: 'Draft', class: 'bg-gray-100 text-gray-800' },
            'submitted': { label: 'Submitted', class: 'bg-yellow-100 text-yellow-800' },
            'pending_approval': { label: 'Pending Approval', class: 'bg-blue-100 text-blue-800' },
            'approved': { label: 'Approved', class: 'bg-green-100 text-green-800' },
            'rejected': { label: 'Rejected', class: 'bg-red-100 text-red-800' }
        };

        const info = statusMap[status] || statusMap['draft'];
        return `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${info.class}">${info.label}</span>`;
    }
};

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { UI };
}
