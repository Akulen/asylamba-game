<?php

namespace Asylamba\Classes\EventListener;

use Asylamba\Classes\Logger\AbstractLogger;
use Asylamba\Classes\Library\Session\SessionWrapper;
use Asylamba\Classes\Library\Flashbag;

use Asylamba\Classes\Library\Http\Request;
use Asylamba\Classes\Library\Http\Response;

use Asylamba\Classes\Event\ExceptionEvent;
use Asylamba\Classes\Event\ErrorEvent;
use Asylamba\Classes\Exception\FormException;

class ExceptionListener {
	/** @var AbstractLogger **/
	protected $logger;
	/** @var SessionWrapper **/
	protected $session;
	
	/**
	 * @param AbstractLogger $logger
	 * @param Session $session
	 */
	public function __construct(AbstractLogger $logger, SessionWrapper $session)
	{
		$this->logger = $logger;
		$this->session = $session;
	}
	
	/**
	 * @param ExceptionEvent $event
	 */
	public function onCoreException(ExceptionEvent $event)
	{
		$exception = $event->getException();
		$this->process(
			$event,
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			($exception instanceof FormException) ? '' : $exception->getTraceAsString(),
			AbstractLogger::LOG_LEVEL_ERROR,
			($exception instanceof FormException) ? Flashbag::TYPE_FORM_ERROR : Flashbag::TYPE_STD_ERROR,
			($exception instanceof FormException) ? $exception->getRedirect() : null
		);
	}
	
	/**
	 * @param ErrorEvent $event
	 */
	public function onCoreError(ErrorEvent $event)
	{
		$error = $event->getError();
		$this->process(
			$event,
			$error->getMessage(),
			$error->getFile(),
			$error->getLine(),
			$error->getTraceAsString(),
			AbstractLogger::LOG_LEVEL_CRITICAL,
			Flashbag::TYPE_BUG_ERROR
		);
	}
	
	/**
	 * @param $event
	 * @param string $message
	 * @param string $file
	 * @param int $line
	 * @param string $trace
	 * @param string $level
	 * @param int $flashbagLevel
	 */
	public function process($event, $message, $file, $line, $trace, $level, $flashbagLevel, $redirect = null)
	{
		$this->logger->log("$message at $file at line $line\n$trace", $level);
		
		$this->session->addFlashbag($message, $flashbagLevel);
		
		$response = new Response();
		$redirectionData = $this->getRedirection($event->getRequest(), $redirect);
		if (isset($redirectionData['redirect'])) {
			$response->redirect($redirectionData['redirect']);
		} else {
			$response->setStatusCode(Response::STATUS_INTERNAL_SERVER_ERROR);
			$response->addTemplate($redirectionData['template']);
		}
		$event->setResponse($response);
	}
	
	/**
	 * @param Request $request
	 * @return string
	 */
	public function getRedirection(Request $request, $redirect = null)
	{
		if ($redirect !== null) {
			return ['redirect' => $redirect];
		}
		
		$history = $this->session->getHistory();

		if (($nbPaths = count($history)) === 0) {
			return ['redirect' => '/'];
		}
		if (($redirect = '/' . $this->session->getLastHistory()) === $request->getPath()) {
			// We get the path before the last one if available
			$redirect = ($nbPaths > 1) ? $history[$nbPaths - 2] : '/';
		}
		// In this case, it means the user is in an error loop
		if ($nbPaths > 3 && $redirect === $history[$nbPaths - 4]) {
			return [
				'template' => TEMPLATE . 'fatal.php'
			];
		}
		return ['redirect' => $redirect];
	}
}