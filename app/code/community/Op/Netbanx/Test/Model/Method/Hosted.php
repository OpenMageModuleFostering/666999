<?php

class Op_Netbanx_Test_Model_Method_Hosted extends EcomDev_PHPUnit_Test_Case
{

    /**
     * Test that the payment method can authorize
     *
     * @test
     */
    public function canAuthorize()
    {
        $method = Mage::getModel('optimal/method_hosted');

        $this->assertEquals(true, $method->canAuthorize());
    }
}