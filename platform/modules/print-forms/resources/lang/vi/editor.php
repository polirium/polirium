<?php

return [
    'form_setting' => 'Mẫu in',
    'name' => 'Print Forms',
    'create' => 'Tạo mẫu in mới',
    'edit' => 'Chỉnh sửa mẫu in',

    // Editor UI
    'invoice' => 'Hoá đơn',
    'receipt' => 'Phiếu thu',
    'payment' => 'Phiếu chi',
    'form_list' => 'Danh sách mẫu in',
    'create_new_form' => 'Tạo mẫu mới',
    'form_name' => 'Tên mẫu',
    'status' => 'Trạng thái',
    'actions' => 'Thao tác',
    'active' => 'Active',
    'hidden' => 'Ẩn',
    'set_as_main' => 'Đặt làm mẫu chính',
    'delete_confirm' => 'Bạn có chắc muốn xóa mẫu in này?',
    'no_forms_yet' => 'Chưa có mẫu in nào',
    'no_forms_subtitle' => 'Nhấn nút "Tạo mẫu mới" để bắt đầu.',
    'preview' => 'Xem trước',
    'select_form_to_preview' => 'Chọn một mẫu để xem trước',
    'print_preview' => 'In thử',
    'select_form_desc' => 'Chọn mẫu in từ danh sách bên trái để xem trước, chỉnh sửa hoặc in thử.',
    'paper_size' => 'Khổ giấy',
    'size_a4' => 'A4 (210mm)',
    'size_a5' => 'A5 (148mm)',
    'size_k80' => 'K80 (80mm)',
    'size_k57' => 'K57 (57mm)',
    'formatting_guide' => 'Hướng dẫn định dạng',
    'formatting_guide_content' => '
        <ul>
            <li>Sử dụng <strong>table</strong> với width="100%" để dàn trang.</li>
            <li>Tránh dùng width cố định (px), hãy dùng phần trăm (%).</li>
            <li>Với khổ K80/K57, giữ nội dung đơn giản, ít cột.</li>
            <li>Sử dụng class <strong>.text-center</strong>, <strong>.text-right</strong> để căn lề.</li>
            <li>Dùng thẻ <strong>&lt;br&gt;</strong> để ngắt dòng thay vì &lt;p&gt; để tiết kiệm giấy.</li>
        </ul>
    ',
    'help' => 'Trợ giúp',

    // Variables
    'variable_list' => 'Danh sách biến',
    'click_to_copy' => 'Click để copy hoặc kéo thả vào trình soạn thảo.',
    'click_to_copy_tooltip' => 'Click để copy',
    'copied' => 'Đã copy',
    'content' => 'Nội dung',
    'edit_content' => 'Soạn thảo nội dung',
    'enter_form_name' => 'Nhập tên mẫu in',
    'form_name_label' => 'Tên mẫu in',
    'changes_applied' => '* Các thay đổi sẽ được áp dụng ngay cho mẫu in này.',
    'select_paper_size' => 'Chọn khổ giấy trước khi soạn thảo để có định dạng tốt nhất.',
    'help_content_guide' => 'Hướng dẫn nội dung',

    // Variable Groups
    'store_info' => 'Thông tin cửa hàng',
    'order_info' => 'Thông tin đơn hàng',
    'customer_info' => 'Thông tin khách hàng',
    'items_info' => 'Danh sách hàng hóa',
    'payment_info' => 'Thông tin thanh toán',

    // Variable Descriptions
    'var_store_logo' => 'Logo cửa hàng',
    'var_store_name' => 'Tên cửa hàng',
    'var_store_address' => 'Địa chỉ cửa hàng',
    'var_store_phone' => 'Điện thoại cửa hàng',
    'var_order_code' => 'Mã đơn hàng',
    'var_created_at' => 'Ngày tạo',
    'var_created_month' => 'Tháng tạo',
    'var_created_year' => 'Năm tạo',
    'var_note' => 'Ghi chú đơn hàng',
    'var_customer_name' => 'Tên khách hàng',
    'var_customer_phone' => 'SĐT khách hàng',
    'var_customer_address' => 'Địa chỉ khách',
    'var_customer_district' => 'Khu vực khách',
    'var_items_table' => 'Bảng hàng hóa',
    'var_total_amount' => 'Tổng tiền hàng',
    'var_discount_amount' => 'Chiết khấu (Tiền)',
    'var_discount_percent' => 'Chiết khấu (%)',
    'var_grand_total' => 'Tổng thanh toán',
    'var_customer_paid' => 'Tiền khách trả',
    'var_change_amount' => 'Tiền thừa',
];
