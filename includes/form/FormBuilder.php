<?php 
    class FormBuilder {
        private $conn;
        private $formTitle = "Form";
        private $table;
        private $id;
        private $fields = [];
        private $data = [];
        private $link;
        
        public function __construct($conn, $formTitle, $table, $id = null) {
            $this->conn = $conn;
            $this->formTitle = $formTitle;
            $this->table = $table;
            $this->id = $id;
            if ($id !== null) {
                $this->loadData();
            }
            $this->link= $_GET["redirect_link"] ?? "../../index.php?view=home";
        }

        private function loadData() {
            $sql = "SELECT * FROM {$this->table} WHERE id = " . (int)$this->id;
            $result = query($sql);
            if ($result && mysqli_num_rows($result) > 0) {
                $this->data = mysqli_fetch_assoc($result);
            }
        }

        public function addField($type, $name, $label, $options = [], $required = false) {
            $this->fields[] = [
                'type' => $type,
                'name' => $name,
                'label' => $label,
                'options' => $options,
                'required' => $required
            ];
        }

        public function handleSubmit() {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $formData = [];

                foreach ($this->fields as $field) {
                    $fieldName = $field['name'];

                    // Matrix xử lý riêng bằng AJAX
                    if ($field['type'] === 'matrix') continue;

                    if ($field['type'] === 'checkbox') {
                        $formData[$fieldName] = isset($_POST[$fieldName]) ? 1 : 0;
                    } elseif (isset($_POST[$fieldName])) {
                        $value = trim($_POST[$fieldName]);
                        $formData[$fieldName] = ($value === '') ? null : $value;
                    } else {
                        $formData[$fieldName] = null;
                    }
                }

                if ($this->id === null) {
                    $this->insertData($formData);
                } else {
                    $this->updateData($formData);
                }
            }
        }

        private function insertData($formData) {
            $columns = [];
            $values = [];
            foreach ($formData as $key => $value) {
                $columns[] = $key;
                if ($value === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . mysqli_real_escape_string($this->conn, $value) . "'";
                }
            }
            $sql = "INSERT INTO {$this->table} (" . implode(", ", $columns) . ") VALUES (" . implode(", ", $values) . ")";
            if (!query($sql)) {
                $msg = "❌ Lỗi khi thêm dữ liệu: " . mysqli_error($this->conn);
                redirect_with_message("error", $msg, $this->link);
                exit;
            } else {
                $entity_id = mysqli_insert_id($this->conn);

                // Resource auto create
                $resource_type = getResourceTypeByName($this->table);
                $resource_type_id = mysqli_fetch_assoc($resource_type)['id'] ?? null;
                if ($resource_type_id) {
                    $resource_name = $this->table . "_" . $entity_id;
                    $resource_description = "Resource for {$this->table} ID {$entity_id}";
                    $org_id = null; 
                    $user_id = $_SESSION['user']['id'] ?? null;
                    $role_name = "creator";
                    
                    createResource($resource_name, $resource_description, $org_id, $resource_type_id, $entity_id);
                    updateResourceTypeVersion($resource_type_id);
                    assignResourceRoleToUser($user_id, $resource_type_id, $entity_id, $role_name);
                }
                redirect_with_message("success", "Thêm dữ liệu thành công.", $this->link);
                exit;
            }
        }

        private function updateData($formData) {
            $sets = [];
            foreach ($formData as $key => $value) {
                if (!array_key_exists($key, $_POST)) {
                    continue;
                }
                if ($value === null) {
                    $sets[] = "$key = NULL";
                } else {
                    $sets[] = "$key = '" . mysqli_real_escape_string($this->conn, $value) . "'";
                }
            }
            if (empty($sets)) {
                return;
            }

            $sql = "UPDATE {$this->table} SET " . implode(", ", $sets) . " WHERE id = " . (int)$this->id;
            if (!query($sql)) {
                $msg = "❌ Lỗi khi cập nhật dữ liệu: " . mysqli_error($this->conn) . 
                    "\n👉 Query: " . $sql;
                redirect_with_message("error", $msg, $this->link);
                exit;
            }

            // Resource auto update
            $resource_type = getResourceTypeByName($this->table);
            $resource_type_id = mysqli_fetch_assoc($resource_type)['id'] ?? null;
            $entity_id = $this->data["id"] ?? null;
            $resource_id = mysqli_fetch_assoc(getResourceByTypeAndID($resource_type_id, $entity_id))['id'] ?? null;

            $newData = query("SELECT * FROM {$this->table} WHERE id = " . (int)$this->id);
            $row = $newData ? mysqli_fetch_assoc($newData) : $this->data;

            $resource_name = $row["name"] ?? null;
            $resource_description = $row["description"] ?? null;
            $org_id = $row["org_id"] ?? null;

            $user_id = $_SESSION['user']['id'] ?? null;
            $role_name = "editor";

            updateResource($resource_id, $resource_name, $resource_description, $org_id, $resource_type_id, $entity_id);
            updateResourceTypeVersion($resource_type_id);
            assignResourceRoleToUser($user_id, $resource_type_id, $entity_id, $role_name);

            redirect_with_message("success", "Cập nhật dữ liệu thành công.", $this->link);
            exit;
        }

        public function render() {
            echo '<form method="POST" class="form">';
            echo '<h1 class="page-title">' . htmlspecialchars($this->formTitle) . '</h1>';
            foreach ($this->fields as $field) {
                $value = $this->data[$field['name']] ?? '';
                echo '<div class="form-group">';
                echo '<label for="' . htmlspecialchars($field['name']) . '">' . htmlspecialchars($field['label']) . '</label>';
                switch ($field['type']) {
                    case 'text':
                    case 'number':
                    case 'email':
                    case 'password':
                        echo '<input type="' . $field['type'] . '" class="input-text" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" value="' . htmlspecialchars($value) . '" ' . ($field['required'] ? 'required' : '') . ' />';
                        break;
                    case 'textarea':
                        echo '<textarea class="input-text" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" ' . ($field['required'] ? 'required' : '') . '>' . htmlspecialchars($value) . '</textarea>';
                        break;
                    case 'select':
                        echo '<select class="input-select" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" ' . ($field['required'] ? 'required' : '') . '>';
                        foreach ($field['options'] as $optValue => $optLabel) {
                            $selected = ($value == $optValue) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($optValue) . '" ' . $selected . '>' . htmlspecialchars($optLabel) . '</option>';
                        }
                        echo '</select>';
                        break;
                    case 'checkbox':
                        $checked = ($value) ? 'checked' : '';
                        echo '<input type="checkbox" name="' . htmlspecialchars($field['name']) . '" id="' . htmlspecialchars($field['name']) . '" value="1" ' . $checked . ' />';
                        break;
                    case 'hidden':
                        echo '<input type="hidden" name="' . htmlspecialchars($field['name']) . '" value="' . htmlspecialchars($value) . '" />';
                        break;
                    case 'matrix':
                        $hasMatrix = true;
                        $data = $field['options']['result'];
                        $idField = $field['options']['idField'] ?? "id";
                        $checkField = $field['options']['checkField'] ?? "is_checked";
                        $updateUrl = $field['options']['updateUrl'] ?? "#";

                        if (!empty($data)) {
                            echo '<table class="matrix-table"><thead><tr>';
                            $firstRow = $data[0];
                            foreach ($firstRow as $fname => $fval) {
                                if ($fname === $checkField) {
                                    echo "<th></th>";
                                } elseif ($fname === "is_inherited") {
                                    // Ẩn cột is_inherited
                                    continue;
                                } else {
                                    echo "<th>" . htmlspecialchars($fname) . "</th>";
                                }
                            }
                            echo "</tr></thead><tbody>";

                            foreach ($data as $row) {
                                echo "<tr>";
                                foreach ($row as $fname => $fval) {
                                    if ($fname === $checkField) {
                                        $idVal = $row[$idField];

                                        // an toàn hơn: check tồn tại cột
                                        $hasChecked = !empty($row[$checkField]);
                                        $hasInherited = isset($row["is_inherited"]) && $row["is_inherited"];

                                        $isChecked = ($hasChecked || $hasInherited) ? "checked" : "";
                                        $isDisabled = ($hasInherited && !$hasChecked) ? "disabled" : "";

                                        echo "<td>
                                            <input type='checkbox' 
                                                class='matrix-checkbox' 
                                                data-id='{$idVal}' 
                                                data-update-url='{$updateUrl}' 
                                                {$isChecked} {$isDisabled}/>
                                        </td>";
                                    } elseif ($fname === "is_inherited") {
                                        // Ẩn luôn cột dữ liệu
                                        continue;
                                    } else {
                                        echo "<td>" . htmlspecialchars($fval) . "</td>";
                                    }
                                }
                                echo "</tr>";
                            }

                            echo "</tbody></table>";
                            echo '<script src="/assets/js/formbuilder.js"></script>';
                        } else {
                            echo "<p>Không có dữ liệu để hiển thị.</p>";
                        }
                        break;
                }
                echo '</div>';
            }

            echo '<div class="form-buttons">';
            if (isset($hasMatrix) && $hasMatrix) {
                $link = $_GET["redirect_link"] ?? "../../index.php?view=home";
                echo '<button type="button" class="button-cancel" onclick="window.location.href=\'' . htmlspecialchars($link) . '\'">Quay lại</button>';
            } else {
                echo '<button type="button" class="button-cancel" onclick="window.history.back()">Cancel</button>';
                echo '<button type="submit" class="button-submit" name="submit">Submit</button>';
            }
            echo '</div>';

            echo '</form>';
        }
    }
?>
