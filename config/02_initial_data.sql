--
-- Dữ liệu ban đầu
--

-- Gộp tất cả các lệnh INSERT cho bảng actions
INSERT INTO actions (id, name, description) VALUES
(1, 'Create', 'Tạo'),
(2, 'View', 'Xem'),
(3, 'Edit', 'Sửa'),
(4, 'Delete', 'Xóa'),
(5, 'Manage', 'Tổng hợp của CRUD');

-- Gộp tất cả các lệnh INSERT cho bảng resource_types
INSERT INTO resource_types (id, name, version) VALUES
(1, 'system_roles', 1),
(2, 'user_resource_roles', 1),
(3, 'org_resource_roles', 1),
(4, 'actions', 1),
(5, 'permissions', 1),
(6, 'resource_types', 1),
(7, 'users', 1),
(8, 'policies', 1),
(9, 'policy_conditions', 1),
(10, 'policy_condition_groups', 1),
(11, 'resources', 1),
(12, 'org_types', 1),
(13, 'organizations', 1),
(14, 'system_role_permissions', 1),
(15, 'user_system_roles', 1),
(16, 'resource_role_actions', 1),
(17, 'action_relations', 1),
(18, 'user_resources', 1);


-- Gộp tất cả các lệnh INSERT cho bảng user_resource_roles
INSERT INTO user_resource_roles (id, name, description) VALUES
(1, 'Creator', 'Người tạo tài nguyên'),
(2, 'Viewer', 'Người xem tài nguyên'),
(3, 'Editor', 'Người chỉnh sửa tài nguyên'),
(4, 'Deleter', 'Người xóa tài nguyên');

-- Gộp tất cả các lệnh INSERT cho bảng org_resource_roles
INSERT INTO org_resource_roles (id, name, description) VALUES
(1, 'Owner', 'Chủ sở hữu của tổ chức'),
(2, 'Collaborator', 'Cộng tác viên của tổ chức');

-- Gộp tất cả các lệnh INSERT cho bảng org_types
INSERT INTO org_types (id, name) VALUES
(1, 'Trường'),
(2, 'Liên chi đoàn'),
(3, 'Chi đoàn'),
(4, 'Câu lạc bộ'),
(5, 'Đội');

-- Gộp tất cả các lệnh INSERT cho bảng users
INSERT INTO users (id, name) VALUES
(1, 'User 1'),
(2, 'User 2'),
(3, 'User 3'),
(4, 'User 4'),
(5, 'User 5');

-- Gộp tất cả các lệnh INSERT cho bảng organizations
INSERT INTO organizations (id, name, parent_org_id, org_level, org_type_id) VALUES
(1, 'Trường ĐHSPHN', NULL, 1, 1),
(2, 'Liên chi đoàn Khoa CNTT', 1, 2, 2),
(3, 'Chi đoàn K72E2', 2, 3, 3);


-- Gộp tất cả các lệnh INSERT cho bảng system_roles
INSERT INTO system_roles (id, name, description, org_id, level, available_slots) VALUES
(1, 'System role cấp trường', NULL, 1, 1, 1),
(2, 'System role cấp liên chi đoàn', NULL, 2, 2, 100),
(3, 'System role cấp chi đoàn', NULL, 3, 3, 1000);


-- Gộp tất cả các lệnh INSERT cho bảng user_system_roles
INSERT INTO user_system_roles (id, user_id, system_role_id) VALUES
(1, 1, 1); -- user_name: 'User 1', system_role_name: 'System role cấp trường'


-- Gộp tất cả các lệnh INSERT cho bảng resource_role_actions
INSERT INTO resource_role_actions (id, resource_role_id, action_id) VALUES
(1, 1, 1), -- resource_role_name: 'Creator', action_name: 'Create'
(2, 3, 3), -- resource_role_name: 'Editor', action_name: 'Edit'
(3, 2, 2), -- resource_role_name: 'Viewer', action_name: 'View'
(4, 4, 4); -- resource_role_name: 'Deleter', action_name: 'Delete'

