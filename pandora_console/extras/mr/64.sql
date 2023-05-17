START TRANSACTION;

UPDATE pandora.tnetwork_component
   SET module_enabled=1
WHERE name='Cisco&#x20;_nameOID_&#x20;power&#x20;state';

COMMIT;
