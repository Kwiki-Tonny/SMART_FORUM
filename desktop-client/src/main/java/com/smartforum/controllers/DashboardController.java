package com.smartforum.controllers;

import java.io.IOException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Random;
import java.util.concurrent.atomic.AtomicBoolean;

import com.google.gson.JsonArray;
import com.google.gson.JsonElement;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import com.smartforum.App;
import com.smartforum.models.Post;
import com.smartforum.services.ApiClient;
import com.smartforum.services.DatabaseHelper;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.Label;
import javafx.scene.control.ListCell;
import javafx.scene.control.ListView;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Priority;
import javafx.scene.layout.Region;
import javafx.scene.layout.StackPane;
import javafx.scene.layout.VBox;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;

public class DashboardController {

    @FXML private ListView<String> groupListView;
    @FXML private ListView<String> topicListView;
    @FXML private ListView<Post> postListView;
    @FXML private Label groupTitleLabel;
    @FXML private Label bottomStatusLabel;
    @FXML private Button backButton;

    private ObservableList<String> groups = FXCollections.observableArrayList();
    private ObservableList<String> topics = FXCollections.observableArrayList();
    private ObservableList<Post> posts = FXCollections.observableArrayList();

    private int currentGroupId = -1;
    private int currentTopicId = -1;
    private boolean isShowingPosts = false;

    // Use two different load IDs to distinguish topics vs posts
    private AtomicBoolean loadingTopics = new AtomicBoolean(false);
    private int loadingGroupId = -1;

    private AtomicBoolean loadingPosts = new AtomicBoolean(false);
    private int loadingTopicId = -1;

    private Map<String, Color> groupColors = new HashMap<>();
    private Random random = new Random();

    private DatabaseHelper db = new DatabaseHelper();

    // Helper: format ISO timestamp to relative time
    private String formatTimestamp(String timestamp) {
        if (timestamp == null || timestamp.isEmpty()) return "Just now";
        try {
            SimpleDateFormat isoFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
            Date date = isoFormat.parse(timestamp);
            long diff = System.currentTimeMillis() - date.getTime();
            long seconds = diff / 1000;
            long minutes = seconds / 60;
            long hours = minutes / 60;
            long days = hours / 24;
            if (days > 0) return days + "d ago";
            if (hours > 0) return hours + "h ago";
            if (minutes > 0) return minutes + "m ago";
            return "Just now";
        } catch (ParseException e) {
            return "Just now";
        }
    }

