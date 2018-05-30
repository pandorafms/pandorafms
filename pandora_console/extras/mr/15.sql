START TRANSACTION;

ALTER TABLE tcluster DROP FOREIGN KEY tcluster_ibfk_1;

ALTER TABLE tcluster_agent DROP FOREIGN KEY tcluster_agent_ibfk_1;

ALTER TABLE tcluster_agent DROP FOREIGN KEY tcluster_agent_ibfk_2;

COMMIT;