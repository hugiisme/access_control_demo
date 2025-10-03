<?php
    /**
     * Kiểm tra xem trong danh sách children có item nào đang active không (đệ quy)
     */
    function hasActiveChild($children, $currentPage) {
        foreach ($children as $child) {
            if ($child['name'] === $currentPage) return true;
            if (!empty($child['children']) && hasActiveChild($child['children'], $currentPage)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Render menu đệ quy
     */
    function renderMenu($views, $currentPage) {
        foreach ($views as $view) {
            if (!empty($view['debugOnly']) && $view['debugOnly'] && (!defined('IS_DEBUG') || IS_DEBUG === false)) {
                continue;
            }

            $hasChildren = !empty($view['children']);
            $isActive = ($currentPage === $view['name']);
            $childActive = $hasChildren ? hasActiveChild($view['children'], $currentPage) : false;

            echo '<li class="menu-item">';

            if ($hasChildren) {
                // parent được tô màu nếu chính nó active hoặc có child active
                $activeClass = ($isActive || $childActive) ? "active" : "";
                echo '<a href="#" class="' . $activeClass . '">' . htmlspecialchars($view['title']) . '</a>';

                echo '<ul class="submenu">';
                foreach ($view['children'] as $child) {
                    $childIsActive = ($currentPage === $child['name']);
                    echo '<li class="menu-item">';
                    echo '<a href="?view=' . $child['name'] . '" class="' . ($childIsActive ? "active" : "") . '">'
                        . htmlspecialchars($child['title']) . '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<a href="?view=' . $view['name'] . '" class="' . ($isActive ? "active" : "") . '">'
                    . htmlspecialchars($view['title']) . '</a>';
            }

            echo '</li>';
        }
    }

    /**
     * Lấy title của view hiện tại (đệ quy)
     */
    function getTitle($name, $views) {
        foreach ($views as $view) {
            if ($view['name'] === $name) return $view['title'];
            if (!empty($view['children'])) {
                $childTitle = getTitle($name, $view['children']);
                if ($childTitle !== null) return $childTitle;
            }
        }
        return null;
    }
?>
