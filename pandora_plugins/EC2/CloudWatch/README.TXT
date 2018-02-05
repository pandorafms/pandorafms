Amazon CloudWatch (Monitoring) Command Line Tools
=================================================

Installation:
-------------

1. Ensure that JAVA version 1.5 or higher is installed on your system: (java -version)
2. Unzip the deployment zip file
3. Set the following environment variables:
3.1 AWS_CLOUDWATCH_HOME - The directory where the deployment files were copied to
        check with:
           Unix: ls ${AWS_CLOUDWATCH_HOME}/bin should list mon-list-metrics ...)
           Windows: dir %AWS_CLOUDWATCH_HOME%\bin should list mon-list-metrics ...)
3.2 JAVA_HOME - Java Installation home directory
4. Add ${AWS_CLOUDWATCH_HOME}/bin (in Windows: %AWS_CLOUDWATCH_HOME%\bin) to your path

Configuration:
--------------

Provide the command line tool with your AWS user credentials.  There
are two ways you can provide credentails: AWS keys, or using X.509
certificates.

Using AWS Keys
--------------

1. Create a credential file: The deployment includes a template file ${AWS_CLOUDWATCH_HOME}/credential-file-path.template.
     Edit a copy of this file to add your information.
     On UNIX, limit permissions to the owner of the credential file: $ chmod 600 <the file created above>.
2. There are several ways to provide your credential information:
      a. Set the following environment variable: AWS_CREDENTIAL_FILE=<the file created in 1>
      b. Alternatively, provide the following option with every command --aws-credential-file <the file created in 1>
      c. Explicitly specify credentials on the command line: --I ACCESS_KEY --S SECRET_KEY
      
Using X.509 Certs
-----------------

1. Save your cetificate and private keys to files: e.g. my-cert.pem
and my-pk.pem.

2. There are two ways to provide the certificate information to the
command line tool
    a.  Set the following environment variables:
        EC2_CERT=/path/to/cert/file
        EC2_PRIVATE_KEY=/path/to/key/file
    b.  Specify the files directly on command-line for every command
        <command> --ec2-cert-file-path=/path/to/cert/file --ec2-private-key-file-path=/path/to/key/file

Setting custom JVM properties
-----------------------------

By setting the environment variable SERVICE_JVM_ARGS, you can pass arbitrary JVM properties to the command line.
For example, the following line sets proxy server properties in Linux/UNIX
  export SERVICE_JVM_ARGS="-Dhttp.proxyHost=http://my.proxy.com -Dhttp.proxyPort=8080" 


Running:
---------

1. Check that your setup works properly, run the following command:
   $ mon-cmd --help
      You should see the usage page for all Monitoring commands

   $ mon-list-metrics --headers
      You should see a header line. If you have any metrics defined, you should see them as well.
