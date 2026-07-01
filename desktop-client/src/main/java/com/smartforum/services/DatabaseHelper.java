package com.smartforum.services;

import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class DatabaseHelper {

    private static final String DB_URL = "jdbc:sqlite:smartforum.db";

    public DatabaseHelper() {
        createTables();
    }

    private void createTables() {
        String groupsTable = """
                CREATE TABLE IF NOT EXISTS groups (
                    id INTEGER PRIMARY KEY,
                    name TEXT NOT NULL,
                    description TEXT,
                    updated_at TEXT
                )
                """;

        String topicsTable = """
                CREATE TABLE IF NOT EXISTS topics (
                    id INTEGER PRIMARY KEY,
                    group_id INTEGER NOT NULL,
                    title TEXT NOT NULL,
                    body TEXT,
                    creator_id INTEGER,
                    creator_name TEXT,
                    ml_category TEXT,
                    created_at TEXT,
                    updated_at TEXT,
                    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE
                )
                """;

        String postsTable = """
                CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER PRIMARY KEY,
                    topic_id INTEGER NOT NULL,
                    user_id INTEGER,
                    user_name TEXT,
                    content TEXT NOT NULL,
                    is_private INTEGER DEFAULT 0,
                    parent_id INTEGER,
                    created_at TEXT,
                    updated_at TEXT,
                    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
                    FOREIGN KEY (parent_id) REFERENCES posts(id) ON DELETE CASCADE
                )
                """;

        String syncQueueTable = """
                CREATE TABLE IF NOT EXISTS sync_queue (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    action TEXT NOT NULL,
                    data TEXT NOT NULL,
                    created_at TEXT,
                    synced INTEGER DEFAULT 0
                )
                """;

        String userTable = """
                CREATE TABLE IF NOT EXISTS user (
                    id INTEGER PRIMARY KEY,
                    name TEXT,
                    email TEXT,
                    token TEXT
                )
                """;

        String metadataTable = """
                CREATE TABLE IF NOT EXISTS metadata (
                    key TEXT PRIMARY KEY,
                    value TEXT
                )
                """;

        // Indexes
        String idxTopicsGroup = "CREATE INDEX IF NOT EXISTS idx_topics_group_id ON topics(group_id)";
        String idxPostsTopic = "CREATE INDEX IF NOT EXISTS idx_posts_topic_id ON posts(topic_id)";
        String idxPostsParent = "CREATE INDEX IF NOT EXISTS idx_posts_parent_id ON posts(parent_id)";
        String idxSyncQueueSynced = "CREATE INDEX IF NOT EXISTS idx_sync_queue_synced ON sync_queue(synced)";

        try (Connection conn = DriverManager.getConnection(DB_URL);
             Statement stmt = conn.createStatement()) {
            stmt.execute(groupsTable);
            stmt.execute(topicsTable);
            stmt.execute(postsTable);
            stmt.execute(syncQueueTable);
            stmt.execute(userTable);
            stmt.execute(metadataTable);
            stmt.execute(idxTopicsGroup);
            stmt.execute(idxPostsTopic);
            stmt.execute(idxPostsParent);
            stmt.execute(idxSyncQueueSynced);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // ---- Groups ----
    public void saveGroups(List<Map<String, Object>> groups) {
        String deleteSQL = "DELETE FROM groups";
        String insertSQL = "INSERT INTO groups (id, name, description, updated_at) VALUES (?, ?, ?, ?)";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement delStmt = conn.prepareStatement(deleteSQL);
             PreparedStatement insStmt = conn.prepareStatement(insertSQL)) {
            delStmt.execute();
            for (Map<String, Object> group : groups) {
                insStmt.setInt(1, (int) group.get("id"));
                insStmt.setString(2, (String) group.get("name"));
                insStmt.setString(3, (String) group.get("description"));
                insStmt.setString(4, (String) group.get("updated_at"));
                insStmt.addBatch();
            }
            insStmt.executeBatch();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public List<Map<String, Object>> getGroups() {
        List<Map<String, Object>> groups = new ArrayList<>();
        String sql = "SELECT id, name, description, updated_at FROM groups";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            while (rs.next()) {
                Map<String, Object> group = new HashMap<>();
                group.put("id", rs.getInt("id"));
                group.put("name", rs.getString("name"));
                group.put("description", rs.getString("description"));
                group.put("updated_at", rs.getString("updated_at"));
                groups.add(group);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return groups;
    }

    // ---- Topics ----
    public void saveTopicsForGroup(int groupId, List<Map<String, Object>> topics) {
        String deleteSQL = "DELETE FROM topics WHERE group_id = ?";
        String insertSQL = """
                INSERT INTO topics (id, group_id, title, body, creator_id, creator_name, ml_category, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement delStmt = conn.prepareStatement(deleteSQL);
             PreparedStatement insStmt = conn.prepareStatement(insertSQL)) {
            delStmt.setInt(1, groupId);
            delStmt.execute();
            for (Map<String, Object> topic : topics) {
                insStmt.setInt(1, (int) topic.get("id"));
                insStmt.setInt(2, groupId);
                insStmt.setString(3, (String) topic.get("title"));
                insStmt.setString(4, (String) topic.get("body"));
                insStmt.setObject(5, topic.get("creator_id"));
                insStmt.setString(6, (String) topic.get("creator_name"));
                insStmt.setString(7, (String) topic.get("ml_category"));
                insStmt.setString(8, (String) topic.get("created_at"));
                insStmt.setString(9, (String) topic.get("updated_at"));
                insStmt.addBatch();
            }
            insStmt.executeBatch();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public List<Map<String, Object>> getTopicsForGroup(int groupId) {
        List<Map<String, Object>> topics = new ArrayList<>();
        String sql = "SELECT * FROM topics WHERE group_id = ? ORDER BY created_at DESC";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, groupId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> topic = new HashMap<>();
                topic.put("id", rs.getInt("id"));
                topic.put("group_id", rs.getInt("group_id"));
                topic.put("title", rs.getString("title"));
                topic.put("body", rs.getString("body"));
                topic.put("creator_id", rs.getObject("creator_id"));
                topic.put("creator_name", rs.getString("creator_name"));
                topic.put("ml_category", rs.getString("ml_category"));
                topic.put("created_at", rs.getString("created_at"));
                topic.put("updated_at", rs.getString("updated_at"));
                topics.add(topic);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return topics;
    }

    // ---- Posts ----
    public void savePostsForTopic(int topicId, List<Map<String, Object>> posts) {
        String deleteSQL = "DELETE FROM posts WHERE topic_id = ?";
        String insertSQL = """
                INSERT INTO posts (id, topic_id, user_id, user_name, content, is_private, parent_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                """;
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement delStmt = conn.prepareStatement(deleteSQL);
             PreparedStatement insStmt = conn.prepareStatement(insertSQL)) {
            delStmt.setInt(1, topicId);
            delStmt.execute();
            for (Map<String, Object> post : posts) {
                Number id = (Number) post.get("id");
                Number userId = (Number) post.get("user_id");
                insStmt.setLong(1, id.longValue());
                insStmt.setInt(2, topicId);
                insStmt.setLong(3, userId != null ? userId.longValue() : 0);
                insStmt.setString(4, (String) post.get("user_name"));
                insStmt.setString(5, (String) post.get("content"));
                insStmt.setInt(6, (Boolean) post.getOrDefault("is_private", false) ? 1 : 0);
                Object parentId = post.get("parent_id");
                if (parentId != null) {
                    insStmt.setLong(7, ((Number) parentId).longValue());
                } else {
                    insStmt.setNull(7, java.sql.Types.INTEGER);
                }
                insStmt.setString(8, (String) post.get("created_at"));
                insStmt.setString(9, (String) post.get("updated_at"));
                insStmt.addBatch();
            }
            insStmt.executeBatch();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public List<Map<String, Object>> getPostsForTopic(int topicId) {
        List<Map<String, Object>> posts = new ArrayList<>();
        String sql = "SELECT * FROM posts WHERE topic_id = ? ORDER BY created_at ASC";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, topicId);
            ResultSet rs = pstmt.executeQuery();
            while (rs.next()) {
                Map<String, Object> post = new HashMap<>();
                post.put("id", rs.getInt("id"));
                post.put("topic_id", rs.getInt("topic_id"));
                post.put("user_id", rs.getObject("user_id"));
                post.put("user_name", rs.getString("user_name"));
                post.put("content", rs.getString("content"));
                post.put("is_private", rs.getInt("is_private") == 1);
                post.put("parent_id", rs.getObject("parent_id"));
                post.put("created_at", rs.getString("created_at"));
                post.put("updated_at", rs.getString("updated_at"));
                posts.add(post);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return posts;
    }

    // ---- Sync Queue ----
    public void addToSyncQueue(String action, String data) {
        String sql = "INSERT INTO sync_queue (action, data, created_at, synced) VALUES (?, ?, datetime('now'), 0)";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, action);
            pstmt.setString(2, data);
            pstmt.execute();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public List<Map<String, Object>> getPendingSyncItems() {
        List<Map<String, Object>> items = new ArrayList<>();
        String sql = "SELECT id, action, data, created_at FROM sync_queue WHERE synced = 0 ORDER BY id ASC";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             Statement stmt = conn.createStatement();
             ResultSet rs = stmt.executeQuery(sql)) {
            while (rs.next()) {
                Map<String, Object> item = new HashMap<>();
                item.put("id", rs.getInt("id"));
                item.put("action", rs.getString("action"));
                item.put("data", rs.getString("data"));
                item.put("created_at", rs.getString("created_at"));
                items.add(item);
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return items;
    }

    public void markSyncItemSynced(int id) {
        String sql = "UPDATE sync_queue SET synced = 1 WHERE id = ?";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setInt(1, id);
            pstmt.execute();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public void clearSyncQueue() {
        String sql = "DELETE FROM sync_queue WHERE synced = 1";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             Statement stmt = conn.createStatement()) {
            stmt.execute(sql);
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // ---- Clear all caches ----
    public void clearAllCaches() {
        try (Connection conn = DriverManager.getConnection(DB_URL);
             Statement stmt = conn.createStatement()) {
            stmt.execute("DELETE FROM groups");
            stmt.execute("DELETE FROM topics");
            stmt.execute("DELETE FROM posts");
            stmt.execute("DELETE FROM sync_queue");
            stmt.execute("DELETE FROM user");
            stmt.execute("DELETE FROM metadata");
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    // ---- Metadata ----
    public void setMetadata(String key, String value) {
        String sql = "INSERT OR REPLACE INTO metadata (key, value) VALUES (?, ?)";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, key);
            pstmt.setString(2, value);
            pstmt.execute();
        } catch (SQLException e) {
            e.printStackTrace();
        }
    }

    public String getMetadata(String key) {
        String sql = "SELECT value FROM metadata WHERE key = ?";
        try (Connection conn = DriverManager.getConnection(DB_URL);
             PreparedStatement pstmt = conn.prepareStatement(sql)) {
            pstmt.setString(1, key);
            ResultSet rs = pstmt.executeQuery();
            if (rs.next()) {
                return rs.getString("value");
            }
        } catch (SQLException e) {
            e.printStackTrace();
        }
        return null;
    }
}