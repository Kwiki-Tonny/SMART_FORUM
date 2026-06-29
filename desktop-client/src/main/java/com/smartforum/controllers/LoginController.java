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

        try {
            String token = ApiClient.login(email, password);
            if (token != null) {
                ApiClient.setToken(token);
                App.showDashboard();
            } else {
                statusLabel.setText("Invalid credentials.");
            }
        } catch (Exception e) {
            statusLabel.setText("Error: " + e.getMessage());
        }
    }

    @FXML
    public void handleRegister() {
        // Open the web registration page (or show a message)
        statusLabel.setText("Please register via the web interface.");
    }
}