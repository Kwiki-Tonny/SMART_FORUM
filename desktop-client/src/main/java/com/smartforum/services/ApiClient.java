package com.smartforum.services;

import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import okhttp3.*;

import java.io.IOException;
import java.util.concurrent.TimeUnit;

public class ApiClient {

    private static final String BASE_URL = "http://localhost:8000/api";
    private static final OkHttpClient client = new OkHttpClient.Builder()
            .connectTimeout(5, TimeUnit.SECONDS)
            .readTimeout(5, TimeUnit.SECONDS)
            .build();
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
            }
            return null;
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

    /**
     * Checks if the server is reachable.
     * @return true if the server responds (any status code), false on timeout or connection failure.
     */
    public static boolean ping() {
        try {
            Request request = new Request.Builder()
                    .url(BASE_URL)
                    .head()
                    .build();
            try (Response response = client.newCall(request).execute()) {
                // Any response means the server is up
                return true;
            }
        } catch (IOException e) {
            return false;
        }
    }
}