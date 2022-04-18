<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Test file. UMC.
 */

namespace UpdateManager;

// Load classes.
require_once __DIR__.'/../vendor/autoload.php';

// Load Config class from updatemanager project to read test.ini settings.
// Embeebed mode.
@require_once __DIR__.'/../../src/lib/Config.php';
@require_once __DIR__.'/../../src/lib/License.php';
@require_once __DIR__.'/../../src/lib/DB.php';

// Referenced mode.
@require_once __DIR__.'/../../updatemanager/src/lib/Config.php';
@require_once __DIR__.'/../../updatemanager/src/lib/License.php';
@require_once __DIR__.'/../../updatemanager/src/lib/DB.php';

/**
 * Test the Client class.
 */
class ClientTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Settings.
     *
     * @var \Config
     */
    private $conf;

    /**
     * Development license.
     *
     * @var string
     */
    private $development_license;

    /**
     * Customer license.
     *
     * @var string
     */
    private $customer_license;

    /**
     * UMS dbh
     *
     * @var \DB
     */
    private $ums_dbh;


    /**
     * Creates a couple of OUM files to test update process.
     *
     * @return void
     */
    private function generateOUMs()
    {
        $path = sys_get_temp_dir().'/oum/';
        $target = 'src/repo/test';
        $online = (bool) $this->conf->token('online');

        if ($online === true) {
            $target = '/var/www/html/updatemanager/repo/test';
        } else if (substr($target, 0, 1) !== '/') {
            $target = __DIR__.'/../../'.$target;
        }

        // OUM 1.
        system('rm -rf '.$path);
        mkdir($path, 0777, true);
        mkdir($path.'/extras');
        mkdir($path.'/extras/delete_files');

        system('echo -e "la cosa del vivir\nmola mucho\n" >'.$path.'1');
        system('cd '.$path.' && zip '.$target.'/test_1.zip -r * >/dev/null');
        system('cp '.$target.'/test_1.zip /tmp/test_prueba.zip');
        system('mv '.$target.'/test_1.zip '.$target.'/test_1.oum');

        $this->ums_dbh->insert(
            'um_packages',
            [
                'version'     => 1,
                'description' => 'test1',
                'file_name'   => '../test/test_1.oum',
                'status'      => 'testing',
            ]
        );

        // OUM 2.
        system('rm -rf '.$path);
        mkdir($path, 0777, true);
        mkdir($path.'/extras');
        mkdir($path.'/extras/delete_files');
        mkdir($path.'/extras/mr');

        system('echo "file2" > '.$path.'2');
        system('echo -e "create table tete (id int);\n" >'.$path.'extras/mr/1.sql');
        system('cd '.$path.' && zip '.$target.'/test_2.zip -r * >/dev/null');
        system('mv '.$target.'/test_2.zip '.$target.'/test_2.oum');

        $this->ums_dbh->insert(
            'um_packages',
            [
                'version'     => 2,
                'description' => 'test2',
                'file_name'   => '../test/test_2.oum',
                'status'      => 'testing',
            ]
        );

        // OUM 3.
        system('rm -rf '.$path);
        mkdir($path, 0777, true);
        mkdir($path.'/extras');
        mkdir($path.'/extras/delete_files');
        mkdir($path.'/extras/mr');

        system('echo "file3" > '.$path.'3');
        file_put_contents($path.'extras/delete_files/delete_files.txt', "1\n");
        system('echo -e "create table toum1 (id int);\n" >'.$path.'extras/mr/2.sql');
        system('echo -e "create table toum2 (id int);\n" >>'.$path.'extras/mr/2.sql');
        system('cd '.$path.' && zip '.$target.'/test_3.zip -r * >/dev/null');
        system('mv '.$target.'/test_3.zip '.$target.'/test_3.oum');

        $this->ums_dbh->insert(
            'um_packages',
            [
                'version'     => 3,
                'description' => 'test3',
                'file_name'   => '../test/test_3.oum',
                'status'      => 'testing',
            ]
        );

        // OUM 4.
        system('rm -rf '.$path);
        mkdir($path, 0777, true);
        mkdir($path.'/extras');
        mkdir($path.'/extras/delete_files');
        mkdir($path.'/extras/mr');

        system('echo "file4" > '.$path.'4');
        file_put_contents($path.'extras/delete_files/delete_files.txt', "1\n");
        system('echo -e "drop table toum1;\n" >'.$path.'extras/mr/3.sql');
        system('cd '.$path.' && zip '.$target.'/test_4.zip -r * >/dev/null');
        system('mv '.$target.'/test_4.zip '.$target.'/test_4.oum');

        $this->ums_dbh->insert(
            'um_packages',
            [
                'version'     => 4,
                'description' => 'test4',
                'file_name'   => '../test/test_4.oum',
                'status'      => 'testing',
            ]
        );

        $this->ums_dbh->insert(
            'um_packages',
            [
                'version'     => 5,
                'description' => 'test5',
                'file_name'   => 'unexistent.oum',
                'status'      => 'published',
            ]
        );

        // Cleanup.
        system('rm -rf '.$path);
    }


    /**
     * Base settings.
     *
     * @return void
     */
    public function setup(): void
    {
        // Load the conf.
        try {
            $this->conf = new \Config(__DIR__.'/../conf/test.ini');
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }

        // Verify endpoint has all needed stuff, like licenses and OUM packages.
        $this->ums_dbh = new \DB(
            $this->conf->token('dbhost'),
            $this->conf->token('dbname'),
            $this->conf->token('dbuser'),
            $this->conf->token('dbpass')
        );

        if ((bool) $this->ums_dbh === false) {
            $this->fail(
                'Failed to prepare environment '.$this->conf->token('dbhost')
            );
        }

        // Cleanup.
        foreach (['Development', 'Customer'] as $company) {
            $id = $this->ums_dbh->select_value(
                'SELECT id FROM um_licenses WHERE company=?',
                [$company]
            );

            if ($id > 0) {
                $this->ums_dbh->delete(
                    'um_licenses',
                    $id
                );
            }
        }

        // Load minimum examples.
        $this->ums_dbh->insert(
            'um_licenses',
            [
                'company'     => 'Development',
                'license_key' => 4,
                'expiry_date' => date('Y-m-d', strtotime(date('Y-m-d', time()).' + 365 day')),
                'developer'   => 1,
            ]
        );
        $this->ums_dbh->insert(
            'um_licenses',
            [
                'company'     => 'Customer',
                'license_key' => 5,
                'expiry_date' => date('Y-m-d', strtotime(date('Y-m-d', time()).' + 365 day')),
                'developer'   => 0,
            ]
        );

        $rs = \License::all_licenses(
            $this->ums_dbh,
            1,
            0,
            ['company' => 'Development'],
            [
                'column' => 'company',
                'dir'    => 'asc',
            ],
            ['*']
        );

        $this->development_license = $rs[0]['license_key'];
        $this->assertEquals(1, $rs[0]['developer'], 'Not a developer license');

        $rs = \License::all_licenses(
            $this->ums_dbh,
            1,
            0,
            ['company' => 'Customer'],
            [
                'column' => 'company',
                'dir'    => 'asc',
            ],
            ['*']
        );

        $this->customer_license = $rs[0]['license_key'];

        $this->base = $this->conf->token('homedir');
        system('rm -rf '.$this->base.'/open');
        system('rm -rf '.$this->base.'/enterprise');

        $this->generateOUMs();
    }


    /**
     * Cleanup.
     *
     * @return void
     */
    public function teardown():void
    {
        foreach (['test1', 'test2', 'test3', 'test4', 'test5'] as $t) {
            $id = $this->ums_dbh->select_value(
                'SELECT id FROM um_packages WHERE description=?',
                [$t]
            );
            $this->ums_dbh->delete(
                'um_packages',
                $id
            );
        }

        // Cleanup.
        foreach (['Development', 'Customer'] as $company) {
            $id = $this->ums_dbh->select_value(
                'SELECT id FROM um_licenses WHERE company=?',
                [$company]
            );
            if ($id > 0) {
                $this->ums_dbh->delete(
                    'um_licenses',
                    $id
                );
            }
        }

        system('rm -rf '.sys_get_temp_dir().'/oum');
        system('rm -rf '.$this->base.'/open');
        system('rm -rf '.$this->base.'/enterprise');
        system('rm -rf '.$this->base);
        system('rm -f '.sys_get_temp_dir().'/test*.oum');
    }


    /**
     * Test client.
     *
     * @return void
     */
    public function testInvalidClient()
    {
        try {
            $this->umc_invalid = new Client([]);
        } catch (\Exception $e) {
            $this->umc_invalid = new Client(
                [
                    'host'          => $this->conf->token('umc_host'),
                    'port'          => $this->conf->token('umc_port'),
                    'remote_config' => sys_get_temp_dir().'/oum',
                    'endpoint'      => 'notexists',
                    'license'       => 'UNKNOWN',
                    'insecure'      => false,
                    'dbconnection'  => null,
                    'homedir'       => $this->base.'/unexistent',
                    'proxy'         => [
                        'user'     => '',
                        'host'     => '',
                        'port'     => '',
                        'password' => '',
                    ],

                ]
            );

            $this->assertFalse(
                $this->umc_invalid->test()
            );

            $this->umc_invalid = new Client(
                [
                    'host'     => $this->conf->token('umc_host'),
                    'port'     => $this->conf->token('umc_port'),
                    'endpoint' => 'notexists',
                    'license'  => 'UNKNOWN',
                    'insecure' => true,
                    'homedir'  => $this->base.'/unexistent',
                ]
            );

            $this->assertNull(
                $this->umc_invalid->listUpdates()
            );

            return;
        }

        $this->fail('Expected exception not found');
    }


    /**
     * Test opensource updates.
     *
     * @return void
     */
    public function atestOpen()
    {
        if (is_dir($this->base.'/open/') === false) {
            mkdir($this->base.'/open/', 0777, true);
        }

        $dbh_open = new \mysqli(
            $this->conf->token('dbhost'),
            $this->conf->token('dbuser'),
            $this->conf->token('dbpass')
        );

        if ($dbh_open->select_db('open') === false) {
            $dbh_open->query('create database open');
            $dbh_open->select_db('open');
        }

        $umc_open = new Client(
            [
                'host'              => $this->conf->token('umc_host'),
                'port'              => $this->conf->token('umc_port'),
                'endpoint'          => ($this->conf->token('endpoint') ?? ''),
                'license'           => 'PANDORA-FREE',
                'insecure'          => true,
                'homedir'           => $this->base.'/open',
                'dbconnection'      => $dbh_open,
                'registration_code' => 'unregistered',
                'current_package'   => 0,
                'MR'                => 0,
            ]
        );

        $this->assertEquals(
            true,
            $umc_open->test(),
            ($umc_open->getLastError() ?? '')
        );

        $open = $umc_open->listUpdates();
            $this->assertEquals(
                '190916',
                $open[0]['version'],
                ($umc_open->getLastError() ?? '')
            );

        $this->assertEquals(
            false,
            $umc_open->updateNextVersion(),
            ($umc_open->getLastError() ?? '')
        );
        if (strpos(
            ($umc_open->getLastError() ?? ''),
            'open.tusuario'
        ) <= 0
        ) {
            $this->fail(($umc_open->getLastError() ?? ''));
        }

        $this->assertEquals(0, $umc_open->getMR());

        $umc_open->getDBH()->query('DROP DATABASE `open`');
    }


    /**
     * Test enterprise updates.
     *
     * @return void
     */
    public function testEnterprise()
    {
        if (is_dir($this->base.'/enterprise/') === false) {
            mkdir($this->base.'/enterprise/', 0777, true);
        }

        $dbh_ent = new \mysqli(
            $this->conf->token('dbhost'),
            $this->conf->token('dbuser'),
            $this->conf->token('dbpass')
        );

        if ($dbh_ent->select_db('ent') === false) {
            $dbh_ent->query('create database ent');
            $dbh_ent->select_db('ent');
            $dbh_ent->query(
                'create table tconfig(
                    `id_config` int(10) unsigned NOT NULL auto_increment,
                    `token` varchar(100),
                    `value` text NOT NULL,
                    primary key (`id_config`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8'
            );
        }

        $umc_enterprise = new Client(
            [
                'host'          => $this->conf->token('umc_host'),
                'port'          => $this->conf->token('umc_port'),
                'remote_config' => sys_get_temp_dir().'/oum',
                'endpoint'      => $this->conf->token('endpoint'),
                'license'       => $this->development_license,
                'insecure'      => true,
                'homedir'       => $this->base.'/enterprise',
                'dbconnection'  => $dbh_ent,
                'tmp'           => sys_get_temp_dir(),
            ]
        );

        $this->assertEquals(
            true,
            $umc_enterprise->test(),
            ($umc_enterprise->getLastError() ?? '')
        );

        $ent = $umc_enterprise->listUpdates();
        $this->assertEquals(
            1,
            $ent[0]['version'],
            ($umc_enterprise->getLastError() ?? '')
        );

        $this->assertEquals(
            true,
            $umc_enterprise->updateNextVersion(),
            ($umc_enterprise->getLastError() ?? '')
        );

        $this->assertEquals(
            1,
            $umc_enterprise->getVersion(),
            ($umc_enterprise->getLastError() ?? '')
        );

        $this->assertEquals(0, $umc_enterprise->getMR());

        try {
            $umc_enterprise->updateLastVersion();
        } catch (\Exception $e) {
            echo $e->getTraceAsString();
            $this->fail('Failed while updating: '.$e->getMessage());
        }

        $this->assertEquals(
            4,
            $umc_enterprise->getVersion(),
            ($umc_enterprise->getLastError() ?? '')
        );

        // Verify MR had worked.
        $this->assertEquals(3, $umc_enterprise->getMR());

        // Verify delete files has worked.
        $this->assertTrue(
            file_exists($this->base.'/enterprise/3'),
            'File '.$this->base.'/enterprise/3 should exist'
        );
        $this->assertFalse(
            file_exists($this->base.'/enterprise/1'),
            'File '.$this->base.'/enterprise/1 should not exist'
        );

        // Customer.
        $umc_enterprise = new Client(
            [
                'host'          => $this->conf->token('umc_host'),
                'port'          => $this->conf->token('umc_port'),
                'remote_config' => sys_get_temp_dir().'/oum',
                'endpoint'      => $this->conf->token('endpoint'),
                'license'       => $this->customer_license,
                'insecure'      => true,
                'homedir'       => $this->base.'/enterprise',
                'dbconnection'  => $dbh_ent,
                'tmp'           => sys_get_temp_dir(),
            ]
        );

        $this->assertFalse(
            $umc_enterprise->updateNextVersion()
        );

        // Cleanup.
        $umc_enterprise->getDBH()->query('DROP DATABASE `ent`');
    }


}
