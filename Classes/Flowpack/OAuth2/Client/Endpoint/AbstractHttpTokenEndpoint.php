<?php
namespace Flowpack\OAuth2\Client\Endpoint;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Flowpack.OAuth2.Client".*
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use Flowpack\OAuth2\Client\Exception as OAuth2Exception;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Http\Request;
use TYPO3\Flow\Http\Uri;
use TYPO3\Flow\Utility\Arrays;

/**
 */
abstract class AbstractHttpTokenEndpoint implements TokenEndpointInterface {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Http\Client\CurlEngine
	 */
	protected $requestEngine;

	/**
	 * @var string
	 */
	protected $endpointUri;

	/**
	 * The client identifier as per http://tools.ietf.org/html/rfc6749#section-2.2
	 * Filled via Objects.yaml
	 *
	 *@var string
	 */
	protected $clientIdentifier;

	/**
	 * The client secret as per http://tools.ietf.org/html/rfc6749#section-2.3.1
	 * Filled via Objects.yaml
	 *
	 *@var string
	 */
	protected $clientSecret;

	/**
	*/
	protected function initializeObject() {
		$this->requestEngine->setOption(CURLOPT_CAINFO, FLOW_PATH_PACKAGES . 'Application/Flowpack.OAuth2.Client/Resources/Private/cacert.pem');
		$this->requestEngine->setOption(CURLOPT_SSL_VERIFYPEER, TRUE);
	}

	/**
	 * Requests an access token for Client Credentials Grant as specified in section 4.4.2
	 *
	 * @param string $code The authorization code received from the authorization server.
	 * @param string $redirectUri REQUIRED, if the "redirect_uri" parameter was included in the authorization request as described in Section 4.1.1, and their values MUST be identical.
	 * @param string $clientIdentifier REQUIRED, if the client is not authenticating with the authorization server as described in Section 3.2.1.
	 * @return mixed
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1.3
	 */
	public function requestAuthorizationCodeGrantAccessToken($code, $redirectUri = NULL, $clientIdentifier = NULL) {
		$accessToken = $this->requestAccessToken(TokenEndpointInterface::GRANT_TYPE_AUTHORIZATION_CODE, array(
			'code' => $code,
			'redirect_uri' => $redirectUri,
			'client_id' => $clientIdentifier
		));
		return $accessToken;
	}

	/**
	 * Requests an access token for Resource Owner Password Credentials Grant as specified in section 4.3.2
	 *
	 * @param string $username The resource owner username.
	 * @param string $password The resource owner password.
	 * @param array $scope The scope of the access request as described by http://tools.ietf.org/html/rfc6749#section-3.3
	 * @return mixed
	 * @see http://tools.ietf.org/html/rfc6749#section-4.3.2
	 */
	public function requestResourceOwnerPasswordCredentialsGrantAccessToken($username, $password, $scope = array()) {
		// TODO: Implement requestResourceOwnerPasswordCredentialsGrantAccessToken() method.
	}

	/**
	 * Requests an access token for Client Credentials Grant as specified in section 4.4.2
	 *
	 * @param array $scope The scope of the access request as described by http://tools.ietf.org/html/rfc6749#section-3.3
	 * @return mixed
	 * @see http://tools.ietf.org/html/rfc6749#section-4.4.2
	 */
	public function requestClientCredentialsGrantAccessToken($scope = array()) {
		$accessToken = $this->requestAccessToken(TokenEndpointInterface::GRANT_TYPE_CLIENT_CREDENTIALS);
		return $accessToken;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->endpointUri;
	}

	/**
	 * @param string $grantType One of this' interface GRANT_TYPE_* constants
	 * @param array $additionalParameters Additional parameters for the request
	 * @return mixed
	 * @throws \Flowpack\OAuth2\Client\Exception
	 * @see http://tools.ietf.org/html/rfc6749#section-4.1.3
	 */
	protected function requestAccessToken($grantType, $additionalParameters = array()) {
		$parameters = array(
			'grant_type' => $grantType,
			'client_id' => $this->clientIdentifier,
			'client_secret' => $this->clientSecret
		);
		$parameters = Arrays::arrayMergeRecursiveOverrule($parameters, $additionalParameters, FALSE, FALSE);
		$request = Request::create(new Uri($this->endpointUri), 'POST', $parameters);
		$request->setHeader('Content-Type', 'application/x-www-form-urlencoded');

		$response = $this->requestEngine->sendRequest($request);
		if ($response->getStatusCode() !== 200) {
			throw new OAuth2Exception(sprintf('The response when requesting the access token was not as expected, code and message was: %d %s', $response->getStatusCode(), $response->getContent()), 1383749757);
		}
		parse_str($response->getContent(), $responseComponents);
		return $responseComponents['access_token'];
	}

}