<?php

namespace sergeymakinen\yii\logmessage\tests;

use sergeymakinen\yii\logmessage\Message;
use sergeymakinen\yii\logmessage\tests\stubs\TestController;
use sergeymakinen\yii\logmessage\tests\stubs\TestIdentity;
use sergeymakinen\yii\logmessage\tests\stubs\TestSession;
use sergeymakinen\yii\logmessage\tests\stubs\TestUser;
use yii\base\ErrorHandler;
use yii\console\Application as ConsoleApplication;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\log\FileTarget;
use yii\log\Logger;

class MessageTest extends TestCase
{
    /**
     * @var array
     */
    protected $rawMessage;

    /**
     * @var Message
     */
    protected $message;

    public function setsProvider()
    {
        $closures = [
            'Hello world' => function () {
                \Yii::error(ErrorHandler::convertExceptionToString(new \Exception('Hello & <world> 🌊')), __METHOD__);
            },
            'bar' => function () {
                \Yii::info(['bar'], __METHOD__);
            },
            'baz' => function () {
                \Yii::info(new \Exception('baz'), __METHOD__);
            },
            'foobar' => function () {
                \Yii::info('foobar', __METHOD__);
            },
        ];
        $methods = [
            'console' => 'createConsoleApplication',
            'web' => 'createWebApplication',
            'none' => null,
        ];
        $sets = [];
        foreach ($closures as $closureName => $closure) {
            foreach ($methods as $methodName => $method) {
                $sets[$closureName . ':' . $methodName] = [$closure, $method];
            }
        }
        return $sets;
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     */
    public function testInitWithWrongTarget()
    {
        $this->bootApplication(null, 'createConsoleApplication');
        new Message([
            '',
            Logger::LEVEL_INFO,
            '',
            1,
        ], new \stdClass());
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetCategory(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        $this->assertSame($this->rawMessage[2], $this->message->category);
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetCommandLine(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if (\Yii::$app instanceof ConsoleApplication) {
            $this->assertSame(implode(' ', $_SERVER['argv']), $this->message->commandLine);

            $argv = $_SERVER['argv'];
            unset($_SERVER['argv']);
            $this->assertSame('', $this->message->commandLine);
            $_SERVER['argv'] = $argv;
        } else {
            $this->assertNull($this->message->commandLine);
        }
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetIsConsoleRequest(\Closure $closure, $method)
    {
        if ($method === null) {
            return;
        }

        $this->bootApplication($closure, $method);
        if (\Yii::$app instanceof ConsoleApplication) {
            $this->assertTrue($this->message->isConsoleRequest);
        } else {
            $this->assertFalse($this->message->isConsoleRequest);
        }
    }

    /**
     * @expectedException \yii\base\InvalidConfigException
     * @expectedExceptionMessage Unable to determine if the application is a console or web application.
     */
    public function testGetIsConsoleRequestWithoutApp()
    {
        (new Message([
            '',
            Logger::LEVEL_INFO,
            '',
            1,
        ]))->isConsoleRequest;
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetLevel(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        $this->assertSame(Logger::getLevelName($this->rawMessage[1]), $this->message->level);
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetPrefix(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if ($method === null) {
            $this->assertSame('', $this->message->prefix);
            return;
        }

        $this->assertSame('foo', $this->message->prefix);

        $this->tearDown();
        $this->bootApplication($closure, $method, [
            'components' => [
                'log' => [
                    'targets' => [
                        'test' => [
                            'prefix' => null,
                        ],
                    ],
                ],
            ],
        ]);
        $this->assertSame(\Yii::$app->log->targets['test']->getMessagePrefix($this->rawMessage), $this->message->prefix);

        $this->message->target = null;
        $this->assertSame('', $this->message->prefix);
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetSessionId(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if ($method === null || \Yii::$app instanceof ConsoleApplication) {
            $this->assertNull($this->message->sessionId);
        } else {
            $this->assertSame('session_id', $this->message->sessionId);
        }
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetStackTrace(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        $this->assertNull($this->message->stackTrace);
    }

    public function testGetStackTraceWithBacktrace()
    {
        $trace = array_filter(debug_backtrace(), function ($trace) {
            return isset($trace['file']);
        });
        $contains = reset($trace);
        $contains = "in {$contains['file']}:{$contains['line']}";
        $this->message = new Message([
            '',
            Logger::LEVEL_INFO,
            '',
            1,
            $trace,
        ]);
        $this->assertContains($contains, $this->message->stackTrace);
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetText(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if (is_string($this->rawMessage[0])) {
            $this->assertSame($this->rawMessage[0], $this->message->text);
        } elseif ($this->rawMessage[0] instanceof \Exception) {
            $this->assertSame((string) $this->rawMessage[0], $this->message->text);
        } else {
            $this->assertSame(VarDumper::export($this->rawMessage[0]), $this->message->text);
        }
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetTimestamp(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        $this->assertSame($this->rawMessage[3], $this->message->timestamp);
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetUrl(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if ($method === null || \Yii::$app instanceof ConsoleApplication) {
            $this->assertNull($this->message->url);
        } else {
            $this->assertSame('http://example.com/index.php?r=test', $this->message->url);
        }
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetUserId(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if ($method === null || \Yii::$app instanceof ConsoleApplication) {
            $this->assertNull($this->message->userId);
        } else {
            $this->assertSame('userId', $this->message->userId);
        }
    }

    /**
     * @dataProvider setsProvider
     *
     * @param \Closure $closure
     * @param $method
     */
    public function testGetUserIp(\Closure $closure, $method)
    {
        $this->bootApplication($closure, $method);
        if ($method === null || \Yii::$app instanceof ConsoleApplication) {
            $this->assertNull($this->message->userIp);
        } else {
            $this->assertSame('0.0.0.0', $this->message->userIp);
        }
    }

    protected function tearDown()
    {
        if (\Yii::$app !== null) {
            \Yii::$app->log->targets['test']->messages = \Yii::$app->log->logger->messages = [];
        }
        \Yii::getLogger()->messages = [];
        $this->rawMessage = $this->message = null;
        parent::tearDown();
    }

    protected function bootApplication($closure, $method, array $config = [])
    {
        $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
        $components = [];
        if ($method === 'createWebApplication') {
            $components = [
                'components' => [
                    'session' => [
                        'class' => TestSession::className(),
                    ],
                    'user' => [
                        'class' => TestUser::className(),
                        'identityClass' => TestIdentity::className(),
                    ],
                ],
            ];
        }
        if ($method !== null) {
            $this->$method(ArrayHelper::merge(
                $components,
                [
                    'components' => [
                        'log' => $this->getLogConfig(),
                    ],
                ],
                $config
            ));
            \Yii::$app->controller = new TestController('test', \Yii::$app);
            if ($method === 'createWebApplication') {
                \Yii::$app->session->isActive;
                \Yii::$app->user->identity;
            }
        }
        if ($closure !== null) {
            $closure();
            if ($method !== null) {
                \Yii::$app->log->logger->flush();
                $this->rawMessage = \Yii::$app->log->targets['test']->messages[0];
                $this->message = new Message($this->rawMessage, \Yii::$app->log->targets['test']);
            } else {
                $this->rawMessage = \Yii::getLogger()->messages[0];
                $this->message = new Message($this->rawMessage);
            }
        }
    }

    protected function getLogConfig()
    {
        return [
            'targets' => [
                'test' => [
                    'class' => FileTarget::className(),
                    'levels' => ['error', 'info'],
                    'categories' => [
                        __NAMESPACE__ . '\*',
                    ],
                    'prefix' => function () {
                        return 'foo';
                    },
                    'logVars' => [],
                ],
            ],
        ];
    }
}
