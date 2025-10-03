INSERT INTO resource_types (name, translated_name, version) VALUES
("users", "Người dùng", 1),
("organizations", "Tổ chức", 1),
("system_role_groups", "Nhóm vai trò hệ thống", 1),
("system_roles", "Vai trò hệ thống", 1),
("system_role_group_roles", "Quan hệ vai trò - nhóm vai trò", 1),
("permissions", "Quyền", 1),
("org_permissions", "Quyền tổ chức", 1),
("system_role_group_permissions", "Quyền nhóm vai trò hệ thống", 1),
("system_role_permissions", "Quyền vai trò hệ thống", 1),
("user_system_roles", "Vai trò hệ thống của người dùng", 1), 
("user_orgs", "Tổ chức của người dùng", 1);

INSERT INTO actions (name, translated_name, description, need_specific_entity) VALUES
("view", "Xem", "Hành động xem", 0), 
("create", "Tạo", "Hành động tạo mới", 0),
("edit", "Chỉnh sửa", "Hành động chỉnh sửa", 1),
("delete", "Xóa", "Hành động xóa", 1),
("manage", "Quản lý", "Hành động quản lý (bao gồm tạo, chỉnh sửa, xóa)", 0);

INSERT INTO action_relations (parent_action_id, child_action_id) VALUES
((SELECT id FROM actions WHERE name = "create"), (SELECT id FROM actions WHERE name = "view")),
((SELECT id FROM actions WHERE name = "edit"), (SELECT id FROM actions WHERE name = "view")),
((SELECT id FROM actions WHERE name = "delete"), (SELECT id FROM actions WHERE name = "view")),
((SELECT id FROM actions WHERE name = "manage"), (SELECT id FROM actions WHERE name = "create")),
((SELECT id FROM actions WHERE name = "manage"), (SELECT id FROM actions WHERE name = "edit")),
((SELECT id FROM actions WHERE name = "manage"), (SELECT id FROM actions WHERE name = "delete"));

INSERT INTO users (name) VALUES 
("Đỗ Ba Chín");

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource User Đỗ Ba Chín", "Resource for User Đỗ Ba Chín", (SELECT id FROM resource_types WHERE name = "user"), (SELECT id FROM users WHERE name = "Đỗ Ba Chín"));

INSERT INTO org_types (name, is_exclusive) VALUES
("Đoàn trường", 1), 
("Liên chi đoàn", 1), 
("Chi đoàn", 1), 
("Đội", 0),
("Câu lạc bộ", 0);

INSERT INTO org_levels (name, level_index, description) VALUES 
("Trường", 1, "Cấp trường hoặc tương đương với cấp đoàn trường"), 
("Liên chi đoàn", 2, "Cấp khoa hoặc tương đương với cấp liên chi đoàn"), 
("Chi đoàn", 3, "Cấp lớp hoặc tương đương với cấp chi đoàn");

INSERT INTO organizations (name, parent_org_id, org_level, org_type_id) VALUES 
("Đoàn trường Đại học Sư phạm Hà Nội", NULL, (SELECT id FROM org_levels WHERE name = "Trường"), (SELECT id FROM org_types WHERE name = "Đoàn trường"));

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource Organization Đoàn trường Đại học Sư phạm Hà Nội", "Resource for Organization Đoàn trường Đại học Sư phạm Hà Nội", (SELECT id FROM resource_types WHERE name = "organizations"), (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội"));

