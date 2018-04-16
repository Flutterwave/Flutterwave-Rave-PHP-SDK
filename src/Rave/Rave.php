<?php

namespace Rave;

use Dotenv\Dotenv;
use Rave\Payment\RavePayment;
use InvalidArgumentException;
use Rave\Event\BaseEventHandler;
use Rave\Event\EventHandlerInterface;
use Rave\Exception\RaveKeysException;
use Rave\Exception\RaveConfigException;
use Dotenv\Exception\InvalidPathException;
use Rave\Exception\RavePaymentFieldException;
use Rave\Exception\RaveInvalidMetadataException;

class Rave
{
	protected $rave = null;
	protected $config = [];
	protected $listener = null;
	protected $metadataFields = [];
	protected $paymentFieldsAliases = [];
	protected $overridesReference = false;

	protected static $instance = null;

	protected static $raveEnvironments = ['staging', 'live'];

	protected static $defaultConfig = [
		'env' => ['staging', 'string'],
		'autoRefs' => [true, 'bool']
	];

	protected static $defaultFields = [
		'amount', 'payment_method', 'description', 'logo', 'title', 'country',
		'currency', 'email', 'firstname', 'lastname', 'phonenumber', 'pay_button_text'
	];

	protected function __construct(RavePayment $rave, EventHandlerInterface $listener)
	{
		$this->rave = $rave;
		$this->listener($listener);
	}

	protected static function loadAppEnvironmentConfig($env = 'staging')
	{
		$vendorDir = realpath(dirname(__DIR__).'/../../../');

		try {
			$dotenv = new Dotenv(dirname($vendorDir));
			$dotenv->load();
		} catch (InvalidPathException $e) {
			throw new RaveConfigException("Could not resolve .env file from assumed root directory.");
		}

		$env = strtolower($env);
		$env = in_array($env, static::$raveEnvironments) ? $env : 'staging';

		$key_template = sprintf("RAVE_%s_%%s_KEY", strtoupper($env));

		$appName = getenv('RAVE_APP_NAME') ?: 'MY_APP_NAME';
		$publicKey = getenv(sprintf($key_template, 'PUBLIC'));
		$secretKey = getenv(sprintf($key_template, 'SECRET'));

		return [
			'app_name' => $appName,
			'public_key' => $publicKey,
			'secret_key' => $secretKey
		];
	}

	protected static function mergeConfig(array $config = [])
	{
		$defaultKeys = array_keys(static::$defaultConfig);

		$defaultVals = array_map(function($val) {
			return $val[0];
		}, array_values(static::$defaultConfig));

		$defaultConfig = array_combine($defaultKeys, $defaultVals);

		$mergedConfig = array_merge($defaultConfig, $config);
		$mergedConfigKeys = array_keys($mergedConfig);

		$mergedConfigKeys = array_filter($mergedConfigKeys, function($key) use ($defaultKeys) {
			return in_array($key, $defaultKeys);
		});

		$mergedConfig = array_combine(
			$mergedConfigKeys,
			array_map(function($key) use ($mergedConfig) {

				$value = $mergedConfig[$key];
				$givenType = gettype($value);
				$expectedType = static::$defaultConfig[$key][1];
				$expectation = sprintf("is_%s", $expectedType);

				if (is_callable($expectation) && $expectation($value)) return $value;

				throw new RaveConfigException("Invalid config value for '{$key}': Expected ({$expectedType}) but got ({$givenType}).");

			}, $mergedConfigKeys)
		);

		return $mergedConfig;
	}

	protected static function currentRequest()
	{
		$scheme = isset($_SERVER['HTTPS']) ? 'https' : 'http';
		$host = $_SERVER['HTTP_HOST'];
		$uri = $_SERVER['REQUEST_URI'];

		return [
			'url' => "{$scheme}://{$host}{$uri}",
			'query' => $_GET,
			'payload' => $_POST
		];
	}

	public static function init(array $config = [])
	{
		if (!static::$instance) {

			$mergedConfig = static::mergeConfig($config);
			extract($mergedConfig, EXTR_PREFIX_ALL, 'config');

			$config_env = strtolower($config_env);

			$raveConfig = static::loadAppEnvironmentConfig($config_env);
			extract($raveConfig, EXTR_PREFIX_ALL, 'rave');

			if (!($rave_public_key && $rave_secret_key)) {
				throw new RaveKeysException("Could not resolve Rave {$config_env} environment keys.");
			}

			$overrideRef = (bool) !$config_autoRefs;

			$rave = new RavePayment(
				$rave_public_key,
				$rave_secret_key,
				$rave_app_name,
				$config_env,
				$overrideRef
			);

			$listener = new BaseEventHandler();

			static::$instance = new Rave($rave, $listener);
			static::$instance->overridesReference = $overrideRef;

		}

		return static::$instance;
	}

	public function listener(EventHandlerInterface $listener)
	{
		$this->listener = $listener;
		$this->rave->eventHandler($this->listener);
		return $this;
	}

	protected function canMakePayment()
	{
		$payload = static::currentRequest()['payload'];

		$amountKey = array_key_exists('amount', $this->paymentFieldsAliases)
			? $this->paymentFieldsAliases['amount']
			: 'amount';

		return isset($payload[$amountKey]);
	}

