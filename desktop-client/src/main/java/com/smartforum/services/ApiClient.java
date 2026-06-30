package com.smartforum.services;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import okhttp3.*;

import java.io.IOException;

public class ApiClient {

    // Change this to your Laravel API URL
    private static final String BASE_URL = "http://localhost:8000/api";
    private static final OkHttpClient client = new OkHttpClient();
    private static String token = null;

    public static void setToken(String t) { token = t; }

    public static String login(String email, String password) throws IOException {
        MediaType JSON = MediaType.parse("application/json; charset=utf-8");
        String json = "{\"email\":\"" + email + "\",\"password\":\"" + password + "\"}";
        RequestBody body = RequestBody.create(json, JSON);
        Request request = new Request.Builder()
                .url(BASE_URL + "/login")
                .post(body)
                .build();
        try (Response response = client.newCall(request).execute()) {
            String responseBody = response.body().string();
            if (response.isSuccessful()) {
                JsonObject obj = JsonParser.parseString(responseBody).getAsJsonObject();
                return obj.get("access_token").getAsString();
            } else {
                return null;
            }
        }
    }

    public static String get(String endpoint) throws IOException {
        Request request = new Request.Builder()
                .url(BASE_URL + endpoint)
                .header("Authorization", "Bearer " + token)
                .build();
        try (Response response = client.newCall(request).execute()) {
            return response.body().string();
        }
    }

    public static String post(String endpoint, String json) throws IOException {
        MediaType JSON = MediaType.parse("application/json; charset=utf-8");
        RequestBody body = RequestBody.create(json, JSON);
        Request request = new Request.Builder()
                .url(BASE_URL + endpoint)
                .post(body)
                .header("Authorization", "Bearer " + token)
                .build();
        try (Response response = client.newCall(request).execute()) {
            return response.body().string();
        }
    }
}