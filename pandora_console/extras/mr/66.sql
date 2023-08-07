START TRANSACTION;

ALTER TABLE tevent_filter ADD private_filter_user text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL;

COMMIT;
