<?php
declare(strict_types=1);

namespace App\Exception;

/**
 * Exception thrown when REST Countries API requests fail.
 * This allows for specific error handling in the sync command.
 */
class RestCountriesApiException extends \RuntimeException
{
}
