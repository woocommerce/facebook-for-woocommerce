<?php

namespace SkyVerge\WooCommerce\Facebook\Debug;

defined( 'ABSPATH' ) || exit;

/**
 * Class ProfilingLogger
 */
class ProfilingLogger {

	/**
	 * Is profile logging enabled.
	 *
	 * @var bool
	 */
	protected $is_enabled = false;

	/**
	 * Active processes in the current request.
	 *
	 * @var ProfilingLoggerProcess[]
	 */
	protected $active_processes = array();

	/**
	 * Past processes in the current request.
	 *
	 * @var ProfilingLoggerProcess[]
	 */
	protected $past_processes = array();

	/**
	 * ProfileLogger constructor.
	 *
	 * @param bool $is_enabled
	 */
	public function __construct( $is_enabled ) {
		$this->is_enabled = $is_enabled;
	}

	/**
	 * Check if a process is running.
	 *
	 * @param string $process_name
	 *
	 * @return bool
	 */
	protected function is_running( $process_name ) {
		return isset( $this->active_processes[ $process_name ] );
	}

	/**
	 * Start a process.
	 *
	 * @param string $process_name
	 *
	 * @return ProfilingLoggerProcess|null
	 */
	public function start( $process_name ) {
		if ( ! $this->is_enabled ) {
			return null;
		}

		if ( $this->is_running( $process_name ) ) {
			$this->log( "$process_name - Failed to start process because it's already started." );
			return null;
		}

		$this->active_processes[ $process_name ] = new ProfilingLoggerProcess();

		return $this->active_processes[ $process_name ];
	}

	/**
	 * Stop and a process.
	 *
	 * @param string $process_name
	 *
	 * @return ProfilingLoggerProcess|null
	 */
	public function stop( $process_name ) {
		if ( ! $this->is_enabled ) {
			return null;
		}

		if ( ! $this->is_running( $process_name ) ) {
			$this->log( "{$process_name} - Failed to stop process because it hasn't started." );
			return null;
		}

		$process = $this->active_processes[ $process_name ];
		$process->stop();

		// Move to past processes array
		unset( $this->active_processes[ $process_name ] );
		$this->past_processes = $process;

		return $process;
	}

	/**
	 * Stop a process and log the memory and time usage.
	 *
	 * @param string $process_name
	 *
	 * @return ProfilingLoggerProcess|null
	 */
	public function stop_and_log( $process_name ) {
		$process = $this->stop( $process_name );
		if ( ! $process ) {
			return null;
		}

		$memory = number_format( $process->get_memory_used() / 1000, 2 );
		$time   = number_format( $process->get_time_used(), 2 );

		$this->log( "{$process_name} - Memory: {$memory} KB, time: {$time}s." );

		return $process;
	}

	/**
	 * @param string $message
	 */
	protected function log( $message ) {
		wc_get_logger()->log( 'debug', $message, array( 'source' => 'facebook-for-wc-profile' ) );
	}

}
