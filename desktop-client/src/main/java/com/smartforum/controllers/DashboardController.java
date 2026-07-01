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
import com.smartforum.models.Quiz;      // ← Quiz import already present
import com.smartforum.services.ApiClient;
import com.smartforum.services.DatabaseHelper;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.Button;
import javafx.scene.control.ButtonBar;
import javafx.scene.control.ButtonType;
import javafx.scene.control.CheckBox;
import javafx.scene.control.Dialog;
import javafx.scene.control.Label;
import javafx.scene.control.ListCell;
import javafx.scene.control.ListView;
import javafx.scene.control.TextArea;
import javafx.scene.control.TextField;
import javafx.scene.control.ToggleButton;
import javafx.scene.control.Alert;
import javafx.scene.layout.GridPane;
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
    @FXML private ListView<Quiz> quizListView;              // ← added
    @FXML private ToggleButton viewToggle;                 // ← added
    @FXML private Label groupTitleLabel;
    @FXML private Label bottomStatusLabel;
    @FXML private Button backButton;

    private ObservableList<String> groups = FXCollections.observableArrayList();
    private ObservableList<String> topics = FXCollections.observableArrayList();
    private ObservableList<Post> posts = FXCollections.observableArrayList();
    private ObservableList<Quiz> quizzes = FXCollections.observableArrayList();  // ← added
    private boolean showingQuizzes = false;                 // ← added: false = topics, true = quizzes

    private int currentGroupId = -1;
    private int currentTopicId = -1;
    private boolean isShowingPosts = false;

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
        quizListView.setItems(quizzes);                     // ← added

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

        // ---- Quiz List Cell Factory (added) ----
        quizListView.setCellFactory(lv -> new ListCell<Quiz>() {
            @Override
            protected void updateItem(Quiz quiz, boolean empty) {
                super.updateItem(quiz, empty);
                if (empty || quiz == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }
                VBox card = new VBox(4);
                card.setStyle("-fx-padding: 10 15; -fx-border-color: #e0e0e0; -fx-border-width: 0 0 1 0;");
                Label titleLabel = new Label(quiz.getTitle());
                titleLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px; -fx-text-fill: #075E54;");
                Label metaLabel = new Label("Duration: " + quiz.getDuration() + " min" + (quiz.getDescription() != null && !quiz.getDescription().isEmpty() ? " · " + quiz.getDescription() : ""));
                metaLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #8899A6;");
                card.getChildren().addAll(titleLabel, metaLabel);
                setGraphic(card);
            }
        });

        // ---- Post List Cell Factory ----
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

                StackPane avatarPane = new StackPane();
                avatarPane.setPrefSize(32, 32);
                avatarPane.setStyle("-fx-background-radius: 50%; -fx-background-color: #075E54; -fx-alignment: center;");
                Label avatarLabel = new Label(post.getAuthor().substring(0, 1).toUpperCase());
                avatarLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px;");
                avatarPane.getChildren().add(avatarLabel);

                Label authorLabel = new Label(post.getAuthor());
                authorLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px; -fx-text-fill: #0F1419;");

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

        // ---- Quiz selection listener (added) ----
        quizListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                handleTakeQuiz(newVal);
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
                    // If we were viewing posts, go back to the list view first
                    if (isShowingPosts) {
                        isShowingPosts = false;
                        backButton.setVisible(false);
                        // Show either topics or quizzes based on toggle state
                        showAppropriateListView();
                    }
                    currentGroupId = newGroupId;
                    groupTitleLabel.setText(groupName);
                    // Load the appropriate content for this group
                    if (showingQuizzes) {
                        loadQuizzes(currentGroupId);
                    } else {
                        loadTopics(currentGroupId);
                    }
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

        // Initial visibility: show topics, hide quizzes and posts
        viewToggle.setText("Quizzes");               // button says "Quizzes" to switch to that view
        showingQuizzes = false;
        topicListView.setVisible(true);
        topicListView.setManaged(true);
        quizListView.setVisible(false);
        quizListView.setManaged(false);
        postListView.setVisible(false);
        postListView.setManaged(false);
        isShowingPosts = false;

        // ---- Start periodic sync for offline topics ----
        startPeriodicSync();
    }

    // Helper: show the appropriate list (topics or quizzes) based on showingQuizzes
    private void showAppropriateListView() {
        if (showingQuizzes) {
            topicListView.setVisible(false);
            topicListView.setManaged(false);
            quizListView.setVisible(true);
            quizListView.setManaged(true);
        } else {
            topicListView.setVisible(true);
            topicListView.setManaged(true);
            quizListView.setVisible(false);
            quizListView.setManaged(false);
        }
        postListView.setVisible(false);
        postListView.setManaged(false);
        isShowingPosts = false;
    }

    // ---- Data loading methods ----

    private void loadGroups() {
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

    // ---- Load Quizzes (added) ----
    private void loadQuizzes(int groupId) {
        bottomStatusLabel.setText("Loading quizzes...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups/" + groupId + "/quizzes");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<Quiz> quizList = new ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    int id = obj.get("id").getAsInt();
                    String title = obj.get("title").getAsString();
                    int duration = obj.get("duration").getAsInt();
                    String description = obj.has("description") && !obj.get("description").isJsonNull() ? obj.get("description").getAsString() : "";
                    quizList.add(new Quiz(id, title, duration, description));
                }
                Platform.runLater(() -> {
                    quizzes.setAll(quizList);
                    bottomStatusLabel.setText("Quizzes loaded (" + quizzes.size() + ")");
                });
            } catch (IOException e) {
                Platform.runLater(() -> bottomStatusLabel.setText("Error loading quizzes: " + e.getMessage()));
                e.printStackTrace();
            }
        }).start();
    }

    private void showPosts(int topicId) {
        loadingPosts.set(true);
        final int currentLoadId = (int) System.currentTimeMillis();
        loadingTopicId = currentLoadId;

        isShowingPosts = true;
        backButton.setVisible(true);
        // Hide both topic and quiz lists, show posts
        topicListView.setVisible(false);
        topicListView.setManaged(false);
        quizListView.setVisible(false);
        quizListView.setManaged(false);
        postListView.setVisible(true);
        postListView.setManaged(true);

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
                List<Map<String, Object>> postMaps = new ArrayList<>();
                for (JsonElement el : postsArray) {
                    JsonObject p = el.getAsJsonObject();
                    int id = p.get("id").getAsInt();
                    String content = (p.get("content") != null && !p.get("content").isJsonNull()) ? p.get("content").getAsString() : "";
                    String userName = "Unknown";
                    int userId = 0;
                    if (p.has("user") && !p.get("user").isJsonNull()) {
                        JsonObject user = p.getAsJsonObject("user");
                        if (user.has("name") && !user.get("name").isJsonNull()) {
                            userName = user.get("name").getAsString();
                        }
                        if (user.has("id") && !user.get("id").isJsonNull()) {
                            userId = user.get("id").getAsInt();
                        }
                    }
                    String createdAt = (p.get("created_at") != null && !p.get("created_at").isJsonNull()) ? p.get("created_at").getAsString() : "";
                    String updatedAt = (p.get("updated_at") != null && !p.get("updated_at").isJsonNull()) ? p.get("updated_at").getAsString() : "";
                    Boolean isPrivate = p.has("is_private") && !p.get("is_private").isJsonNull() && p.get("is_private").getAsBoolean();
                    Integer parentId = (p.has("parent_id") && !p.get("parent_id").isJsonNull()) ? p.get("parent_id").getAsInt() : null;

                    postList.add(new Post(userName, content, createdAt));

                    Map<String, Object> map = new HashMap<>();
                    map.put("id", (long) id);
                    map.put("topic_id", topicId);
                    map.put("user_id", (long) userId);
                    map.put("user_name", userName);
                    map.put("content", content);
                    map.put("is_private", isPrivate);
                    map.put("parent_id", parentId != null ? (long) parentId : null);
                    map.put("created_at", createdAt);
                    map.put("updated_at", updatedAt);
                    postMaps.add(map);
                }
                db.savePostsForTopic(topicId, postMaps);

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

    // ---- CREATE TOPIC (with offline queue) ----

    @FXML
    public void handleNewTopic() {
        if (currentGroupId == -1) {
            showAlert("Please select a group first.");
            return;
        }

        Dialog<TopicData> dialog = new Dialog<>();
        dialog.setTitle("New Topic");
        dialog.setHeaderText("Create a new topic in this group");

        ButtonType createButtonType = new ButtonType("Create", ButtonBar.ButtonData.OK_DONE);
        dialog.getDialogPane().getButtonTypes().addAll(createButtonType, ButtonType.CANCEL);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(20, 150, 10, 10));

        TextField titleField = new TextField();
        titleField.setPromptText("Title");
        TextArea bodyField = new TextArea();
        bodyField.setPromptText("Body");
        bodyField.setPrefRowCount(4);
        CheckBox privateCheck = new CheckBox("Private");

        grid.add(new Label("Title:"), 0, 0);
        grid.add(titleField, 1, 0);
        grid.add(new Label("Body:"), 0, 1);
        grid.add(bodyField, 1, 1);
        grid.add(privateCheck, 1, 2);

        dialog.getDialogPane().setContent(grid);

        Platform.runLater(() -> titleField.requestFocus());

        dialog.setResultConverter(dialogButton -> {
            if (dialogButton == createButtonType) {
                return new TopicData(titleField.getText(), bodyField.getText(), privateCheck.isSelected());
            }
            return null;
        });

        dialog.showAndWait().ifPresent(data -> {
            if (data.title.isEmpty() || data.body.isEmpty()) {
                showAlert("Title and body are required.");
                return;
            }
            createTopic(currentGroupId, data.title, data.body, data.isPrivate);
        });
    }

    private static class TopicData {
        String title;
        String body;
        boolean isPrivate;
        TopicData(String title, String body, boolean isPrivate) {
            this.title = title;
            this.body = body;
            this.isPrivate = isPrivate;
        }
    }

    private void createTopic(int groupId, String title, String body, boolean isPrivate) {
        // Try to send to API immediately
        try {
            String json = String.format("{\"title\":\"%s\",\"body\":\"%s\",\"is_private\":%b}", title, body, isPrivate);
            String response = ApiClient.post("/groups/" + groupId + "/topics", json);
            JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
            int topicId = obj.get("id").getAsInt();
            Platform.runLater(() -> {
                topics.add(0, topicId + ": " + title);
                loadTopics(groupId); // refresh cache
                bottomStatusLabel.setText("Topic created online");
            });
        } catch (IOException e) {
            // Offline: queue
            String data = String.format("{\"group_id\":%d,\"title\":\"%s\",\"body\":\"%s\",\"is_private\":%b}", groupId, title, body, isPrivate);
            db.addToSyncQueue("create_topic", data);
            Platform.runLater(() -> {
                topics.add(0, "⏳ " + title + " (pending sync)");
                bottomStatusLabel.setText("Topic queued for sync (offline)");
            });
        }
    }

    // ---- SYNC QUEUE ----

    private void syncPendingItems() {
        List<Map<String, Object>> pending = db.getPendingSyncItems();
        if (pending.isEmpty()) return;

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
                    ApiClient.post("/groups/" + groupId + "/topics", json);
                    db.markSyncItemSynced(id);
                    syncedCount++;
                    // Refresh topics for that group
                    loadTopics(groupId);
                }
                // Add other actions later
            } catch (IOException e) {
                // keep in queue
            }
        }
        final int finalSynced = syncedCount;
        Platform.runLater(() -> {
            bottomStatusLabel.setText("Sync completed: " + finalSynced + " items synced.");
        });
    }

    private void startPeriodicSync() {
        new Thread(() -> {
            while (true) {
                try {
                    Thread.sleep(30000); // 30 seconds
                    if (ApiClient.ping()) {
                        syncPendingItems();
                    }
                } catch (InterruptedException e) {
                    break;
                }
            }
        }).start();
    }

    // ---- TOGGLE VIEW (added) ----
    @FXML
    public void toggleView() {
        showingQuizzes = !showingQuizzes;
        viewToggle.setText(showingQuizzes ? "Topics" : "Quizzes");
        // Switch visibility between topics and quizzes
        if (showingQuizzes) {
            topicListView.setVisible(false);
            topicListView.setManaged(false);
            quizListView.setVisible(true);
            quizListView.setManaged(true);
            // Load quizzes for current group if available
            if (currentGroupId != -1) {
                loadQuizzes(currentGroupId);
            }
        } else {
            topicListView.setVisible(true);
            topicListView.setManaged(true);
            quizListView.setVisible(false);
            quizListView.setManaged(false);
            // Load topics for current group if available
            if (currentGroupId != -1) {
                loadTopics(currentGroupId);
            }
        }
        // Hide posts and reset back button
        postListView.setVisible(false);
        postListView.setManaged(false);
        backButton.setVisible(false);
        isShowingPosts = false;
        // Update group title? Keep it as group name, no change.
    }

    // ---- HANDLE TAKE QUIZ (added) ----
    private void handleTakeQuiz(Quiz quiz) {
        try {
            // Pass groupId, quizId, title, duration (convert minutes to seconds)
            App.showQuiz(currentGroupId, quiz.getId(), quiz.getTitle(), quiz.getDuration() * 60);
        } catch (Exception e) {
            e.printStackTrace();
            bottomStatusLabel.setText("Error opening quiz: " + e.getMessage());
        }
    }

    // ---- NAVIGATION ----

    @FXML
    public void handleBack() {
        isShowingPosts = false;
        backButton.setVisible(false);
        // Return to the appropriate list (topics or quizzes)
        showAppropriateListView();
        // Restore group title (may have been changed to "Topics" by old code)
        // We keep it as group name; we might show "Topics" or "Quizzes" prefix
        if (showingQuizzes) {
            groupTitleLabel.setText("Quizzes");
        } else {
            groupTitleLabel.setText("Topics");
        }
        bottomStatusLabel.setText("Back to " + (showingQuizzes ? "quizzes" : "topics"));
        currentTopicId = -1;
    }

    @FXML
    public void handleLogout() throws Exception {
        db.clearAllCaches();
        ApiClient.setToken(null);
        App.showLoginScreen();
    }

    private void showAlert(String message) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle("Warning");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }
}