package com.smartforum.controllers;

import com.google.gson.*;
import com.smartforum.App;
import com.smartforum.services.ApiClient;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Pos;
import javafx.scene.control.*;
import javafx.scene.layout.HBox;
import javafx.scene.layout.Region;
import javafx.scene.layout.VBox;
import javafx.scene.layout.StackPane;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;

import java.io.IOException;
import java.util.List;
import java.util.concurrent.atomic.AtomicBoolean;

public class DashboardController {

    @FXML private ListView<String> groupListView;
    @FXML private ListView<String> topicListView;
    @FXML private ListView<String> postListView;
    @FXML private Label groupTitleLabel;
    @FXML private Label bottomStatusLabel;
    @FXML private Button backButton;

    private ObservableList<String> groups = FXCollections.observableArrayList();
    private ObservableList<String> topics = FXCollections.observableArrayList();
    private ObservableList<String> posts = FXCollections.observableArrayList();

    private int currentGroupId = -1;
    private int currentTopicId = -1;
    private boolean isShowingPosts = false;

    // Cancellation flag for topic loading
    private AtomicBoolean loadingTopics = new AtomicBoolean(false);
    private int loadingGroupId = -1;

    @FXML
    public void initialize() {
        groupListView.setItems(groups);
        topicListView.setItems(topics);
        postListView.setItems(posts);

        // --- Topic List Cell Factory (WhatsApp style) ---
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

        // --- Post List Cell Factory (X-style thread) with wrapping ---
        postListView.setCellFactory(lv -> new ListCell<String>() {
            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }
                // Parse "user: content"
                String[] parts = item.split(": ", 2);
                String author = parts.length > 0 ? parts[0] : "Unknown";
                String content = parts.length > 1 ? parts[1] : item;

                // --- Card container ---
                VBox card = new VBox(4);
                card.getStyleClass().add("post-card");
                card.setMaxWidth(Double.MAX_VALUE);

                // --- Header: Avatar + Author + Timestamp ---
                HBox header = new HBox(8);
                header.getStyleClass().add("header");

                // Avatar (StackPane with circle background and initial)
                StackPane avatarPane = new StackPane();
                avatarPane.setPrefSize(32, 32);
                avatarPane.setStyle("-fx-background-radius: 50%; -fx-background-color: #075E54; -fx-alignment: center;");
                Label avatarLabel = new Label(author.substring(0, 1).toUpperCase());
                avatarLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px;");
                avatarPane.getChildren().add(avatarLabel);

                Label authorLabel = new Label(author);
                authorLabel.getStyleClass().add("author-name");

                Label timestampLabel = new Label("Just now"); // placeholder
                timestampLabel.getStyleClass().add("timestamp");

                Region spacer = new Region();
                HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);

                header.getChildren().addAll(avatarPane, authorLabel, timestampLabel);

                // --- Content (wrapped) ---
                Label contentLabel = new Label(content);
                contentLabel.getStyleClass().add("content");
                contentLabel.setWrapText(true);
                contentLabel.setMaxWidth(Double.MAX_VALUE);

                // --- Actions (placeholders) ---
                HBox actions = new HBox(20);
                actions.getStyleClass().add("actions");
                Button likeBtn = new Button("❤️ Like");
                likeBtn.getStyleClass().add("action-btn");
                Button replyBtn = new Button("💬 Reply");
                replyBtn.getStyleClass().add("action-btn");
                Button shareBtn = new Button("🔗 Share");
                shareBtn.getStyleClass().add("action-btn");
                actions.getChildren().addAll(likeBtn, replyBtn, shareBtn);

                card.getChildren().addAll(header, contentLabel, actions);
                setGraphic(card);
            }
        });

        // Load groups
        loadGroups();

        // Group selection listener
        groupListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null && !isShowingPosts) {
                String[] parts = newVal.split(": ", 2);
                if (parts.length == 2) {
                    currentGroupId = Integer.parseInt(parts[0]);
                    String groupName = parts[1];
                    groupTitleLabel.setText(groupName);
                    loadTopics(currentGroupId);
                }
            }
        });

        // Topic selection listener
        topicListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                String[] parts = newVal.split(": ", 2);
                if (parts.length == 2) {
                    currentTopicId = Integer.parseInt(parts[0]);
                    showPosts(currentTopicId);
                }
            }
        });

        // Back button
        backButton.setOnAction(e -> handleBack());
        backButton.setVisible(false);
    }

    // ---- Data loading methods (unchanged) ----

    private void loadGroups() {
        bottomStatusLabel.setText("Loading groups...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<String> groupStrings = new java.util.ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    int id = obj.get("id").getAsInt();
                    String name = obj.get("name").getAsString();
                    groupStrings.add(id + ": " + name);
                }
                Platform.runLater(() -> {
                    groups.setAll(groupStrings);
                    bottomStatusLabel.setText("Groups loaded");
                });
            } catch (IOException e) {
                Platform.runLater(() -> bottomStatusLabel.setText("Error loading groups: " + e.getMessage()));
                e.printStackTrace();
            }
        }).start();
    }

    private void loadTopics(int groupId) {
        // Cancel any ongoing topic load
        loadingTopics.set(true);
        loadingGroupId = groupId;

        bottomStatusLabel.setText("Loading topics...");
        new Thread(() -> {
            final int requestGroupId = groupId;
            try {
                String response = ApiClient.get("/groups/" + groupId + "/topics");
                JsonArray jsonArray = JsonParser.parseString(response).getAsJsonArray();
                List<String> topicStrings = new java.util.ArrayList<>();
                for (JsonElement el : jsonArray) {
                    JsonObject obj = el.getAsJsonObject();
                    int id = obj.get("id").getAsInt();
                    String title = obj.get("title").getAsString();
                    topicStrings.add(id + ": " + title);
                }
                // Only update if this is the latest request and not cancelled
                if (loadingGroupId == requestGroupId && loadingTopics.get()) {
                    Platform.runLater(() -> {
                        topics.setAll(topicStrings);
                        bottomStatusLabel.setText("Topics loaded");
                        loadingTopics.set(false);
                    });
                }
            } catch (IOException e) {
                if (loadingGroupId == requestGroupId) {
                    Platform.runLater(() -> {
                        bottomStatusLabel.setText("Error loading topics: " + e.getMessage());
                        loadingTopics.set(false);
                    });
                }
                e.printStackTrace();
            }
        }).start();
    }

    private void showPosts(int topicId) {
        isShowingPosts = true;
        backButton.setVisible(true);
        topicListView.setVisible(false);
        topicListView.setManaged(false);
        postListView.setVisible(true);
        postListView.setManaged(true);

        bottomStatusLabel.setText("Loading posts...");
        new Thread(() -> {
            try {
                String response = ApiClient.get("/groups/" + currentGroupId + "/topics/" + topicId);
                JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
                JsonArray postsArray = obj.getAsJsonArray("posts");
                List<String> postStrings = new java.util.ArrayList<>();
                for (JsonElement el : postsArray) {
                    JsonObject p = el.getAsJsonObject();
                    String content = p.get("content").getAsString();
                    String userName = p.get("user") != null && p.get("user").getAsJsonObject().has("name") ?
                            p.get("user").getAsJsonObject().get("name").getAsString() : "Unknown";
                    postStrings.add(userName + ": " + content);
                }
                Platform.runLater(() -> {
                    posts.setAll(postStrings);
                    bottomStatusLabel.setText("Posts loaded");
                });
            } catch (IOException e) {
                Platform.runLater(() -> bottomStatusLabel.setText("Error loading posts: " + e.getMessage()));
                e.printStackTrace();
            }
        }).start();
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
        ApiClient.setToken(null);
        App.showLoginScreen();
    }
}