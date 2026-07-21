<?php
/**
 * Danh mục chức năng PCCM – cập nhật tại đây khi thêm tính năng mới.
 * Trang chủ sẽ tự hiển thị theo danh sách này.
 */
function get_app_info() {
    return [
        'name' => 'PCCM',
        'full_name' => 'Phân công Chuyên môn',
        'year' => '2026 – 2027',
        'author' => 'Thầy giáo Nguyễn Hồng Dân',
        'school' => 'Trường Nội trú Xin Man',
        'tagline' => 'Công cụ phân công giảng dạy & kiêm nhiệm hiện đại, trực quan',
        'version' => '2.1',
    ];
}

function get_app_features() {
    return [
        [
            'icon' => 'bi-clipboard-check',
            'title' => 'Bàn làm việc phân công',
            'desc' => 'Một màn hình cố định: thêm phân công phía trên, bảng theo dõi phía dưới. Thêm xong tự cuộn xuống bảng.',
            'items' => [
                'Thêm dạy môn (1 lớp hoặc nhiều lớp cùng lúc)',
                'Thêm kiêm nhiệm (GVCN, TTCM…) với số tiết lẻ đến 0,01',
                'Số tiết tự động theo môn + khối',
                'Popup cảnh báo trùng – tùy chọn thay thế GV cũ',
            ],
        ],
        [
            'icon' => 'bi-table',
            'title' => 'Bảng theo dõi trực quan',
            'desc' => 'Xem tải dạy / kiêm nhiệm / tổng tiết / định mức từng giáo viên.',
            'items' => [
                'Chip phân công có thể bấm để sửa (GV, môn, lớp, tiết)',
                'Kéo-thả chip sang GV khác để chuyển phân công',
                'Lọc nhanh theo tên / môn / lớp',
                'Xóa nhanh bằng nút × trên chip',
            ],
        ],
        [
            'icon' => 'bi-search',
            'title' => 'Rà soát & cảnh báo',
            'desc' => 'Mở popup không làm thay đổi màn hình chính.',
            'items' => [
                'Phát hiện trùng môn + lớp',
                'Danh sách thiếu môn + lớp chưa giao',
                'GV thiếu / thừa tiết so với định mức',
                'Cảnh báo đỏ ngay trên bàn làm việc',
            ],
        ],
        [
            'icon' => 'bi-arrow-left-right',
            'title' => 'Đổi chéo giáo viên',
            'desc' => 'Hoán đổi toàn bộ phân công dạy và kiêm nhiệm giữa 2 GV.',
            'items' => [
                'Xác nhận trước khi thực hiện',
                'Gợi ý tạo phiên bản Kết quả trước khi đổi hàng loạt',
            ],
        ],
        [
            'icon' => 'bi-people',
            'title' => 'Quản lý giáo viên',
            'desc' => 'Hồ sơ đầy đủ cho từng GV.',
            'items' => [
                'Chuyên môn (Toán, Ngữ văn… – chọn nhiều môn)',
                'Tổ KHXH / KHTN',
                'Cấp THCS / THPT / Tập sự',
                'Định mức tiết/tuần riêng THCS & THPT',
                'Sắp xếp theo tên, lọc đa tiêu chí',
            ],
        ],
        [
            'icon' => 'bi-journal-text',
            'title' => 'Môn học · Lớp · Kiêm nhiệm',
            'desc' => 'Cấu hình dữ liệu gốc linh hoạt.',
            'items' => [
                'Số tiết chuẩn theo từng khối lớp',
                'Danh sách lớp THCS / THPT',
                'Chức vụ kiêm nhiệm + số tiết chuẩn',
            ],
        ],
        [
            'icon' => 'bi-folder2-open',
            'title' => 'Phiên bản Kết quả',
            'desc' => 'Lưu nhiều lần phân công (lần 1, lần 2…).',
            'items' => [
                'Tạo phiên bản mới, sao chép từ lần trước',
                'Xem / so sánh từng phiên bản',
                'Ai cũng xem được Kết quả (không cần đăng nhập)',
            ],
        ],
        [
            'icon' => 'bi-printer',
            'title' => 'Xuất bảng in',
            'desc' => 'In phân công theo mẫu nhà trường / Sở GD&ĐT.',
            'items' => [
                'Tiêu đề hành chính chuẩn',
                'Bảng theo giáo viên: dạy môn + kiêm nhiệm + tổng tiết',
            ],
        ],
        [
            'icon' => 'bi-shield-lock',
            'title' => 'Bảo mật & phân quyền',
            'desc' => 'Tách rõ xem kết quả và chỉnh sửa.',
            'items' => [
                'Đăng nhập quản trị để thêm / sửa / xóa',
                'Khách chỉ xem Kết quả',
                'Thông báo dạng toast góc dưới phải',
            ],
        ],
    ];
}
