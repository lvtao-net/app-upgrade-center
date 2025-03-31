<?php
require_once 'db.php';

// 获取所有应用
function getApps() {
    global $db;
    return $db->query('SELECT * FROM apps ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

// 根据 ID 获取单个应用
function getAppById($id) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM apps WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getLatestVersion($app_id) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM app_versions WHERE app_id = :app_id ORDER BY created_at DESC, id DESC LIMIT 1');
    $stmt->execute(['app_id' => $app_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 获取所有版本
function getAllVersions() {
    global $db;
    return $db->query('SELECT * FROM app_versions ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

// 根据 ID 获取单个版本
function getVersionById($id) {
    global $db;
    $stmt = $db->prepare('SELECT * FROM app_versions WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 添加应用
function addApp($data) {
    global $db;

    // 动态生成 SQL 语句的字段名和占位符
    $fields = implode(', ', array_keys($data)); // 生成字段名部分
    $placeholders = ':' . implode(', :', array_keys($data)); // 生成占位符部分

    // 准备动态 SQL
    $sql = "INSERT INTO apps ($fields) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);

    // 执行 SQL，并绑定参数
    $stmt->execute($data);
}

// 编辑应用
function editApp($id, $data) {
    global $db;

    // 动态生成字段和值的绑定部分
    $fields = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
    }
    $fields = implode(', ', $fields);

    // 准备 SQL 语句
    $sql = "UPDATE apps SET $fields WHERE id = :id";
    $stmt = $db->prepare($sql);

    // 为了绑定 `id` 参数，将其加入数据数组
    $data['id'] = $id;

    // 执行 SQL
    $stmt->execute($data);
}

// 删除应用
function deleteApp($id) {
    global $db;
    $db->beginTransaction();
    try {
        $stmt = $db->prepare('DELETE FROM app_versions WHERE app_id = :app_id');
        $stmt->execute(['app_id' => $id]);

        $stmt = $db->prepare('DELETE FROM apps WHERE id = :id');
        $stmt->execute(['id' => $id]);

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

// 添加版本
function addVersion($data) {
    global $db;

    // 动态生成字段名和占位符
    $fields = implode(', ', array_keys($data));
    $placeholders = ':' . implode(', :', array_keys($data));

    // 准备 SQL 语句
    $sql = "INSERT INTO app_versions ($fields) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);

    // 执行 SQL
    $stmt->execute($data);
}

// 编辑版本
function editVersion($id, $data) {
    global $db;

    // 动态生成字段和值的绑定部分
    $fields = [];
    foreach ($data as $key => $value) {
        $fields[] = "$key = :$key";
    }
    $fields = implode(', ', $fields);

    // 准备 SQL 语句
    $sql = "UPDATE app_versions SET $fields WHERE id = :id";
    $stmt = $db->prepare($sql);

    // 添加 ID 到数据中
    $data['id'] = $id;

    // 执行 SQL
    $stmt->execute($data);
}

// 删除版本
function deleteVersion($id) {
    global $db;
    $stmt = $db->prepare('DELETE FROM app_versions WHERE id = :id');
    $stmt->execute(['id' => $id]);
}

function getScheme(){
    if(isset($_SERVER['HTTP_X_CLIENT_SCHEME'])){
        $scheme = $_SERVER['HTTP_X_CLIENT_SCHEME'] . '://';
    }elseif(isset($_SERVER['REQUEST_SCHEME'])){
        $scheme = $_SERVER['REQUEST_SCHEME'] . '://';
    }else{
        $scheme = 'http://';
    }
    return $scheme;
}

// 计算发布时间距离现在的时间
function timeAgo($timestamp) {
    $timeDifference = time() - strtotime($timestamp);
    if ($timeDifference < 60) {
        return "刚刚";
    } elseif ($timeDifference < 3600) {
        return floor($timeDifference / 60) . "分钟前";
    } elseif ($timeDifference < 86400) {
        return floor($timeDifference / 3600) . "小时前";
    } elseif ($timeDifference < 604800) {
        return floor($timeDifference / 86400) . "天前";
    } elseif ($timeDifference < 2419200) { // 4周以内
        return floor($timeDifference / 604800) . "周前";
    } elseif ($timeDifference < 31536000) { // 1年以内
        return date("n月j日", strtotime($timestamp));
    } else {
        return date("Y年n月j日", strtotime($timestamp));
    }
}

function parseMarkdown($markdown) {
    $lines = explode("\n", $markdown);
    $html = "";
    $inList = false;
    $inBlock = false;
    $inTable = false;
    $tableRows = [];
    
    foreach ($lines as $line) {
        $originalLine = htmlspecialchars($line, ENT_NOQUOTES);
        $trimmedLine = trim($line);

        if ($trimmedLine === "") {
            if ($inBlock) {
                $html .= "</blockquote>\n";
                $inBlock = false;
            }
            if ($inList) {
                $html .= "</ul>\n";
                $inList = false;
            }
            if ($inTable && !empty($tableRows)) {
                $html .= processTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }
            continue;
        }

        // 处理标题
        if (preg_match('/^(#{1,6})\s*(.+)$/', $trimmedLine, $matches)) {
            if ($inTable && !empty($tableRows)) {
                $html .= processTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }
            $level = strlen($matches[1]);
            $content = processInline(htmlspecialchars($matches[2]));
            $html .= "<h$level>$content</h$level>\n";
            continue;
        }

        // 处理区块
        if (preg_match('/^>\s*(.+)$/', $trimmedLine, $matches)) {
            if ($inTable && !empty($tableRows)) {
                $html .= processTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }
            if (!$inBlock) {
                $html .= "<blockquote>\n";
                $inBlock = true;
            }
            $html .= "<p>" . processInline(htmlspecialchars($matches[1])) . "</p>\n";
            continue;
        } else if ($inBlock) {
            $html .= "</blockquote>\n";
            $inBlock = false;
        }

        // 处理无序列表
        if (preg_match('/^[-*+]\s+(.+)$/', $trimmedLine, $matches)) {
            if ($inTable && !empty($tableRows)) {
                $html .= processTable($tableRows);
                $tableRows = [];
                $inTable = false;
            }
            if (!$inList) {
                $html .= "<ul>\n";
                $inList = true;
            }
            $html .= "<li>" . processInline(htmlspecialchars($matches[1])) . "</li>\n";
            continue;
        } else if ($inList) {
            $html .= "</ul>\n";
            $inList = false;
        }

        // 处理表格
        if (preg_match('/^\|(.+)\|$/', $trimmedLine)) {
            $inTable = true;
            $row = array_map('trim', explode('|', trim($trimmedLine, '|')));
            $tableRows[] = $row;
            continue;
        } else if ($inTable && !empty($tableRows)) {
            $html .= processTable($tableRows);
            $tableRows = [];
            $inTable = false;
        }

        // 处理普通文本
        if (!$inList && !$inBlock && !$inTable) {
            $html .= "<p>" . processInline($originalLine) . "</p>\n";
        } else if (!$inTable) {
            $html .= processInline($originalLine) . "\n";
        }
    }

    // 关闭未结束的元素
    if ($inBlock) {
        $html .= "</blockquote>\n";
    }
    if ($inList) {
        $html .= "</ul>\n";
    }
    if ($inTable && !empty($tableRows)) {
        $html .= processTable($tableRows);
    }

    return $html;
}

function processTable($rows) {
    if (empty($rows) || count($rows) < 2) {
        return '';
    }

    $html = "<table>\n";
    $isFirstRowSeparator = preg_match('/^-+$/', trim($rows[0][0]));

    if ($isFirstRowSeparator && count($rows) >= 2) {
        // 第一行是分隔行，第二行是表头
        $html .= "<thead>\n<tr>\n";
        foreach ($rows[1] as $header) {
            $html .= "<th>" . processInline(htmlspecialchars($header)) . "</th>\n";
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";
        
        // 从第三行开始是内容
        for ($i = 2; $i < count($rows); $i++) {
            $html .= "<tr>\n";
            foreach ($rows[$i] as $cell) {
                $html .= "<td>" . processInline(htmlspecialchars($cell)) . "</td>\n";
            }
            $html .= "</tr>\n";
        }
    } else {
        // 没有分隔行的情况，第一行作为表头
        $html .= "<thead>\n<tr>\n";
        foreach ($rows[0] as $header) {
            $html .= "<th>" . processInline(htmlspecialchars($header)) . "</th>\n";
        }
        $html .= "</tr>\n</thead>\n<tbody>\n";
        
        for ($i = 1; $i < count($rows); $i++) {
            $html .= "<tr>\n";
            foreach ($rows[$i] as $cell) {
                $html .= "<td>" . processInline(htmlspecialchars($cell)) . "</td>\n";
            }
            $html .= "</tr>\n";
        }
    }

    $html .= "</tbody>\n</table>\n";
    return $html;
}

function processInline($text) {
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $text);
    $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
    $text = preg_replace('/_(.+?)_/', '<em>$1</em>', $text);
    $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
    // 处理链接：[文本](URL)
    $text = preg_replace(
        '/\[([^]]+?)\]\((https?:\/\/[^)\s]+)\)/', 
        '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', 
        $text
    );
    $text = nl2br($text);
    return $text;
}

function addLogs($data) {
    global $db;

    // 动态生成 SQL 语句的字段名和占位符
    $fields = implode(', ', array_keys($data)); // 生成字段名部分
    $placeholders = ':' . implode(', :', array_keys($data)); // 生成占位符部分

    // 准备动态 SQL
    $sql = "INSERT INTO logs ($fields) VALUES ($placeholders)";
    $stmt = $db->prepare($sql);

    // 执行 SQL，并绑定参数
    $stmt->execute($data);
}
?>