-- Gộp tất cả các lệnh INSERT cho bảng action_relations
INSERT INTO action_relations (id, parent_action_id, child_action_id) VALUES
(1, 3, 2), -- parent_action_name: 'Edit', child_action_name: 'View'
(2, 1, 2), -- parent_action_name: 'Create', child_action_name: 'View'
(3, 4, 2), -- parent_action_name: 'Delete', child_action_name: 'View'
(4, 5, 1), -- parent_action_name: 'Manage', child_action_name: 'Create'
(5, 5, 2), -- parent_action_name: 'Manage', child_action_name: 'View'
(6, 5, 3), -- parent_action_name: 'Manage', child_action_name: 'Edit'
(7, 5, 4); -- parent_action_name: 'Manage', child_action_name: 'Delete'

-- Gộp tất cả các lệnh INSERT cho bảng user_resources
INSERT INTO user_resources (id, user_id, resource_id, resource_role_id) VALUES
(1, 1, NULL, 2); -- user_name: 'User 1', resource_id: NULL, resource_role_name: 'Viewer'


--
-- Bảng cần gộp và chỉnh sửa ID
--

-- Gộp tất cả các lệnh INSERT cho bảng permissions, đánh lại ID từ 1
INSERT INTO permissions (id, action_id, resource_type_id) VALUES
(1, 2, 13), -- action_name: 'View', resource_type_name: 'organizations'
(2, 2, 4), -- action_name: 'View', resource_type_name: 'actions'
(3, 2, 12), -- action_name: 'View', resource_type_name: 'org_types'
(4, 2, 7), -- action_name: 'View', resource_type_name: 'users'
(5, 2, 1), -- action_name: 'View', resource_type_name: 'system_roles'
(6, 2, 5), -- action_name: 'View', resource_type_name: 'permissions'
(7, 2, 6), -- action_name: 'View', resource_type_name: 'resource_types'
(8, 3, 13), -- action_name: 'Edit', resource_type_name: 'organizations'
(9, 3, 4), -- action_name: 'Edit', resource_type_name: 'actions'
(10, 3, 12), -- action_name: 'Edit', resource_type_name: 'org_types'
(11, 3, 7), -- action_name: 'Edit', resource_type_name: 'users'
(12, 3, 1), -- action_name: 'Edit', resource_type_name: 'system_roles'
(13, 3, 5), -- action_name: 'Edit', resource_type_name: 'permissions'
(14, 3, 6), -- action_name: 'Edit', resource_type_name: 'resource_types'
(15, 1, 13), -- action_name: 'Create', resource_type_name: 'organizations'
(16, 1, 4), -- action_name: 'Create', resource_type_name: 'actions'
(17, 1, 12), -- action_name: 'Create', resource_type_name: 'org_types'
(18, 1, 7), -- action_name: 'Create', resource_type_name: 'users'
(19, 1, 1), -- action_name: 'Create', resource_type_name: 'system_roles'
(20, 1, 5), -- action_name: 'Create', resource_type_name: 'permissions'
(21, 1, 6); -- action_name: 'Create', resource_type_name: 'resource_types'

-- Gộp tất cả các lệnh INSERT cho bảng system_role_permissions, đánh lại ID từ 1
INSERT INTO system_role_permissions (id, system_role_id, permission_id) VALUES
(1, 1, 1), -- system_role_name: 'System role cấp trường', permission_description: 'View organizations'
(2, 1, 2), -- system_role_name: 'System role cấp trường', permission_description: 'View actions'
(3, 1, 3), -- system_role_name: 'System role cấp trường', permission_description: 'View org_types'
(4, 1, 4), -- system_role_name: 'System role cấp trường', permission_description: 'View users'
(5, 1, 5), -- system_role_name: 'System role cấp trường', permission_description: 'View system_roles'
(6, 1, 6), -- system_role_name: 'System role cấp trường', permission_description: 'View permissions'
(7, 1, 7), -- system_role_name: 'System role cấp trường', permission_description: 'View resource_types'
(8, 1, 8), -- system_role_name: 'System role cấp trường', permission_description: 'Edit organizations'
(9, 1, 9), -- system_role_name: 'System role cấp trường', permission_description: 'Edit actions'
(10, 1, 10), -- system_role_name: 'System role cấp trường', permission_description: 'Edit org_types'
(11, 1, 11), -- system_role_name: 'System role cấp trường', permission_description: 'Edit users'
(12, 1, 12), -- system_role_name: 'System role cấp trường', permission_description: 'Edit system_roles'
(13, 1, 13), -- system_role_name: 'System role cấp trường', permission_description: 'Edit permissions'
(14, 1, 14), -- system_role_name: 'System role cấp trường', permission_description: 'Edit resource_types'
(15, 1, 15), -- system_role_name: 'System role cấp trường', permission_description: 'Create organizations'
(16, 1, 16), -- system_role_name: 'System role cấp trường', permission_description: 'Create actions'
(17, 1, 17), -- system_role_name: 'System role cấp trường', permission_description: 'Create org_types'
(18, 1, 18), -- system_role_name: 'System role cấp trường', permission_description: 'Create users'
(19, 1, 19), -- system_role_name: 'System role cấp trường', permission_description: 'Create system_roles'
(20, 1, 20), -- system_role_name: 'System role cấp trường', permission_description: 'Create permissions'
(21, 1, 21); -- system_role_name: 'System role cấp trường', permission_description: 'Create resource_types'

