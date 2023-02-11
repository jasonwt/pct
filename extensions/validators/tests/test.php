<?php
    interface PaymentInterface {
        public function SubmitPayment($amount, $cardnumber, $cardexpdate, $cardaddress, $cardccv) : bool;
    }
?>

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