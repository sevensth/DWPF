<?php
class DWLibError
{
	public static function throwE404Exception($message = NULL)
	{
		if (!$message || !is_string($message))
		{
			$message = "E404";
		}
		throw new Exception($message, 404);
	}

    public static function SendHTTPStatusCodeHeader($code, $data, $replace = true)
    {
        if ($code >= 300 && $code < 400) {
            self::_SendHTTPStatusCode3xxHeader($code, $data, $replace);
        } else if ($code >= 400 && $code < 500) {
            self::_SendHTTPStatusCode4xxHeader($code, $data, $replace);
        } else {
            throw new Exception("Currently only support 3xx and 4xx");
        }
    }

    private static function _SendHTTPStatusCode3xxHeader($code, $url, $replace)
    {
        header("Location: $url", $replace, $code);
    }

    private static function _SendHTTPStatusCode4xxHeader($code, $message, $replace)
    {
        header($_SERVER["SERVER_PROTOCOL"] . ' ' . $code . ' ' . $message, $replace);
    }
}
