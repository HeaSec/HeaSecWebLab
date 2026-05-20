<?php
/**
 * HeaSec 链接管理API接口（统一分类表版本）
 *
 * @package HeavenlySecret\API
 * @version HeaSec v1.0.0
 */

require_once __DIR__ . '/../../config/config.php';

class HeaSec_LinkManager
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 获取链接列表（支持统一分类表）
     */
    public function getLinks()
    {
        try {
            HeaSec_validateRequest();

            // 使用递归CTE获取分类路径，构建完整的分类信息
            $sql = "SELECT l.id, l.title, l.description, l.code, l.difficulty, l.url,
                           l.category_id, l.sort_order, l.status, l.learning_status, 
                           l.created_at, l.updated_at,
                           c.name as direct_category_name,
                           c.level as category_level,
                           c.parent_id as parent_category_id
                    FROM links l
                    LEFT JOIN all_categories c ON l.category_id = c.id
                    WHERE l.status = 1";

            // 检查是否有分类过滤参数
            $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
            $subcategory_id = isset($_GET['subcategory_id']) ? (int) $_GET['subcategory_id'] : null;
            $third_level_category_id = isset($_GET['third_level_category_id']) ? (int) $_GET['third_level_category_id'] : null;

            $params = [];

            if ($third_level_category_id) {
                // 精确匹配三级分类
                $sql .= " AND l.category_id = ?";
                $params[] = $third_level_category_id;
            } elseif ($subcategory_id) {
                // 匹配二级分类及其下的三级分类
                $sql .= " AND (l.category_id = ? OR l.category_id IN (
                            SELECT id FROM all_categories WHERE parent_id = ? AND level = 3
                          ))";
                $params[] = $subcategory_id;
                $params[] = $subcategory_id;
            } elseif ($category_id) {
                // 匹配一级分类及其所有后代
                $sql .= " AND (l.category_id = ? OR l.category_id IN (
                            SELECT id FROM all_categories WHERE parent_id = ? AND level = 2
                          ) OR l.category_id IN (
                            SELECT c3.id FROM all_categories c3
                            JOIN all_categories c2 ON c3.parent_id = c2.id
                            WHERE c2.parent_id = ? AND c3.level = 3
                          ))";
                $params[] = $category_id;
                $params[] = $category_id;
                $params[] = $category_id;
            }

            $sql .= " ORDER BY l.sort_order ASC, l.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $links = $stmt->fetchAll();

            // 为每个链接构建完整的分类层级信息
            foreach ($links as &$link) {
                $categoryPath = $this->getCategoryPath($link['category_id']);
                $link['category_path'] = $categoryPath;

                // 初始化兼容字段
                $link['subcategory_id'] = null;
                $link['third_level_category_id'] = null;
                $link['category_name'] = null;
                $link['subcategory_name'] = null;
                $link['third_level_category_name'] = null;

                // 根据链接直接所属分类的层级设置对应字段
                $catLevel = $link['category_level'];
                $catId = $link['category_id'];

                if ($catLevel == 1) {
                    // 链接直接属于一级分类（保持category_id）
                    if (count($categoryPath) >= 1) {
                        $link['category_name'] = $categoryPath[0]['name'];
                    }
                } elseif ($catLevel == 2) {
                    // 链接直接属于二级分类
                    $link['subcategory_id'] = $catId;
                    if (count($categoryPath) >= 1) {
                        $link['category_name'] = $categoryPath[0]['name'];
                    }
                    if (count($categoryPath) >= 2) {
                        $link['subcategory_name'] = $categoryPath[1]['name'];
                    }
                } elseif ($catLevel == 3) {
                    // 链接直接属于三级分类
                    $link['third_level_category_id'] = $catId;
                    if (count($categoryPath) >= 1) {
                        $link['category_name'] = $categoryPath[0]['name'];
                    }
                    if (count($categoryPath) >= 2) {
                        $link['subcategory_name'] = $categoryPath[1]['name'];
                        $link['subcategory_id'] = $categoryPath[1]['id']; // 为三级分类的链接也保留父级二级分类ID
                    }
                    if (count($categoryPath) >= 3) {
                        $link['third_level_category_name'] = $categoryPath[2]['name'];
                    }
                }
            }

            // 记录操作日志
            HeaSec_log('get_links', [
                'count' => count($links),
                'category_id' => $category_id,
                'subcategory_id' => $subcategory_id,
                'success' => true
            ]);

            HeaSec_returnResponse(true, '获取链接成功', $links);

        } catch (Exception $e) {
            HeaSec_handleError('获取链接失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取分类的完整路径（从根到当前节点）
     */
    private function getCategoryPath($categoryId)
    {
        if (!$categoryId)
            return [];

        $path = [];
        $currentId = $categoryId;

        while ($currentId !== null) {
            $stmt = $this->db->prepare(
                "SELECT id, parent_id, name, level FROM all_categories WHERE id = ?"
            );
            $stmt->execute([$currentId]);
            $cat = $stmt->fetch();

            if ($cat) {
                array_unshift($path, $cat);
                $currentId = $cat['parent_id'];
            } else {
                break;
            }
        }

        return $path;
    }
}

// 处理请求
$manager = new HeaSec_LinkManager();
$manager->getLinks();
?>