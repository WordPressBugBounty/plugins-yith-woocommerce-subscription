<?php
/**
 * Legacy payment tokens for vault version 2 endpoint.
 *
 * @package YITH/Subscription/Gateways
 */
declare( strict_types = 1 );

use WooCommerce\PayPalCommerce\ApiClient\Authentication\Bearer;
use WooCommerce\PayPalCommerce\ApiClient\Endpoint\RequestTrait;
use WooCommerce\PayPalCommerce\ApiClient\Entity\PaymentToken;
use WooCommerce\PayPalCommerce\ApiClient\Exception\PayPalApiException;
use WooCommerce\PayPalCommerce\ApiClient\Repository\CustomerRepository;
use WooCommerce\PayPalCommerce\Vendor\Psr\Log\LoggerInterface;

class YWSBS_WC_PayPal_Payments_Token_Endpoint {
	use RequestTrait;

	/**
	 * The bearer.
	 *
	 * @var Bearer
	 */
	private $bearer;

	/**
	 * The host.
	 *
	 * @var string
	 */
	private $host;

	/**
	 * The logger.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The customer repository.
	 *
	 * @var CustomerRepository
	 */
	protected $customer_repository;

	/**
	 * PaymentTokenEndpoint constructor.
	 *
	 * @param string                         $host The host.
	 * @param Bearer                         $bearer The bearer.
	 * @param LoggerInterface                $logger The logger.
	 * @param CustomerRepository             $customer_repository The customer repository.
	 */
	public function __construct(string $host, Bearer $bearer, LoggerInterface $logger, CustomerRepository $customer_repository)
	{
		$this->host = $host;
		$this->bearer = $bearer;
		$this->logger = $logger;
		$this->customer_repository = $customer_repository;
	}

	/**
	 * Returns the payment tokens for the given user id.
	 *
	 * @param int $id The user id.
	 *
	 * @return PaymentToken[]
	 * @throws RuntimeException If the request fails.
	 */
	public function for_user(int $id): array
	{
		$bearer = $this->bearer->bearer();
		$url = trailingslashit($this->host) . 'v2/vault/payment-tokens/?customer_id=' . $this->customer_repository->customer_id_for_user($id);
		$args = array('method' => 'GET', 'headers' => array('Authorization' => 'Bearer ' . $bearer->token(), 'Content-Type' => 'application/json'));

		$response = $this->request($url, $args);

		if (is_wp_error($response)) {
			$error = new RuntimeException(__('Could not fetch payment token for customer id.', 'woocommerce-paypal-payments'));
			$this->logger->log('warning', $error->getMessage(), array('args' => $args, 'response' => $response));
			throw $error;
		}

		$json = json_decode($response['body']);
		$status_code = (int) wp_remote_retrieve_response_code($response);

		if (200 !== $status_code) {
			$error = new PayPalApiException($json, $status_code);
			$this->logger->log('warning', $error->getMessage(), array('args' => $args, 'response' => $response));
			throw $error;
		}

		$tokens = array();
		foreach ($json->payment_tokens as $token_value) {
			$tokens[] = $this->from_paypal_response($token_value);
		}

		return $tokens;
	}

	/**
	 * Returns a PaymentToken based off a PayPal Response object.
	 *
	 * @param object $data The JSON object.
	 *
	 * @return PaymentToken
	 * @throws RuntimeException When JSON object is malformed.
	 */
	public function from_paypal_response($data): PaymentToken
	{
		if (!isset($data->id)) {
			throw new RuntimeException('No id for payment token given');
		}
		return new PaymentToken($data->id, $data->source, isset($data->type) ? $data->type : PaymentToken::TYPE_PAYMENT_METHOD_TOKEN);
	}
}