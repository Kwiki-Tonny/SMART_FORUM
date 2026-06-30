package com.smartforum.controllers;

import com.smartforum.App;
import com.smartforum.services.ApiClient;
import javafx.fxml.FXML;
import javafx.scene.control.Label;
import javafx.scene.control.PasswordField;
import javafx.scene.control.TextField;

public class LoginController {

    @FXML private TextField emailField;
    @FXML private PasswordField passwordField;
    @FXML private Label statusLabel;

    @FXML
    public void handleLogin() {
        String email = emailField.getText().trim();
        String password = passwordField.getText();

        if (email.isEmpty() || password.isEmpty()) {
            statusLabel.setText("Please fill in all fields.");
            return;
        }

        new Thread(() -> {
            try {
                String token = ApiClient.login(email, password);
                if (token != null) {
                    ApiClient.setToken(token);
                    javafx.application.Platform.runLater(() -> {
                        try {
                            App.showDashboard();
                        } catch (Exception e) {
                            statusLabel.setText("Error: " + e.getMessage());
                        }
                    });
                } else {
                    javafx.application.Platform.runLater(() ->
                        statusLabel.setText("Invalid credentials.")
                    );
                }
            } catch (Exception e) {
                javafx.application.Platform.runLater(() ->
                    statusLabel.setText("Error: " + e.getMessage())
                );
            }
        }).start();
    }

    @FXML
    public void handleRegister() {
        statusLabel.setText("Please register via the web interface.");
    }
}