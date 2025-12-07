import { Controller } from '@hotwired/stimulus';

/**
 * Calendar Stimulus Controller
 *
 * Provides interactive functionality for the calendar bundle including:
 * - Form validation before Turbo submission
 * - Keyboard shortcuts
 * - Modal management
 * - Responsive interactions
 */
export default class extends Controller {
    static targets = ['day', 'modal', 'eventList', 'form', 'startDate', 'endDate', 'title'];

    static values = {
        newEventUrl: String,
        editEventUrl: String,
        selectedDate: String
    };

    connect() {
        this.selectedDayElement = null;
        this.loadingToastId = null;
        this.bindKeyboardShortcuts();
        this.bindFormValidation();
        this.bindTurboNotifications();
        console.log('Calendar controller connected');
    }

    disconnect() {
        this.unbindKeyboardShortcuts();
        this.unbindFormValidation();
        this.unbindTurboNotifications();
        console.log('Calendar controller disconnected');
    }

    /**
     * Bind form validation to intercept Turbo submissions
     */
    bindFormValidation() {
        // Store bound handler for later cleanup
        this.validateFormHandler = this.validateForm.bind(this);
        document.addEventListener('turbo:submit-start', this.validateFormHandler);
    }

    /**
     * Unbind form validation
     */
    unbindFormValidation() {
        if (this.validateFormHandler) {
            document.removeEventListener('turbo:submit-start', this.validateFormHandler);
            this.validateFormHandler = null;
        }
    }

    /**
     * Validate form before Turbo submits it
     */
    validateForm(event) {
        const form = event.target;

        // Only validate calendar event forms
        if (!form.closest('#event-modal')) return;

        const errors = this.getValidationErrors(form);

        if (errors.length > 0) {
            event.preventDefault();
            this.showValidationErrors(form, errors);
        } else {
            this.clearValidationErrors(form);
        }
    }

    /**
     * Get all validation errors for the form
     */
    getValidationErrors(form) {
        const errors = [];

        // Get form fields
        const title = form.querySelector('[name*="[title]"]');
        const startDate = form.querySelector('[name*="[startDate]"]');
        const endDate = form.querySelector('[name*="[endDate]"]');

        // Validate title
        if (title && !title.value.trim()) {
            errors.push({
                field: title,
                message: 'Le titre est obligatoire'
            });
        }

        if (title && title.value.length > 255) {
            errors.push({
                field: title,
                message: 'Le titre ne peut pas dépasser 255 caractères'
            });
        }

        // Validate dates
        if (startDate && !startDate.value) {
            errors.push({
                field: startDate,
                message: 'La date de début est obligatoire'
            });
        }

        if (endDate && !endDate.value) {
            errors.push({
                field: endDate,
                message: 'La date de fin est obligatoire'
            });
        }

        // Validate date order
        if (startDate && endDate && startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);

            if (end < start) {
                errors.push({
                    field: endDate,
                    message: 'La date de fin doit être après la date de début'
                });
            }
        }

        // Validate color format if present
        const color = form.querySelector('[name*="[color]"]');
        if (color && color.value && !this.isValidHexColor(color.value)) {
            errors.push({
                field: color,
                message: 'Format de couleur invalide (ex: #3788d8)'
            });
        }

