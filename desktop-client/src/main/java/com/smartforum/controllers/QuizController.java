package com.smartforum.controllers;

import com.google.gson.*;
import com.smartforum.App;
import com.smartforum.services.ApiClient;
import javafx.application.Platform;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.scene.control.*;
import javafx.scene.layout.VBox;
import javafx.stage.Stage;

import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.Timer;
import java.util.TimerTask;

public class QuizController {

    @FXML private Label quizTitle;
    @FXML private Label timerLabel;
    @FXML private VBox questionsContainer;

    private int groupId;
    private int quizId;
    private int durationSeconds;
    private int timeLeft;
    private Timer timer;
    private Timer focusTimer;
    private List<Map<String, Object>> questions = new ArrayList<>();
    private Map<Integer, String> answers = new HashMap<>();
    private int breachCount = 0;
    private static final int MAX_BREACHES = 3;
    private boolean submitted = false;
    private Stage stage;
    private String autoSubmitReason = null; // store reason for auto-submit

    public void initData(int groupId, int quizId, String title, int durationSeconds) {
        this.groupId = groupId;
        this.quizId = quizId;
        this.durationSeconds = durationSeconds;
        this.timeLeft = durationSeconds;
        quizTitle.setText(title);
        startTimer();
        setupLockdown();
        loadQuestions();
    }

    private void loadQuestions() {
        try {
            String response = ApiClient.get("/groups/" + groupId + "/quizzes/" + quizId + "/take");
            JsonObject obj = JsonParser.parseString(response).getAsJsonObject();
            JsonArray questionsArray = obj.getAsJsonArray("questions");
            for (JsonElement el : questionsArray) {
                JsonObject q = el.getAsJsonObject();
                Map<String, Object> map = new HashMap<>();
                map.put("id", q.get("id").getAsInt());
                map.put("question", q.get("question").getAsString());
                map.put("type", q.get("type").getAsString());
                if (q.has("options") && !q.get("options").isJsonNull()) {
                    JsonArray opts = q.getAsJsonArray("options");
                    List<String> options = new ArrayList<>();
                    for (JsonElement opt : opts) {
                        options.add(opt.getAsString());
                    }
                    map.put("options", options);
                }
                questions.add(map);
            }
            Platform.runLater(this::renderQuestions);
        } catch (IOException e) {
            e.printStackTrace();
            Platform.runLater(() -> {
                Alert alert = new Alert(Alert.AlertType.ERROR);
                alert.setTitle("Error");
                alert.setHeaderText("Could not load quiz");
                alert.setContentText(e.getMessage());
                alert.showAndWait();
                try { App.showDashboard(); } catch (Exception ex) { ex.printStackTrace(); }
            });
        }
    }

    private void renderQuestions() {
        questionsContainer.getChildren().clear();
        for (int i = 0; i < questions.size(); i++) {
            Map<String, Object> q = questions.get(i);
            String type = (String) q.get("type");
            String questionText = (String) q.get("question");
            int qId = (int) q.get("id");

            VBox questionBox = new VBox(8);
            questionBox.setPadding(new Insets(10, 0, 10, 0));
            questionBox.setStyle("-fx-border-color: #e0e0e0; -fx-border-width: 0 0 1 0;");

            Label questionLabel = new Label((i+1) + ". " + questionText);
            questionLabel.setStyle("-fx-font-weight: bold; -fx-font-size: 14px;");

            questionBox.getChildren().add(questionLabel);

            if ("multiple_choice".equals(type)) {
                List<String> options = (List<String>) q.get("options");
                ToggleGroup group = new ToggleGroup();
                for (String opt : options) {
                    RadioButton rb = new RadioButton(opt);
                    rb.setToggleGroup(group);
                    rb.setUserData(opt);
                    rb.setOnAction(e -> answers.put(qId, opt));
                    questionBox.getChildren().add(rb);
                }
            } else if ("true_false".equals(type)) {
                ToggleGroup group = new ToggleGroup();
                RadioButton rbTrue = new RadioButton("True");
                RadioButton rbFalse = new RadioButton("False");
                rbTrue.setToggleGroup(group);
                rbFalse.setToggleGroup(group);
                rbTrue.setUserData("true");
                rbFalse.setUserData("false");
                rbTrue.setOnAction(e -> answers.put(qId, "true"));
                rbFalse.setOnAction(e -> answers.put(qId, "false"));
                questionBox.getChildren().addAll(rbTrue, rbFalse);
            } else { // short_answer
                TextField tf = new TextField();
                tf.setPromptText("Type your answer...");
                tf.textProperty().addListener((obs, oldVal, newVal) -> answers.put(qId, newVal));
                questionBox.getChildren().add(tf);
            }

            questionsContainer.getChildren().add(questionBox);
        }
    }

