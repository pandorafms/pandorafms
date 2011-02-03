//     Pandora FMS Embedded Agent
//     (c) Artica Soluciones Tecnológicas S.L 2011
//     (c) Sancho Lerena <slerena@artica.es>

//     This program is free software; you can redistribute it and/or modify
//     it under the terms of the GNU General Public License as published by
//     the Free Software Foundation; either version 2 of the License.
//
//     This program is distributed in the hope that it will be useful,
//     but WITHOUT ANY WARRANTY; without even the implied warranty of
//     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//     GNU General Public License for more details.


void
init_parameters (struct pandora_setup* pandorasetup);

int
fill_pandora_setup (struct pandora_setup *ps, char *field, char *value);

int
fill_pandora_module (struct pandora_module *pm, char *field, char *value);

int
parse_config (struct pandora_setup *pandorasetup, struct pandora_module **list, char *config_file);


