CREATE TABLE  "tfiles_repo" ("id" SERIAL NOT NULL PRIMARY KEY, "name" VARCHAR(255) NOT NULL, "description" VARCHAR(500) NULL default '', "hash" VARCHAR(8) NULL default '');
CREATE TABLE  "tfiles_repo_group" ("id" SERIAL NOT NULL PRIMARY KEY, "id_file" INTEGER NOT NULL REFERENCES tfiles_repo("id") ON DELETE CASCADE, "id_group" INTEGER NOT NULL);
