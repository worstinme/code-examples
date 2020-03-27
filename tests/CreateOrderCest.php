<?php

class CreateOrderCest
{
    public function _fixtures()
    {
        return [
            'orders'=> \app\tests\fixtures\OrdersFixture::class,
        ];
    }

    private function orderData($mixin = [])
    {
        return array_merge([
            'model_id' => 1,
            'client_email' => 'test@email.email',
            'client_wa' => '79051111111',
            'start' => '2030-01-01',
            'end' => '2030-01-10',
        ], $mixin);
    }

    public function _before(ApiTester $I)
    {
        /* $I->wantTo('Login user');
        $I->sendPOST('/user/login', ['username' => 'tester', 'password' => 'tester']);
        $I->seeResponseContains('{"username":"tester"}'); */
    }

    // tests
    public function tryToSendEmptyData(ApiTester $I)
    {
        $I->sendPOST('/cabinet/create', []);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            ['field' => 'model_id', 'msg' => Yii::t('app', 'REQUIRED')],
            ['field' => 'client_email', 'msg' => Yii::t('app', 'REQUIRED')],
            ['field' => 'client_wa', 'msg' => Yii::t('app', 'REQUIRED')],
            ['field' => 'end', 'msg' => Yii::t('app', 'REQUIRED')],
            ['field' => 'start', 'msg' => Yii::t('app', 'REQUIRED')],
        ]);
    }

    public function tryToOrderRandomModel(ApiTester $I)
    {
        $I->sendPOST('/cabinet/create', $this->orderData(['model_id' => 999]));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            ['field' => 'model_id', 'msg' => 'Значение «Модель» неверно.'],
        ]);
    }

    public function tryToOrderWithWrongWA(ApiTester $I)
    {
        $I->sendPOST('/cabinet/create', $this->orderData(['client_wa' => 'sdfsdf']));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            ['field' => 'client_wa', 'msg' => 'Обязательное поле'],
        ]);
    }

    public function tryToOrderWithEnglishLanguage(ApiTester $I)
    {
        $I->sendPOST('/cabinet/create', $this->orderData(['lang' => 'en', 'model_id' => 100]));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            ['field' => 'model_id', 'msg' => 'Model is invalid.'],
        ]);
    }

    public function tryToOrderWithWrongEmail(ApiTester $I)
    {
        $I->sendPOST('/cabinet/create', $this->orderData(['client_email' => 'sdfsdf']));
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson([
            [
                'field' => 'client_email',
                'msg' => 'Значение «E-mail (отправим подтверждение)» не является правильным email адресом.'
            ],
        ]);
    }

    public function makeOrder(ApiTester $I)
    {
        $orderData = $this->orderData();
        $I->sendPOST('/cabinet/create', $orderData);
        $I->seeResponseCodeIs(201);
        $I->seeResponseMatchesJsonType([
            'redirect' => 'string',
        ]);

        $order_id = $I->grabFromDatabase('orders','id',[
            'start_at' => strtotime($orderData['start']),
            'end_at' => strtotime($orderData['end']),
            'client_wa' => $orderData['client_wa'],
            'client_email' => $orderData['client_email'],
            'client_language' => 'ru',
            'state' => 0,
        ]);

        expect($order_id)->scalar();

        $I->seeInDatabase('tasks',[
            'order_id'=>$order_id,
            'type'=>3,
        ]);

        $I->seeInDatabase('tasks',[
            'order_id'=>$order_id,
            'type'=>4,
        ]);

        $I->seeInDatabase('orders_extras',[
            'order_id'=>$order_id,
            'extra_id'=>1,
            'sum'=>750000,
            'quantity'=> round((strtotime($orderData['end']) - strtotime($orderData['start'])) / (24*60*60)  + 1)
        ]);

    }
}