    private void startTimer() {
        timer = new Timer(true);
        timer.scheduleAtFixedRate(new TimerTask() {
            @Override
            public void run() {
                Platform.runLater(() -> {
                    timeLeft--;
                    timerLabel.setText(formatTime(timeLeft));
                    if (timeLeft <= 0) {
                        timer.cancel();
                        autoSubmit("Time expired");
                    }
                });
            }
        }, 1000, 1000);
    }

    private String formatTime(int seconds) {
        int mins = seconds / 60;
        int secs = seconds % 60;
        return String.format("%02d:%02d", mins, secs);
    }

    private void setupLockdown() {
        Stage stage = (Stage) quizTitle.getScene().getWindow();
        this.stage = stage;
        stage.setFullScreen(true);
        stage.setAlwaysOnTop(true);

        focusTimer = new Timer(true);
        focusTimer.scheduleAtFixedRate(new TimerTask() {
            @Override
            public void run() {
                if (!stage.isFocused() && !submitted && timeLeft > 0) {
                    Platform.runLater(() -> {
                        breachCount++;
                        java.awt.Toolkit.getDefaultToolkit().beep();
                        if (breachCount >= MAX_BREACHES) {
                            autoSubmit("Focus lost 3 times");
                        } else {
                            Alert alert = new Alert(Alert.AlertType.WARNING);
                            alert.setTitle("Warning");
                            alert.setHeaderText(null);
                            alert.setContentText("You switched tabs/apps. (" + breachCount + "/" + MAX_BREACHES + ")");
                            alert.showAndWait();
                        }
                    });
                }
            }
        }, 0, 250);
    }

    private void autoSubmit(String reason) {
        if (submitted) return;
        submitted = true;
        autoSubmitReason = reason;
        if (timer != null) timer.cancel();
        if (focusTimer != null) focusTimer.cancel();
        submitQuiz(true);
    }

    @FXML
    private void handleSubmit() {
        if (submitted) return;
        submitted = true;
        if (timer != null) timer.cancel();
        if (focusTimer != null) focusTimer.cancel();
        submitQuiz(false);
    }

    private void submitQuiz(boolean auto) {
        // Build JSON payload
        JsonObject payload = new JsonObject();
        JsonArray answersArray = new JsonArray();
        for (Map.Entry<Integer, String> entry : answers.entrySet()) {
            JsonObject ans = new JsonObject();
            ans.addProperty("question_id", entry.getKey());
            ans.addProperty("answer", entry.getValue());
            answersArray.add(ans);
        }
        payload.add("answers", answersArray);
        payload.addProperty("auto_submit", auto);

        try {
            String response = ApiClient.post("/groups/" + groupId + "/quizzes/" + quizId + "/submit", payload.toString());
            JsonObject result = JsonParser.parseString(response).getAsJsonObject();
            if (result.has("success") && result.get("success").getAsBoolean()) {
                double score = result.get("score").getAsDouble();
                Platform.runLater(() -> {
                    Alert alert = new Alert(Alert.AlertType.INFORMATION);
                    alert.setTitle("Quiz Submitted");
                    String reasonText = auto ? "Auto-submitted: " + autoSubmitReason : "Manual submission";
                    alert.setHeaderText("Your score: " + score + "%");
                    alert.setContentText(reasonText);
                    alert.showAndWait();
                    try { App.showDashboard(); } catch (Exception e) { e.printStackTrace(); }
                });
            } else {
                Platform.runLater(() -> {
                    Alert alert = new Alert(Alert.AlertType.ERROR);
                    alert.setTitle("Error");
                    alert.setHeaderText("Submission failed");
                    alert.setContentText(result.get("message").getAsString());
                    alert.showAndWait();
                });
            }
        } catch (IOException e) {
            e.printStackTrace();
            Platform.runLater(() -> {
                Alert alert = new Alert(Alert.AlertType.ERROR);
                alert.setTitle("Error");
                alert.setHeaderText("Network error");
                alert.setContentText("Could not submit quiz. Please try again.");
                alert.showAndWait();
            });
        }
    }

    @FXML
    private void handleCancel() {
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION);
        confirm.setTitle("Cancel Quiz");
        confirm.setHeaderText("Are you sure you want to cancel?");
        confirm.setContentText("Your progress will be lost.");
        confirm.showAndWait().ifPresent(response -> {
            if (response == ButtonType.OK) {
                submitted = true;
                if (timer != null) timer.cancel();
                if (focusTimer != null) focusTimer.cancel();
                try { App.showDashboard(); } catch (Exception e) { e.printStackTrace(); }
            }
        });
    }
}