START TRANSACTION

ALTER TABLE tusuario add default_event_filter int(10) unsigned NOT NULL DEFAULT 0;

COMMIT