<?php

return [
    'admin' => [
        [
            'name' => 'dashboard',
            'title' => 'Dashboard',
            'icon' => 'bi bi-grid',
            'route' => 'admin.index',
            'submenu' => [],
            'number' => 1
        ],
        [
            'name' => 'banner',
            'title' => 'Banner hình ảnh',
            'icon' => 'bi bi-grid',
            'route' => 'admin.banner.index',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'trademark',
            'title' => 'Thương hiệu',
            'icon' => 'bi bi-grid',
            'route' => 'admin.trademark.index',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'category',
            'title' => 'Danh mục sản phẩm',
            'icon' => 'bi bi-grid',
            'route' => 'admin.category.index',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'product',
            'title' => 'Quản lý sản phẩm',
            'icon' => 'bi bi-grid',
            'route' => null,
            'submenu' => [
                [
                    'title' => 'Sản phẩm chưa duyệt',
                    'route' => 'admin.products.approved-not',
                    'name' => 'not_yet_approved'
                ],
                [
                    'title' => 'Sản phẩm đã duyệt',
                    'route' => 'admin.products.approved',
                    'name' => 'approved'
                ],
            ],
            'number' => 2
        ],
        [
            'name' => 'request',
            'title' => 'Quản lý yêu cầu',
            'icon' => 'bi bi-grid',
            'route' => null,
            'submenu' => [
                [
                    'title' => 'Yêu cầu chưa duyệt',
                    'route' => 'admin.request.approved-not',
                    'name' => 'not_yet_approved'
                ],
                [
                    'title' => 'Yêu cầu đã duyệt',
                    'route' => 'admin.request.approved',
                    'name' => 'approved'
                ],
            ],
            'number' => 2
        ],
        [
            'name' => 'order',
            'title' => 'Quản lý đơn hàng',
            'icon' => 'bi bi-grid',
            'route' => 'admin.order.index',
            'parameters' => ['status' => 'all'],
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'user',
            'title' => 'Quản lý người dùng',
            'icon' => 'bi bi-grid',
            'route' => 'admin.get-user',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'shop',
            'title' => 'Quản lý shop',
            'icon' => 'bi bi-grid',
            'route' => 'admin.get-shop',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'setting',
            'title' => 'Cài đặt chung',
            'icon' => 'bi bi-grid',
            'route' => 'admin.setting.index',
            'submenu' => [],
            'number' => 2
        ],
        [
            'name' => 'footer',
            'title' => 'Bài viết footer',
            'icon' => 'bi bi-grid',
            'route' => 'admin.footer.index',
            'submenu' => [],
            'number' => 2
        ],
]
];
