<?php
    $views = [
        [
            "name" => "home",
            "title" => "Trang chủ",
            "debugOnly" => false
        ], 
        [
            "name" => "manage_org",
            "title" => "Quản lý tổ chức",
            "debugOnly" => false,
            "children" => [
                [
                    "name" => "organizations",
                    "title" => "Tổ chức",
                    "debugOnly" => false
                ]
            ]
        ],
        [
            "name" => "manage_roles",
            "title" => "Quản lý vai trò",
            "debugOnly" => false,
            "children" => [
                [
                    "name" => "system_role_groups",
                    "title" => "Nhóm vai trò",
                    "debugOnly" => false
                ],
                [
                    "name" => "system_roles",
                    "title" => "Vai trò",
                    "debugOnly" => false
                ]
            ]
        ],
        [
            "name" => "manage_users",
            "title" => "Quản lý người dùng",
            "debugOnly" => false,
            "children" => [
                [
                    "name" => "user_orgs",
                    "title" => "Người dùng",
                    "debugOnly" => false
                ]
            ]
        ],
        [
            "name" => "manage_permissions",
            "title" => "Phân quyền",
            "debugOnly" => false,
            "children" => [
                [
                    "name" => "org_permissions",
                    "title" => "Quản lý quyền của tổ chức",
                    "debugOnly" => false
                ],
                [
                    "name" => "system_role_group_permissions",
                    "title" => "Quyền theo nhóm vai trò",
                    "debugOnly" => false
                ],
                [
                    "name" => "system_role_permissions",
                    "title" => "Quyền theo vai trò",
                    "debugOnly" => false
                ]
            ]
        ]
    ];   
?>