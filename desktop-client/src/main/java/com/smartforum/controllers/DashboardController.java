package com.smartforum.controllers;

import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

import com.smartforum.App;
import com.smartforum.services.ApiClient;
import com.smartforum.services.DataService;
import com.smartforum.services.NetworkService;

import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.collections.ObservableList;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.control.Alert;
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
import javafx.scene.layout.GridPane;
import javafx.scene.layout.HBox;
import javafx.scene.layout.VBox;
import javafx.scene.shape.Circle;

public class DashboardController {

    @FXML private ListView<String> groupListView;
    @FXML private ListView<String> topicListView;
    @FXML private ListView<String> postListView;
    @FXML private Label groupTitleLabel;
    @FXML private Label statusLabel;
    @FXML private Label bottomStatusLabel;
    @FXML private TextField searchField;
    @FXML private Button backButton;
    @FXML private Circle statusDot;

    private final ObservableList<String> groups = FXCollections.observableArrayList();
    private final ObservableList<String> topics = FXCollections.observableArrayList();
    private final ObservableList<String> posts = FXCollections.observableArrayList();

    private final DataService data = DataService.getInstance();
    private final NetworkService network = NetworkService.getInstance();

    private int currentGroupId = -1;
    private int currentTopicId = -1;
    private boolean isShowingPosts = false;

    @FXML
    public void initialize() {
        // Set cell factories with proper width handling
        groupListView.setCellFactory(lv -> new GroupCell());
        topicListView.setCellFactory(lv -> new TopicCell());
        postListView.setCellFactory(lv -> new PostCell());

        groupListView.setItems(groups);
        topicListView.setItems(topics);
        postListView.setItems(posts);

        // Network status
        network.start(this::updateStatusUI);

        // Load groups
        loadGroups();

        // Group selection -> load topics
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

        // Topic selection -> show posts
        topicListView.getSelectionModel().selectedItemProperty().addListener((obs, oldVal, newVal) -> {
            if (newVal != null) {
                String[] parts = newVal.split(": ", 2);
                if (parts.length == 2) {
                    currentTopicId = Integer.parseInt(parts[0]);
                    showPosts(currentTopicId);
                }
            }
        });

        // Search filter (placeholder)
        searchField.textProperty().addListener((obs, oldVal, newVal) -> filterGroups(newVal));
    }

    // =============================================
    // DATA LOADING
    // =============================================

    private void loadGroups() {
        bottomStatusLabel.setText("Loading groups...");
        data.loadGroups(
            groupList -> {
                groups.setAll(groupList.stream()
                        .map(g -> g.get("id") + ": " + g.get("name"))
                        .collect(Collectors.toList()));
                bottomStatusLabel.setText("Groups loaded (" + groups.size() + ")");
            },
            error -> bottomStatusLabel.setText("Error loading groups: " + error)
        );
    }

    private void loadTopics(int groupId) {
        bottomStatusLabel.setText("Loading topics...");
        data.loadTopics(groupId,
            topicList -> {
                List<String> newTopics = topicList.stream()
                        .map(t -> t.get("id") + ": " + t.get("title"))
                        .collect(Collectors.toList());
                Platform.runLater(() -> {
                    topics.setAll(newTopics); // atomic replacement – no flicker
                    bottomStatusLabel.setText("Topics loaded (" + topics.size() + ")");
                });
            },
            error -> Platform.runLater(() ->
                bottomStatusLabel.setText("Error loading topics: " + error)
            )
        );
    }

    private void showPosts(int topicId) {
        isShowingPosts = true;
        backButton.setVisible(true);
        topicListView.setVisible(false);
        topicListView.setManaged(false);
        postListView.setVisible(true);
        postListView.setManaged(true);

        bottomStatusLabel.setText("Loading posts...");
        data.loadPosts(currentGroupId, topicId,
            postList -> {
                List<String> newPosts = postList.stream()
                        .map(p -> p.get("user_name") + ": " + p.get("content"))
                        .collect(Collectors.toList());
                Platform.runLater(() -> {
                    posts.setAll(newPosts);
                    bottomStatusLabel.setText("Posts loaded (" + posts.size() + ")");
                });
            },
            error -> Platform.runLater(() ->
                bottomStatusLabel.setText("Error loading posts: " + error)
            )
        );
    }

    // =============================================
    // NAVIGATION
    // =============================================

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

