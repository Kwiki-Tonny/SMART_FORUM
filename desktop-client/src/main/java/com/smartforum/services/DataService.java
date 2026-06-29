package com.smartforum.services;

import java.io.IOException;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.function.Consumer;

import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;

import javafx.application.Platform;

public class DataService {

    private static DataService instance;
    private final DatabaseHelper db;
    private final ApiClient api;

    // Loading state
    private volatile boolean loadingGroups = false;
    private volatile boolean loadingTopics = false;
    private volatile int loadingGroupId = -1;
    private volatile boolean loadingPosts = false;
    private volatile int loadingTopicId = -1;

    private DataService() {
        db = new DatabaseHelper();
        api = new ApiClient();
    }

    public static synchronized DataService getInstance() {
        if (instance == null) {
            instance = new DataService();
        }
        return instance;
    }

    // ---- Groups ----
    public void loadGroups(Consumer<List<Map<String, Object>>> onSuccess, Consumer<String> onError) {
        if (loadingGroups) return;
        loadingGroups = true;

        // First, emit cached groups
        List<Map<String, Object>> cached = db.getGroups();
        if (!cached.isEmpty()) {
            Platform.runLater(() -> onSuccess.accept(cached));
        }

        new Thread(() -> {
            try {
                String response = api.get("/groups");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<Map<String, Object>> groupList = new java.util.ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    Map<String, Object> g = new HashMap<>();
                    g.put("id", obj.get("id").getAsInt());
                    g.put("name", obj.get("name").getAsString());
                    g.put("description", obj.get("description") != null ? obj.get("description").getAsString() : "");
                    g.put("updated_at", obj.get("updated_at") != null ? obj.get("updated_at").getAsString() : "");
                    groupList.add(g);
                }
                db.saveGroups(groupList);
                Platform.runLater(() -> {
                    onSuccess.accept(groupList);
                    loadingGroups = false;
                });
            } catch (IOException e) {
                e.printStackTrace();
                Platform.runLater(() -> {
                    onError.accept(e.getMessage());
                    loadingGroups = false;
                });
            }
        }).start();
    }

    // ---- Topics ----
    public void loadTopics(int groupId, Consumer<List<Map<String, Object>>> onSuccess, Consumer<String> onError) {
        if (loadingTopics && loadingGroupId == groupId) return;
        loadingTopics = true;
        loadingGroupId = groupId;

        // Cache first
        List<Map<String, Object>> cached = db.getTopicsForGroup(groupId);
        if (!cached.isEmpty()) {
            Platform.runLater(() -> onSuccess.accept(cached));
        }

        new Thread(() -> {
            try {
                String response = api.get("/groups/" + groupId + "/topics");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<Map<String, Object>> topicList = new java.util.ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    Map<String, Object> t = new HashMap<>();
                    t.put("id", obj.get("id").getAsInt());
                    t.put("title", obj.get("title").getAsString());
                    t.put("body", obj.get("body") != null ? obj.get("body").getAsString() : "");
                    t.put("creator_id", obj.get("creator_id") != null ? obj.get("creator_id").getAsInt() : null);
                    t.put("creator_name", obj.get("creator") != null && obj.get("creator").getAsJsonObject().has("name") ?
                            obj.get("creator").getAsJsonObject().get("name").getAsString() : "");
                    t.put("ml_category", obj.get("ml_category") != null ? obj.get("ml_category").getAsString() : "");
                    t.put("created_at", obj.get("created_at") != null ? obj.get("created_at").getAsString() : "");
                    t.put("updated_at", obj.get("updated_at") != null ? obj.get("updated_at").getAsString() : "");
                    topicList.add(t);
                }
                db.saveTopicsForGroup(groupId, topicList);
                Platform.runLater(() -> {
                    if (loadingGroupId == groupId) {
                        onSuccess.accept(topicList);
                        loadingTopics = false;
                    }
                });
            } catch (IOException e) {
                e.printStackTrace();
                Platform.runLater(() -> {
                    if (loadingGroupId == groupId) {
                        onError.accept(e.getMessage());
                        loadingTopics = false;
                    }
                });
            }
        }).start();
    }

    // ---- Posts ----
    public void loadPosts(int groupId, int topicId, Consumer<List<Map<String, Object>>> onSuccess, Consumer<String> onError) {
        if (loadingPosts && loadingTopicId == topicId) return;
        loadingPosts = true;
        loadingTopicId = topicId;

        // Cache first
        List<Map<String, Object>> cached = db.getPostsForTopic(topicId);
        if (!cached.isEmpty()) {
            Platform.runLater(() -> onSuccess.accept(cached));
        }

        new Thread(() -> {
            try {
                String response = api.get("/groups/" + groupId + "/topics/" + topicId);
                JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
                JsonArray postsArray = obj.getAsJsonArray("posts");
                List<Map<String, Object>> postList = new java.util.ArrayList<>();
                for (JsonElement el : postsArray) {
                    JsonObject p = el.getAsJsonObject();
                    Map<String, Object> post = new HashMap<>();
                    post.put("id", p.get("id").getAsInt());
                    post.put("content", p.get("content").getAsString());
                    post.put("user_name", p.get("user") != null && p.get("user").getAsJsonObject().has("name") ?
                            p.get("user").getAsJsonObject().get("name").getAsString() : "Unknown");
                    post.put("parent_id", p.get("parent_id") != null && !p.get("parent_id").isJsonNull() ? p.get("parent_id").getAsInt() : null);
                    post.put("created_at", p.get("created_at") != null ? p.get("created_at").getAsString() : "");
                    post.put("updated_at", p.get("updated_at") != null ? p.get("updated_at").getAsString() : "");
                    post.put("is_private", p.get("is_private") != null && p.get("is_private").getAsBoolean());
                    post.put("user_id", p.get("user_id") != null ? p.get("user_id").getAsInt() : null);
                    postList.add(post);
                }
                db.savePostsForTopic(topicId, postList);
                Platform.runLater(() -> {
                    if (loadingTopicId == topicId) {
                        onSuccess.accept(postList);
                        loadingPosts = false;
                    }
                });
            } catch (IOException e) {
                e.printStackTrace();
                Platform.runLater(() -> {
                    if (loadingTopicId == topicId) {
                        onError.accept(e.getMessage());
                        loadingPosts = false;
                    }
                });
            }
        }).start();
    }

    // ---- Create Topic ----
    public void createTopic(int groupId, String title, String body, boolean isPrivate,
                            Consumer<Integer> onSuccess, Consumer<String> onError) {
        new Thread(() -> {
            try {
                String json = String.format("{\"title\":\"%s\",\"body\":\"%s\",\"is_private\":%b}", title, body, isPrivate);
                String response = api.post("/groups/" + groupId + "/topics", json);
                JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
                int topicId = obj.get("id").getAsInt();
                // Save to cache (we'll refresh later)
                Platform.runLater(() -> onSuccess.accept(topicId));
            } catch (IOException e) {
                e.printStackTrace();
                // Offline: queue
                String data = String.format("{\"group_id\":%d,\"title\":\"%s\",\"body\":\"%s\",\"is_private\":%b}", groupId, title, body, isPrivate);
                db.addToSyncQueue("create_topic", data);
                Platform.runLater(() -> onError.accept("Queued for sync (offline)"));
            }
        }).start();
    }

    // ---- Sync ----
    public void syncPendingItems(Runnable onComplete, Consumer<String> onError) {
        new Thread(() -> {
            List<Map<String, Object>> pending = db.getPendingSyncItems();
            if (pending.isEmpty()) {
                Platform.runLater(onComplete);
                return;
            }
            int syncedCount = 0;
            for (Map<String, Object> item : pending) {
                int id = (int) item.get("id");
                String action = (String) item.get("action");
                String data = (String) item.get("data");
                try {
                    if ("create_topic".equals(action)) {
                        JsonObject obj = JsonParser.parseString(data).getAsJsonObject();
                        int groupId = obj.get("group_id").getAsInt();
                        String title = obj.get("title").getAsString();
                        String body = obj.get("body").getAsString();
                        boolean isPrivate = obj.get("is_private").getAsBoolean();
                        String json = String.format("{\"title\":\"%s\",\"body\":\"%s\",\"is_private\":%b}", title, body, isPrivate);
                        api.post("/groups/" + groupId + "/topics", json);
                        db.markSyncItemSynced(id);
                        syncedCount++;
                    }
                } catch (IOException e) {
                    e.printStackTrace();
                }
            }
            final int finalSynced = syncedCount;
            Platform.runLater(() -> {
                onComplete.run();
            });
        }).start();
    }

    // ---- Clear all caches (on logout) ----
    public void clearAllCaches() {
        db.clearAllCaches();
    }
}