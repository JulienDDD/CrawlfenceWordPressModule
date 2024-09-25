<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Empêche l'accès direct
}

class CrawlFenceAPI {
    private $api_key;
    private $api_url = 'https://api.crawlfence.com/api';

    public function __construct( $api_key ) {
        if ( empty( $api_key ) ) {
            throw new Exception( 'La clé API est requise.' );
        }
        $this->api_key = $api_key;
    }

    public function handle_request() {
        $ip_address = $this->get_client_ip();
        $headers_received = $this->get_headers_received();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isp = ''; // Optionnel

        $response = $this->check_access( $ip_address, $headers_received, $user_agent, $isp );
        $this->process_response( $response );
    }

    private function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip_address;
    }

    private function get_headers_received() {
        $headers_received = [
            'Accept'            => $_SERVER['HTTP_ACCEPT'] ?? '',
            'Accept-Language'   => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'Accept-Encoding'   => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'User-Agent'        => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'Content-Length'    => $_SERVER['CONTENT_LENGTH'] ?? '',
            'Host'              => $_SERVER['HTTP_HOST'] ?? '',
            'Cache-Control'     => $_SERVER['HTTP_CACHE_CONTROL'] ?? '',
            'Forwarded'         => $_SERVER['HTTP_FORWARDED'] ?? '',
            'X-Forwarded-For'   => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        ];

        return array_filter( $headers_received );
    }

    private function trimArrayKeys($array)
    {
        $trimmed_array = [];
        foreach ($array as $key => $value) {
            $trimmed_key = trim($key);
            if (is_array($value)) {
                $value = $this->trimArrayKeys($value);
            }
            $trimmed_array[$trimmed_key] = $value;
        }
        return $trimmed_array;
    }

    private function process_response( $response ) {
        $response = $this->trimArrayKeys($response);
        $status = $response['status'] ?? 'UNKNOWN';
        switch ( $status ) {
            case 'ALLOWED':
                // Accès accordé, continuer
                break;

            case 'CAPTCHA_REQUIRED':
                $captcha_url = $response['captcha_url'] ?? '';
                if ( ! empty( $captcha_url ) ) {
                    wp_redirect( $captcha_url );
                    exit;
                } else {
                    wp_die( 'Un CAPTCHA est requis, mais aucune URL de CAPTCHA n\'a été fournie.' );
                }
                break;

            case 'BLOCKED':
                wp_die( $response['message'] ?? 'Accès bloqué en raison des politiques de sécurité.' );
                break;

            default:
                wp_die( 'Une erreur inconnue est survenue.' );
                break;
        }
    }

    
function get_domain() {
    // Récupérer l'URL actuelle
    $host = $_SERVER['HTTP_HOST'];

    // Supprimer 'www.' s'il existe
    $host = preg_replace('/^www\./', '', $host);


    return $host;  // Retourne le domaine complet (avec sous-domaine si présent)
}


    private function send_request( $params = [] ) {
        $params['api_key'] = $this->api_key;
        $params['domain'] = $this->get_domain();

        $url = $this->api_url . '?' . http_build_query( $params );

        $response = wp_remote_get( $url, [
            'headers' => [-
                'Accept' => 'application/json',
            ],
        ] );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'Erreur lors de la requête à l\'API CrawlFence.' );
        }

        $body = wp_remote_retrieve_body( $response );
        $decoded_response = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            throw new Exception( 'Erreur de décodage JSON : ' . json_last_error_msg() );
        }

        return $decoded_response;
    }

    public function check_access( $ip_address, $headers_received = [], $user_agent = '', $isp = '' ) {
        $params = [
            'ip'               => $ip_address,
            'headers_received' => json_encode( $headers_received ),
            'user_agent'       => $user_agent,
            'isp'              => $isp,
        ];

        return $this->send_request( $params );
    }
}