-- Gộp tất cả các lệnh INSERT cho bảng resources, đánh lại ID từ 1
INSERT INTO resources (id, name, description, org_id, resource_type_id, entity_id) VALUES
-- Resources cho bảng Actions
(1, 'Action Create Resource', 'Tài nguyên hành động Tạo', NULL, 4, 1), -- resource_type_name: 'actions', entity_name: 'Create'
(2, 'Action Read Resource', 'Tài nguyên hành động Xem', NULL, 4, 2), -- resource_type_name: 'actions', entity_name: 'View'
(3, 'Action Update Resource', 'Tài nguyên hành động Sửa', NULL, 4, 3), -- resource_type_name: 'actions', entity_name: 'Edit'
(4, 'Action Delete Resource', 'Tài nguyên hành động Xóa', NULL, 4, 4), -- resource_type_name: 'actions', entity_name: 'Delete'
(5, 'Action Manage Resource', 'Tài nguyên hành động Quản lý', NULL, 4, 5), -- resource_type_name: 'actions', entity_name: 'Manage'

-- Resources cho bảng Resource Types
(6, 'Resource Type System Roles Resource', 'Tài nguyên loại hệ thống vai trò', NULL, 6, 1), -- resource_type_name: 'resource_types', entity_name: 'system_roles'
(7, 'Resource Type User Resource Roles', 'Tài nguyên loại vai trò tài nguyên người dùng', NULL, 6, 2), -- resource_type_name: 'resource_types', entity_name: 'user_resource_roles'
(8, 'Resource Type Org Resource Role', 'Tài nguyên loại vai trò tài nguyên tổ chức', NULL, 6, 3), -- resource_type_name: 'resource_types', entity_name: 'org_resource_roles'
(9, 'Resource Type Action', 'Tài nguyên loại hành động', NULL, 6, 4), -- resource_type_name: 'resource_types', entity_name: 'actions'
(10, 'Resource Type Permission', 'Tài nguyên loại quyền hạn', NULL, 6, 5), -- resource_type_name: 'resource_types', entity_name: 'permissions'
(11, 'Resource Type Resource Type', 'Tài nguyên loại loại tài nguyên', NULL, 6, 6), -- resource_type_name: 'resource_types', entity_name: 'resource_types'
(12, 'Resource Type User', 'Tài nguyên loại người dùng', NULL, 6, 7), -- resource_type_name: 'resource_types', entity_name: 'users'
(13, 'Resource Type Policy', 'Tài nguyên loại chính sách', NULL, 6, 8), -- resource_type_name: 'resource_types', entity_name: 'policies'
(14, 'Resource Type Policy Conditions', 'Tài nguyên loại điều kiện chính sách', NULL, 6, 9), -- resource_type_name: 'resource_types', entity_name: 'policy_conditions'
(15, 'Resource Type Policy Condition Groups', 'Tài nguyên loại nhóm điều kiện chính sách', NULL, 6, 10), -- resource_type_name: 'resource_types', entity_name: 'policy_condition_groups'
(16, 'Resource Type Resources', 'Tài nguyên loại tài nguyên khác', NULL, 6, 11), -- resource_type_name: 'resource_types', entity_name: 'resources'

-- Resources cho bảng User Resource Roles
(17, 'User Resource Role Creator Resource', 'Tài nguyên vai trò người dùng Creator', NULL, 2, 1), -- resource_type_name: 'user_resource_roles', entity_name: 'Creator'
(18, 'User Resource Role Viewer Resource', 'Tài nguyên vai trò người dùng Viewer', NULL, 2, 2), -- resource_type_name: 'user_resource_roles', entity_name: 'Viewer'
(19, 'User Resource Role Editor Resource', 'Tài nguyên vai trò người dùng Editor', NULL, 2, 3), -- resource_type_name: 'user_resource_roles', entity_name: 'Editor'
(20, 'User Resource Role Deleter Resource', 'Tài nguyên vai trò người dùng Deleter', NULL, 2, 4), -- resource_type_name: 'user_resource_roles', entity_name: 'Deleter'

