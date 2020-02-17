#!/usr/bin/python
# Kevin Rojas 2018

import os
import glob
import hashlib
import argparse


def main():
    global args
    global confdir
    global md5dir
    global agents

    # Argument parser
    parser = argparse.ArgumentParser()
    parser.add_argument('-r', '--run', required=False, dest='run', action='store_true',
                        help='run the tool to recreate md5 files')
    parser.add_argument('-d', dest='dir', default='/var/spool/pandora/data_in',
                        help='data_in folder path (default /var/spool/pandora/data_in)')
    parser.add_argument('-v', dest='verb', action='store_true',
                        help='verbose mode: Shows the files being updated')

    # Definitions
    args = vars(parser.parse_args())
    datadir = args['dir']
    confdir = datadir + '/conf/'
    md5dir = datadir + '/md5/'
    agents = glob.glob(confdir + '*.conf')  # Check folder for .conf files

    # Run script or show help
    if len(args) > 0 and args['run']:
        updatemd5()
    else:
        parser.print_help()


def md5sum(filename, blocksize=65536):
    # Open files and calculate MD5 from its content
    hash = hashlib.md5()
    with open(filename, 'rb') as f:
        for block in iter(lambda: f.read(blocksize), b''):
            hash.update(block)
    return hash.hexdigest()


def updatemd5():
    debug = args['verb']
    if not agents:
        print(' ERROR: There are no .conf files at ' +
              confdir + '. Please check the path provided.')
    else:
        count = 0
        for i in agents:
            agentmd5 = md5dir + os.path.basename(os.path.splitext(i)[0]) + '.md5'
            with open(agentmd5, 'w') as f:
                f.write(md5sum(i))
                f.close()
            count += 1

            if debug:
                print(os.path.basename(os.path.splitext(i)[0]) + '--> OK')

        print('Number of configuration files updated: ' + str(count))


main()
