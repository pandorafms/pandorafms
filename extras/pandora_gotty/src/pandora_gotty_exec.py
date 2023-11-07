#!/usr/bin/env python
# -*- coding: utf-8 -*-

__author__ = "PandoraFMS Team"
__copyright__ = "Copyright 2023, PandoraFMS"
#__credits__ = ["Rob Knight", "Peter Maxwell", "Gavin Huttley", "Matthew Wakefield"]
__maintainer__ = "Projects/QA department"
__status__ = "Prod"
__version__ = "1.0"

import sys, argparse, signal, re, datetime, subprocess

info= f"""
SSH and TELNET helper for pandora_gotty.
Version: {__version__}
"""

parser = argparse.ArgumentParser(description= info, formatter_class=argparse.RawTextHelpFormatter)
parser.add_argument('exec_cmd', 
                    help='Aplication to be executed, avalibles: ssh or telnet',type=str, choices=['ssh', 'telnet'])
parser.add_argument('address', 
                    help='IP addres or dns name to connect', type=str, default="")
parser.add_argument('port', 
                    help='Port to connect', type=int, default=23)
parser.add_argument('user', 
                    help='Username, only requiered for ssh connection', type=str, default="", nargs='?')

args = parser.parse_args()

# Define a function to handle the SIGINT signal
def sigint_handler(signal, frame):
    print ('\nInterrupted by user', file=sys.stderr)
    sys.exit(0)
signal.signal(signal.SIGINT, sigint_handler)

# Define a function to handle the SIGTERM signal
def sigterm_handler(signum, frame):
    print("Received SIGTERM signal.", file=sys.stderr)
    sys.exit(0)
signal.signal(signal.SIGTERM, sigterm_handler)

# Functions
def is_valid_add(add:str):
    # Regular expression to match an IP address
    ip_pattern = r'^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$'
    
    # Regular expression to match a DNS name (domain name)
    dns_pattern = r'^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$'
    
    if re.match(ip_pattern, add) or re.match(dns_pattern, add):
        return True
    else:
        print(f"Error not valid address: {add}", file=sys.stderr)
        return False
    
def is_valid_username(username:str):
    # Regular expression to match a valid Linux username
    username_pattern = r'^[a-zA-Z_][a-zA-Z0-9_]{0,31}$'
    if re.match(username_pattern, username) is not None:
        return True
    else:
        print(f"Error not valid username: {username}", file=sys.stderr)
        return False
    
def exec_ssh (user:str, add:str, port:int):
    # Previus checks
    if is_valid_username(user) == False: 
        return False
    if is_valid_add(add) == False:
        return False
    if port == 0 : 
        return False
    
    try:
        print("> Starting SSH connection...")
        ssh_command = f"ssh {user}@{add} -p {port}"  
        subprocess.run(ssh_command, shell=True, encoding='utf-8', text=True)

    except subprocess.CalledProcessError as e:
        raise SystemExit(e)
    return True

def exec_telnet (add:str, port:int):
    # Previus checks
    if is_valid_add(add) == False:
        return False
    
    try:
        print("> Starting Telnet connection...")
        ssh_command = f"telnet -E {add} {port}"  
        subprocess.run(ssh_command, shell=True, encoding='utf-8', text=True)

    except subprocess.CalledProcessError as e:
        raise SystemExit(e)
    return True


# Main
if __name__ == "__main__":
    if args.exec_cmd == "ssh":
        exec_ssh(args.user, args.address, args.port)
        print ("> ssh session finished")
        sys.exit(0)

    if args.exec_cmd == "telnet":
        exec_telnet(args.address, args.port)
        print ("> telnet session finished")
        sys.exit(0)

    sys.exit(0)
