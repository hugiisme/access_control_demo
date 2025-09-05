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

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Resource Type system_roles Resource', 'Resource Type system_roles Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_roles')),
('Resource Type user_resource_roles Resource', 'Resource Type user_resource_roles Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'user_resource_roles')),
('Resource Type org_resource_roles Resource', 'Resource Type org_resource_roles Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'org_resource_roles')),
('Resource Type actions Resource', 'Resource Type actions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'actions')),
('Resource Type permissions Resource', 'Resource Type permissions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'permissions')),
('Resource Type resource_types Resource', 'Resource Type resource_types Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'resource_types')),
('Resource Type users Resource', 'Resource Type users Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'users')),
('Resource Type policies Resource', 'Resource Type policies Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'policies')),
('Resource Type policy_conditions Resource', 'Resource Type policy_conditions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'policy_conditions')),
('Resource Type policy_condition_groups Resource', 'Resource Type policy_condition_groups Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'policy_condition_groups')),
('Resource Type resources Resource', 'Resource Type resources Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'resources')),
('Resource Type org_types Resource', 'Resource Type org_types Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'org_types')),
('Resource Type organizations Resource', 'Resource Type organizations Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'organizations')),
('Resource Type system_role_permissions Resource', 'Resource Type system_role_permissions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_role_permissions')),
('Resource Type user_system_roles Resource', 'Resource Type user_system_roles Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'user_system_roles')),
('Resource Type resource_role_actions Resource', 'Resource Type resource_role_actions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'resource_role_actions')),
('Resource Type action_relations Resource', 'Resource Type action_relations Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'action_relations')),
('Resource Type user_resources Resource', 'Resource Type user_resources Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'user_resources')),
('Resource Type system_role_groups Resource', 'Resource Type system_role_groups Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_role_groups')),
('Resource Type user_orgs Resource', 'Resource Type user_orgs Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'user_orgs')),
('Resource Type org_permissions Resource', 'Resource Type org_permissions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'org_permissions')),
('Resource Type system_role_group_permissions Resource', 'Resource Type system_role_group_permissions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_role_group_permissions')),
('Resource Type system_role_group_roles Resource', 'Resource Type system_role_group_roles Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_role_group_roles')),
('Resource Type system_role_perrmissions Resource', 'Resource Type system_role_perrmissions Resource', (SELECT id FROM resource_types WHERE name = 'resource_types'), (SELECT id FROM resource_types WHERE name = 'system_role_perrmissions'));

INSERT INTO actions (name, description) VALUES
('View', 'Xem'),
('Create', 'Tạo'),
('Edit', 'Chỉnh sửa'),
('Delete', 'Xóa');

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Action View Resource', 'Action View Resource', (SELECT id FROM resource_types WHERE name = 'actions'), 1),
('Action Create Resource', 'Action Create Resource', (SELECT id FROM resource_types WHERE name = 'actions'), 2),
('Action Edit Resource', 'Action Edit Resource', (SELECT id FROM resource_types WHERE name = 'actions'), 3),
('Action Delete Resource', 'Action Delete Resource', (SELECT id FROM resource_types WHERE name = 'actions'), 4);

INSERT INTO action_relations (parent_action_id, child_action_id) VALUES
((SELECT id FROM actions WHERE name = 'Edit' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Create' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Create' LIMIT 1), (SELECT id FROM actions WHERE name = 'Edit' LIMIT 1)),
((SELECT id FROM actions WHERE name = 'Delete' LIMIT 1), (SELECT id FROM actions WHERE name = 'View' LIMIT 1));

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Action Relation Edit-View Resource', 'Action Relation Edit-View Resource', (SELECT id FROM resource_types WHERE name = 'action_relations'), 1),
('Action Relation Create-View Resource', 'Action Relation Edit-View Resource', (SELECT id FROM resource_types WHERE name = 'action_relations'), 2),
('Action Relation Create-View Resource', 'Action Relation Edit-View Resource', (SELECT id FROM resource_types WHERE name = 'action_relations'), 3),
('Action Relation Delete-View Resource', 'Action Relation Edit-View Resource', (SELECT id FROM resource_types WHERE name = 'action_relations'), 4);

INSERT INTO users (id, name) VALUES
(1, 'Admin User');

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('User ID 1 Resource', 'Resource for User ID 1', (SELECT id FROM resource_types WHERE name = 'users' LIMIT 1), 1);

INSERT INTO org_types (id, name) VALUES
(1, 'Default Type');

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Organization Type ID 1 Resource', 'Resource for Organization Type ID 1', (SELECT id FROM resource_types WHERE name = 'org_types' LIMIT 1), 1);

INSERT INTO organizations (id, name, parent_org_id, org_level, org_type_id) VALUES
(1, 'Default Organization', NULL, 1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('Organization ID 1 Resource', 'Resource for Organization ID 1', (SELECT id FROM resource_types WHERE name = 'organizations' LIMIT 1), 1);

INSERT INTO user_orgs (user_id, org_id) VALUES
(1, 1);

INSERT INTO resources (name, description, resource_type_id, entity_id) VALUES 
('User-Organization Association for User ID 1 and Organization ID 1', 'Resource for User-Organization association between User ID 1 and Organization ID 1', (SELECT id FROM resource_types WHERE name = 'user_orgs' LIMIT 1), 1);