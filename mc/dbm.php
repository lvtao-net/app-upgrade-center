<?php
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

try {
    $db = $db ?? null; // 数据库连接实例在 auth 中定义
    if (!$db) {
        die('数据库连接失败，请检查配置');
    }

    // 获取表列表
    function listTables(PDO $db) {
        $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // 获取表结构
    function getTableStructure(PDO $db, $tableName) {
        $stmt = $db->query("PRAGMA table_info($tableName)");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 动态处理表单操作
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $table = $_POST['table'] ?? '';

        if ($action === 'create-table') {
            $tableName = $_POST['table_name'];
            $db->exec("CREATE TABLE $tableName (id INTEGER PRIMARY KEY AUTOINCREMENT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
            header("Location: dbm.php?table=$tableName");
            exit();
        }

        if ($action === 'delete-table') {
            $db->exec("DROP TABLE $table");
            header("Location: dbm.php");
            exit();
        }

        if ($action === 'add-field') {
            $fieldName = $_POST['field_name'];
            $fieldType = $_POST['field_type'];
            $defaultValue = $_POST['default_value'] ?? null;
        
            // 构造字段定义
            $fieldDefinition = "$fieldName $fieldType";
            if ($_POST['is_not_null'] ?? 0) {
                $fieldDefinition .= " NOT NULL";
            }
            if ($_POST['is_primary_key'] ?? 0) {
                $fieldDefinition .= " PRIMARY KEY";
            }
            if ($defaultValue !== null && $defaultValue !== '') {
               // 检查默认值是否是函数（简单判断：没有引号且是全大写字母的标识符）
                if (preg_match('/^[A-Z_]+$/', $defaultValue)) {
                    $fieldDefinition .= " DEFAULT $defaultValue"; // 函数值直接使用
                } else {
                    $defaultValue = is_numeric($defaultValue) ? $defaultValue : "'$defaultValue'";
                    $fieldDefinition .= " DEFAULT $defaultValue";
                }
            }
        
            // 添加字段
            $db->exec("ALTER TABLE $table ADD COLUMN $fieldDefinition");
            header("Location: dbm.php?table=$table");
            exit();
        }

        if ($action === 'delete-field') {
            $fieldName = $_POST['field_name'];
        
            // 获取原表结构
            $structure = getTableStructure($db, $table);
        
            // 构造新表的字段定义，排除要删除的字段
            $columns = [];
            $columnsForInsert = [];
            foreach ($structure as $column) {
                if ($column['name'] !== $fieldName) {
                    $columnDefinition = $column['name'] . ' ' . $column['type'];
                    if ($column['notnull']) {
                        $columnDefinition .= ' NOT NULL';
                    }
                    if ($column['dflt_value'] !== null) {
                        // 检查并处理默认值
                        if (is_numeric($column['dflt_value'])) {
                            $defaultValue = $column['dflt_value']; // 数字直接使用
                        } else {
                            // 如果不是数字且未包裹引号，则包裹一次单引号
                            $defaultValue = trim($column['dflt_value'], "'"); // 去掉可能存在的多余引号
                            $defaultValue = "'" . str_replace("'", "''", $defaultValue) . "'";
                        }
                        $columnDefinition .= " DEFAULT $defaultValue";
                    }
                    if ($column['pk']) {
                        $columnDefinition .= ' PRIMARY KEY';
                    }
                    $columns[] = $columnDefinition;
                    $columnsForInsert[] = $column['name'];
                }
            }
        
            $newColumnsStr = implode(', ', $columns);
            $columnsForInsertStr = implode(', ', $columnsForInsert);
        
            // 创建新表并迁移数据
            $db->exec("CREATE TABLE ${table}_new ($newColumnsStr)");
            $db->exec("INSERT INTO ${table}_new ($columnsForInsertStr) SELECT $columnsForInsertStr FROM $table");
        
            // 删除旧表并重命名新表
            $db->exec("DROP TABLE $table");
            $db->exec("ALTER TABLE ${table}_new RENAME TO $table");
        
            header("Location: dbm.php?table=$table");
            exit();
        }

        if ($action === 'edit-field') {
            $oldFieldName = $_POST['old_field_name'];
            $newFieldName = $_POST['new_field_name'];
            $newFieldType = $_POST['new_field_type'];
            $defaultValue = $_POST['default_value'] ?? null;
        
            // 获取表结构，创建新的表结构
            $structure = getTableStructure($db, $table);
            $columns = [];
            $columnsForInsert = [];
        
            foreach ($structure as $column) {
                if ($column['name'] === $oldFieldName) {
                    // 针对被编辑的字段，使用表单中提交的值
                    $fieldDefinition = "$newFieldName $newFieldType";
        
                    // 设置新默认值，仅对被修改字段生效
                    if ($defaultValue !== null && $defaultValue !== '') {
                        if (preg_match('/^[A-Z_]+$/', $defaultValue)) {
                            $fieldDefinition .= " DEFAULT $defaultValue"; // 函数值直接使用
                        } else {
                            if (!is_numeric($defaultValue) && !preg_match("/^'.*'$/", $defaultValue)) {
                                $defaultValue = "'" . str_replace("'", "''", $defaultValue) . "'";
                            }
                            $fieldDefinition .= " DEFAULT $defaultValue";
                        }
                    }
        
                    // 保留字段的非空和主键约束
                    if ($_POST['is_not_null'] ?? 0) {
                        $fieldDefinition .= " NOT NULL";
                    }
                    if ($_POST['is_primary_key'] ?? 0) {
                        $fieldDefinition .= " PRIMARY KEY";
                    }
        
                    $columns[] = $fieldDefinition;
                    $columnsForInsert[] = $newFieldName;
                } else {
                    // 对于未修改的字段，完全保留其原始定义
                    $fieldDefinition = $column['name'] . ' ' . $column['type'];
        
                    if ($column['dflt_value'] !== null) {
                        // 仅保留字段的原始默认值，不改变
                        $originalDefaultValue = is_numeric($column['dflt_value'])
                            ? $column['dflt_value']
                            : "'" . str_replace("'", "''", trim($column['dflt_value'], "'")) . "'";
                        $fieldDefinition .= " DEFAULT $originalDefaultValue";
                    }
        
                    // 保留非空和主键约束
                    if ($column['notnull']) {
                        $fieldDefinition .= " NOT NULL";
                    }
                    if ($column['pk']) {
                        $fieldDefinition .= " PRIMARY KEY";
                    }
        
                    $columns[] = $fieldDefinition;
                    $columnsForInsert[] = $column['name'];
                }
            }
        
            $newColumnsStr = implode(', ', $columns);
            $columnsForInsertStr = implode(', ', $columnsForInsert);
        
            // 创建新表并迁移数据
            $db->exec("CREATE TABLE ${table}_new ($newColumnsStr)");
            $db->exec("INSERT INTO ${table}_new ($columnsForInsertStr) SELECT $columnsForInsertStr FROM $table");
        
            // 删除旧表并重命名新表
            $db->exec("DROP TABLE $table");
            $db->exec("ALTER TABLE ${table}_new RENAME TO $table");
        
            header("Location: dbm.php?table=$table");
            exit();
        }

        if ($action === 'delete-record') {
            $id = $_POST['id'];
            $db->exec("DELETE FROM $table WHERE id = $id");
            header("Location: dbm.php?table=$table");
            exit();
        }
        
        if (isset($_POST['sql_query'])) {
            $sqlQuery = trim($_POST['sql_query']);
            try {
                $stmt = $db->query($sqlQuery);
                if ($stmt) {
                    $queryResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $queryResults = [];
                }
            } catch (Exception $e) {
                $queryError = $e->getMessage();
            }
        }
    }

    $tables = listTables($db);
    $currentTable = $_GET['table'] ?? null;
    $tableStructure = $currentTable ? getTableStructure($db, $currentTable) : [];

} catch (Exception $e) {
    die('数据库操作失败: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <title>SQLite数据库管理</title>
</head>
<body class="bg-light">
<div class="container my-5">
      <div class="d-flex justify-content-between align-items-center">
            <h1 onclick="window.location.href='dbm.php';" style="cursor: pointer;">SQLite 数据库管理工具</h1>
            <a href="dashboard.php" class="btn btn-secondary">返回后台首页</a>
        </div>
    <div class="row mt-4">
        <!-- 表列表 -->
        <div class="col-md-3">
            <h3>表列表</h3>
            <ul class="list-group">
                <?php foreach ($tables as $table): ?>
                    <li class="list-group-item <?= ($table === $currentTable) ? 'active' : '' ?>">
                        <a href="dbm.php?table=<?= urlencode($table) ?>" class="text-decoration-none <?= ($table === $currentTable) ? 'text-light' : '' ?>">
                            <?= htmlspecialchars($table) ?>
                        </a>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="delete-table">
                            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
                            <button type="submit" class="btn btn-sm btn-danger float-end">删除</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <form method="POST" class="mt-3">
                <input type="hidden" name="action" value="create-table">
                <div class="input-group">
                    <input type="text" name="table_name" class="form-control" placeholder="新建表名" required>
                    <button type="submit" class="btn btn-primary">添加表</button>
                </div>
            </form>
        </div>

        <!-- 表结构 -->
        <div class="col-md-9">
            <?php if ($currentTable): ?>
                <h3>表: <?= htmlspecialchars($currentTable) ?></h3>

                <!-- 表结构 -->
                <h4>字段管理</h4>
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>字段名</th>
                        <th>类型</th>
                        <th>是否为空</th>
                        <th>默认值</th>
                        <th>主键</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tableStructure as $column): ?>
                        <tr>
                            <td><?= htmlspecialchars($column['name']) ?></td>
                            <td><?= htmlspecialchars($column['type']) ?></td>
                            <td><?= $column['notnull'] ? '否' : '是' ?></td>
                            <td><?= htmlspecialchars($column['dflt_value']) ?></td>
                            <td><?= $column['pk'] ? '是' : '否' ?></td>
                            <td>
                                <?php if (!$column['pk']): ?>
                                    <!-- 删除字段 -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="delete-field">
                                        <input type="hidden" name="table" value="<?= htmlspecialchars($currentTable) ?>">
                                        <input type="hidden" name="field_name" value="<?= htmlspecialchars($column['name']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">删除</button>
                                    </form>
                                    <!-- 编辑字段 -->
                                    <button class="btn btn-sm btn-warning" onclick="editField('<?= htmlspecialchars($column['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($column['type'], ENT_QUOTES) ?>', '<?= htmlspecialchars(trim($column['dflt_value'], "'"), ENT_QUOTES) ?>', <?= $column['notnull'] ? 'true' : 'false' ?>, <?= $column['pk'] ? 'true' : 'false' ?>)">编辑</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 添加字段 -->
                <h4>添加字段</h4>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="add-field">
                    <input type="hidden" name="table" value="<?= htmlspecialchars($currentTable) ?>">
                    <div class="col-md-4">
                        <label for="field_name" class="form-label">字段名</label>
                        <input type="text" name="field_name" class="form-control" id="field_name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="field_type" class="form-label">字段类型</label>
                        <select name="field_type" id="field_type" class="form-select" required>
                            <option value="" disabled selected>请选择字段类型</option>
                            <option value="INTEGER">INTEGER (整型)</option>
                            <option value="TEXT">TEXT (文本)</option>
                            <option value="REAL">REAL (浮点型)</option>
                            <option value="BLOB">BLOB (二进制数据)</option>
                            <option value="NUMERIC">NUMERIC (数值)</option>
                            <option value="DATETIME">DATETIME (日期时间)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="default_value" class="form-label">默认值</label>
                        <input type="text" name="default_value" class="form-control" id="default_value" placeholder="可选">
                    </div>
                    <div class="col-md-4">
                        <label for="is_not_null" class="form-label">是否非空</label>
                        <select name="is_not_null" id="is_not_null" class="form-select">
                            <option value="0" selected>允许为空</option>
                            <option value="1">非空</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="is_primary_key" class="form-label">是否主键</label>
                        <select name="is_primary_key" id="is_primary_key" class="form-select">
                            <option value="0" selected>否</option>
                            <option value="1">是</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">添加字段</button>
                    </div>
                </form>

                <!-- 编辑字段模态框 -->
                <div class="modal fade" id="editFieldModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">编辑字段</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="edit-field">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($currentTable) ?>">
                                <div class="mb-3">
                                    <label for="old_field_name" class="form-label">原字段名</label>
                                    <input type="text" id="old_field_name" name="old_field_name" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="new_field_name" class="form-label">新字段名</label>
                                    <input type="text" id="new_field_name" name="new_field_name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_field_type" class="form-label">新字段类型</label>
                                    <select name="new_field_type" id="new_field_type" class="form-select" required>
                                        <option value="INTEGER">INTEGER (整型)</option>
                                        <option value="TEXT">TEXT (文本)</option>
                                        <option value="REAL">REAL (浮点型)</option>
                                        <option value="BLOB">BLOB (二进制数据)</option>
                                        <option value="NUMERIC">NUMERIC (数值)</option>
                                        <option value="DATETIME">DATETIME (日期时间)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="default_value" class="form-label">默认值</label>
                                    <input type="text" id="default_value_edit" name="default_value" class="form-control" placeholder="留空则无默认值">
                                </div>
                                <div class="mb-3">
                                    <label for="is_not_null_edit" class="form-label">是否非空</label>
                                    <select name="is_not_null" id="is_not_null_edit" class="form-select">
                                        <option value="0" selected>允许为空</option>
                                        <option value="1">非空</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="is_primary_key_edit" class="form-label">是否主键</label>
                                    <select name="is_primary_key" id="is_primary_key_edit" class="form-select">
                                        <option value="0" selected>否</option>
                                        <option value="1">是</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                <button type="submit" class="btn btn-primary">保存更改</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <p>请选择一个表以进行操作。</p>
                
                <!-- SQL 执行区域 -->
                <h4>SQL 执行器</h4>
                <form method="POST" class="mb-4">
                    <div class="mb-3">
                        <label for="sql_query" class="form-label">输入 SQL 查询语句：</label>
                        <textarea name="sql_query" id="sql_query" rows="5" class="form-control" placeholder="输入 SQL 查询"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">执行 SQL</button>
                </form>
        
                <h3>执行结果</h3>
                <?php if (isset($queryError)): ?>
                    <div class="alert alert-danger">SQL 执行错误: <?= htmlspecialchars($queryError) ?></div>
                <?php elseif (isset($queryResults)): ?>
                    <?php if (empty($queryResults)): ?>
                        <p class="text-muted">查询成功，但未返回任何结果。</p>
                    <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                            <tr>
                                <?php foreach (array_keys($queryResults[0]) as $column): ?>
                                    <th><?= htmlspecialchars($column) ?></th>
                                <?php endforeach; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($queryResults as $row): ?>
                                <tr>
                                    <?php foreach ($row as $value): ?>
                                        <td><?= htmlspecialchars($value) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted">请输入 SQL 查询并点击“执行 SQL”以查看结果。</p>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
function editField(fieldName, fieldType, defaultValue, isNotNull, isPrimaryKey) {
    // 设置旧字段名
    document.getElementById('old_field_name').value = fieldName;

    // 设置新字段名为旧字段名（默认值）
    document.getElementById('new_field_name').value = fieldName;

    // 设置字段类型默认值
    const fieldTypeSelect = document.getElementById('new_field_type');
    for (let option of fieldTypeSelect.options) {
        if (option.value === fieldType) {
            option.selected = true;
            break;
        }
    }

    // 设置默认值
    document.getElementById('default_value_edit').value = defaultValue || '';
    
    // 设置非空约束
    document.getElementById('is_not_null_edit').value = isNotNull ? '1' : '0';

    // 设置主键约束
    document.getElementById('is_primary_key_edit').value = isPrimaryKey ? '1' : '0';
    // 打开模态框
    const modal = new bootstrap.Modal(document.getElementById('editFieldModal'));
    modal.show();
}
</script>
</body>
</html>