<?php

return [
    // Auth
    'unauthorized' => 'Không có quyền truy cập',
    'login_success' => 'Đăng nhập thành công',
    'logout_success' => 'Đăng xuất thành công',
    'invalid_credentials' => 'Tên đăng nhập hoặc mật khẩu không đúng',

    // Generic CRUD
    'fetch_success' => 'Lấy dữ liệu thành công',
    'create_success' => 'Tạo thành công',
    'update_success' => 'Cập nhật thành công',
    'delete_success' => 'Xóa thành công',

    // User
    'user_list_success' => 'Lấy danh sách người dùng thành công',
    'user_create_success' => 'Tạo người dùng thành công',
    'user_show_success' => 'Lấy thông tin người dùng thành công',
    'user_update_success' => 'Cập nhật người dùng thành công',
    'user_delete_success' => 'Xóa người dùng thành công',
    'user_cannot_delete_self' => 'Không thể xóa tài khoản của chính bạn',
    'user_stats_success' => 'Lấy thống kê thành công',

    // Batch
    'batch_create_success' => 'Tạo lô kiểm tra thành công.',
    'batch_update_success' => 'Cập nhật lô kiểm tra thành công.',
    'batch_delete_success' => 'Đã xóa lô kiểm tra thành công.',
    'batch_cannot_edit_completed' => 'Không thể sửa lô đã hoàn thành.',
    'batch_has_data_force' => 'Lô này đã có dữ liệu kiểm tra. Thêm ?force=true để xóa.',
    'batch_closed' => 'Đã đóng lô kiểm tra.',
    'batch_reopened' => 'Đã mở lại lô kiểm tra.',
    'batch_approved' => 'Đã duyệt đề xuất thành công.',
    'batch_rejected' => 'Đã từ chối đề xuất.',
    'batch_add_cabinets_success' => 'Đã thêm tủ cáp vào lô.',
    'batch_remove_cabinet_success' => 'Đã xóa tủ khỏi lô.',
    'batch_swap_cabinet_success' => 'Đã swap tủ cáp.',
    'inspector_role_required' => 'Người được giao phải có vai trò Inspector.',

    // Cabinet
    'cabinet_create_success' => 'Tạo tủ cáp thành công',
    'cabinet_update_success' => 'Cập nhật tủ cáp thành công',
    'cabinet_delete_success' => 'Xóa tủ cáp thành công',
    'import_completed' => 'Import hoàn thành: :success thành công, :failed thất bại',
    'missing_export_token' => 'Thiếu token export.',
    'import_result_expired' => 'Kết quả import không tồn tại hoặc đã hết hạn.',

    // Checklist
    'checklist_create_success' => 'Tạo checklist thành công.',
    'checklist_update_success' => 'Cập nhật checklist thành công.',
    'checklist_delete_success' => 'Đã xóa checklist thành công.',
    'checklist_in_use_edit' => 'Checklist đang được lô kiểm tra sử dụng, không thể sửa. Hãy clone ra bản mới.',
    'checklist_in_use_delete' => 'Checklist đang được lô kiểm tra sử dụng, không thể xóa.',
    'checklist_clone_success' => 'Đã clone checklist thành công (:count hạng mục).',
    'checklist_in_use_add_item' => 'Checklist đang được sử dụng, không thể thêm hạng mục.',
    'checklist_in_use_edit_item' => 'Checklist đang được sử dụng, không thể sửa hạng mục.',
    'checklist_in_use_delete_item' => 'Checklist đang được sử dụng, không thể xóa hạng mục.',
    'item_add_success' => 'Đã thêm hạng mục thành công.',
    'item_update_success' => 'Cập nhật hạng mục thành công.',
    'item_delete_success' => 'Đã xóa hạng mục thành công.',

    // Inspection
    'inspection_not_found' => 'Không tìm thấy kết quả kiểm tra cho tủ này',
    'cabinet_already_inspected' => 'Tủ này đã được kiểm tra rồi.',
    'inspection_save_success' => 'Lưu kết quả kiểm tra thành công.',

    // Plan
    'plan_complete' => 'Đã đánh dấu hoàn thành.',
    'batch_ended_cannot_review' => 'Không thể duyệt - lô đã kết thúc.',
    'cabinet_not_inspected' => 'Tủ này chưa được kiểm tra.',

    // Upload
    'upload_success' => 'Tải ảnh lên thành công',
    'no_image_provided' => 'Chưa chọn ảnh để tải lên',

    // Sync
    'sync_success' => 'Đã đồng bộ :count kết quả kiểm tra',

    // Profile
    'profile_update_success' => 'Cập nhật hồ sơ thành công',
    'password_change_success' => 'Đổi mật khẩu thành công',
    'current_password_incorrect' => 'Mật khẩu hiện tại không đúng',
];
