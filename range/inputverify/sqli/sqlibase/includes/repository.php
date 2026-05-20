<?php
/**
 * HeaSec天积安全团队 - SQL注入基础靶场数据库操作
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 获取数据库连接。
 *
 * @return PDO
 */
function sqlibase_get_pdo()
{
    $config = sqlibase_get_config();
    return HeaSec_Database::getConnection($config['database']);
}

/**
 * 获取资讯列表。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function sqlibase_get_article_list(PDO $pdo)
{
    $articlesTable = sqlibase_table('articles');
    $categoriesTable = sqlibase_table('categories');
    $usersTable = sqlibase_table('users');

    $sql = "SELECT a.id, a.title, a.content, a.publish_date, a.view_count,
                   c.name AS category_name, u.name AS author_name
            FROM {$articlesTable} a
            LEFT JOIN {$categoriesTable} c ON a.category_id = c.id
            LEFT JOIN {$usersTable} u ON a.author_id = u.id
            WHERE a.status = 1
            ORDER BY a.publish_date DESC";

    $stmt = $pdo->query($sql);
    $articles = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($articles as &$article) {
        $article['summary'] = mb_substr($article['content'], 0, 80, 'UTF-8') . '...';
        unset($article['content']);
    }

    return $articles;
}

/**
 * 获取分类列表。
 *
 * @param PDO $pdo 数据库连接
 * @return array
 */
function sqlibase_get_categories(PDO $pdo)
{
    $table = sqlibase_table('categories');
    $stmt = $pdo->query("SELECT * FROM {$table} ORDER BY sort_order ASC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

/**
 * 通过用户名获取用户。
 *
 * @param PDO $pdo 数据库连接
 * @param string $username 用户名
 * @return array|null
 */
function sqlibase_fetch_user_by_username(PDO $pdo, $username)
{
    $table = sqlibase_table('users');
    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

/**
 * 创建用户。
 *
 * @param PDO $pdo 数据库连接
 * @param array $data 用户数据
 * @return int
 */
function sqlibase_create_user(PDO $pdo, array $data)
{
    $table = sqlibase_table('users');
    $stmt = $pdo->prepare("INSERT INTO {$table} (username, password, name, role, status) VALUES (?, ?, ?, 'user', 1)");
    $stmt->execute([$data['username'], $data['password'], $data['name']]);
    return (int) $pdo->lastInsertId();
}

/**
 * 插入反馈记录。
 *
 * @param PDO $pdo 数据库连接
 * @param int $userId 用户ID
 * @param mixed $categoryId 分类ID
 * @param string $content 反馈内容
 * @param string|null $screenshot 截图路径
 */
function sqlibase_insert_feedback(PDO $pdo, $userId, $categoryId, $content, $screenshot)
{
    $table = sqlibase_table('feedback');
    $stmt = $pdo->prepare("INSERT INTO {$table} (user_id, category_id, content, screenshot, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([(int) $userId, (int) $categoryId, $content, $screenshot]);
}
