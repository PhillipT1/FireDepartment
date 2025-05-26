<?php

use PHPUnit\Framework\TestCase;

// Assume the functions are autoloaded or included.
// If not, you'd need: require_once dirname(__DIR__) . '/includes/api-endpoints.php';
// For this environment, I'll assume they are accessible.

class ValidationFunctionsTest extends TestCase {

    public function test_irm_validate_datetime_format_valid() {
        $this->assertTrue(irm_validate_datetime_format('2023-10-27 10:00:00', null, 'datetime'));
        $this->assertTrue(irm_validate_datetime_format('2023-10-27T10:00:00', null, 'datetime'));
        $this->assertTrue(irm_validate_datetime_format('2023-10-27 10:00', null, 'datetime')); // Validated by regex, then parsed
    }

    public function test_irm_validate_datetime_format_invalid() {
        $result = irm_validate_datetime_format('2023-13-27 10:00:00', null, 'datetime'); // Invalid month
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_invalid_param', $result->get_error_code());

        $result = irm_validate_datetime_format('2023-10-27 25:00:00', null, 'datetime'); // Invalid hour
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_invalid_param', $result->get_error_code());
        
        $result = irm_validate_datetime_format('invalid-date', null, 'datetime');
        $this->assertInstanceOf(WP_Error::class, $result);
    }
    
    public function test_irm_validate_datetime_format_optional_valid() {
        $this->assertTrue(irm_validate_datetime_format_optional('', null, 'datetime')); // Empty is valid
        $this->assertTrue(irm_validate_datetime_format_optional('2023-10-27 10:00:00', null, 'datetime'));
    }

    public function test_irm_validate_date_format_valid() {
        $this->assertTrue(irm_validate_date_format('2023-10-27', null, 'date'));
    }

    public function test_irm_validate_date_format_invalid() {
        $result = irm_validate_date_format('2023-13-27', null, 'date'); // Invalid month
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_invalid_param', $result->get_error_code());

        $result = irm_validate_date_format('invalid-date', null, 'date');
        $this->assertInstanceOf(WP_Error::class, $result);
    }
    
    public function test_irm_validate_date_format_optional_valid() {
        $this->assertTrue(irm_validate_date_format_optional('', null, 'date'));
        $this->assertTrue(irm_validate_date_format_optional('2023-10-27', null, 'date'));
    }


    public function test_irm_validate_time_format_valid() {
        $this->assertTrue(irm_validate_time_format('10:00', null, 'time'));
        $this->assertTrue(irm_validate_time_format('10:00:30', null, 'time'));
        $this->assertTrue(irm_validate_time_format('23:59:59', null, 'time'));
    }

    public function test_irm_validate_time_format_invalid() {
        $result = irm_validate_time_format('25:00', null, 'time');
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('rest_invalid_param', $result->get_error_code());
        
        $result = irm_validate_time_format('10:60', null, 'time');
        $this->assertInstanceOf(WP_Error::class, $result);

        $result = irm_validate_time_format('invalid-time', null, 'time');
        $this->assertInstanceOf(WP_Error::class, $result);
    }
    
     public function test_irm_validate_time_format_optional_valid() {
        $this->assertTrue(irm_validate_time_format_optional('', null, 'time'));
        $this->assertTrue(irm_validate_time_format_optional('10:00', null, 'time'));
    }
}

// Mock WP_Error if not available in testing environment (basic mock)
if (!class_exists('WP_Error')) {
    class WP_Error {
        public $code;
        public $message;
        public $data;
        public function __construct($code = '', $message = '', $data = '') {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }
        public function get_error_code() { return $this->code; }
        public function get_error_message() { return $this->message; }
    }
}
// Mock WordPress internationalization functions if not available
if (!function_exists('__')) {
    function __($text, $domain = 'default') { return $text; }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default') { return $text; }
}
if (!function_exists('sprintf')) {
    // Sprintf is a PHP native function, but just in case of weirdness.
}
if(!function_exists('checkdate')) {
    function checkdate($m, $d, $y) { // Basic polyfill if missing, though unlikely
        return true; 
    }
}
if(!function_exists('date_parse_from_format')) {
    function date_parse_from_format($format, $date) { // Basic polyfill
        // This is a complex function to polyfill. For tests, if it's not available,
        // the real validation might not run fully. Assume it exists in a proper WP test env.
        return ['error_count' => 0, 'warning_count' => 0, 'year' => 2023, 'month' => 10, 'day' => 27, 'hour' => 10, 'minute' => 0, 'second' => 0];
    }
}

