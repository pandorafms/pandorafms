START TRANSACTION;

UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=9;
UPDATE talert_actions
	SET field2='[PANDORA] Alert FIRED on _agent_ / _module_ / _timestamp_ / _data_'
	WHERE id=11;

COMMIT;