    @FXML
    public void initialize() {
        groupListView.setItems(groups);
        topicListView.setItems(topics);
        postListView.setItems(posts);

        // ---- Group Cell Factory ----
        groupListView.setCellFactory(lv -> new ListCell<String>() {
            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }
                String[] parts = item.split(": ", 2);
                String name = parts.length > 1 ? parts[1] : item;
                String initial = name.substring(0, 1).toUpperCase();

                Color color = groupColors.get(item);
                if (color == null) {
                    int hue = Math.abs(name.hashCode()) % 360;
                    color = Color.hsb(hue, 0.7, 0.7);
                    groupColors.put(item, color);
                }

                HBox hbox = new HBox(10);
                hbox.setAlignment(javafx.geometry.Pos.CENTER_LEFT);
                hbox.setPadding(new Insets(8, 12, 8, 12));

                Circle avatar = new Circle(20);
                avatar.setFill(color);
                avatar.setStroke(Color.WHITE);
                avatar.setStrokeWidth(1);

                Label initialLabel = new Label(initial);
                initialLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px;");
                StackPane avatarPane = new StackPane(avatar, initialLabel);
                avatarPane.setPrefSize(40, 40);

                Label nameLabel = new Label(name);
                nameLabel.setStyle("-fx-font-size: 14px; -fx-font-weight: 500; -fx-text-fill: #1A1A1A;");

                hbox.getChildren().addAll(avatarPane, nameLabel);
                setGraphic(hbox);
            }
        });

        // ---- Topic List Cell Factory ----
        topicListView.setCellFactory(lv -> new ListCell<String>() {
            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }
                Label label = new Label(item);
                label.setStyle("-fx-font-weight: 500; -fx-font-size: 14px; -fx-padding: 8 0; -fx-text-fill: #075E54;");
                setGraphic(label);
            }
        });

        // ---- Post List Cell Factory (X‑style with real timestamps) ----
        postListView.setCellFactory(lv -> new ListCell<Post>() {
            @Override
            protected void updateItem(Post post, boolean empty) {
                super.updateItem(post, empty);
                if (empty || post == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }

                if (getListView() != null && !prefWidthProperty().isBound()) {
                    prefWidthProperty().bind(getListView().widthProperty());
                }
                setMaxWidth(Double.MAX_VALUE);

                VBox card = new VBox(4);
                card.setMaxWidth(Double.MAX_VALUE);
                card.setStyle("-fx-background-color: white; -fx-border-color: #e0e0e0; -fx-border-width: 0 0 1 0; -fx-padding: 12 15;");

                HBox header = new HBox(8);
                header.setStyle("-fx-alignment: center-left;");

                // Avatar
                StackPane avatarPane = new StackPane();
                avatarPane.setPrefSize(32, 32);
                avatarPane.setStyle("-fx-background-radius: 50%; -fx-background-color: #075E54; -fx-alignment: center;");
                Label avatarLabel = new Label(post.getAuthor().substring(0, 1).toUpperCase());
                avatarLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px;");
                avatarPane.getChildren().add(avatarLabel);

                Label authorLabel = new Label(post.getAuthor());
                authorLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px; -fx-text-fill: #0F1419;");

                // Real timestamp
                String displayTime = formatTimestamp(post.getTimestamp());
                Label timestampLabel = new Label(displayTime);
                timestampLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #8899A6;");

                Region spacer = new Region();
                HBox.setHgrow(spacer, Priority.ALWAYS);

                header.getChildren().addAll(avatarPane, authorLabel, timestampLabel);

                Label contentLabel = new Label(post.getContent());
                contentLabel.setWrapText(true);
                contentLabel.setMaxWidth(Double.MAX_VALUE);
                contentLabel.setStyle("-fx-font-size: 14px; -fx-text-fill: #0F1419; -fx-padding: 4 0 8 0;");

                HBox actions = new HBox(20);
                actions.setStyle("-fx-padding: 4 0 0 0;");
                Button likeBtn = new Button("👍 Like");
                likeBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #8899A6; -fx-font-size: 13px; -fx-cursor: hand; -fx-padding: 0;");
                likeBtn.setOnAction(e -> System.out.println("Like clicked"));
                Button replyBtn = new Button("Reply");
                replyBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #8899A6; -fx-font-size: 13px; -fx-cursor: hand; -fx-padding: 0;");
                replyBtn.setOnAction(e -> System.out.println("Reply clicked"));
                Button shareBtn = new Button("🔗 Share");
                shareBtn.setStyle("-fx-background-color: transparent; -fx-text-fill: #8899A6; -fx-font-size: 13px; -fx-cursor: hand; -fx-padding: 0;");
                shareBtn.setOnAction(e -> System.out.println("Share clicked"));
                actions.getChildren().addAll(likeBtn, replyBtn, shareBtn);

                card.getChildren().addAll(header, contentLabel, actions);
                setGraphic(card);
            }
        });

        // ---- Load groups ----
        loadGroups();

        // ---- Listeners ----
        groupListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                String[] parts = newVal.split(": ", 2);
                if (parts.length == 2) {
                    int newGroupId = Integer.parseInt(parts[0]);
                    String groupName = parts[1];
                    if (isShowingPosts) {
                        isShowingPosts = false;
                        backButton.setVisible(false);
                        topicListView.setVisible(true);
                        topicListView.setManaged(true);
                        postListView.setVisible(false);
                        postListView.setManaged(false);
                        currentTopicId = -1;
                    }
                    currentGroupId = newGroupId;
                    groupTitleLabel.setText(groupName);
                    loadTopics(currentGroupId);
                }
            }
        });

        topicListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                String[] parts = newVal.split(": ", 2);
                if (parts.length == 2) {
                    currentTopicId = Integer.parseInt(parts[0]);
                    showPosts(currentTopicId);
                }
            }
        });

        backButton.setOnAction(e -> handleBack());
        backButton.setVisible(false);

        topicListView.setVisible(true);
        topicListView.setManaged(true);
        postListView.setVisible(false);
        postListView.setManaged(false);
        isShowingPosts = false;
    }

    // ---- Data loading methods (with caching and null‑safe parsing) ----

    private void loadGroups() {
        // Load from cache
        List<Map<String, Object>> cached = db.getGroups();
        if (!cached.isEmpty()) {
            List<String> groupStrings = new ArrayList<>();
            for (Map<String, Object> g : cached) {
                groupStrings.add(g.get("id") + ": " + g.get("name"));
            }
            Platform.runLater(() -> {
                groups.setAll(groupStrings);
                bottomStatusLabel.setText("Groups loaded from cache");
            });
        }

        bottomStatusLabel.setText("Refreshing groups...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<Map<String, Object>> groupList = new ArrayList<>();
                List<String> groupStrings = new ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    int id = obj.get("id").getAsInt();
                    String name = obj.get("name").getAsString();
                    String description = (obj.get("description") != null && !obj.get("description").isJsonNull()) ? obj.get("description").getAsString() : "";
                    String updatedAt = (obj.get("updated_at") != null && !obj.get("updated_at").isJsonNull()) ? obj.get("updated_at").getAsString() : "";
                    groupStrings.add(id + ": " + name);

                    Map<String, Object> g = new HashMap<>();
                    g.put("id", id);
                    g.put("name", name);
                    g.put("description", description);
                    g.put("updated_at", updatedAt);
                    groupList.add(g);
                }
                db.saveGroups(groupList);
                Platform.runLater(() -> {
                    groups.setAll(groupStrings);
                    bottomStatusLabel.setText("Groups refreshed from API");
                });
            } catch (IOException e) {
                Platform.runLater(() -> bottomStatusLabel.setText("Error refreshing groups: " + e.getMessage()));
                e.printStackTrace();
            }
        }).start();
    }

    private void loadTopics(int groupId) {
        loadingTopics.set(true);
        final int currentLoadId = (int) System.currentTimeMillis();
        loadingGroupId = currentLoadId;

        // Load from cache
        List<Map<String, Object>> cached = db.getTopicsForGroup(groupId);
        if (!cached.isEmpty()) {
            List<String> topicStrings = new ArrayList<>();
            for (Map<String, Object> t : cached) {
                topicStrings.add(t.get("id") + ": " + t.get("title"));
            }
            Platform.runLater(() -> {
                topics.setAll(topicStrings);
                bottomStatusLabel.setText("Topics loaded from cache");
            });
        }

        bottomStatusLabel.setText("Refreshing topics...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups/" + groupId + "/topics");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<Map<String, Object>> topicList = new ArrayList<>();
                List<String> topicStrings = new ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    int id = obj.get("id").getAsInt();
                    String title = obj.get("title").getAsString();
                    topicStrings.add(id + ": " + title);

                    Map<String, Object> t = new HashMap<>();
                    t.put("id", id);
                    t.put("title", title);
                    t.put("body", (obj.get("body") != null && !obj.get("body").isJsonNull()) ? obj.get("body").getAsString() : "");
                    t.put("creator_id", (obj.has("creator_id") && !obj.get("creator_id").isJsonNull()) ? obj.get("creator_id").getAsInt() : null);
                    String creatorName = "";
                    if (obj.has("creator") && !obj.get("creator").isJsonNull()) {
                        JsonObject creator = obj.getAsJsonObject("creator");
                        if (creator.has("name") && !creator.get("name").isJsonNull()) {
                            creatorName = creator.get("name").getAsString();
                        }
                    }
                    t.put("creator_name", creatorName);
                    t.put("ml_category", (obj.get("ml_category") != null && !obj.get("ml_category").isJsonNull()) ? obj.get("ml_category").getAsString() : "");
                    t.put("created_at", (obj.get("created_at") != null && !obj.get("created_at").isJsonNull()) ? obj.get("created_at").getAsString() : "");
                    t.put("updated_at", (obj.get("updated_at") != null && !obj.get("updated_at").isJsonNull()) ? obj.get("updated_at").getAsString() : "");
                    topicList.add(t);
                }
                db.saveTopicsForGroup(groupId, topicList);

                if (loadingGroupId == currentLoadId) {
                    Platform.runLater(() -> {
                        topics.setAll(topicStrings);
                        bottomStatusLabel.setText("Topics refreshed from API");
                        loadingTopics.set(false);
                    });
                }
            } catch (IOException e) {
                if (loadingGroupId == currentLoadId) {
                    Platform.runLater(() -> {
                        bottomStatusLabel.setText("Error refreshing topics: " + e.getMessage());
                        loadingTopics.set(false);
                    });
                }
                e.printStackTrace();
            }
        }).start();
    }

    private void showPosts(int topicId) {
        loadingPosts.set(true);
        final int currentLoadId = (int) System.currentTimeMillis();
        loadingTopicId = currentLoadId;

        // Switch UI to posts view
        isShowingPosts = true;
        backButton.setVisible(true);
        topicListView.setVisible(false);
        topicListView.setManaged(false);
        postListView.setVisible(true);
        postListView.setManaged(true);

        // Load from cache
        List<Map<String, Object>> cached = db.getPostsForTopic(topicId);
        if (!cached.isEmpty()) {
            List<Post> postList = new ArrayList<>();
            for (Map<String, Object> p : cached) {
                String userName = (String) p.get("user_name");
                String content = (String) p.get("content");
                String createdAt = (String) p.get("created_at");
                postList.add(new Post(userName, content, createdAt));
            }
            Platform.runLater(() -> {
                posts.setAll(postList);
                bottomStatusLabel.setText("Posts loaded from cache");
            });
        }

        bottomStatusLabel.setText("Refreshing posts...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups/" + currentGroupId + "/topics/" + topicId);
                JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
                JsonArray postsArray = obj.getAsJsonArray("posts");
                List<Post> postList = new ArrayList<>();
                for (JsonElement el : postsArray) {
                    JsonObject p = el.getAsJsonObject();
                    String content = (p.get("content") != null && !p.get("content").isJsonNull()) ? p.get("content").getAsString() : "";
                    
                    String userName = "Unknown";
                    if (p.has("user") && !p.get("user").isJsonNull()) {
                        JsonObject user = p.getAsJsonObject("user");
                        if (user.has("name") && !user.get("name").isJsonNull()) {
                            userName = user.get("name").getAsString();
                        }
                    }
                    String createdAt = (p.get("created_at") != null && !p.get("created_at").isJsonNull()) ? p.get("created_at").getAsString() : "";
                    postList.add(new Post(userName, content, createdAt));
                }
                // Save to cache
                db.savePostsForTopic(topicId, postListToMap(postList, topicId));

                if (loadingTopicId == currentLoadId) {
                    Platform.runLater(() -> {
                        posts.setAll(postList);
                        bottomStatusLabel.setText("Posts refreshed from API");
                        loadingPosts.set(false);
                    });
                }
            } catch (IOException e) {
                if (loadingTopicId == currentLoadId) {
                    Platform.runLater(() -> {
                        bottomStatusLabel.setText("Error refreshing posts: " + e.getMessage());
                        loadingPosts.set(false);
                    });
                }
                e.printStackTrace();
            }
        }).start();
    }

    // Helper: convert List<Post> to List<Map> for DatabaseHelper (keeps cache)
    private List<Map<String, Object>> postListToMap(List<Post> postList, int topicId) {
        List<Map<String, Object>> result = new ArrayList<>();
        for (Post p : postList) {
            Map<String, Object> map = new HashMap<>();
            map.put("id", System.currentTimeMillis() + new Random().nextInt(1000)); // dummy id for cache
            map.put("topic_id", topicId);
            map.put("user_name", p.getAuthor());
            map.put("content", p.getContent());
            map.put("created_at", p.getTimestamp());
            map.put("updated_at", p.getTimestamp());
            map.put("user_id", 0); // placeholder
            map.put("is_private", false);
            map.put("parent_id", null);
            result.add(map);
        }
        return result;
    }

    @FXML
    public void handleBack() {
        isShowingPosts = false;
        backButton.setVisible(false);
        topicListView.setVisible(true);
        topicListView.setManaged(true);
        postListView.setVisible(false);
        postListView.setManaged(false);
        groupTitleLabel.setText("Topics");
        bottomStatusLabel.setText("Back to topics");
        currentTopicId = -1;
    }

    @FXML
    public void handleLogout() throws Exception {
        db.clearAllCaches();
        ApiClient.setToken(null);
        App.showLoginScreen();
    }
}