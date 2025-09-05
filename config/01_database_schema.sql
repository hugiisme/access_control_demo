
-- Bảng gốc 
-- Bảng người dùng
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100)
);

-- Bảng hành động (view, edit, delete,...)
CREATE TABLE actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT
);

-- Bảng loại tổ chức (Trường, Liên chi đoàn, chi đoàn, câu lạc bộ, đội, nhóm,...) (Chi đoàn cơ sở, chi đoàn cán bộ)
CREATE TABLE org_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    is_exclusive INT -- Chỉ được tham gia 1 = 1, được tham gia nhiều = 0
);

-- Bảng tổ chức (nhóm người dùng)
CREATE TABLE organizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    parent_org_id INT COMMENT 'ID của tổ chức cha, NULL nếu là tổ chức gốc',
    org_level INT COMMENT 'Cấp tương đương trong file phân quyền',
    org_type_id INT,
    FOREIGN KEY (parent_org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_type_id) REFERENCES org_types(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng loại tài nguyên (các loại thực thể như hoạt động, minh chứng, thành viên, tài liệu,...)
CREATE TABLE resource_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    version BIGINT COMMENT 'Phiên bản của loại tài nguyên, dùng để so sánh khi cập nhật'
);

-- Bảng quyền tổng quát (quyền = hành động + loại tài nguyên => áp dụng với mọi tài nguyên thuộc loại tài nguyên đó)
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action_id INT,
    resource_type_id INT,
    FOREIGN KEY (action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_type_id) REFERENCES resource_types(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng vai trò hệ thống (Các vai trò trong tổ chức)
CREATE TABLE system_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    org_id INT NOT NULL,
    level INT COMMENT 'Cấp của vai trò (ví dụ 1: Trưởng, 2: Phó, 3: Thư ký, 4: Thành viên)',
    available_slots INT COMMENT 'Số lượng ô trống có thể phân cho vai trò này', 
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng vai trò tài nguyên của người dùng (editor, deleter, viewer,...)
CREATE TABLE user_resource_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT
);

-- Bảng vai trò tài nguyên của tổ chức (owner, collborator, sharer,...)
CREATE TABLE org_resource_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT
);

-- Bảng tài nguyên
CREATE TABLE resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    org_id INT,
    resource_type_id INT COMMENT 'ID trong bảng loại tài nguyên, cũng là tên bảng chứa thực thể',
    entity_id INT COMMENT 'ID của thực thể trong bảng thực thể tương ứng với loại tài nguyên',
    FOREIGN KEY (resource_type_id) REFERENCES resource_types(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng nhóm điều kiện chính sách
CREATE TABLE policy_condition_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    parent_group_id INT COMMENT 'ID nhóm cha',
    operator VARCHAR(50) COMMENT 'Toán tử kết hợp',
    FOREIGN KEY (parent_group_id) REFERENCES policy_condition_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng chính sách
CREATE TABLE policies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    action_id INT,
    resource_id INT,
    condition_group_id INT COMMENT 'ID của nhóm điều kiện, NULL nếu không có',
    FOREIGN KEY (action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (condition_group_id) REFERENCES policy_condition_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng điều kiện chính sách
CREATE TABLE policy_conditions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    condition_group_id INT,
    attribute_name VARCHAR(100) COMMENT 'Tên thuộc tính',
    operator VARCHAR(50) COMMENT 'Toán tử',
    value TEXT COMMENT 'Giá trị',
    FOREIGN KEY (condition_group_id) REFERENCES policy_condition_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng nhóm vai trò hệ thống (Ban thường trực, ban thường vụ, ban chấp hành,...)
CREATE TABLE system_role_groups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    org_id INT COMMENT 'ID tổ chức sở hữu nhóm vai trò',
    parent_group_id INT COMMENT 'ID nhóm cha, NULL nếu là nhóm gốc',
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (parent_group_id) REFERENCES system_role_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ tổ chức - quyền
CREATE TABLE org_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    org_id INT,
    permission_id INT,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ người dùng - tổ chức
CREATE TABLE user_orgs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    org_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Loại quan hệ tài nguyên
CREATE TABLE resource_relation_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) COMMENT 'Tên loại quan hệ',
    description TEXT
);

-- Quan hệ tài nguyên đa hình
CREATE TABLE resource_relations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_id INT,
    related_resource_id INT,
    relation_type INT,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (related_resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (relation_type) REFERENCES resource_relation_types(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ vai trò tài nguyên - hành động
CREATE TABLE resource_role_actions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resource_role_id INT,
    action_id INT,
    FOREIGN KEY (resource_role_id) REFERENCES user_resource_roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng quan hệ giữa các actions 
CREATE TABLE action_relations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    parent_action_id INT COMMENT 'ID của hành động cha',
    child_action_id INT COMMENT 'ID của hành động con',
    FOREIGN KEY (parent_action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (child_action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ vai trò hệ thống - quyền
CREATE TABLE system_role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_role_id INT,
    permission_id INT,
    FOREIGN KEY (system_role_id) REFERENCES system_roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ người dùng - resource
CREATE TABLE user_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    resource_id INT,
    resource_role_id INT COMMENT 'ID vai trò tài nguyên người dùng',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_role_id) REFERENCES user_resource_roles(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ tổ chức - resource
CREATE TABLE org_resources (
    id INT PRIMARY KEY AUTO_INCREMENT,
    org_id INT,
    resource_id INT,
    org_resource_role_id INT COMMENT 'ID vai trò tài nguyên tổ chức',
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_resource_role_id) REFERENCES org_resource_roles(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ người dùng - vai trò hệ thống
CREATE TABLE user_system_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    system_role_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (system_role_id) REFERENCES system_roles(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Quan hệ vai trò hệ thống - nhóm vai trò
CREATE TABLE system_role_group_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_role_id INT,
    system_role_group_id INT,
    FOREIGN KEY (system_role_id) REFERENCES system_roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (system_role_group_id) REFERENCES system_role_groups(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE system_role_group_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    system_role_group_id INT,
    permission_id INT,
    FOREIGN KEY (system_role_group_id) REFERENCES system_role_groups(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Snapshot quyền người dùng
CREATE TABLE user_permission_snapshots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_id INT,
    resource_id INT,
    resource_type_id INT,
    org_id INT COMMENT 'ID tổ chức áp dụng quyền',
    resource_type_version BIGINT COMMENT 'Phiên bản loại tài nguyên tại thời điểm snapshot', -- Không dùng khóa ngoại vì cần có chênh lệch để so sánh
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (action_id) REFERENCES actions(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- Bảng lưu danh sách chờ duyệt vào tổ chức
CREATE TABLE org_join_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    org_id INT,
    status ENUM('Chờ duyệt', 'Đã duyệt', 'Từ chối') COMMENT 'Trạng thái yêu cầu (Chờ duyệt, Đã duyệt, Từ chối)' NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (org_id) REFERENCES organizations(id) ON DELETE CASCADE ON UPDATE CASCADE
);

