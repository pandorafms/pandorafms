-- ---------------------------------------------------------------------
-- Table `tlayout`
-- ---------------------------------------------------------------------

ALTER TABLE "tlayout" DROP COLUMN "fullscreen";

-- ---------------------------------------------------------------------
-- Table `tlayout_data`
-- ---------------------------------------------------------------------

ALTER TABLE "tlayout_data" DROP COLUMN "no_link_color";
ALTER TABLE "tlayout_data" DROP COLUMN "label_color";
ALTER TABLE "tlayout_data" ADD COLUMN "border_width" INTEGER NOT NULL default 0;
ALTER TABLE "tlayout_data" ADD COLUMN "border_color" varchar(200) DEFAULT "";
ALTER TABLE "tlayout_data" ADD COLUMN "fill_color" varchar(200) DEFAULT "";

-- ---------------------------------------------------------------------
-- Table `ttag_module`
-- ---------------------------------------------------------------------

ALTER TABLE tlayout_data ADD COLUMN "id_policy_module" INTEGER NOT NULL DEFAULT 0;

/* 2014/12/10 */
-- ----------------------------------------------------------------------
-- Table `tuser_double_auth`
-- ----------------------------------------------------------------------
CREATE TABLE "tuser_double_auth" (
	"id" SERIAL NOT NULL PRIMARY KEY,
	"id_user" varchar(60) NOT NULL UNIQUE REFERENCES "tusuario"("id_user") ON DELETE CASCADE,
	"secret" varchar(20) NOT NULL
);
