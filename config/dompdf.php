<?php

// Cấu hình tùy chọn tạo và xuất tài liệu PDF bằng DOMPDF.

return [

    'show_warnings' => false,

    'public_path' => null,

    'convert_entities' => true,

    'options' => [

        'font_dir' => storage_path('fonts'),

        'font_cache' => storage_path('fonts'),

        'temp_dir' => sys_get_temp_dir(),

        // Giới hạn DOMPDF chỉ đọc tệp trong thư mục dự án.
        'chroot' => realpath(base_path()),

        'allowed_protocols' => [
            'data://' => ['rules' => []],
            'file://' => ['rules' => []],
            'http://' => ['rules' => []],
            'https://' => ['rules' => []],
        ],

        'artifactPathValidation' => null,

        'log_output_file' => null,

        'enable_font_subsetting' => false,

        'pdf_backend' => 'CPDF',

        'default_media_type' => 'screen',

        'default_paper_size' => 'a4',

        'default_paper_orientation' => 'portrait',

        'default_font' => 'DejaVu Sans',

        'dpi' => 96,

        // Giữ script và tải tài nguyên từ xa tắt với nội dung không tin cậy.
        'enable_php' => false,

        'enable_javascript' => true,

        'enable_remote' => false,

        'allowed_remote_hosts' => null,

        'font_height_ratio' => 1.1,

        'enable_html5_parser' => true,
    ],

];
