-- ============================================
-- Copyright (c) 2005-2011 Artica Soluciones Tecnológicas, http://www.artica.es
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

-- Priority : 0 - Maintance (grey)
-- Priority : 1 - Low (green)
-- Priority : 2 - Normal (blue)
-- Priority : 3 - Warning (yellow)
-- Priority : 4 - Critical (red)

-- ---------------------------------------------------------------------
-- Table `ttable_test`
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `ttable_test2` (
	`id` int(10) unsigned NOT NULL auto_increment,
	`field1` varchar(60) NOT NULL default '',
	`field2` int(10) unsigned NOT NULL default '0',
	PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- ---------------------------------------------------------------------
-- Table `ttable_test`
-- ---------------------------------------------------------------------
ALTER TABLE `ttable_test` ADD COLUMN `field3` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `ttable_test` MODIFY COLUMN `field1` tinyint(1) NOT NULL DEFAULT 0;
