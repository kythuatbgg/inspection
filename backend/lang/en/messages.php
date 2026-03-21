<?php

return [
    // Auth
    'unauthorized' => 'Unauthorized',
    'login_success' => 'Login successful',
    'logout_success' => 'Logout successful',
    'invalid_credentials' => 'Invalid username or password',

    // Generic CRUD
    'fetch_success' => 'Data fetched successfully',
    'create_success' => 'Created successfully',
    'update_success' => 'Updated successfully',
    'delete_success' => 'Deleted successfully',

    // User
    'user_list_success' => 'User list fetched successfully',
    'user_create_success' => 'User created successfully',
    'user_show_success' => 'User details fetched successfully',
    'user_update_success' => 'User updated successfully',
    'user_delete_success' => 'User deleted successfully',
    'user_cannot_delete_self' => 'You cannot delete your own account',
    'user_stats_success' => 'Statistics fetched successfully',

    // Batch
    'batch_create_success' => 'Batch created successfully.',
    'batch_update_success' => 'Batch updated successfully.',
    'batch_delete_success' => 'Batch deleted successfully.',
    'batch_cannot_edit_completed' => 'Cannot edit a completed batch.',
    'batch_has_data_force' => 'This batch has inspection data. Add ?force=true to delete.',
    'batch_closed' => 'Batch closed successfully.',
    'batch_reopened' => 'Batch reopened successfully.',
    'batch_approved' => 'Proposal approved successfully.',
    'batch_rejected' => 'Proposal rejected.',
    'batch_add_cabinets_success' => 'Cabinets added to batch.',
    'batch_remove_cabinet_success' => 'Cabinet removed from batch.',
    'batch_swap_cabinet_success' => 'Cabinet swapped successfully.',
    'inspector_role_required' => 'Assigned user must have the Inspector role.',

    // Cabinet
    'cabinet_create_success' => 'Cabinet created successfully',
    'cabinet_update_success' => 'Cabinet updated successfully',
    'cabinet_delete_success' => 'Cabinet deleted successfully',
    'import_completed' => 'Import completed: :success success, :failed failed',
    'missing_export_token' => 'Missing export token.',
    'import_result_expired' => 'Import result not found or has expired.',

    // Checklist
    'checklist_create_success' => 'Checklist created successfully.',
    'checklist_update_success' => 'Checklist updated successfully.',
    'checklist_delete_success' => 'Checklist deleted successfully.',
    'checklist_in_use_edit' => 'This checklist is used by inspection batches and cannot be edited. Please clone a new copy.',
    'checklist_in_use_delete' => 'This checklist is used by inspection batches and cannot be deleted.',
    'checklist_clone_success' => 'Checklist cloned successfully (:count items).',
    'checklist_in_use_add_item' => 'Checklist is in use, cannot add items.',
    'checklist_in_use_edit_item' => 'Checklist is in use, cannot edit items.',
    'checklist_in_use_delete_item' => 'Checklist is in use, cannot delete items.',
    'item_add_success' => 'Item added successfully.',
    'item_update_success' => 'Item updated successfully.',
    'item_delete_success' => 'Item deleted successfully.',

    // Inspection
    'inspection_not_found' => 'No inspection found for this plan',
    'cabinet_already_inspected' => 'This cabinet has already been inspected.',
    'inspection_save_success' => 'Inspection result saved successfully.',

    // Plan
    'plan_complete' => 'Marked as complete.',
    'batch_ended_cannot_review' => 'Cannot review - batch has ended.',
    'cabinet_not_inspected' => 'This cabinet has not been inspected yet.',

    // Upload
    'upload_success' => 'Image uploaded successfully',
    'no_image_provided' => 'No image file provided',

    // Sync
    'sync_success' => 'Synced :count inspections',

    // Profile
    'profile_update_success' => 'Profile updated successfully',
    'password_change_success' => 'Password changed successfully',
    'current_password_incorrect' => 'Current password is incorrect',
];