        return errors;
    }

    /**
     * Check if color is valid hex format
     */
    isValidHexColor(color) {
        return /^#[0-9A-Fa-f]{6}$/.test(color);
    }

    /**
     * Display validation errors on the form
     */
    showValidationErrors(form, errors) {
        // Clear previous errors
        this.clearValidationErrors(form);

        errors.forEach(error => {
            // Add error class to field
            error.field.classList.add('is-invalid');

            // Create error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = error.message;

            // Insert after field
            error.field.parentNode.appendChild(errorDiv);
        });

        // Focus first error field
        if (errors.length > 0) {
            errors[0].field.focus();
        }

        // Show general error message
        this.showFlashMessage('Veuillez corriger les erreurs dans le formulaire', 'danger');
    }

    /**
     * Clear all validation errors from form
     */
    clearValidationErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });

        form.querySelectorAll('.invalid-feedback').forEach(error => {
            error.remove();
        });
    }

    /**
     * Show a flash message
     */
    showFlashMessage(message, type = 'info') {
        const flashContainer = document.getElementById('flash-messages');
        if (!flashContainer) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        flashContainer.appendChild(alert);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }

    /**
     * Show a toast notification (more elegant than alerts)
     */
    showToast(message, type = 'success', icon = null) {
        // Create toast container if not exists
        let toastContainer = document.getElementById('calendar-toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'calendar-toast-container';
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '1100';
            document.body.appendChild(toastContainer);
        }

        // Determine icon based on type
        const icons = {
            success: '<i class="bi bi-check-circle-fill text-success me-2"></i>',
            error: '<i class="bi bi-x-circle-fill text-danger me-2"></i>',
            warning: '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>',
            info: '<i class="bi bi-info-circle-fill text-info me-2"></i>',
            loading: '<div class="spinner-border spinner-border-sm text-primary me-2" role="status"><span class="visually-hidden">Loading...</span></div>'
        };

        const toastIcon = icon || icons[type] || icons.info;

        // Create toast element
        const toastId = `toast-${Date.now()}`;
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = 'toast align-items-center border-0';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        // Set background based on type
        const bgClasses = {
            success: 'bg-success text-white',
            error: 'bg-danger text-white',
            warning: 'bg-warning text-dark',
            info: 'bg-info text-white',
            loading: 'bg-light text-dark'
        };

        toast.classList.add(...(bgClasses[type] || bgClasses.info).split(' '));

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center">
                    ${toastIcon}
                    <span>${message}</span>
                </div>
                <button type="button" class="btn-close ${type === 'warning' || type === 'loading' ? '' : 'btn-close-white'} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        // Initialize and show Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: type !== 'loading',
            delay: type === 'error' ? 6000 : 4000
        });
        bsToast.show();

        // Remove from DOM after hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });

        return toastId;
    }

    /**
     * Hide a specific toast (useful for loading toasts)
     */
    hideToast(toastId) {
        const toast = document.getElementById(toastId);
        if (toast) {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
        }
    }

    /**
     * Listen for Turbo events to show notifications
     */
    bindTurboNotifications() {
        // Store bound handlers for later cleanup
        this.submitStartHandler = (event) => {
            const form = event.target;
            if (form.closest('#event-modal')) {
                this.loadingToastId = this.showToast('Enregistrement en cours...', 'loading');
            }
        };

        this.submitEndHandler = (event) => {
            if (this.loadingToastId) {
                this.hideToast(this.loadingToastId);
                this.loadingToastId = null;
            }
        };

        this.streamRenderHandler = (event) => {
            const action = event.target.getAttribute('action');
            const target = event.target.getAttribute('target');

            // Detect what action was performed
            if (target === 'event-modal' && action === 'update') {
                // Modal closed = action successful
                setTimeout(() => {
                    // Check what type of operation by looking at other streams
                    const streams = document.querySelectorAll('turbo-stream');
                    streams.forEach(stream => {
                        const streamAction = stream.getAttribute('action');
                        if (streamAction === 'append') {
                            this.showToast('Événement créé avec succès !', 'success');
                        } else if (streamAction === 'replace') {
                            this.showToast('Événement modifié avec succès !', 'success');
                        } else if (streamAction === 'remove') {
                            this.showToast('Événement supprimé !', 'success');
                        }
                    });
                }, 100);
            }
        };

        this.frameMissingHandler = (event) => {
            this.showToast('Erreur de chargement. Veuillez réessayer.', 'error');
        };

        this.fetchErrorHandler = (event) => {
            if (this.loadingToastId) {
                this.hideToast(this.loadingToastId);
            }
            this.showToast('Erreur de connexion. Vérifiez votre réseau.', 'error');
        };

        // Bind all handlers
        document.addEventListener('turbo:submit-start', this.submitStartHandler);
        document.addEventListener('turbo:submit-end', this.submitEndHandler);
        document.addEventListener('turbo:before-stream-render', this.streamRenderHandler);
        document.addEventListener('turbo:frame-missing', this.frameMissingHandler);
        document.addEventListener('turbo:fetch-request-error', this.fetchErrorHandler);
    }

    /**
     * Unbind Turbo notification handlers
     */
    unbindTurboNotifications() {
        if (this.submitStartHandler) {
            document.removeEventListener('turbo:submit-start', this.submitStartHandler);
            this.submitStartHandler = null;
        }
        if (this.submitEndHandler) {
            document.removeEventListener('turbo:submit-end', this.submitEndHandler);
            this.submitEndHandler = null;
        }
        if (this.streamRenderHandler) {
            document.removeEventListener('turbo:before-stream-render', this.streamRenderHandler);
            this.streamRenderHandler = null;
        }
        if (this.frameMissingHandler) {
            document.removeEventListener('turbo:frame-missing', this.frameMissingHandler);
            this.frameMissingHandler = null;
        }
        if (this.fetchErrorHandler) {
            document.removeEventListener('turbo:fetch-request-error', this.fetchErrorHandler);
            this.fetchErrorHandler = null;
        }
    }

    /**
     * Handle day cell click - select the day and optionally create new event
     */
    selectDay(event) {
        const dayCell = event.currentTarget;
        const date = dayCell.dataset.date;

        if (!date) return;

        // Remove previous selection
        if (this.selectedDayElement) {
            this.selectedDayElement.classList.remove('calendar-cell-selected');
        }

        // Add selection to current day
        dayCell.classList.add('calendar-cell-selected');
        this.selectedDayElement = dayCell;
        this.selectedDateValue = date;

        // Dispatch custom event for external listeners
        this.dispatch('daySelected', {
            detail: { date: date, element: dayCell }
        });
    }

    /**
     * Handle double-click on day to create new event
     */
    createEventOnDay(event) {
        const dayCell = event.currentTarget;
        const date = dayCell.dataset.date;

        if (!date) return;

        // Navigate to new event form with pre-filled date
        const newEventUrl = this.hasNewEventUrlValue
            ? `${this.newEventUrlValue}?date=${date}`
            : `/events/new?date=${date}`;

        // Use Turbo to load the form in the modal frame
        const modalFrame = document.getElementById('event-modal');
        if (modalFrame) {
            modalFrame.src = newEventUrl;
        } else {
            window.location.href = newEventUrl;
        }
    }

    /**
     * Handle event click to show event details or edit form
     */
    showEvent(event) {
        event.stopPropagation(); // Prevent day selection

        const eventElement = event.currentTarget;
        const eventId = eventElement.dataset.eventId;

        if (!eventId) return;

        // Navigate to edit form
        const editUrl = this.hasEditEventUrlValue
            ? this.editEventUrlValue.replace('__ID__', eventId)
            : `/events/${eventId}/edit`;

        // Use Turbo to load the form in the modal frame
        const modalFrame = document.getElementById('event-modal');
        if (modalFrame) {
            modalFrame.src = editUrl;
        } else {
            window.location.href = editUrl;
        }

        // Dispatch custom event
        this.dispatch('eventSelected', {
            detail: { eventId: eventId, element: eventElement }
        });
    }

    /**
     * Close the event modal
     */
    closeModal() {
        const modalFrame = document.getElementById('event-modal');
        if (modalFrame) {
            modalFrame.innerHTML = '';
            modalFrame.src = '';
        }

        this.dispatch('modalClosed');
    }

    /**
     * Refresh the calendar grid (useful after CRUD operations)
     */
    refresh() {
        const calendarFrame = document.getElementById('calendar');
        if (calendarFrame) {
            calendarFrame.reload();
        }
    }

    /**
     * Navigate to previous month
     */
    previousMonth() {
        const prevButton = document.querySelector('[data-action*="previous"]')
            || document.querySelector('.btn-outline-primary:first-child');
        if (prevButton) {
            prevButton.click();
        }
    }

    /**
     * Navigate to next month
     */
    nextMonth() {
        const nextButton = document.querySelector('[data-action*="next"]')
            || document.querySelector('.btn-outline-primary:last-child');
        if (nextButton) {
            nextButton.click();
        }
    }

    /**
     * Navigate to current month (today)
     */
    goToToday() {
        const todayButton = document.querySelector('[data-action*="today"]')
            || document.querySelector('.btn-outline-secondary');
        if (todayButton) {
            todayButton.click();
        }
    }

    /**
     * Bind keyboard shortcuts for navigation
     */
    bindKeyboardShortcuts() {
        this.keydownHandler = this.handleKeydown.bind(this);
        document.addEventListener('keydown', this.keydownHandler);
    }

    /**
     * Unbind keyboard shortcuts
     */
    unbindKeyboardShortcuts() {
        if (this.keydownHandler) {
            document.removeEventListener('keydown', this.keydownHandler);
            this.keydownHandler = null;
        }
    }

    /**
     * Handle keyboard navigation
     */
    handleKeydown(event) {
        // Don't handle if user is typing in an input
        if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
            return;
        }

        switch (event.key) {
            case 'ArrowLeft':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.previousMonth();
                }
                break;
            case 'ArrowRight':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.nextMonth();
                }
                break;
            case 't':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.goToToday();
                }
                break;
            case 'n':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    this.openNewEventForm();
                }
                break;
            case 'Escape':
                this.closeModal();
                break;
        }
    }

    /**
     * Open new event form
     */
    openNewEventForm() {
        const newEventButton = document.querySelector('[href*="new"]');
        if (newEventButton) {
            newEventButton.click();
        }
    }

    /**
     * Highlight events on hover
     */
    highlightEvent(event) {
        const eventElement = event.currentTarget;
        eventElement.classList.add('calendar-event-highlighted');
    }

    /**
     * Remove event highlight
     */
    unhighlightEvent(event) {
        const eventElement = event.currentTarget;
        eventElement.classList.remove('calendar-event-highlighted');
    }

    /**
     * Handle form submission success (for Turbo Stream responses)
     */
    handleFormSuccess(event) {
        this.closeModal();
        this.dispatch('eventSaved');
    }

    /**
     * Get the currently selected date
     */
    getSelectedDate() {
        return this.selectedDateValue || null;
    }

    /**
     * Programmatically select a specific date
     */
    selectDateProgrammatically(dateString) {
        const dayCell = this.element.querySelector(`[data-date="${dateString}"]`);
        if (dayCell) {
            this.selectDay({ currentTarget: dayCell });
        }
    }
}
