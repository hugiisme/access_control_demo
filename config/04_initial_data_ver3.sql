INSERT INTO resource_types (name, version) VALUES
('system_roles', 1),
('user_resource_roles', 1),
('org_resource_roles', 1),
('actions', 1),
('permissions', 1),
('resource_types', 1),
('users', 1),
('policies', 1),
('policy_conditions', 1),
('policy_condition_groups', 1),
('resources', 1),
('org_types', 1),
('organizations', 1),
('system_role_permissions', 1),
('user_system_roles', 1),
('resource_role_actions', 1),
('action_relations', 1),
('user_resources', 1),
('system_role_groups', 1),
('user_orgs', 1),
('org_permissions', 1),
('system_role_group_permissions', 1),
('system_role_group_roles', 1),
('system_role_perrmissions', 1);

INSERT INTO actions (name, description) VALUES
('View', 'Xem'),
('Create', 'Tạo'),
('Edit', 'Chỉnh sửa'),
('Delete', 'Xóa');

INSERT INTO action_relations (parent_action_id, child_action_id) VALUES
((SELECT id FROM actions WHERE name = 'Edit' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Create' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Create' LIMIT 1), (SELECT id FROM actions WHERE name = 'Edit' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Delete' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1));

INSERT INTO users (id, name) VALUES
(1, 'Tài khoản Bí thư đoàn trường - Đỗ Ba Chín');

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('User ID 1 Resource', 'Resource for User ID 1', (SELECT id FROM resource_types WHERE name = 'users' LIMIT 1), 1);

INSERT INTO org_types (id, name, is_exclusive) VALUES
(1, 'Đoàn trường', 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Organization Type ID 1 Resource', 'Resource for Organization Type ID 1', (SELECT id FROM resource_types WHERE name = 'org_types' LIMIT 1), 1);

INSERT INTO organizations (id, name, parent_org_id, org_level, org_type_id) VALUES
(1, 'Đoàn trường Đại Học Sư Phạm Hà Nội', NULL, 1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Organization ID 1 Resource', 'Resource for Organization ID 1', (SELECT id FROM resource_types WHERE name = 'organizations' LIMIT 1), 1);

INSERT INTO user_orgs (user_id, org_id) VALUES
(1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('User-Organization Association for User ID 1 and Organization ID 1', 'Resource for User-Organization association between User ID 1 and Organization ID 1', (SELECT id FROM resource_types WHERE name = 'user_orgs' LIMIT 1), 1);

-- Insert toàn bộ combinations (action × resource_type) vào bảng permissions
INSERT INTO permissions (action_id, resource_type_id)
SELECT a.id, r.id
FROM actions a
CROSS JOIN resource_types r
LEFT JOIN permissions p 
    ON p.action_id = a.id AND p.resource_type_id = r.id
WHERE p.id IS NULL;

SET @perm_resource_type_id := (
    SELECT id FROM resource_types WHERE name = 'permissions' LIMIT 1
);

-- Bước 2: Tạo resource cho tất cả permission chưa có resource
INSERT INTO resources (name, description, resource_type_id, entity_id)
SELECT 
    CONCAT('Permission #', p.id) AS name,
    CONCAT('Resource for permission ', p.id) AS description,
    @perm_resource_type_id AS resource_type_id,
    p.id AS entity_id
FROM permissions p
LEFT JOIN resources r
    ON r.resource_type_id = @perm_resource_type_id 
   AND r.entity_id = p.id
WHERE r.id IS NULL; -- tránh tạo trùng

INSERT INTO system_roles  (name, description, org_id, level, available_slots) VALUES 
("Bí thư Đoàn Trường", null, 1, 1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
("Resource system role 1", null, (SELECT id FROM resource_types WHERE name = "system_roles"), 1);

INSERT INTO system_role_permissions (system_role_id, permission_id)
SELECT 
    1 AS system_role_id,
    p.id AS permission_id
FROM permissions p
LEFT JOIN system_role_permissions srp
    ON srp.permission_id = p.id
   AND srp.system_role_id = 1
WHERE srp.id IS NULL;

INSERT INTO user_system_roles (user_id, system_role_id) VALUES (1, 1);
INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
("Resource user system role 1", null, (SELECT id FROM resource_types WHERE name = "user_system_roles"), 1);