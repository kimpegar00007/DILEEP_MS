<style>
.notification-dropdown {
    padding: 0;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(27, 122, 61, 0.12);
    border-radius: 0.75rem;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s;
    cursor: pointer;
}

.notification-item:hover {
    background-color: rgba(27, 122, 61, 0.04);
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item.severity-danger {
    border-left: 4px solid #dc3545;
}

.notification-item.severity-warning {
    border-left: 4px solid #ffc107;
}

.notification-item.severity-info {
    border-left: 4px solid #1B7A3D;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.85rem;
    color: #495057;
    margin-bottom: 0.25rem;
}

.notification-details {
    font-size: 0.75rem;
    color: #6c757d;
}

.notification-actions {
    margin-top: 0.5rem;
}

.notification-actions .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>
