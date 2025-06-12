![logo Pandora-FMS](https://user-images.githubusercontent.com/8567291/151817953-dc9c4c88-5f3c-459b-98a7-da0534930a2c.png)

### Installation from installation script

To install a new Pandora FMS instance, the easy and recommended way to do it is using the online installation tool.
Just download the correct script for your OS in extras/deploy-scripts/

The nomenclature for the corresponding OS scripts are:

 - **pandora_deploy_community_gh.sh**: centos7 (to be deprecated)
 - **pandora_deploy_community_el8_gh.sh**: rockylinux8, almalinux 8, RHEL8
 - **pandora_deploy_community_el9_gh.sh**: rockylinux9, almalinux 9, RHEL9
 - **pandora_deploy_community_ubuntu_2204_gh.sh**: Ubuntu Server 22.04

Once you download and copy the script to the home directory of your server, give it the correct permission to execute.

```
chmod +x pandora_deploy_community_el8_gh.sh
```

Then just run the script to start the installation process.

```
./pandora_deploy_community_el8_gh.sh
```

### Install Agent online scrip

To install a new Pandora FMS instance, the easy and recommended way to do it is using the online installation tool.
Just download the correct script for your OS in extras/deploy-scripts/

it will handle all the dependencies and configurations for you.

```
chmod +x pandora_agent_deploy_gh.sh
./pandora_agent_deploy_gh.sh
```

### Update Pandora FMS

The easiest and recommend way to update the environment is to use the update manager integrated in the Pandora FMS web console, just clicking in online update.

To update it manually, is possible to do it from the release packages

#### Update from RPM packages

For RHEL and RHEL compatible systems such as rockylinux or almalinux just download the rpms packages from release section and install it with the proper package manager such as yum or dnf.

For example.

```
dnf install ./pandorafms_agent_linux-7.0NG.772.noarch.rpm
dnf install ./pandorafms_console-7.0NG.772.noarch.rpm
dnf install ./pandorafms_server-7.0NG.772.x86_64.rpm
```

#### Update from Tarball packages
For Ubuntu Server systems the only path to update outside the update manager is the Tarball source code packages, it also compatible with RHEL compatible systems if you prefer to do it in such way.

##### Console
To update the condos you should download the tarball package, decompress it and then copy the pandora_console format to your Apache folder.

```
tar xvzf pandorafms_console-7.0NG.772.tar.gz
cp -R pandora_console /var/www/html/
```
Run in the terminal the MR updater tool

```
php /var/www/html/pandora_console/godmode/um_client/updateMR.php
```

Delete deprecated files, run in terminal:
```
cd /var/www/html/pandora_console/ && cat extras/delete_files/delete_files.txt  | xargs rm -fr
```

##### Server

To update the server is similar just download the tarball package from release section, decompress it, move it to the pandora_server folder and execute the installation script.

```
tar xvfz pandorafms_server-7.0NG.772.tar.gz  
cd pandora_server
./pandora_server_installer --install 
```
##### Agent

To update the agent, similar just download the tarball package from release section, decompress it, move it to the pandora_server folder and execute the installation script.
```
tar xvzf pandorafms_agent_linux-7.0NG.tar.gz 
cd unix
./pandora_agent_installer --install 
```