-- Resources cho bảng Org Resource Roles
(21, 'Org Resource Role Owner Resource', 'Tài nguyên vai trò tổ chức Owner', NULL, 3, 1), -- resource_type_name: 'org_resource_roles', entity_name: 'Owner'
(22, 'Org Resource Role Collaborator Resource', 'Tài nguyên vai trò tổ chức Collaborator', NULL, 3, 2), -- resource_type_name: 'org_resource_roles', entity_name: 'Collaborator'

-- Resources cho bảng Org Types
(23, 'Org Type School Resource', 'Tài nguyên loại tổ chức Trường', NULL, 12, 1), -- resource_type_name: 'org_types', entity_name: 'Trường'
(24, 'Org Type Union Branch Resource', 'Tài nguyên loại tổ chức Liên chi đoàn', NULL, 12, 2), -- resource_type_name: 'org_types', entity_name: 'Liên chi đoàn'
(25, 'Org Type Union Cell Resource', 'Tài nguyên loại tổ chức Chi đoàn', NULL, 12, 3), -- resource_type_name: 'org_types', entity_name: 'Chi đoàn'
(26, 'Org Type Club Resource', 'Tài nguyên loại tổ chức Câu lạc bộ', NULL, 12, 4), -- resource_type_name: 'org_types', entity_name: 'Câu lạc bộ'
(27, 'Org Type Team Resource', 'Tài nguyên loại tổ chức Đội', NULL, 12, 5), -- resource_type_name: 'org_types', entity_name: 'Đội'

-- Resources cho bảng Users
(28, 'User 1 Resource', 'Tài nguyên của User 1', NULL, 7, 1), -- resource_type_name: 'users', entity_name: 'User 1'
(29, 'User 2 Resource', 'Tài nguyên của User 2', NULL, 7, 2), -- resource_type_name: 'users', entity_name: 'User 2'
(30, 'User 3 Resource', 'Tài nguyên của User 3', NULL, 7, 3), -- resource_type_name: 'users', entity_name: 'User 3'
(31, 'User 4 Resource', 'Tài nguyên của User 4', NULL, 7, 4), -- resource_type_name: 'users', entity_name: 'User 4'
(32, 'User 5 Resource', 'Tài nguyên của User 5', NULL, 7, 5), -- resource_type_name: 'users', entity_name: 'User 5'

