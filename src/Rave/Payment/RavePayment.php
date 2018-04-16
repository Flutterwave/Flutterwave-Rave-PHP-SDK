<?php

namespace Rave\Payment;

class RavePayment extends AbstractRavePayment
{

	public function initUsingPaymentReference()
	{
		$payload = $_POST;

		return isset($payload['ref'])
			? new static(
				$this->publicKey,
				$this->secretKey,
				$payload['ref'],
				$this->env,
				true
			)
			: $this;
	}

}
