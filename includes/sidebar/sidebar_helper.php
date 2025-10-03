<?php 
function buildTree($array, $parentId = 0) {
    $tree = [];
    foreach ($array as $element) {
        if ($element['parent_org_id'] == $parentId) {
            $children = buildTree($array, $element['id']);
            if ($children) {
                $element['children'] = $children;
            }
            $tree[] = $element;
        }
    }
    return $tree;
}

function renderTree($tree, $activeId = null, $level = 0, $isRoot = true) {
    if (empty($tree)) return;

    $currentView = $_GET['view'] ?? 'home';
    $expandId = isset($_GET['expand']) ? intval($_GET['expand']) : null;

    $ulClass = $isRoot ? 'sidebar-tree' : '';
    echo '<ul class="' . $ulClass . '">';

    foreach ($tree as $element) {
        $hasChildren = isset($element['children']);
        $isActive = ($element['id'] == $activeId);
        $shouldExpand = ($element['id'] == $expandId);
        $hasActiveChild = $hasChildren ? hasActiveChildNode($element['children'], $activeId) : false;

        echo '<li class="' . ($isActive ? 'active' : '') . '">';

        $link = "index.php?view={$currentView}&org_id={$element['id']}";

        if ($hasChildren) {
            $iconClass = ($shouldExpand || $hasActiveChild)
                ? 'toggle-icon open' : 'toggle-icon closed';
            echo '<a href="' . htmlspecialchars($link) . '" class="tree-link has-children">';
            echo '<span class="' . $iconClass . '"></span>';
            echo htmlspecialchars($element['name']);
            echo '</a>';

            // chỉ tạo <ul> 1 lần ở đây, KHÔNG gọi renderTree() thêm <ul> nữa
            $childUlClass = ($shouldExpand || $hasActiveChild) ? 'expanded' : '';
            echo '<ul class="' . $childUlClass . '">';
            foreach ($element['children'] as $child) {
                renderTreeNode($child, $activeId, $currentView, $expandId, $level + 1);
            }
            echo '</ul>';
        } else {
            echo '<a href="' . htmlspecialchars($link) . '" class="tree-link">';
            echo htmlspecialchars($element['name']);
            echo '</a>';
        }

        echo '</li>';
    }

    echo '</ul>';
}

// Hàm riêng chỉ render <li> cho node con
function renderTreeNode($element, $activeId, $currentView, $expandId, $level) {
    $hasChildren = isset($element['children']);
    $isActive = ($element['id'] == $activeId);
    $shouldExpand = ($element['id'] == $expandId);
    $hasActiveChild = $hasChildren ? hasActiveChildNode($element['children'], $activeId) : false;

    echo '<li class="' . ($isActive ? 'active' : '') . '">';
    $link = "index.php?view={$currentView}&org_id={$element['id']}";

    if ($hasChildren) {
        $iconClass = ($shouldExpand || $hasActiveChild)
            ? 'toggle-icon open' : 'toggle-icon closed';
        echo '<a href="' . htmlspecialchars($link) . '" class="tree-link has-children">';
        echo '<span class="' . $iconClass . '"></span>';
        echo htmlspecialchars($element['name']);
        echo '</a>';

        $childUlClass = ($shouldExpand || $hasActiveChild) ? 'expanded' : '';
        echo '<ul class="' . $childUlClass . '">';
        foreach ($element['children'] as $child) {
            renderTreeNode($child, $activeId, $currentView, $expandId, $level + 1);
        }
        echo '</ul>';
    } else {
        echo '<a href="' . htmlspecialchars($link) . '" class="tree-link">';
        echo htmlspecialchars($element['name']);
        echo '</a>';
    }

    echo '</li>';
}

function hasActiveChildNode($tree, $activeId) {
    foreach ($tree as $element) {
        if ($element['id'] == $activeId) return true;
        if (isset($element['children']) && hasActiveChildNode($element['children'], $activeId)) {
            return true;
        }
    }
    return false;
}
?>
