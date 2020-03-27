<?php

namespace app\tests\unit\components\analytics;

use app\components\analytics\models\Visitors;
use app\components\analytics\models\Visits;
use app\components\analytics\Visitor;
use app\tests\fixtures\VisitorFixture;
use app\tests\fixtures\VisitsFixture;

class
VisitorTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function _fixtures()
    {
        return [
            'analytics_visits' => VisitsFixture::class,
            'analytics_visitors' => VisitorFixture::class,
        ];
    }

    private $visitor;

    public function testCreateNewVisitor(): void
    {
        $this->visitor = (new Visitor());
        expect_that($this->visitor->isNew);
        expect($this->visitor->id)->equals(2);
    }

    public function testGetVisitorByUUID(): void
    {
        expect(\tests\_support\UnitHelper::callMethod(
            (new Visitor()),
            'getVisitorByUUID',
            ['c9978853-4b81-426d-ba62-07817f1e4b19'],
            ))->isInstanceOf(Visitors::class);
    }

    public function testGenerateUUID(): void
    {
        expect(\tests\_support\UnitHelper::callMethod(
            (new Visitor()),
            'generateUUID',
            []
        ))->string();
    }

    public function testGetLastPartnerId(): void
    {
        $this->visitor = (new Visitor());

        $this->tester->haveRecord(Visits::class,
            [
                'visitor_id' => $this->visitor->id,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => time(),
                'updated_at' => time(),
                'partner_id' => 1001,
                'ip' => 2887188482,
            ]);

        $this->tester->haveRecord(Visits::class,
            [
                'visitor_id' => 1,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => time() + 50,
                'updated_at' => time() + 50,
                'partner_id' => 1004,
                'ip' => 2887188482,
            ]);

        expect(\tests\_support\UnitHelper::callMethod(
            $this->visitor,
            'getLastPartnerId',
            [$this->visitor->id]
        ))->equals(1001);

        $this->tester->haveRecord(Visits::class, [
            'visitor_id' => $this->visitor->id,
            'referrer_id' => null,
            'url_id' => null,
            'views' => 1,
            'created_at' => time() + 100,
            'updated_at' => time() + 100,
            'partner_id' => 1002,
            'ip' => 2887188482,
        ]);

        expect(\tests\_support\UnitHelper::callMethod(
            $this->visitor,
            'getLastPartnerId',
            [$this->visitor->id]
        ))->equals(1002);

        if (array_key_exists('hosts_partners', \Yii::$app->params)) {

            $this->tester->haveRecord(Visits::class, [
                'visitor_id' => $this->visitor->id,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => time() + 100,
                'updated_at' => time() + 100,
                'partner_id' => \Yii::$app->params['hosts_partners'][array_rand(\Yii::$app->params['hosts_partners'])],
                'ip' => 2887188482,
            ]);

            expect(\tests\_support\UnitHelper::callMethod(
                $this->visitor,
                'getLastPartnerId',
                [$this->visitor->id]
            ))->equals(1002);

        }

    }

    public function testGetActiveManagerId(): void
    {
        $this->tester->haveInDatabase(Visitors::tableName(),
            [
                'id' => 2,
                'uuid' => 'test',
                'created_at' => (new \DateTime())->modify('-1 hour')->getTimestamp(),
            ]);

        $this->tester->haveInDatabase(Visits::tableName(),
            [
                'id' => 1,
                'visitor_id' => 2,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => (new \DateTime())->modify('-1 hour')->getTimestamp(),
                'updated_at' => (new \DateTime())->modify('-1 hour')->getTimestamp(),
                'partner_id' => 1001,
                'manager_id' => 1,
                'ip' => 2887188482,
            ]);

        $this->tester->haveInDatabase(Visits::tableName(),
            [
                'id' => 3,
                'visitor_id' => 1,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => (new \DateTime())->modify('-2 hours')->getTimestamp(),
                'updated_at' => (new \DateTime())->modify('-2 hours')->getTimestamp(),
                'partner_id' => 1001,
                'manager_id' => 2,
                'ip' => 2887188482,
            ]);

        $this->tester->haveInDatabase(Visits::tableName(),
            [
                'id' => 2,
                'visitor_id' => 2,
                'referrer_id' => null,
                'url_id' => null,
                'views' => 1,
                'created_at' => (new \DateTime())->modify('-1 day')->getTimestamp(),
                'updated_at' => (new \DateTime())->modify('-1 day')->getTimestamp(),
                'partner_id' => 1001,
                'manager_id' => 3,
                'ip' => 2887188482,
            ]);

        expect(\tests\_support\UnitHelper::callMethod(
            (new Visitor()),
            'getActiveManagerId',
            [2]
        ))->equals(1);

    }

}