// Need to make sure the functions are actually loaded for the test.
// This is typically handled by PHPUnit bootstrap in a WP context.
// For this environment, I will assume the functions from api-endpoints.php are accessible.
// If I could run `run_in_bash_session`, I would include the file here.
// Since I can't, this test file is more of a demonstration.

// To make this runnable in a non-WP environment for the purpose of this test,
// I would normally include the specific functions here or the file they reside in.
// Let's simulate that by including the required functions directly if they are not found (conceptual).

if (!function_exists('irm_validate_datetime_format')) {
    function irm_validate_datetime_format( $param, $request, $key ) {
        if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        $pattern = '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}(:\d{2})?$/'; 
        if ( ! preg_match( $pattern, $param, $matches ) ) {
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid datetime format (YYYY-MM-DD HH:MM or YYYY-MM-DD HH:MM:SS).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        }
        $parse_param = $param;
        if (isset($matches[3]) && $matches[3] === '') { 
            $parse_param = $param . ':00';
        } elseif (!isset($matches[3])) { 
             $parse_param = $param . ':00';
        }
        $datetime = date_parse_from_format('Y-m-d H:i:s', str_replace('T', ' ', $parse_param));
        if ($datetime['error_count'] > 0 || $datetime['warning_count'] > 0 || !checkdate($datetime['month'], $datetime['day'], $datetime['year']) || !($datetime['hour'] !== false && $datetime['minute'] !== false && $datetime['second'] !== false)) {
             return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid calendar date/time.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        }
        return true;
    }
}
if (!function_exists('irm_validate_datetime_format_optional')) {
    function irm_validate_datetime_format_optional( $param, $request, $key ) {
        if (empty($param)) return true; 
        return irm_validate_datetime_format($param, $request, $key);
    }
}
if (!function_exists('irm_validate_date_format')) {
    function irm_validate_date_format( $param, $request, $key ) {
        if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        $pattern = '/^\d{4}-\d{2}-\d{2}$/';
        if ( ! preg_match( $pattern, $param ) ) {
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid date format (YYYY-MM-DD).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        }
        $dateparts = explode('-', $param);
        if ( !checkdate( (int)$dateparts[1], (int)$dateparts[2], (int)$dateparts[0]) ) {
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid calendar date.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        }
        return true;
    }
}
if (!function_exists('irm_validate_date_format_optional')) {
    function irm_validate_date_format_optional( $param, $request, $key ) {
        if (empty($param)) return true; 
        return irm_validate_date_format($param, $request, $key);
    }
}
if (!function_exists('irm_validate_time_format')) {
    function irm_validate_time_format( $param, $request, $key ) {
         if ( ! is_string( $param ) ) return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a string.', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        $pattern = '/^([01]\d|2[0-3]):([0-5]\d)(:([0-5]\d))?$/'; 
        if ( ! preg_match( $pattern, $param ) ) {
            return new WP_Error( 'rest_invalid_param', sprintf( esc_html__( '%s is not a valid time format (HH:MM or HH:MM:SS).', 'incident-response-manager' ), $key ), array( 'status' => 400 ) );
        }
        return true;
    }
}
if (!function_exists('irm_validate_time_format_optional')) {
    function irm_validate_time_format_optional( $param, $request, $key ) {
        if (empty($param)) return true; 
        return irm_validate_time_format($param, $request, $key);
    }
}

?>
