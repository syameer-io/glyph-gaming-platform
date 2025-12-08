{{--
    Role Permissions Modal Component
    Phase 5: Role Permissions UI

    Discord-style modal for editing role permissions with:
    - Permission categories (dynamically populated via JS)
    - Toggle switches for each permission
    - DANGEROUS badge for critical permissions
    - Dark theme matching Glyph design

    Usage:
    Include at the end of the settings page and control via JavaScript:
    - openPermissionsModal(roleId, roleName, roleColor, permissions)
    - closePermissionsModal()
    - saveRolePermissions()
--}}

<style>
/* Role Permissions Modal Styles */
.permissions-modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.85);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.permissions-modal {
    background-color: #1f1f23;
    border-radius: 12px;
    width: 90%;
    max-width: 560px;
    max-height: 85vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
    animation: modalSlideIn 0.2s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.permissions-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #303035;
}

.permissions-modal-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.permissions-modal-title h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #efeff1;
}

.permissions-modal-role-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
    color: #efeff1;
}

.permissions-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    background-color: transparent;
    border: none;
    color: #71717a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.permissions-modal-close:hover {
    background-color: #3f3f46;
    color: #efeff1;
}

.permissions-modal-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
}

.permission-category {
    margin-bottom: 24px;
}

.permission-category:last-child {
    margin-bottom: 0;
}

.permission-category h4 {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #71717a;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid #303035;
}

.permission-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    background-color: #18181b;
    transition: background-color 0.2s;
}

.permission-item:hover {
    background-color: #26262c;
}

.permission-item:last-child {
    margin-bottom: 0;
}

.permission-item.dangerous {
    border-left: 3px solid #f87171;
}

.permission-info {
    flex: 1;
    min-width: 0;
}

.permission-label {
    font-size: 14px;
    font-weight: 600;
    color: #efeff1;
    margin-bottom: 4px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.permission-description {
    font-size: 13px;
    color: #71717a;
    line-height: 1.4;
}

.danger-badge {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    background-color: rgba(248, 113, 113, 0.15);
    color: #f87171;
}

/* Toggle Switch Styles */
.permission-toggle {
    flex-shrink: 0;
    margin-top: 2px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 22px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #3f3f46;
    border-radius: 22px;
    transition: all 0.3s;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 3px;
    bottom: 3px;
    background-color: #efeff1;
    border-radius: 50%;
    transition: all 0.3s;
}

.toggle-switch input:checked + .toggle-slider {
    background-color: #9147ff;
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(18px);
}

.toggle-switch input:focus + .toggle-slider {
    box-shadow: 0 0 0 2px rgba(145, 71, 255, 0.3);
}

.permissions-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #303035;
    background-color: #18181b;
    border-radius: 0 0 12px 12px;
}

.permissions-modal-footer .btn {
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.permissions-modal-footer .btn-cancel {
    background-color: transparent;
    border: 1px solid #3f3f46;
    color: #efeff1;
}

.permissions-modal-footer .btn-cancel:hover {
    background-color: #3f3f46;
}

.permissions-modal-footer .btn-save {
    background-color: #9147ff;
    border: none;
    color: #ffffff;
}

.permissions-modal-footer .btn-save:hover {
    background-color: #7c3aed;
}

/* Scrollbar styling for modal body */
.permissions-modal-body::-webkit-scrollbar {
    width: 8px;
}

.permissions-modal-body::-webkit-scrollbar-track {
    background: #18181b;
    border-radius: 4px;
}

.permissions-modal-body::-webkit-scrollbar-thumb {
    background: #3f3f46;
    border-radius: 4px;
}

.permissions-modal-body::-webkit-scrollbar-thumb:hover {
    background: #52525b;
}

/* Loading state */
.permissions-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: #71717a;
}

.permissions-loading-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid #3f3f46;
    border-top-color: #9147ff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin-bottom: 12px;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}
</style>

<div id="role-permissions-modal" class="permissions-modal-overlay" onclick="if(event.target === this) closePermissionsModal()">
    <div class="permissions-modal" onclick="event.stopPropagation()">
        {{-- Modal Header --}}
        <div class="permissions-modal-header">
            <div class="permissions-modal-title">
                <span id="modal-role-badge" class="permissions-modal-role-badge">Role Name</span>
                <h3>Edit Permissions</h3>
            </div>
            <button class="permissions-modal-close" onclick="closePermissionsModal()" title="Close">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </button>
        </div>

        {{-- Hidden field for role ID --}}
        <input type="hidden" id="modal-role-id" value="">

        {{-- Modal Body --}}
        <div class="permissions-modal-body">
            {{-- Loading state --}}
            <div id="permissions-loading" class="permissions-loading" style="display: none;">
                <div class="permissions-loading-spinner"></div>
                <span>Loading permissions...</span>
            </div>

            {{-- Permission categories will be populated here by JavaScript --}}
            <div id="permission-categories">
                <div class="permissions-loading">
                    <div class="permissions-loading-spinner"></div>
                    <span>Loading permissions...</span>
                </div>
            </div>
        </div>

        {{-- Modal Footer --}}
        <div class="permissions-modal-footer">
            <button type="button" class="btn btn-cancel" onclick="closePermissionsModal()">Cancel</button>
            <button type="button" class="btn btn-save" onclick="saveRolePermissions()">Save Permissions</button>
        </div>
    </div>
</div>
