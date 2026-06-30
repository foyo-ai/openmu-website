<?php

return [
    'list_title' => 'Nhân vật của tôi',
    'empty'      => 'Bạn chưa có nhân vật nào. Hãy tạo nhân vật trong game!',
    'view'       => 'Xem',
    'back'       => 'Quay lại danh sách',

    // columns
    'class'  => 'Lớp',
    'name'   => 'Tên',
    'resets' => 'Reset',
    'level'  => 'Cấp',
    'points' => 'Điểm cộng',
    'master_points' => 'Điểm Master',
    'kills'  => 'Điểm PK',
    'actions' => 'Thao tác',

    // actions
    'add_points' => 'Cộng điểm',
    'rename'     => 'Đổi tên',
    'reset'      => 'Reset',
    'clear_pk'   => 'Xoá PK',
    'unstick'    => 'Về thị trấn',
    'delete'     => 'Xoá nhân vật',
    'save'       => 'Lưu',
    'cancel'     => 'Huỷ',

    'rename_label'   => 'Tên mới (tối đa 10 ký tự, chữ và số)',
    'reset_confirm'  => 'Reset nhân vật này? Cấp độ sẽ về 1.',
    'clearpk_confirm' => 'Xoá trạng thái PK của nhân vật này?',
    'unstick_confirm' => 'Di chuyển nhân vật về thị trấn an toàn?',

    'delete_title'   => 'Xoá nhân vật',
    'delete_warning' => 'Nhân vật sẽ bị khoá và giải phóng tên. Nhập mã bảo mật để xác nhận.',
    'security_code'  => 'Mã bảo mật',

    'offline_note'   => 'Nhân vật phải đang offline để thực hiện thao tác.',
    'stale_note'     => 'Số liệu được cập nhật khi bạn đăng xuất khỏi game. Khi đang online, dữ liệu có thể bị trễ.',

    // success
    'renamed'    => 'Đã đổi tên nhân vật.',
    'reset_done' => 'Đã reset nhân vật.',
    'pk_cleared' => 'Đã xoá trạng thái PK.',
    'unstuck'    => 'Đã đưa nhân vật về thị trấn.',
    'deleted'    => 'Đã xoá nhân vật.',

    // errors
    'err_name_length' => 'Tên phải từ 1 đến 10 ký tự.',
    'err_name_chars'  => 'Tên chỉ được chứa chữ cái và số.',
    'err_name_taken'  => 'Tên này đã được sử dụng.',
    'err_reset_level' => 'Cần đạt cấp :level để reset.',
    'err_safezone'    => 'Không tìm thấy bản đồ an toàn.',
    'err_online'      => 'Nhân vật đang online. Hãy đăng xuất khỏi game trước.',
    'err_security_code' => 'Mã bảo mật không đúng.',
];
