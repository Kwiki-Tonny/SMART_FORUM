package com.smartforum.models;

public class Quiz {
    private int id;
    private String title;
    private int duration;
    private String description;

    public Quiz(int id, String title, int duration, String description) {
        this.id = id;
        this.title = title;
        this.duration = duration;
        this.description = description;
    }

    public int getId() { return id; }
    public String getTitle() { return title; }
    public int getDuration() { return duration; }
    public String getDescription() { return description; }
}