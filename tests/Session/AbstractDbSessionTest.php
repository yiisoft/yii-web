<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Web\Tests\Session;

use yii\helpers\Yii;
use Yiisoft\Db\Connection;
use Yiisoft\Db\Query;
use Yiisoft\Web\DbSession;
use yii\tests\TestCase;

/**
 * @group db
 */
abstract class AbstractDbSessionTest extends TestCase
{
    /**
     * @return string[] the driver names that are suitable for the test (mysql, pgsql, etc)
     */
    abstract protected function getDriverNames();

    protected function setUp()
    {
        parent::setUp();

        $this->mockApplication();
        $this->container->set('db', $this->getDbConfig());
        $this->db = $this->container->get('db');
        $this->createTableSession();
    }

    protected function tearDown()
    {
        $this->dropTableSession();
        parent::tearDown();
    }

    protected function getDbConfig()
    {
        $driverNames = $this->getDriverNames();
        $databases = self::getParam('databases');
        foreach ($driverNames as $driverName) {
            if (in_array($driverName, \PDO::getAvailableDrivers()) && array_key_exists($driverName, $databases)) {
                $driverAvailable = $driverName;
                break;
            }
        }
        if (!isset($driverAvailable)) {
            $this->markTestIncomplete(get_called_class() . ' requires ' . implode(' or ', $driverNames) . ' PDO driver! Configuration for connection required too.');
            return [];
        }
        $config = $databases[$driverAvailable];

        $result = [
            '__class' => Connection::class,
            'dsn' => $config['dsn'],
        ];

        if (isset($config['username'])) {
            $result['username'] = $config['username'];
        }
        if (isset($config['password'])) {
            $result['password'] = $config['password'];
        }

        return $result;
    }

    protected function createTableSession()
    {
        $this->runMigrate('up');
    }

    protected function dropTableSession()
    {
        try {
            $this->runMigrate('down', ['all']);
        } catch (\Exception $e) {
            // Table may not exist for different reasons, but since this method
            // reverts DB changes to make next test pass, this exception is skipped.
        }
    }

    // Tests :

    public function testReadWrite()
    {
        $session = new DbSession($this->db);

        $session->writeSession('test', 'session data');
        $this->assertEquals('session data', $session->readSession('test'));
        $session->destroySession('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInitializeWithConfig()
    {
        // should produce no exceptions
        $session = new DbSession($this->db);
        $session->useCookies = true;

        $session->writeSession('test', 'session data');
        $this->assertEquals('session data', $session->readSession('test'));
        $session->destroySession('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    /**
     * @depends testReadWrite
     */
    public function testGarbageCollection()
    {
        $session = new DbSession($this->db);

        $session->writeSession('new', 'new data');
        $session->writeSession('expire', 'expire data');

        $session->db->createCommand()
            ->update('session', ['expire' => time() - 100], 'id = :id', ['id' => 'expire'])
            ->execute();
        $session->gcSession(1);

        $this->assertEquals('', $session->readSession('expire'));
        $this->assertEquals('new data', $session->readSession('new'));
    }

    /**
     * @depends testReadWrite
     */
    public function testWriteCustomField()
    {
        $session = new DbSession($this->db);

        $session->writeCallback = function ($session) {
            return ['data' => 'changed by callback data'];
        };

        $session->writeSession('test', 'session data');

        $query = new Query();
        $this->assertSame('changed by callback data', $session->readSession('test'));
    }

    protected function buildObjectForSerialization()
    {
        $object = new \stdClass();
        $object->nullValue = null;
        $object->floatValue = pi();
        $object->textValue = str_repeat('QweåßƒТест', 200);
        $object->array = [null, 'ab' => 'cd'];
        $object->binary = base64_decode('5qS2UUcXWH7rjAmvhqGJTDNkYWFiOGMzNTFlMzNmMWIyMDhmOWIwYzAwYTVmOTFhM2E5MDg5YjViYzViN2RlOGZlNjllYWMxMDA0YmQxM2RQ3ZC0in5ahjNcehNB/oP/NtOWB0u3Skm67HWGwGt9MA==');
        $object->with_null_byte = 'hey!' . "\0" . 'y"ûƒ^äjw¾bðúl5êù-Ö=W¿Š±¬GP¥Œy÷&ø';

        return $object;
    }

    public function testSerializedObjectSaving()
    {
        $session = new DbSession($this->db);

        $serializedObject = serialize($this->buildObjectForSerialization());
        $session->writeSession('test', $serializedObject);
        $this->assertSame($serializedObject, $session->readSession('test'));
    }

    protected function runMigrate($action, $params = [])
    {
        $migrate = new EchoMigrateController('migrate', $this->app);
        $migrate->migrationPath = '@yii/web/migrations';
        $migrate->interactive = false;

        ob_start();
        ob_implicit_flush(false);
        $migrate->runAction($action, $params);
        ob_get_clean();

        return array_map(function ($version) {
            return substr($version, 15);
        }, (new Query())->select(['version'])->from('migration')->column());
    }

    public function testMigration()
    {
        $this->dropTableSession();
        $this->mockWebApplication();
        $this->container->set('db', $this->getDbConfig());

        $history = $this->runMigrate('history');
        $this->assertEquals(['base'], $history);

        $history = $this->runMigrate('up');
        $this->assertEquals(['base', 'session_init'], $history);

        $history = $this->runMigrate('down');
        $this->assertEquals(['base'], $history);
        $this->createTableSession();
    }

    public function testInstantiate()
    {
        $oldTimeout = ini_get('session.gc_maxlifetime');
        // unset $this->app->db to make sure that all queries are made against sessionDb
        $this->container->set('sessionDb', $this->app->db);
        $this->container->set('db', null);

        $session = new DbSession($this->container->get('sessionDb'));
        $session->timeout = 300;

        $this->assertSame($this->container->get('sessionDb'), $session->db);
        $this->assertSame(300, $session->timeout);
        $session->close();

        $this->container->set('db', $this->container->get('sessionDb'));
        $this->container->set('sessionDb', null);
        ini_set('session.gc_maxlifetime', $oldTimeout);
    }
}