-- Resources cho bảng Permissions (lưu ý: entity_id ở đây trỏ đến ID đã được đánh lại ở bảng permissions)
(33, 'Permission View Organizations Resource', 'Tài nguyên quyền xem tổ chức', NULL, 5, 1), -- resource_type_name: 'permissions', entity_name: 'View organizations'
(34, 'Permission View Actions Resource', 'Tài nguyên quyền xem hành động', NULL, 5, 2), -- resource_type_name: 'permissions', entity_name: 'View actions'
(35, 'Permission View Org Types Resource', 'Tài nguyên quyền xem loại tổ chức', NULL, 5, 3), -- resource_type_name: 'permissions', entity_name: 'View org_types'
(36, 'Permission View Users Resource', 'Tài nguyên quyền xem người dùng', NULL, 5, 4), -- resource_type_name: 'permissions', entity_name: 'View users'
(37, 'Permission View System Roles Resource', 'Tài nguyên quyền xem vai trò hệ thống', NULL, 5, 5), -- resource_type_name: 'permissions', entity_name: 'View system_roles'
(38, 'Permission View Permissions Resource', 'Tài nguyên quyền xem quyền hạn', NULL, 5, 6), -- resource_type_name: 'permissions', entity_name: 'View permissions'
(39, 'Permission View Resource Types Resource', 'Tài nguyên quyền xem loại tài nguyên', NULL, 5, 7), -- resource_type_name: 'permissions', entity_name: 'View resource_types'
(40, 'Permission Edit Organizations Resource', 'Tài nguyên quyền edit tổ chức', NULL, 5, 8), -- resource_type_name: 'permissions', entity_name: 'Edit organizations'
(41, 'Permission Edit Actions Resource', 'Tài nguyên quyền edit hành động', NULL, 5, 9), -- resource_type_name: 'permissions', entity_name: 'Edit actions'
(42, 'Permission Edit Org Types Resource', 'Tài nguyên quyền edit loại tổ chức', NULL, 5, 10), -- resource_type_name: 'permissions', entity_name: 'Edit org_types'
(43, 'Permission Edit Users Resource', 'Tài nguyên quyền edit người dùng', NULL, 5, 11), -- resource_type_name: 'permissions', entity_name: 'Edit users'
(44, 'Permission Edit System Roles Resource', 'Tài nguyên quyền edit vai trò hệ thống', NULL, 5, 12), -- resource_type_name: 'permissions', entity_name: 'Edit system_roles'
(45, 'Permission Edit Permissions Resource', 'Tài nguyên quyền edit quyền hạn', NULL, 5, 13), -- resource_type_name: 'permissions', entity_name: 'Edit permissions'
(46, 'Permission Edit Resource Types Resource', 'Tài nguyên quyền edit loại tài nguyên', NULL, 5, 14), -- resource_type_name: 'permissions', entity_name: 'Edit resource_types'
(47, 'Permission Create Organizations Resource', 'Tài nguyên tạo tổ chức', NULL, 5, 15), -- resource_type_name: 'permissions', entity_name: 'Create organizations'
(48, 'Permission Create Actions Resource', 'Tài nguyên tạo hành động', NULL, 5, 16), -- resource_type_name: 'permissions', entity_name: 'Create actions'
(49, 'Permission Create Org Types Resource', 'Tài nguyên tạo loại tổ chức', NULL, 5, 17), -- resource_type_name: 'permissions', entity_name: 'Create org_types'
(50, 'Permission Create Users Resource', 'Tài nguyên tạo người dùng', NULL, 5, 18), -- resource_type_name: 'permissions', entity_name: 'Create users'
(51, 'Permission Create System Roles Resource', 'Tài nguyên tạo vai trò hệ thống', NULL, 5, 19), -- resource_type_name: 'permissions', entity_name: 'Create system_roles'
(52, 'Permission Create Permissions Resource', 'Tài nguyên tạo quyền hạn', NULL, 5, 20), -- resource_type_name: 'permissions', entity_name: 'Create permissions'
(53, 'Permission Create Resource Types Resource', 'Tài nguyên tạo loại tài nguyên', NULL, 5, 21), -- resource_type_name: 'permissions', entity_name: 'Create resource_types'

-- Resources cho bảng Organizations
(54, 'Organization Trường ĐHSPHN Resource', 'Tài nguyên tổ chức Trường ĐHSPHN', NULL, 13, 1), -- resource_type_name: 'organizations', entity_name: 'Trường ĐHSPHN'
(55, 'Organization Liên chi đoàn Khoa CNTT Resource', 'Tài nguyên tổ chức Liên chi đoàn Khoa CNTT', NULL, 13, 2), -- resource_type_name: 'organizations', entity_name: 'Liên chi đoàn Khoa CNTT'
(56, 'Organization Chi đoàn K72E2 Resource', 'Tài nguyên tổ chức Chi đoàn K72E2', NULL, 13, 3), -- resource_type_name: 'organizations', entity_name: 'Chi đoàn K72E2'

-- Resources cho bảng System Roles
(57, 'System Role System role cấp trường Resource', 'Tài nguyên vai trò hệ thống cấp trường', NULL, 1, 1), -- resource_type_name: 'system_roles', entity_name: 'System role cấp trường'
(58, 'System Role System role cấp liên chi đoàn Resource', 'Tài nguyên vai trò hệ thống cấp liên chi đoàn', NULL, 1, 2), -- resource_type_name: 'system_roles', entity_name: 'System role cấp liên chi đoàn'
(59, 'System Role System role cấp chi đoàn Resource', 'Tài nguyên vai trò hệ thống cấp chi đoàn', NULL, 1, 3), -- resource_type_name: 'system_roles', entity_name: 'System role cấp chi đoàn'

