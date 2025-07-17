-- Drop existing trigger if it exists
DROP TRIGGER IF EXISTS create_notification_on_overload;

-- Create trigger for INSERT operations
DELIMITER //
CREATE TRIGGER create_notification_on_overload_insert
AFTER INSERT ON bus_logs
FOR EACH ROW
BEGIN
    -- Check if the new record has overloading status
    IF NEW.status = 'overloading' THEN
        -- Insert notification automatically
        INSERT INTO notifications (bus_id, bus_log_id, message, sent_at, status, comment)
        VALUES (
            NEW.bus_id,
            NEW.id,
            CONCAT('OVERLOADING ALERT: Bus has ', NEW.passenger_count, ' passengers during ', NEW.event, ' event'),
            NOW(),
            'pending',
            ''
        );
    END IF;
END//

-- Create trigger for UPDATE operations
CREATE TRIGGER create_notification_on_overload_update
AFTER UPDATE ON bus_logs
FOR EACH ROW
BEGIN
    -- Check if status changed to overloading
    IF NEW.status = 'overloading' AND (OLD.status != 'overloading' OR OLD.status IS NULL) THEN
        -- Check if notification already exists for this bus_log_id
        IF NOT EXISTS (SELECT 1 FROM notifications WHERE bus_log_id = NEW.id) THEN
            -- Insert notification automatically
            INSERT INTO notifications (bus_id, bus_log_id, message, sent_at, status, comment)
            VALUES (
                NEW.bus_id,
                NEW.id,
                CONCAT('OVERLOADING ALERT: Bus has ', NEW.passenger_count, ' passengers during ', NEW.event, ' event'),
                NOW(),
                'pending',
                ''
            );
        END IF;
    END IF;
END//

DELIMITER ;

-- Show the created triggers
SHOW TRIGGERS LIKE 'bus_logs'; 