	protected function canRequeryPayment()
	{
		$query = static::currentRequest()['query'];
		return isset($query['txref']);
	}

	protected function shouldCancelPayment()
	{
		$query = static::currentRequest()['query'];
		return isset($query['cancelled']) && isset($query['txref']);
	}

	protected static function setMethodFromField(string $field)
	{
		$splittedField = explode('_', $field);

		$capitalizedFields = array_map(function($frag) {
			return ucfirst(strtolower($frag));
		}, $splittedField);

		return sprintf("set%s", join('', $capitalizedFields));
	}

	protected function attachPaymentMetadata(RavePayment $rave)
	{
		$payload = static::currentRequest()['payload'];

		return array_reduce(
			$this->metadataFields,
			function($_rave, $field) use($payload) {
				return $_rave->setMetaData([
					'metaname' => $field,
					'metavalue' => @( $payload[$field] ?: null )
				]);
			},
			$rave
		);
	}

	protected function attachPaymentFieldData(RavePayment $rave)
	{
		$payload = static::currentRequest()['payload'];

		$allowedFields = static::$defaultFields;
		$fieldsAliases = $this->paymentFieldsAliases;

		return array_reduce(
			$allowedFields,
			function($_rave, $field) use($payload, $fieldsAliases) {

				$method = static::setMethodFromField($field);

				$value = array_key_exists($field, $fieldsAliases)
					? @( $payload[ $fieldsAliases[$field] ] ?: null )
					: @( $payload[$field] ?: null );


				return call_user_func([$_rave, $method], $value);
			},
			$rave
		);
	}

	protected function makePayment(RavePayment $rave)
	{
		$url = static::currentRequest()['url'];

		$raveWithFields = $this->attachPaymentFieldData($rave);

		$raveWithFields->setRedirectUrl($url);

		$raveWithMetadata = $this->attachPaymentMetadata($raveWithFields);

		$raveWithMetadata->initialize();
	}

	protected function cancelPayment(RavePayment $rave)
	{
		$query = static::currentRequest()['query'];
		$reference = $query['txref'];

		$rave
			->requeryTransaction($reference)
			->paymentCanceled($reference);
	}

	protected function completePayment(RavePayment $rave)
	{
		$query = static::currentRequest()['query'];
		$reference = $query['txref'];

		$rave->logger->notice('Payment completed. Now requerying payment.');
		$rave->requeryTransaction($reference);
	}

	protected function requirePaymentReference(RavePayment $rave)
	{
		$rave->logger->warn('Stop!!! Please pass the txref parameter!');
		echo 'Stop!!! Please pass the txref parameter!';
	}

	public function meta(array $metadata)
	{
		$metaKeys = array_keys($metadata);
		$metaFields = array_unique(array_values($metadata));

		$numericKeys = array_filter($metaKeys, 'is_int');
		$stringFields = array_filter($metaFields, 'is_string');

		$isIndexedArray = count($metaKeys) === count($numericKeys);
		$allStringedFields = count($metaFields) === count($stringFields);

		if ($isIndexedArray && $allStringedFields) {
			$fields = array_filter($metaFields, function($field) {
				return preg_match("/^[_a-z][_a-z0-9]+$/i", $field);
			});

			$badFields = array_diff($metaFields, $fields);

			if (empty($badFields)) {
				$this->metadataFields = $fields;
				return $this;
			}

			throw new RavePaymentFieldException(sprintf("Contains %s invalid metadata field names: ['%s'].", count($badFields), join("', '", $badFields)));
		}

		throw new RaveInvalidMetadataException("Invalid metadata fields array.");
	}

	public function fields(array $fields)
	{
		$fieldKeys = array_unique(array_keys($fields));
		$allowedFields = static::$defaultFields;

		$allowedFieldKeys = array_filter($fieldKeys, function($field) use($allowedFields, $fields) {
			return in_array($field, $allowedFields) && preg_match("/^[_a-z][_a-z0-9]+$/i", $fields[$field]);
		});

		$badOrUnknownFields = array_diff($fieldKeys, $allowedFieldKeys);

		if (!empty($badOrUnknownFields)) {
			$errorFields = array_filter($badOrUnknownFields, 'is_string');
			$message = "Contains some invalid or unknown payment field names";

			if (count($errorFields) >= 1) {
				$message = sprintf("%s: ['%s']", $message, join("', '", $errorFields));
			}

			throw new RavePaymentFieldException("{$message}.");
		}

		$fieldsAliases = array_reduce(
			$allowedFieldKeys,
			function($aliases, $key) use($fields) {
				$aliases[$key] = $fields[$key];
				return $aliases;
			},
			[]
		);

		$this->paymentFieldsAliases = $fieldsAliases;

		return $this;
	}

	public function run()
	{
		$rave = $this->overridesReference
			? $this->rave->initUsingPaymentReference()->eventHandler($this->listener)
			: $this->rave;

		// Make payment
		if ($this->canMakePayment()) return $this->makePayment($rave);

		// Handle cancelled payment
		if ($this->shouldCancelPayment()) return $this->cancelPayment($rave);

		// Handle completed payments
		if ($this->canRequeryPayment()) return $this->completePayment($rave);

		return $this->requirePaymentReference($rave);
	}
}
