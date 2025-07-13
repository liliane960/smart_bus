-- database.sql
CREATE DATABASE smart_bus;
USE smart_bus;

CREATE TABLE buses (
    bus_id INT AUTO_INCREMENT PRIMARY KEY,
    plate_number VARCHAR(20) NOT NULL,
    capacity INT DEFAULT 18,
    status VARCHAR(20) DEFAULT 'active',
    driver_id INT
);

CREATE TABLE drivers (
    driver_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    phone VARCHAR(20)
);

CREATE TABLE passenger_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT,
    event ENUM('entry','exit'),
    count INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20),
    FOREIGN KEY (bus_id) REFERENCES buses(bus_id)
);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT,
    message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20),
    comment TEXT,
    FOREIGN KEY (bus_id) REFERENCES buses(bus_id)
);
