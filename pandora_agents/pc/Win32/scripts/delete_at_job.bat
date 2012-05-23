AT|grep restart_pandora_agent|gawk "{print \"@AT \"$1\" /DELETE\"}" > DEL-AT.BAT & @DEL-AT.BAT & DEL DEL-AT.BAT
