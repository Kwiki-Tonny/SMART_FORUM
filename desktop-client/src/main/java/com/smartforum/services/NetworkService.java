package com.smartforum.services;

import java.util.function.Consumer;

import javafx.application.Platform;

public class NetworkService {

    private static NetworkService instance;
    private volatile boolean isOnline = false;
    private Thread watcher;
    private Consumer<Boolean> onStatusChange;
    private static final long CHECK_INTERVAL = 5000;

    private NetworkService() {}

    public static synchronized NetworkService getInstance() {
        if (instance == null) {
            instance = new NetworkService();
        }
        return instance;
    }

    public void start(Consumer<Boolean> onStatusChange) {
        this.onStatusChange = onStatusChange;
        if (watcher != null && watcher.isAlive()) return;
        watcher = new Thread(() -> {
            while (!Thread.currentThread().isInterrupted()) {
                boolean nowOnline = ApiClient.ping();
                if (nowOnline != isOnline) {
                    isOnline = nowOnline;
                    Platform.runLater(() -> {
                        if (onStatusChange != null) {
                            onStatusChange.accept(isOnline);
                        }
                    });
                }
                try {
                    Thread.sleep(CHECK_INTERVAL);
                } catch (InterruptedException e) {
                    Thread.currentThread().interrupt();
                }
            }
        });
        watcher.setDaemon(true);
        watcher.start();
    }

    public void stop() {
        if (watcher != null) {
            watcher.interrupt();
            watcher = null;
        }
    }

    public boolean isOnline() {
        return isOnline;
    }
}