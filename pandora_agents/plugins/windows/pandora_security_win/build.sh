#!/bin/bash
# Build the winexe binary.
wine pip install -r src/requirements.txt
wine pyinstaller --onefile src/pandora_security_win.py
rm -rf build/ __pycache__/ pandora_security_win.spec
