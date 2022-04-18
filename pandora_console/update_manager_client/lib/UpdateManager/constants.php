<?php
/**
 * Defines some useful constants.
 *
 * @file       constants.php
 * @package    UMC distributed updates.
 * @subpackage constants
 */

/**
 * Application version.
 */
defined('VERSION') || define(
    'VERSION',
    '4.0'
);

/*
 * Application build.
 */

defined('BUILD') || define(
    'BUILD',
    '20131120'
);

/*
 * Extension of open packages (without a leading dot).
 */

defined('OPEN_EXTENSION') || define(
    'OPEN_EXTENSION',
    'tar.gz'
);

/*
 * Extension of server packages (without a leading dot).
 */

defined('SERVER_EXTENSION') || define(
    'SERVER_EXTENSION',
    'tar.gz'
);

/*
 * Package statuses.
 */

defined('PACKAGE_STATUSES') || define(
    'PACKAGE_STATUSES',
    serialize([ 0 => 'disabled', 1 => 'testing', 2 => 'published'])
);

/*
 * License modes.
 */

defined('LICENSE_MODES') || define(
    'LICENSE_MODES',
    serialize([ 0 => 'trial', 1 => 'client'])
);

/*
 * Limit modes.
 */

defined('LIMIT_MODES') || define(
    'LIMIT_MODES',
    serialize([ 0 => 'agents', 1 => 'modules'])
);

/*
 * License types. Offline licenses must be 3, not 2!
 */

defined('LICENSE_TYPES') || define(
    'LICENSE_TYPES',
    serialize([ 0 => 'console', 1 => 'metaconsole', 3 => 'offline'])
);

/*
 * Extension of digital signatures.
 */

defined('SIGNATURE_EXTENSION') || define(
    'SIGNATURE_EXTENSION',
    '.sig'
);

/*
 * Public key used to verify signatures.
 */

defined('PUB_KEY') || define(
    'PUB_KEY',
    '-----BEGIN CERTIFICATE-----
MIIGbDCCBVSgAwIBAgIRAO+uHm0PBdm1YtvKjFwNqg4wDQYJKoZIhvcNAQELBQAw
gY8xCzAJBgNVBAYTAkdCMRswGQYDVQQIExJHcmVhdGVyIE1hbmNoZXN0ZXIxEDAO
BgNVBAcTB1NhbGZvcmQxGDAWBgNVBAoTD1NlY3RpZ28gTGltaXRlZDE3MDUGA1UE
AxMuU2VjdGlnbyBSU0EgRG9tYWluIFZhbGlkYXRpb24gU2VjdXJlIFNlcnZlciBD
QTAeFw0xOTEwMDEwMDAwMDBaFw0yMTEwMjAyMzU5NTlaMFgxITAfBgNVBAsTGERv
bWFpbiBDb250cm9sIFZhbGlkYXRlZDEdMBsGA1UECxMUUG9zaXRpdmVTU0wgV2ls
ZGNhcmQxFDASBgNVBAMMCyouYXJ0aWNhLmVzMIIBIjANBgkqhkiG9w0BAQEFAAOC
AQ8AMIIBCgKCAQEAvYafsbq6mH/GP9jc1zAHXeuh7kz8WYitCawXx5CYUFvO0ch8
H5v8a7aiLJ+pgrgFyZeZ489a+FJW0wddyMGnch+lGU5BbvoH91BMjZV1CLTOexB2
liid9aAEasFRBWkwIGMo4fkYFjBwBNofFd8y8vUu9550wZ4QbcshNrhk4E932BuZ
P6WWCR0fEoyP0mQRIRMUTAT5WeOYZHkSIJFiQ5JYH5ClVEOogJkO9QTn5t6GT3Py
SKhFYqskOOtHODztXdX/qKE+wZ2yssOEie5VvfmcoTAwkwLVZAY8Q7wqjtkaQ9kv
weonYXq40KpQ2fksPP0x1FL0rrfsROL/tyv/wwIDAQABo4IC9zCCAvMwHwYDVR0j
BBgwFoAUjYxexFStiuF36Zv5mwXhuAGNYeEwHQYDVR0OBBYEFDxO4vgu+Bht6JnH
Xv4uwf459n7cMA4GA1UdDwEB/wQEAwIFoDAMBgNVHRMBAf8EAjAAMB0GA1UdJQQW
MBQGCCsGAQUFBwMBBggrBgEFBQcDAjBJBgNVHSAEQjBAMDQGCysGAQQBsjEBAgIH
MCUwIwYIKwYBBQUHAgEWF2h0dHBzOi8vc2VjdGlnby5jb20vQ1BTMAgGBmeBDAEC
ATCBhAYIKwYBBQUHAQEEeDB2ME8GCCsGAQUFBzAChkNodHRwOi8vY3J0LnNlY3Rp
Z28uY29tL1NlY3RpZ29SU0FEb21haW5WYWxpZGF0aW9uU2VjdXJlU2VydmVyQ0Eu
Y3J0MCMGCCsGAQUFBzABhhdodHRwOi8vb2NzcC5zZWN0aWdvLmNvbTAhBgNVHREE
GjAYggsqLmFydGljYS5lc4IJYXJ0aWNhLmVzMIIBfQYKKwYBBAHWeQIEAgSCAW0E
ggFpAWcAdwD2XJQv0XcwIhRUGAgwlFaO400TGTO/3wwvIAvMTvFk4wAAAW2Gc+pN
AAAEAwBIMEYCIQDweXVdMk4MVPxu0H7MDSs8g9FFLfjthnFv1GBO9wpOvQIhAKvI
fGbNpA2GWSP+L+opz5KnIwoJSL/JD7CMLbboexZsAHUARJRlLrDuzq/EQAfYqP4o
wNrmgr7YyzG1P9MzlrW2gagAAAFthnPqagAABAMARjBEAiAWnW/bqGiL7elEQASm
YT6V0oQOviCJ4vYi+ekYtyHi1QIgDb3PCSOf9TZXnCx8d48NrD1OvabV9LaOimVF
NPfEZQ4AdQBVgdTCFpA2AUrqC5tXPFPwwOQ4eHAlCBcvo6odBxPTDAAAAW2Gc+pK
AAAEAwBGMEQCIEoT2BgNg+WrJcqPeNDcBfiWyT8GoJyscj9UxpAXmMJkAiANowLt
4mz/mc4uYjRxwZnA/BMZgOVPMLtMwaA0b1C32TANBgkqhkiG9w0BAQsFAAOCAQEA
MNwCq90OYGnqoQ4vmX9BPe7e5qoQ7asU82u3XH/nA+lMuD5D4pKHFNFcA/KZbmvc
OXcFt0CwbeWOuAbBom8hvFaF8abtkayG27pknQSv4i5YRKF1pEx3hrMgC8c9e32e
ebGE1kQoj1TQkf6e0t7Ss/XvdnSITSo5Onio/g/dUv9MFI1bjcf+8gWWDueTOKyt
FcVU5xb8g3EtXju01C70KVcN9SgHzSmyBrcBSEvJQ7emnZjyg0xdmlytvSo1Y7jf
JWkEbLlOEtYdkgyWy1ZFi/oO5U4UR6X6xgHiJTRZyXvPoS3mk7k+YgAkdOad0vEI
5JdvxQyReDoCO3u4FtKHEA==
-----END CERTIFICATE-----'
);