-- Resources cho bảng System Role Permissions
(60, 'System role permissions System role cấp trường có quyền xem tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem tổ chức', NULL, 14, 1), -- resource_type_name: 'system_role_permissions', entity_name: 'View organizations'
(61, 'System role permissions System role cấp trường có quyền xem hành động Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem hành động', NULL, 14, 2), -- resource_type_name: 'system_role_permissions', entity_name: 'View actions'
(62, 'System role permissions System role cấp trường có quyền xem loại tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem loại tổ chức', NULL, 14, 3), -- resource_type_name: 'system_role_permissions', entity_name: 'View org_types'
(63, 'System role permissions System role cấp trường có quyền xem người dùng Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem người dùng', NULL, 14, 4), -- resource_type_name: 'system_role_permissions', entity_name: 'View users'
(64, 'System role permissions System role cấp trường có quyền xem vai trò hệ thống Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem vai trò hệ thống', NULL, 14, 5), -- resource_type_name: 'system_role_permissions', entity_name: 'View system_roles'
(65, 'System role permissions System role cấp trường có quyền xem quyền hạn Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem quyền hạn', NULL, 14, 6), -- resource_type_name: 'system_role_permissions', entity_name: 'View permissions'
(66, 'System role permissions System role cấp trường có quyền xem loại tài nguyên Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền xem loại tài nguyên', NULL, 14, 7), -- resource_type_name: 'system_role_permissions', entity_name: 'View resource_types'
(67, 'System role permissions System role cấp trường có quyền edit tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit tổ chức', NULL, 14, 8), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit organizations'
(68, 'System role permissions System role cấp trường có quyền edit hành động Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit hành động', NULL, 14, 9), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit actions'
(69, 'System role permissions System role cấp trường có quyền edit loại tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit loại tổ chức', NULL, 14, 10), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit org_types'
(70, 'System role permissions System role cấp trường có quyền edit người dùng Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit người dùng', NULL, 14, 11), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit users'
(71, 'System role permissions System role cấp trường có quyền edit vai trò hệ thống Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit vai trò hệ thống', NULL, 14, 12), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit system_roles'
(72, 'System role permissions System role cấp trường có quyền edit quyền hạn Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit quyền hạn', NULL, 14, 13), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit permissions'
(73, 'System role permissions System role cấp trường có quyền edit loại tài nguyên Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền edit loại tài nguyên', NULL, 14, 14), -- resource_type_name: 'system_role_permissions', entity_name: 'Edit resource_types'
(74, 'System role permissions System role cấp trường có quyền tạo tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo tổ chức', NULL, 14, 15), -- resource_type_name: 'system_role_permissions', entity_name: 'Create organizations'
(75, 'System role permissions System role cấp trường có quyền tạo hành động Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo hành động', NULL, 14, 16), -- resource_type_name: 'system_role_permissions', entity_name: 'Create actions'
(76, 'System role permissions System role cấp trường có quyền tạo loại tổ chức Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo loại tổ chức', NULL, 14, 17), -- resource_type_name: 'system_role_permissions', entity_name: 'Create org_types'
(77, 'System role permissions System role cấp trường có quyền tạo người dùng Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo người dùng', NULL, 14, 18), -- resource_type_name: 'system_role_permissions', entity_name: 'Create users'
(78, 'System role permissions System role cấp trường có quyền tạo vai trò hệ thống Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo vai trò hệ thống', NULL, 14, 19), -- resource_type_name: 'system_role_permissions', entity_name: 'Create system_roles'
(79, 'System role permissions System role cấp trường có quyền tạo quyền hạn Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo quyền hạn', NULL, 14, 20), -- resource_type_name: 'system_role_permissions', entity_name: 'Create permissions'
(80, 'System role permissions System role cấp trường có quyền tạo loại tài nguyên Resource', 'Tài nguyên quyền hệ thống role cấp trường có quyền tạo loại tài nguyên', NULL, 14, 21), -- resource_type_name: 'system_role_permissions', entity_name: 'Create resource_types'

-- Resources cho bảng User System Roles
(81, 'User System Role User 1 Resource', 'Tài nguyên vai trò hệ thống của User 1', NULL, 15, 1), -- resource_type_name: 'user_system_roles', entity_name: 'User 1'

