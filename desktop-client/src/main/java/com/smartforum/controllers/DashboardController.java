package com.smartforum.controllers;

import com.google.gson.*;
import com.smartforum.App;
import com.smartforum.services.ApiClient;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.scene.control.*;

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