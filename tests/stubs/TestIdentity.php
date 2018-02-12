<?php

namespace sergeymakinen\yii\logmessage\tests\stubs;

use yii\base\BaseObject;
use yii\base\InvalidCallException;
use yii\web\IdentityInterface;

class TestIdentity extends BaseObject implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public static function findIdentity($id)
    {
        throw new InvalidCallException('Mocked');
    }

    /**
     * @inheritDoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new InvalidCallException('Mocked');
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return 'userId';
    }

    /**
     * @inheritDoc
     */
    public function getAuthKey()
    {
        throw new InvalidCallException('Mocked');
    }

    /**
     * @inheritDoc
     */
    public function validateAuthKey($authKey)
    {
        throw new InvalidCallException('Mocked');
    }
}
