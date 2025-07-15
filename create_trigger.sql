-- SQL script to create automatic notification trigger
-- This will automatically create notifications when overloading events are detected

-- First, let's create a stored procedure to handle the notification creation
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS CreateOverloadingNotification(
    IN p_bus_id INT,
    IN p_passenger_count INT,
    IN p_event_type VARCHAR(50),
    IN p_created_at DATETIME
)
BEGIN
    DECLARE v_plate_number VARCHAR(20);
    DECLARE v_comment TEXT;
    DECLARE v_message VARCHAR(255);
    
    -- Get the plate number for the bus
    SELECT plate_number INTO v_plate_number 
    FROM buses 
    WHERE bus_id = p_bus_id;
    
    -- Create the comment
    SET v_comment = CONCAT('Auto-generated: Bus ', v_plate_number, ' had ', p_passenger_count, ' passengers during ', p_event_type, ' event at ', p_created_at);
    
    -- Create the message
    SET v_message = CONCAT('Overloading detected - ', p_passenger_count, ' passengers');
    
    -- Insert the notification (only if it doesn't already exist for this bus)
    INSERT INTO notifications (bus_id, message, status, comment)
    SELECT p_bus_id, v_message, 'pending', v_comment
    WHERE NOT EXISTS (
        SELECT 1 FROM notifications 
        WHERE bus_id = p_bus_id 
        AND message LIKE '%overloading%'
    );
END$$

DELIMITER ;

-- Now create the trigger that will fire when a new overloading event is inserted
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS tr_bus_logs_overloading_notification
AFTER INSERT ON bus_logs
FOR EACH ROW
BEGIN
    -- Check if the new record is an overloading event
    IF NEW.status = 'overloading' THEN
        -- Call the stored procedure to create notification
        CALL CreateOverloadingNotification(
            NEW.bus_id, 
            NEW.passenger_count, 
            NEW.event, 
            NEW.created_at
        );
    END IF;
END$$

DELIMITER ;

-- Alternative: Simple trigger without stored procedure (if the above doesn't work)
DELIMITER $$

CREATE TRIGGER IF NOT EXISTS tr_bus_logs_simple_notification
AFTER INSERT ON bus_logs
FOR EACH ROW
BEGIN
    DECLARE v_plate_number VARCHAR(20);
    DECLARE v_comment TEXT;
    DECLARE v_message VARCHAR(255);
    
    -- Only process overloading events
    IF NEW.status = 'overloading' THEN
        -- Get plate number
        SELECT plate_number INTO v_plate_number 
        FROM buses 
        WHERE bus_id = NEW.bus_id;
        
        -- Create comment and message
        SET v_comment = CONCAT('Auto-generated: Bus ', v_plate_number, ' had ', NEW.passenger_count, ' passengers during ', NEW.event, ' event at ', NEW.created_at);
        SET v_message = CONCAT('Overloading detected - ', NEW.passenger_count, ' passengers');
        
        -- Insert notification if it doesn't exist
        INSERT INTO notifications (bus_id, message, status, comment)
        SELECT NEW.bus_id, v_message, 'pending', v_comment
        WHERE NOT EXISTS (
            SELECT 1 FROM notifications 
            WHERE bus_id = NEW.bus_id 
            AND message LIKE '%overloading%'
        );
    END IF;
END$$

DELIMITER ;

-- Show the created triggers
SHOW TRIGGERS LIKE 'bus_logs';

-- Test the trigger by inserting a test overloading event
-- INSERT INTO bus_logs (bus_id, event, passenger_count, status) VALUES (1, 'entry', 30, 'overloading');

-- Check if notification was created
-- SELECT * FROM notifications WHERE bus_id = 1 ORDER BY sent_at DESC LIMIT 1; 