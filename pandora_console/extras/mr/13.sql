START TRANSACTION;

	SET @st_oum720 = (SELECT IF(
		(SELECT value FROM tconfig WHERE token like 'short_module_graph_data') = 1, 
		"UPDATE tconfig SET value = 2 WHERE token LIKE 'short_module_graph_data'", 
		"UPDATE tconfig SET value = '' WHERE token LIKE 'short_module_graph_data'"
	));

	PREPARE pr_oum720 FROM @st_oum720;
	EXECUTE pr_oum720;
	DEALLOCATE PREPARE pr_oum720;

COMMIT;