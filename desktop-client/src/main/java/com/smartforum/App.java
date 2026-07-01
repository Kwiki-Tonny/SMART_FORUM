package com.smartforum;

import com.smartforum.controllers.QuizController;

import javafx.application.Application;
import javafx.fxml.FXMLLoader;
import javafx.scene.Scene;
import javafx.scene.layout.Pane;
import javafx.stage.Stage;

public class App extends Application {

    private static Stage primaryStage;

    @Override
    public void start(Stage stage) throws Exception {
        primaryStage = stage;
        showLoginScreen();
    }

    public static void showLoginScreen() throws Exception {
        FXMLLoader loader = new FXMLLoader(App.class.getResource("/fxml/login.fxml"));
        Pane root = loader.load();
        Scene scene = new Scene(root, 400, 500);
        scene.getStylesheets().add(App.class.getResource("/styles/style.css").toExternalForm());
        primaryStage.setTitle("Smart Forum - Login");
        primaryStage.setScene(scene);
        primaryStage.show();
    }

    public static void showDashboard() throws Exception {
        FXMLLoader loader = new FXMLLoader(App.class.getResource("/fxml/dashboard.fxml"));
        Pane root = loader.load();
        Scene scene = new Scene(root, 900, 600);
        scene.getStylesheets().add(App.class.getResource("/styles/style.css").toExternalForm());
        primaryStage.setTitle("Smart Forum");
        primaryStage.setScene(scene);
        primaryStage.show();
    }

    public static void showQuiz(int groupId, int quizId, String title, int durationSeconds) throws Exception {
    FXMLLoader loader = new FXMLLoader(App.class.getResource("/fxml/quiz.fxml"));
    Pane root = loader.load();
    QuizController controller = loader.getController();
    controller.initData(groupId, quizId, title, durationSeconds);
    Scene scene = new Scene(root, 800, 600);
    scene.getStylesheets().add(App.class.getResource("/styles/style.css").toExternalForm());
    Stage stage = new Stage();
    stage.setTitle("Quiz");
    stage.setScene(scene);
    stage.show();
    }

    public static void main(String[] args) {
        launch(args);
    }
}