package com.smartforum.controllers;

import com.google.gson.*;
import com.smartforum.App;
import com.smartforum.services.ApiClient;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.layout.*;
import javafx.scene.paint.Color;
import javafx.scene.shape.Circle;

import java.io.IOException;
import java.util.List;
import java.util.Random;
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

    private AtomicBoolean loadingTopics = new AtomicBoolean(false);
    private int loadingGroupId = -1;

    private java.util.Map<String, Color> groupColors = new java.util.HashMap<>();
    private Random random = new Random();

    @FXML
    public void initialize() {
        groupListView.setItems(groups);
        topicListView.setItems(topics);
        postListView.setItems(posts);

        // ---- Group Cell Factory (Telegram style) ----
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

        // ---- Post List Cell Factory (X-style) ----
        postListView.setCellFactory(lv -> new ListCell<String>() {
            {
                prefWidthProperty().bind(getListView().widthProperty());
            }

            @Override
            protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || item == null) {
                    setText(null);
                    setGraphic(null);
                    return;
                }

                String[] parts = item.split(": ", 2);
                String author = parts.length > 0 ? parts[0] : "Unknown";
                String content = parts.length > 1 ? parts[1] : item;

                VBox card = new VBox(4);
                card.setMaxWidth(Double.MAX_VALUE);
                card.setStyle("-fx-background-color: white; -fx-border-color: #e0e0e0; -fx-border-width: 0 0 1 0; -fx-padding: 12 15;");

                HBox header = new HBox(8);
                header.setStyle("-fx-alignment: center-left;");

                StackPane avatarPane = new StackPane();
                avatarPane.setPrefSize(32, 32);
                avatarPane.setStyle("-fx-background-radius: 50%; -fx-background-color: #075E54; -fx-alignment: center;");
                Label avatarLabel = new Label(author.substring(0, 1).toUpperCase());
                avatarLabel.setStyle("-fx-text-fill: white; -fx-font-weight: bold; -fx-font-size: 14px;");
                avatarPane.getChildren().add(avatarLabel);

                Label authorLabel = new Label(author);
                authorLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px; -fx-text-fill: #0F1419;");

                Label timestampLabel = new Label("Just now");
                timestampLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #8899A6;");

                Region spacer = new Region();
                HBox.setHgrow(spacer, javafx.scene.layout.Priority.ALWAYS);

                header.getChildren().addAll(avatarPane, authorLabel, timestampLabel);

                Label contentLabel = new Label(content);
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

                    System.out.println("Group clicked: " + groupName + " (ID: " + newGroupId + ")");

                    // If currently viewing posts, switch back to topics view
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
                    System.out.println("Topic clicked: " + parts[1] + " (ID: " + currentTopicId + ")");
                    showPosts(currentTopicId);
                }
            }
        });

        backButton.setOnAction(e -> handleBack());
        backButton.setVisible(false);

        // Initially, ensure the topic list is visible and post list hidden
        topicListView.setVisible(true);
        topicListView.setManaged(true);
        postListView.setVisible(false);
        postListView.setManaged(false);
        isShowingPosts = false;
    }

    // ---- Data loading methods ----

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
                System.out.println("Topics loaded for group " + groupId + ": " + topicStrings.size());
                if (loadingGroupId == requestGroupId && loadingTopics.get()) {
                    Platform.runLater(() -> {
                        topics.setAll(topicStrings);
                        // Ensure visibility
                        topicListView.setVisible(true);
                        topicListView.setManaged(true);
                        postListView.setVisible(false);
                        postListView.setManaged(false);
                        bottomStatusLabel.setText("Topics loaded (" + topics.size() + ")");
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
                System.out.println("Posts loaded for topic " + topicId + ": " + postStrings.size());
                Platform.runLater(() -> {
                    posts.setAll(postStrings);
                    bottomStatusLabel.setText("Posts loaded (" + posts.size() + ")");
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