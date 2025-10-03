<?php 
function resultToOptions($result, $keyField = 'id', $labelField = 'name', $hasNullOption = false) {
    $options = [];
    if ($hasNullOption) {
        $options[''] = '-- None --';
    }
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $options[$row[$keyField]] = $row[$labelField];
        }
    }
    return $options;
}
?>