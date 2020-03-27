<?php

use app\components\analytics\models\Visits;
use app\components\analytics\queue\VisitJob;
use app\components\analytics\tests\VisitorCest;

class AnalyticsVisitorCest
{
    /**
     * Load fixtures before db transaction begin
     * Called in _before()
     * @return array
     * @see \Codeception\Module\Yii2::loadFixtures()
     * @see \Codeception\Module\Yii2::_before()
     */
    public function _fixtures()
    {
        return [
            'analytics_visitors' => [
                'class' => \app\tests\fixtures\VisitorFixture::class,
            ],
            'analytics_visits' => [
                'class' => \app\tests\fixtures\VisitsFixture::class,
            ],
        ];
    }

    public function checkVisitorRecord(\AcceptanceTester $I)
    {
        $I->amOnPage('/visitor');
        $I->canSeeRecord(\app\components\analytics\models\Visitors::class, ['id' => 2]);
    }

    public function checkVisitorCookie(\AcceptanceTester $I)
    {
        $I->amOnPage('/visitor');
        $I->seeCookie('visitor_uuid');
    }

    public function checkVisitWithParams(\AcceptanceTester $I)
    {
        $I->amOnPage('/visitor?utm_pid=10');
        //  var_dump($I->sendCommandToRedis('hgetall','test-queue.messages'));
        $job = $I->sendCommandToRedis('hget', 'test-queue.messages', 1);
        expect('job placed in Redis exists', $job)->string();
        expect('it is valid type of job', $job)->stringContainsString(VisitJob::class);
        expect('and partner id is detected correctly', $job)->stringContainsString('"partner_id";s:2:"10"');
        $I->setCookie('visitor_uuid',
            '872023b23253fee368ca7a2cef832ebd0e0a179899204211e66c94421cdaf89da%3A2%3A%7Bi%3A0%3Bs%3A12%3A%22visitor_uuid%22%3Bi%3A1%3Bs%3A36%3A%22c9978853-4b81-426d-ba62-07817f1e4b19%22%3B%7D');
        $I->amOnPage('/visitor?utm_pid=77');
        $job = $I->sendCommandToRedis('hget', 'test-queue.messages', 2);
        expect('job placed in Redis exists', $job)->string();
        expect('it is valid type of job', $job)->stringContainsString(VisitJob::class);
        expect('and partner id is detected correctly', $job)->stringContainsString('"partner_id";s:2:"77"');
        expect('and visitor_uuid is detected correctly', $job)->stringContainsString('s:10:"visitor_id";i:1;');
        $I->amOnPage('/visitor?m=9');
        $job = $I->sendCommandToRedis('hget', 'test-queue.messages', 3);
        expect('job placed in Redis exists', $job)->string();
        expect('it is valid type of job', $job)->stringContainsString(VisitJob::class);
        expect('and visitor_uuid is detected correctly', $job)->stringContainsString('"manager_id";s:1:"9"');
    }
}
