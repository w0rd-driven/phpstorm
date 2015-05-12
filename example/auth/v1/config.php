<?php
	function normalizeKey($key) {
		$key = strtolower($key);
		$key = str_replace(array('-', '_'), ' ', $key);
		$key = preg_replace('#^http #', '', $key);
		$key = ucwords($key);
		$key = str_replace(' ', '-', $key);
		return $key;
	}

	$app->container->singleton('logger', function () {
		// the default date format is "Y-m-d H:i:s"
		$dateFormat = "Y-m-d g:i:s a";
		// the default output format is "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"
		$output = "[%datetime%] %level_name%: %message% %context% %extra%\n";
		// finally, create a formatter
		$formatter = new \Monolog\Formatter\LineFormatter($output, $dateFormat, TRUE, TRUE);
		$streamDebug = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/DEBUG-".date("Y-m-d").".log",
		                                                  \Monolog\Logger::DEBUG, FALSE);
		$streamDebug->setFormatter($formatter);
		$streamInfo = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/INFO-".date("Y-m-d").".log",
		                                                 \Monolog\Logger::INFO, FALSE);
		$streamInfo->setFormatter($formatter);
		$streamNotice = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/NOTICE-".date("Y-m-d").".log",
		                                                   \Monolog\Logger::NOTICE, FALSE);
		$streamNotice->setFormatter($formatter);
		$streamWarning = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/WARNING-".date("Y-m-d").".log",
		                                                    \Monolog\Logger::WARNING, FALSE);
		$streamWarning->setFormatter($formatter);
		$streamError = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/ERROR-".date("Y-m-d").".log",
		                                                  \Monolog\Logger::ERROR, FALSE);
		$streamError->setFormatter($formatter);
		$streamCritical = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/CRITICAL-".date("Y-m-d").".log",
		                                                     \Monolog\Logger::CRITICAL, FALSE);
		$streamCritical->setFormatter($formatter);
		$streamAlert = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/ALERT-".date("Y-m-d").".log",
		                                                  \Monolog\Logger::ALERT, FALSE);
		$streamAlert->setFormatter($formatter);
		$streamEmergency = new \Monolog\Handler\StreamHandler(__DIR__ . "/../logs/EMERGENCY-".date("Y-m-d").".log",
		                                                      \Monolog\Logger::EMERGENCY, FALSE);
		$streamEmergency->setFormatter($formatter);

		$logger = new \Flynsarmy\SlimMonolog\Log\MonologWriter([
			'handlers' => [
				$streamDebug,
				$streamInfo,
				$streamNotice,
				$streamWarning,
				$streamError,
				$streamCritical,
				$streamAlert,
				$streamEmergency,
			],
		]);
		return $logger;
	});

	// Only invoked if mode is "production"
	$app->configureMode("production", function () use ($app) {
		$app->config([
			"log.writer" => $app->logger,
			"log.level" => \Slim\Log::WARN,
			"log.enable" => true, //! HACK: This is pointless due to some weird bug I don't care to track down
			"debug" => false
		]);
		$app->log->setEnabled(true);
	});

	// Only invoked if mode is "development"
	$app->configureMode("development", function () use ($app) {
		$app->config([
			"log.writer" => $app->logger,
			"log.level" => \Slim\Log::DEBUG,
			"log.enable" => false, //! HACK: This is pointless due to some weird bug I don't care to track down
			"debug" => true
		]);
		$app->log->setEnabled(false);
	});

	// slim.after.dispatch or slim.after.router would probably work just as well. Experiment
	$app->hook('slim.after', function () use ($app) {
		$request = $app->request;
		$response = $app->response;

		try {
			$headers = "";
			$headerArray = $request->headers()->extract($_SERVER);
			foreach ($headerArray as $key => $value) {
				$name = normalizeKey($key);
				// We don't care about these headers specifically
				if (($key == "HTTP_HOST") || ($key == "HTTP_USER_AGENT")) {
					continue;
				}
				$headers .= "\n$name: $value";
			}

			$app->log->debug("---------------");
			$app->log->debug("Headers: " . $headers);
			$app->log->debug("IP: " . $request ->getIp());
			$app->log->debug("User Agent: " . $request->getUserAgent());
			$app->log->debug("Status: " . $response->getMessageForCode($response->getStatus()));
			$app->log->debug("Path: " . $request->getPathInfo());
			$referrer = $request->getReferrer();
			if (!empty($referrer))
				$app->log->debug("Referrer: " . $referrer);

			$body = $request->getBody();
			if (!empty($body))
				$app->log->debug("Request Body: \n" . $body);
			$body = $response->getBody();
			if (!empty($body))
				$app->log->debug("Response Body: \n" . $body);
		}
		catch(Exception $exception) {
			// Do not care if any of this dies
		}
	});
?>