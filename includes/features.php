<?php
/**
 * Danh mục chức năng Chuyên môn – cập nhật khi thêm tính năng mới.
 */
function get_app_info() {
    return [
        'name' => 'Chuyên môn',
        'full_name' => 'Ứng dụng Chuyên môn',
        'year' => '2025 – 2026',
        'author' => 'Thầy giáo Nguyễn Hồng Dân',
        'school' => 'Trường PTDTNT THCS&THPT Xín Mần',
        'tagline' => 'Phân công · Kế hoạch · Báo cáo · Thống kê',
        'version' => '3.0',
    ];
}

function get_app_features() {
    return [
        [
            'icon' => 'bi-clipboard-check',
            'title' => 'PCCM – Phân công chuyên môn',
            'desc' => 'Tổng quan, phân công, danh sách, kết quả, nhập liệu (GV · môn · lớp · kiêm nhiệm).',
            'items' => [
                'Bàn làm việc: thêm PC, đổi chéo, rà soát',
                'Bảng theo dõi chip, kéo-thả, lọc',
                'Phiên bản Kết quả · xuất bảng in',
            ],
        ],
        [
            'icon' => 'bi-calendar2-week',
            'title' => 'Kế hoạch',
            'desc' => 'Quản lý văn bản kế hoạch, thông báo chuyên môn, chỉ tiêu.',
            'items' => [
                'Tải file hoặc chèn link',
                'Ghi nội dung / ghi chú',
                'Sửa · xóa từng mục',
            ],
        ],
        [
            'icon' => 'bi-file-earmark-text',
            'title' => 'Báo cáo',
            'desc' => 'Báo cáo tháng, tiến độ CT, NCBH, STEAM, KHKT, CLB, dự giờ, kỳ thi.',
            'items' => [
                'Gõ nội dung trực tiếp',
                'Tải file / chèn link',
                'Lọc theo từng loại báo cáo',
            ],
        ],
        [
            'icon' => 'bi-bar-chart-line',
            'title' => 'Thống kê',
            'desc' => 'Thống kê phân công theo lớp, khối, môn; thiếu/lệch tiết; biểu đồ tải GV.',
            'items' => [
                'So sánh với tiết chuẩn chương trình',
                'Lọc nhanh',
                'Theo dõi phủ tiết toàn trường',
            ],
        ],
    ];
}
