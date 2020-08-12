<?php
/**
 * Created by PhpStorm.
 * User: djay
 * Date: 19-Jul-19
 * Time: 11:48 AM
 */

namespace App\Paypal;

use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Common\PayPalModel;


class SubscriptionPlan extends Paypal
{
    public function create () {

        $plan = $this->plan();

        $paymentDefinition = $this->paymentDefinition();

        $chargeModel = $this->chargeModel();

        $paymentDefinition->setChargeModels(array($chargeModel));

        $merchantPreferences = $this->merchantPreferences();


        $plan->setPaymentDefinitions(array($paymentDefinition));

        $plan->setMerchantPreferences($merchantPreferences);

        try {

            $output = $plan->create($this->apiContext);

            dd($output);

        } catch (Exception $ex) {

            //exit(1);
        }
    }

    /**
     * @return Plan
     */
    protected function plan(): Plan
    {
        $plan = new Plan();

        $plan->setName('New Month Club Plan')
            ->setDescription('Template creation.')
            ->setType('fixed');

        return $plan;
    }

    /**
     * @return PaymentDefinition
     */
    protected function paymentDefinition(): PaymentDefinition
    {
        $paymentDefinition = new PaymentDefinition();
        $paymentDefinition->setName('Regular Payments')
                            ->setType('REGULAR')
                            ->setFrequency('Month')
                            ->setFrequencyInterval("2")
                            ->setCycles("12")
                            ->setAmount(new Currency(array('value' => 100, 'currency' => 'USD')));

        return $paymentDefinition;
    }

    /**
     * @return ChargeModel
     */
    protected function chargeModel(): ChargeModel
    {
        $chargeModel = new ChargeModel();
        $chargeModel->setType('SHIPPING')
                    ->setAmount(new Currency(array('value' => 10, 'currency' => 'USD')));

        return $chargeModel;
    }

    /**
     * @return MerchantPreferences
     */
    protected function merchantPreferences(): MerchantPreferences
    {
        $merchantPreferences = new MerchantPreferences();

        $merchantPreferences->setReturnUrl(config('services.paypal.url.executeAgreement.success'))
                            ->setCancelUrl(config('services.paypal.url.executeAgreement.failure'))
                            ->setAutoBillAmount("yes")
                            ->setInitialFailAmountAction("CONTINUE")
                            ->setMaxFailAttempts("0")
                            ->setSetupFee(new Currency(array('value' => 1, 'currency' => 'USD')));

        return $merchantPreferences;
    }

    public function listPlan ()
    {
        //try {

            $params = array('page_size' => '10');
            $planList = Plan::all($params, $this->apiContext);

            return $planList;

//        } catch (Exception $ex) {
//
//            exit(1);
//        }
    }

    public function getPlanDetails($id)
    {
        try {
            $plan = Plan::get($id, $this->apiContext);

            return $plan;

        } catch (Exception $ex) {

            exit(1);
        }
    }

    public function activate($id)
    {
        $createdPlan = $this->getPlanDetails($id);

        try {
            $patch = new Patch();

            $value = new PayPalModel('{"state":"ACTIVE"}');

            $patch->setOp('replace')
                    ->setPath('/')
                    ->setValue($value);

            $patchRequest = new PatchRequest();
            $patchRequest->addPatch($patch);

            $createdPlan->update($patchRequest, $this->apiContext);

            $plan = Plan::get($createdPlan->getId(), $this->apiContext);

            return $plan;

        } catch (Exception $ex) {

            //exit(1);
        }
    }
}
