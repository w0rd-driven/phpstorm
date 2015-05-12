<?php
	use Swagger\Annotations as SWG;

	/**
	 * @package
	 * @category
	 *
	 * @SWG\Resource(
	 *   apiVersion="1.0.0",
	 *   swaggerVersion="1.2",
	 *   basePath="/auth/v1",
	 *   resourcePath="/user",
	 *   description="Operations about user",
	 *   @SWG\Produces("application/json")
	 * )
	 */

	/**
	 * @SWG\Api(path="/user/forgotPassword/{email}",
	 *   @SWG\Operation(
	 *     method="POST",
	 *     summary="Send user credentials by email",
	 *     notes="",
	 *     type="User",
	 *     nickname="userForgotPassword",
	 *     @SWG\Parameter(
	 *       name="email",
	 *       description="The email address. Use jbrayton@rethinkgroup.org for testing.",
	 *       required=true,
	 *       type="string",
	 *       paramType="path"
	 *     ),
	 *     @SWG\ResponseMessage(code=400, message="Invalid email address supplied."),
	 *     @SWG\ResponseMessage(code=404, message="Object not found.")
	 *   )
	 * )
	 */
	$app->post('/user/forgotPassword/:email', function ($email) use ($app) {
		$user = new \model\User($app->log);
		$results = $user->forgotPassword($email);
		echo json_encode($results);
	});
?>