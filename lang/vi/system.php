<?php

return [
    // Settings → System
    'settings_title' => 'Hệ thống',
    'settings_description' => 'Trang chủ và cập nhật phần mềm',
    'homepage_mode' => 'Trang chủ (tên miền gốc)',
    'homepage_mode_description' => 'Khi mở tên miền gốc (ví dụ xxx.com): hiện trang web, hoặc vào thẳng trang quản trị.',
    'homepage_website' => 'Hiện trang web',
    'homepage_admin' => 'Vào thẳng trang quản trị',

    // Updater
    'update' => 'Cập nhật hệ thống',
    'current_version' => 'Phiên bản hiện tại',
    'latest_version' => 'Phiên bản mới nhất',
    'check_now' => 'Kiểm tra bản mới',
    'checked_at' => 'Kiểm tra lúc :time',
    'up_to_date' => 'Hệ thống đang dùng phiên bản mới nhất.',
    'update_available' => 'Có phiên bản mới :version',
    'update_now' => 'Cập nhật lên :version',
    'release_notes' => 'Nội dung bản cập nhật',
    'published_at' => 'Phát hành :time',
    'package_size' => 'Dung lượng gói: :size',
    'check_failed' => 'Không kiểm tra được bản mới: :message',
    'confirm_update' => 'Cập nhật lên phiên bản :version? Hệ thống sẽ sao lưu, tạm bảo trì khoảng 1 phút rồi tự chạy lại.',
    'requirements' => 'Yêu cầu hệ thống',
    'requirement_ok' => 'Đạt',
    'requirement_fail' => 'Chưa đạt',
    'running' => 'Đang cập nhật, vui lòng không đóng trang…',
    'done' => 'Đã cập nhật lên phiên bản :version.',
    'failed' => 'Cập nhật thất bại: :message',
    'rolled_back' => 'Đã khôi phục mã nguồn về phiên bản :version.',
    'rollback' => 'Khôi phục bản trước (:version)',
    'confirm_rollback' => 'Khôi phục mã nguồn về :version? Dữ liệu không thay đổi.',
    'nothing_to_rollback' => 'Không có bản sao lưu để khôi phục.',
    'backups' => 'Bản sao lưu mã nguồn',
    'history' => 'Nhật ký cập nhật',
    'busy' => 'Đang có một lần cập nhật khác chạy.',
    'not_writable' => 'Không ghi được vào :path',
    'checksum_mismatch' => 'Mã kiểm tra (SHA-256) của gói không khớp — gói có thể bị lỗi khi tải.',
    'asset_missing' => 'Bản phát hành :version chưa có gói cài đặt.',
    'release_missing' => 'Không tìm thấy bản phát hành :version.',
    'not_newer' => 'Phiên bản :version không mới hơn phiên bản hiện tại.',

    'no_releases' => 'Chưa có bản phát hành nào trên GitHub.',
    'no_update_running' => 'Không có lần cập nhật nào đang chạy.',

    'stages' => [
        'download' => 'Tải gói cập nhật',
        'verify' => 'Kiểm tra gói và yêu cầu hệ thống',
        'install' => 'Sao lưu, cài đặt và cập nhật cơ sở dữ liệu',
        'finish' => 'Hoàn tất',
    ],
];
