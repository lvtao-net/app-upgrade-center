<?php
$dbFilePath = __DIR__ . '/../db/app_distribution.db';

try {
    if (!file_exists($dbFilePath)) {
        $db = new PDO('sqlite:' . $dbFilePath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 初始化数据库
        $db->exec("
            CREATE TABLE admins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL
            );
            CREATE TABLE apps (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                package_name TEXT NOT NULL,
                logo TEXT,
                name TEXT NOT NULL,
                description TEXT,
                summary TEXT,
                email TEXT,
                com TEXT,
                company TEXT,
                screenshots TEXT,
                rating TEXT DEFAULT '4.9',
                category TEXT DEFAULT '工具',
                age TEXT DEFAULT '4+',
                status INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE app_versions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                app_id INTEGER NOT NULL,
                version TEXT NOT NULL,
                ios_version TEXT NOT NULL,
                changelog TEXT,
                apk_url TEXT,
                ios_store_url TEXT,
                ipa_url TEXT,
                force INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (app_id) REFERENCES apps(id) ON DELETE CASCADE
            );
        ");

        // 添加默认管理员
        $defaultPassword = password_hash('admin', PASSWORD_BCRYPT);
        $db->exec("INSERT INTO admins (username, password) VALUES ('admin', '$defaultPassword')");
    } else {
        $db = new PDO('sqlite:' . $dbFilePath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 检查字段是否存在
        /*
        $result = $db->query("PRAGMA table_info(apps)");
        $columns = $result->fetchAll(PDO::FETCH_COLUMN, 1); // 获取所有字段名
        if (!in_array('com', $columns)) {
            // 添加新字段
            $db->exec("ALTER TABLE apps ADD COLUMN com TEXT");
            echo "Column com added";
        }
        if (!in_array('company', $columns)) {
            // 添加新字段
            $db->exec("ALTER TABLE apps ADD COLUMN company TEXT");
            echo "Column company added";
        }
        */
    }
} catch (Exception $e) {
    die('数据库初始化失败: ' . $e->getMessage());
}
?>