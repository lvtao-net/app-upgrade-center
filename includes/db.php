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
            CREATE TABLE logs (
                id                INTEGER primary key autoincrement,
                appid             INTEGER,
                platform          TEXT,
                version           TEXT,
                header            TEXT,
                info              TEXT,
                created_at        INTEGER,
                ip_address        TEXT,
                user_agent        TEXT,
                request_uri       TEXT,
                host              TEXT,
                content_type      TEXT,
                server_protocol   TEXT,
                https             TEXT,
                device_brand      TEXT,
                device_model      TEXT,
                os_version        TEXT,
                app_language      TEXT,
                screen_width      INTEGER,
                screen_height     INTEGER,
                status_bar_height INTEGER,
                safe_area         TEXT,
                uni_version       TEXT
            );
            CREATE TABLE logs(
                id                INTEGER
                    primary key autoincrement,
                appid             INTEGER,
                platform          TEXT,
                version           TEXT,
                header            TEXT,
                info              TEXT,
                created_at        INTEGER,
                ip_address        TEXT,
                user_agent        TEXT,
                request_uri       TEXT,
                host              TEXT,
                content_type      TEXT,
                server_protocol   TEXT,
                https             TEXT,
                device_brand      TEXT,
                device_model      TEXT,
                os_version        TEXT,
                app_language      TEXT,
                screen_width      INTEGER,
                screen_height     INTEGER,
                status_bar_height INTEGER,
                safe_area         TEXT,
                uni_version       TEXT
            );
            CREATE TABLE apps (id INTEGER PRIMARY KEY, package_name TEXT NOT NULL, logo TEXT, name TEXT NOT NULL, description TEXT, summary TEXT, status INTEGER DEFAULT 1, created_at DATETIME DEFAULT 'CURRENT_TIMESTAMP', rating TEXT DEFAULT '4.9', category TEXT DEFAULT '工具', age TEXT DEFAULT '4+', screenshots TEXT, com TEXT, company TEXT, email TEXT, s_google TEXT, s_huawei TEXT, s_honor TEXT, s_mi TEXT, s_qq TEXT, s_vivo TEXT, s_oppo TEXT, s_apple TEXT);
            CREATE TABLE app_versions (id INTEGER PRIMARY KEY, app_id INTEGER, version TEXT, apk_url TEXT, ipa_url TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, changelog TEXT, ios_version TEXT, force INTEGER);
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
