

-- New data

UPDATE tconfig SET value = '1.4-dev' WHERE token = 'db_scheme_version';
UPDATE tconfig SET value = 'PD080121' WHERE token = 'db_scheme_build';

INSERT INTO `ttipo_modulo` VALUES (100,'keep_alive',-1,'KeepAlive','mod_keepalive.png'), (19, 'image_jpg',4,'Image JPG data', 'mod_image_jpg.png'), (20, 'image_png',4,'Image PNG data', 'mod_image_png.png'), (21, 'async_proc', 5, 'Asyncronous proc data', 'mod_async_proc.png'), (22, 'async_data', 5, 'Asyncronous numeric data', 'mod_async_data.png'), (23, 'async_string', 5, 'Asyncronous string data', 'mod_async_string.png'), (24, 'predictive', 5, 'Predictive Estimation Data', 'mod_predictive.png');

INSERT INTO tconfig (token, value) VALUES ('string_days_purge','7');
INSERT INTO tconfig (token, value) VALUES ('image_days_purge','2');