    // =============================================
    // CREATE TOPIC
    // =============================================

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
            data.createTopic(currentGroupId, data.title, data.body, data.isPrivate,
                topicId -> {
                    loadTopics(currentGroupId);
                    bottomStatusLabel.setText("Topic created online");
                },
                error -> bottomStatusLabel.setText("Topic " + error)
            );
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
        void createTopic(int groupId, String title, String body, boolean isPrivate,
                         java.util.function.Consumer<Integer> onSuccess,
                         java.util.function.Consumer<String> onError) {
            DataService.getInstance().createTopic(groupId, title, body, isPrivate, onSuccess, onError);
        }
    }

    // =============================================
    // SYNC
    // =============================================

    @FXML
    public void handleSync() {
        bottomStatusLabel.setText("Syncing...");
        data.syncPendingItems(
            () -> {
                bottomStatusLabel.setText("Sync completed.");
                loadGroups(); // refresh
            },
            error -> bottomStatusLabel.setText("Sync error: " + error)
        );
    }

    // =============================================
    // LOGOUT
    // =============================================

    @FXML
    public void handleLogout() throws Exception {
        network.stop();
        data.clearAllCaches();
        ApiClient.setToken(null);
        App.showLoginScreen();
    }

    // =============================================
    // STATUS UI
    // =============================================

    private void updateStatusUI(boolean online) {
        Platform.runLater(() -> {
            statusLabel.setText(online ? "Online" : "Offline");
            statusDot.getStyleClass().removeAll("online", "offline");
            statusDot.getStyleClass().add(online ? "online" : "offline");
            bottomStatusLabel.setText(online ? "Connected" : "Offline - using cache");
            if (online) {
                // Auto‑sync when we come online
                handleSync();
            }
        });
    }

    private void filterGroups(String query) {
        // Placeholder – we can implement later
    }

    private void showAlert(String message) {
        Alert alert = new Alert(Alert.AlertType.WARNING);
        alert.setTitle("Warning");
        alert.setHeaderText(null);
        alert.setContentText(message);
        alert.showAndWait();
    }

    // =============================================
    // CUSTOM CELL FACTORIES (with overflow fix)
    // =============================================

    private class GroupCell extends ListCell<String> {
        @Override
        protected void updateItem(String item, boolean empty) {
            super.updateItem(item, empty);
            if (empty || item == null) {
                setText(null);
                setGraphic(null);
                return;
            }
            Label label = new Label(item);
            label.getStyleClass().add("group-item");
            setGraphic(label);
        }
    }

    private class TopicCell extends ListCell<String> {
        @Override
        protected void updateItem(String item, boolean empty) {
            super.updateItem(item, empty);
            if (empty || item == null) {
                setText(null);
                setGraphic(null);
                return;
            }
            setMaxWidth(Double.MAX_VALUE); // fill cell

            VBox card = new VBox(4);
            card.setMaxWidth(Double.MAX_VALUE);
            card.getStyleClass().add("card");

            Label titleLabel = new Label(item);
            titleLabel.setWrapText(true);
            titleLabel.setMaxWidth(Double.MAX_VALUE);
            titleLabel.getStyleClass().add("title");

            Label metaLabel = new Label("by User • 2h ago");
            metaLabel.getStyleClass().add("meta");

            card.getChildren().addAll(titleLabel, metaLabel);
            setGraphic(card);
        }
    }

    private class PostCell extends ListCell<String> {
        @Override
        protected void updateItem(String item, boolean empty) {
            super.updateItem(item, empty);
            if (empty || item == null) {
                setText(null);
                setGraphic(null);
                return;
            }
            setMaxWidth(Double.MAX_VALUE); // fill cell

            String[] parts = item.split(": ", 2);
            String author = parts.length > 0 ? parts[0] : "Unknown";
            String content = parts.length > 1 ? parts[1] : item;

            VBox card = new VBox(6);
            card.setMaxWidth(Double.MAX_VALUE);
            card.getStyleClass().add("card");

            HBox header = new HBox(6);
            header.setAlignment(Pos.CENTER_LEFT);
            Label authorLabel = new Label(author);
            authorLabel.getStyleClass().add("author");
            Label timestampLabel = new Label("Just now");
            timestampLabel.getStyleClass().add("timestamp");
            header.getChildren().addAll(authorLabel, timestampLabel);

            Label contentLabel = new Label(content);
            contentLabel.setWrapText(true);
            contentLabel.setMaxWidth(Double.MAX_VALUE);
            contentLabel.getStyleClass().add("content");

            HBox actions = new HBox(15);
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
    }
}