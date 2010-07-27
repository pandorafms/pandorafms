-- Pandora FMS - the Flexible Monitoring System
-- ============================================
-- Copyright (c) 2010 Artica Soluciones Tecnológicas, http://www.artica.es
-- Please see http://pandora.sourceforge.net for full contribution list

-- This program is free software; you can redistribute it and/or
-- modify it under the terms of the GNU General Public License
-- as published by the Free Software Foundation for version 2.
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

-- PLEASE NO NOT USE MULTILINE COMMENTS 
-- Because Pandora Installer don't understand them
-- and fails creating database !!!

-- -----------------------------------------------------
-- Table `tgrupo`
-- -----------------------------------------------------
ALTER TABLE `tgrupo` ADD COLUMN `propagate` tinyint(1) unsigned NOT NULL default '0';

-- -----------------------------------------------------
-- Table `treport_content`
-- -----------------------------------------------------
ALTER TABLE `treport_content` ADD COLUMN `time_from` time default '00:00:00';
ALTER TABLE `treport_content` ADD COLUMN `time_to` time default '00:00:00';
ALTER TABLE `treport_content` ADD COLUMN `monday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `tuesday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `wednesday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `thursday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `friday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `saturday` tinyint(1) default 1;
ALTER TABLE `treport_content` ADD COLUMN `sunday` tinyint(1) default 1;