INSERT INTO system_role_groups (name, description, org_id, parent_group_id) VALUES 
("Ban thường trực Đoàn trường", "Ban thường trực Đoàn trường Đại học Sư phạm Hà Nội", (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội"), NULL);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource System Role Group Ban thường trực Đoàn trường", "Resource for System Role Group Ban thường trực Đoàn trường", (SELECT id FROM resource_types WHERE name = "system_role_groups"), (SELECT id FROM system_role_groups WHERE name = "Ban thường trực Đoàn trường"));

INSERT INTO system_roles (name, description, org_id, level, available_slots) VALUES 
("Bí thư Đoàn trường", "Bí thư Đoàn trường Đại học Sư phạm Hà Nội", (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội"), 1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource System Role Bí thư Đoàn trường", "Resource for System Role Bí thư Đoàn trường", (SELECT id FROM resource_types WHERE name = "system_roles"), (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường"));

INSERt INTO system_role_group_roles (system_role_group_id, system_role_id) VALUES 
((SELECT id FROM system_role_groups WHERE name = "Ban thường trực Đoàn trường"), (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường"));

INSERT INTO permissions (action_id, resource_type_id)
SELECT a.id AS action_id, rt.id AS resource_type_id
FROM actions a
CROSS JOIN resource_types rt
LEFT JOIN permissions p 
    ON p.action_id = a.id AND p.resource_type_id = rt.id
WHERE p.id IS NULL;

INSERT INTO resources (name, description, resource_type_id, entity_id)
SELECT 
    CONCAT('Resource Permission ', p.id) AS name,
    CONCAT('Resource for Permission ID ', p.id) AS description,
    (SELECT id FROM resource_types WHERE name = 'permissions') AS resource_type_id,
    p.id AS entity_id
FROM permissions p
LEFT JOIN resources r
    ON r.resource_type_id = (SELECT id FROM resource_types WHERE name = 'permissions')
   AND r.entity_id = p.id
WHERE r.id IS NULL;

-- 1. Gán tất cả permission vào tổ chức Đoàn trường
INSERT INTO org_permissions (org_id, permission_id)
SELECT 
    (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội"),
    p.id
FROM permissions p
LEFT JOIN org_permissions op 
    ON op.permission_id = p.id
   AND op.org_id = (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội")
WHERE op.id IS NULL;

-- Tạo resource cho từng quan hệ org_permissions
INSERT INTO resources (name, description, resource_type_id, entity_id)
SELECT 
    CONCAT('Resource OrgPermission ', op.id),
    CONCAT('Resource for OrgPermission ID ', op.id),
    (SELECT id FROM resource_types WHERE name = 'org_permissions'),
    op.id
FROM org_permissions op
LEFT JOIN resources r
    ON r.resource_type_id = (SELECT id FROM resource_types WHERE name = 'org_permissions')
   AND r.entity_id = op.id
WHERE r.id IS NULL;


-- 2. Gán tất cả permission vào nhóm vai trò Ban thường trực Đoàn trường
INSERT INTO system_role_group_permissions (system_role_group_id, permission_id)
SELECT 
    (SELECT id FROM system_role_groups WHERE name = "Ban thường trực Đoàn trường"),
    p.id
FROM permissions p
LEFT JOIN system_role_group_permissions sgp
    ON sgp.permission_id = p.id
   AND sgp.system_role_group_id = (SELECT id FROM system_role_groups WHERE name = "Ban thường trực Đoàn trường")
WHERE sgp.id IS NULL;

-- Tạo resource cho từng quan hệ system_role_group_permissions
INSERT INTO resources (name, description, resource_type_id, entity_id)
SELECT 
    CONCAT('Resource SystemRoleGroupPermission ', sgp.id),
    CONCAT('Resource for SystemRoleGroupPermission ID ', sgp.id),
    (SELECT id FROM resource_types WHERE name = 'system_role_group_permissions'),
    sgp.id
FROM system_role_group_permissions sgp
LEFT JOIN resources r
    ON r.resource_type_id = (SELECT id FROM resource_types WHERE name = 'system_role_group_permissions')
   AND r.entity_id = sgp.id
WHERE r.id IS NULL;


-- 3. Gán tất cả permission vào vai trò Bí thư Đoàn trường
INSERT INTO system_role_permissions (system_role_id, permission_id)
SELECT 
    (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường"),
    p.id
FROM permissions p
LEFT JOIN system_role_permissions srp
    ON srp.permission_id = p.id
   AND srp.system_role_id = (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường")
WHERE srp.id IS NULL;

-- Tạo resource cho từng quan hệ system_role_permissions
INSERT INTO resources (name, description, resource_type_id, entity_id)
SELECT 
    CONCAT('Resource SystemRolePermission ', srp.id),
    CONCAT('Resource for SystemRolePermission ID ', srp.id),
    (SELECT id FROM resource_types WHERE name = 'system_role_permissions'),
    srp.id
FROM system_role_permissions srp
LEFT JOIN resources r
    ON r.resource_type_id = (SELECT id FROM resource_types WHERE name = 'system_role_permissions')
   AND r.entity_id = srp.id
WHERE r.id IS NULL;

INSERT INTO user_system_roles (user_id, system_role_id) VALUES 
((SELECT id FROM users WHERE name = "Đỗ Ba Chín"), (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường"));

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource UserSystemRole Đỗ Ba Chín - Bí thư Đoàn trường", "Resource for UserSystemRole Đỗ Ba Chín - Bí thư Đoàn trường", (SELECT id FROM resource_types WHERE name = "user_system_roles"), (SELECT id FROM user_system_roles WHERE user_id = (SELECT id FROM users WHERE name = "Đỗ Ba Chín") AND system_role_id = (SELECT id FROM system_roles WHERE name = "Bí thư Đoàn trường")));

INSERT INTO user_orgs (user_id, org_id) VALUES 
((SELECT id FROM users WHERE name = "Đỗ Ba Chín"), (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội"));

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES
("Resource UserOrg Đỗ Ba Chín - Đoàn trường Đại học Sư phạm Hà Nội", "Resource for UserOrg Đỗ Ba Chín - Đoàn trường Đại học Sư phạm Hà Nội", (SELECT id FROM resource_types WHERE name = "user_orgs"), (SELECT id FROM user_orgs WHERE user_id = (SELECT id FROM users WHERE name = "Đỗ Ba Chín") AND org_id = (SELECT id FROM organizations WHERE name = "Đoàn trường Đại học Sư phạm Hà Nội")));

INSERT INTO user_resource_roles (name, description) VALUES
("Creator", "Người có quyền tạo"),
("Editor", "Người có quyền chỉnh sửa"),
("Viewer", "Người có quyền xem"),
("Deleter", "Người có quyền xóa");

INSERT INTO resource_role_actions (resource_role_id, action_id) VALUES
((SELECT id FROM user_resource_roles WHERE name = "Creator"), (SELECT id FROM actions WHERE name = "create")),
((SELECT id FROM user_resource_roles WHERE name = "Editor"), (SELECT id FROM actions WHERE name = "edit")),
((SELECT id FROM user_resource_roles WHERE name = "Viewer"), (SELECT id FROM actions WHERE name = "view")),
((SELECT id FROM user_resource_roles WHERE name = "Deleter"), (SELECT id FROM actions WHERE name = "delete"));