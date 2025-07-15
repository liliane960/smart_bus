-- Update existing notifications with timestamps
-- Run this script to fix notifications that have NULL sent_at values

USE smart_bus;

-- Update notifications that have NULL sent_at to use current timestamp
UPDATE notifications 
SET sent_at = CURRENT_TIMESTAMP 
WHERE sent_at IS NULL;

-- Alternative: If you want to set them to a specific date (e.g., 1 day ago)
-- UPDATE notifications 
-- SET sent_at = DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 1 DAY) 
-- WHERE sent_at IS NULL;

-- Show the updated records
SELECT notification_id, bus_id, message, sent_at, comment 
FROM notifications 
ORDER BY notification_id DESC; 