-- Resources cho bảng Resource Role Actions
(82, 'Resource Role Actions User Resource Role Creator Resource Action Create Resource', 'Tài nguyên hành động tạo tài nguyên của vai trò người dùng Creator', NULL, 16, 1), -- resource_type_name: 'resource_role_actions', entity_name: 'Creator, Create'
(83, 'Resource Role Actions User Resource Role Viewer Resource Action View Resource', 'Tài nguyên hành động xem tài nguyên của vai trò người dùng Viewer', NULL, 16, 2), -- resource_type_name: 'resource_role_actions', entity_name: 'Viewer, View'
(84, 'Resource Role Actions User Resource Role Editor Resource Action Edit Resource', 'Tài nguyên hành động sửa tài nguyên của vai trò người dùng Editor', NULL, 16, 3), -- resource_type_name: 'resource_role_actions', entity_name: 'Editor, Edit'
(85, 'Resource Role Actions User Resource Role Deleter Resource Action Delete Resource', 'Tài nguyên hành động xóa tài nguyên của vai trò người dùng Deleter', NULL, 16, 4), -- resource_type_name: 'resource_role_actions', entity_name: 'Deleter, Delete'
(86, 'Resource Role Actions Org Resource Role Owner Resource Action Create Resource', 'Tài nguyên hành động tạo tài nguyên của vai trò tổ chức Owner', NULL, 16, 1), -- resource_type_name: 'resource_role_actions', entity_name: 'Owner, Create'
(87, 'Resource Role Actions Org Resource Role Owner Resource Action Edit Resource', 'Tài nguyên hành động sửa tài nguyên của vai trò tổ chức Owner', NULL, 16, 3), -- resource_type_name: 'resource_role_actions', entity_name: 'Owner, Edit'
(88, 'Resource Role Actions Org Resource Role Owner Resource Action View Resource', 'Tài nguyên hành động xem tài nguyên của vai trò tổ chức Owner', NULL, 16, 2), -- resource_type_name: 'resource_role_actions', entity_name: 'Owner, View'
(89, 'Resource Role Actions Org Resource Role Collaborator Resource Action Edit Resource', 'Tài nguyên hành động sửa tài nguyên của vai trò tổ chức Collaborator', NULL, 16, 4), -- resource_type_name: 'resource_role_actions', entity_name: 'Collaborator, Edit'

-- Resources cho bảng Action Relations
(90, 'Action Relations Update bao gồm View Resource', 'Tài nguyên quan hệ hành động Update bao gồm View', NULL, 17, 1), -- resource_type_name: 'action_relations', entity_name: 'Edit bao gồm View'
(91, 'Action Relations Create bao gồm View Resource', 'Tài nguyên quan hệ hành động Create bao gồm View', NULL, 17, 2), -- resource_type_name: 'action_relations', entity_name: 'Create bao gồm View'
(92, 'Action Relations Delete bao gồm View Resource', 'Tài nguyên quan hệ hành động Delete bao gồm View', NULL, 17, 3), -- resource_type_name: 'action_relations', entity_name: 'Delete bao gồm View'
(93, 'Action Relations Manage bao gồm Create, View, Update, Delete Resource', 'Tài nguyên quan hệ hành động Manage bao gồm Create, View, Update, Delete', NULL, 17, 4), -- resource_type_name: 'action_relations', entity_name: 'Manage bao gồm Create'
(94, 'Action Relations Manage bao gồm Create, View, Update, Delete Resource', 'Tài nguyên quan hệ hành động Manage bao gồm Create, View, Update, Delete', NULL, 17, 5), -- resource_type_name: 'action_relations', entity_name: 'Manage bao gồm View'
(95, 'Action Relations Manage bao gồm Create, View, Update, Delete Resource', 'Tài nguyên quan hệ hành động Manage bao gồm Create, View, Update, Delete', NULL, 17, 6), -- resource_type_name: 'action_relations', entity_name: 'Manage bao gồm Edit'
(96, 'Action Relations Manage bao gồm Create, View, Update, Delete Resource', 'Tài nguyên quan hệ hành động Manage bao gồm Create, View, Update, Delete', NULL, 17, 7), -- resource_type_name: 'action_relations', entity_name: 'Manage bao gồm Delete'

-- Resources cho bảng User Resources
(97, 'User Resource User 1 Resource Resource Role Creator Resource', 'Tài nguyên người dùng User 1 với vai trò Creator', NULL, 18, 1); -- resource_type_name: 'user_resources', entity_name: 'User 1, Viewer'


-- Cập nhật các bản ghi đã tồn tại
-- (Lệnh này giữ nguyên vì nó là UPDATE, không phải INSERT)
UPDATE user_resources SET resource_id = 40 WHERE id = 1; -- resource_id: 'Organization Trường ĐHSPHN Resource'

-- Cập nhật tất cả các resource có org_id = NULL
-- (Lệnh này cũng giữ nguyên vì nó là UPDATE, không phải INSERT)
UPDATE resources SET org_id = 1;