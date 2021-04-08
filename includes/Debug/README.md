# Debug

## Profiling Logger

The profiling logger can be used to log the time and memory usage of a process in a single request. 
Results are logged to WooCommerce logs with the name `facebook_for_woocommerce_profiling`.

## Enabling

Logging must be enabled with a constant in the `wp-config.php` file. 

`define( 'FACEBOOK_FOR_WOOCOMMERCE_PROFILING_LOG_ENABLED', true );`

## Code example

```php
$profiling = facebook_for_woocommerce()->get_profiling_logger();

$profiling->start( 'unique_process_name' );

// Some slow code here

$profiling->stop( 'unique_process_name' );
```
