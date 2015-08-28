<?php

namespace Franzl\Lti;

/**
 * Class to represent an OAuth datastore
 *
 * @author  Stephen P Vickers <stephen@spvsoftwareproducts.com>
 * @version 2.5.00
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3
 */
class HttpMessage
{

    /**
     * @var request Request body.
     */
    public $request = null;

    /**
     * @var request_headers Request headers.
     */
    public $request_headers = '';

    /**
     * @var response Response body.
     */
    public $response = null;

    /**
     * @var response_headers Response headers.
     */
    public $response_headers = '';

    /**
     * @var status Status of response (0 if undetermined).
     */
    public $status = 0;

    /**
     * @var error Error message
     */
    public $error = '';

    /**
     * @var url Request URL.
     */
    private $url = null;

    /**
     * @var method Request method.
     */
    private $method = null;

    /**
     * Class constructor.
     *
     * @param string $url     URL to send request to
     * @param string $method  Request method to use (optional, default is GET)
     * @param mixed  $params  Associative array of parameter values to be passed or message body (optional, default is none)
     * @param string $header  Values to include in the request header (optional, default is none)
     */
    function __construct($url, $method = 'GET', $params = null, $header = null)
    {

        $this->url = $url;
        $this->method = strtoupper($method);
        if (is_array($params)) {
            $this->request = http_build_query($params);
        } else {
            $this->request = $params;
        }
        if (!empty($header)) {
            $this->request_headers = explode("\n", $header);
        }

    }

    /**
     * Send the request to the target URL.
     *
     * @return boolean TRUE if the request was successful
     */
    public function send()
    {

        $ok = false;
// Try using curl if available
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->url);
            if (!empty($this->request_headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $this->request_headers);
            } else {
                curl_setopt($ch, CURLOPT_HEADER, 0);
            }
            if ($this->method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
            } else if ($this->method != 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);
                if (!is_null($this->request)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);
                }
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            $ch_resp = curl_exec($ch);
            $ok = $ch_resp !== false;
            if ($ok) {
                $ch_resp = str_replace("\r\n", "\n", $ch_resp);
                $ch_resp_split = explode("\n\n", $ch_resp, 2);
                if ((count($ch_resp_split) > 1) && (substr($ch_resp_split[1], 0, 5) == 'HTTP/')) {
                    $ch_resp_split = explode("\n\n", $ch_resp_split[1], 2);
                }
                $this->response_headers = $ch_resp_split[0];
                $resp = $ch_resp_split[1];
                $this->status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $ok = $this->status < 400;
                if (!$ok) {
                    $this->error = curl_error($ch);
                }
            }
            $this->request_headers = str_replace("\r\n", "\n", curl_getinfo($ch, CURLINFO_HEADER_OUT));
            curl_close($ch);
            $this->response = $resp;
        } else {
// Try using fopen if curl was not available
            $opts = array('method' => $this->method,
                          'content' => $this->request
            );
            if (!empty($this->request_headers)) {
                $opts['header'] = $this->request_headers;
            }
            try {
                $ctx = stream_context_create(array('http' => $opts));
                $fp = @fopen($this->url, 'rb', false, $ctx);
                if ($fp) {
                    $resp = @stream_get_contents($fp);
                    $ok = $resp !== false;
                }
            } catch (Exception $e) {
                $ok = false;
            }
        }

        return $ok;

    }
}
