<?php

namespace Andihoerudin\Dropbox;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Storage;

class DropboxService extends Dropboxendpoint implements DropboxBuilder
{

    /** @var string */
    protected $appKey = null;

    /** @var string */
    protected $appSecret = null;

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var string */
    protected $path = null;

    /** @var string */
    protected $contents = null;

    /** @var bool */
    protected $publish = false;

    public function __construct()
    {
        $this->client = new GuzzleClient();
    }


    /**
     * Set the value of appKey
     *
     * @return  self
     */
    final public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    /**
     * Set the value of appSecret
     *
     * @return  self
     */
    final function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;

        return $this;
    }

    /**
     * Set the value of path
     *
     * @return  self
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Set the value of contents
     *
     * @return  self
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * Set the value of publish
     *
     * @return  self
     */
    public function setPublish($publish)
    {
        $this->publish = $publish;

        return $this;
    }

    public function getToken(): mixed
    {

        $response = $this->authentication('code', env('DROPBOX_CODE'), 'grant_type', 'authorization_code');
        if ($response == 400) {
            $response = $this->refreshToken();
        } else {
            Storage::disk('local')->put('getToken.json', json_encode($response));
            $response = $this->refreshToken();
        }
        return $response;
    }

    /**
     * return @array
     */
    public function refreshToken(): mixed
    {
        try {
            $refreshtoken = json_decode(Storage::disk('local')->get('getToken.json'));
            $response = $this->authentication('grant_type', 'refresh_token', 'refresh_token', $refreshtoken->refresh_token);
            return $response;
        } catch (\Throwable $th) {
             //return error json 
            return 'invalid dropbox code';
        }
    }

    /**
     * return @array
     */
    public function upload($mode = 'add', $autorename = false): array
    {
        $token = $this->getToken();
        $response = $this->client->request('POST', parent::UPLOAD_URL . parent::END_POINT_UPLOAD_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token['access_token'],
                'Content-Type' => 'application/octet-stream',
                'Dropbox-API-Arg' => json_encode([
                    'path' => $this->normalizePath($this->path),
                    'mode' => $mode,
                    'autorename' => $autorename,
                    'mute' => true,
                    'strict_conflict' => false
                ]),
            ],
            'body' => fopen($this->contents, "r"),
        ]);
        if ($this->publish && $response->getStatusCode() == 200) {
            return $this->published();
        } else {
            return json_decode($response->getBody()->getContents(), true);
        }
    }


    public function published() : mixed
    {
        try {
            $token = $this->getToken();
            $data = [
                'path' => $this->normalizePath($this->path),
                'settings' => [
                    "audience" => "public",
                    "access" => "viewer",
                    "requested_visibility" => "public",
                    "allow_download" => true
                ],
            ];
            $response = $this->client->request('POST', parent::BASE_URL . parent::END_POINT_SHARE_LINK_FILE, [
                'headers' => $this->getHeadersForBearerToken($token['access_token']),
                'body' => json_encode($data)
            ]);
            $replace = json_decode($response->getBody()->getContents(), true);
            $replace['url'] = str_replace('dl=0', 'dl=1', $replace['url']);
            return $replace;
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public function listFile() : mixed
    {
        try {
            $token = $this->getToken();
            $credential = $this->getHeadersForBearerToken($token['access_token']);
            $data = [
                'path' => $this->normalizePath($this->path)
            ];
            $response = $this->client->request('POST', parent::BASE_URL . parent::END_POINT_LIST_FILE_OF_FOLDER, [
                'headers' => $credential,
                'body' => json_encode($data)
            ]);
        return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return json_decode($e->getResponse()->getBody()->getContents(), true);
        }
    }

    public function sharelink () : mixed {
        return $this->published();
    }

    public function deleteFile() : mixed {
           try {
                $token = $this->getToken();
                $credential = $this->getHeadersForBearerToken($token['access_token']);
                $data = [
                    'path' => $this->normalizePath($this->path)
                ];
                $response = $this->client->request('POST', parent::BASE_URL . parent::END_POINT_DELETE_FILE_OF_FOLDER, [
                    'headers' => $credential,
                    'body' => json_encode($data)
                ]);
                return json_decode($response->getBody()->getContents(), true);
            } catch (RequestException $e) {
                return json_decode($e->getResponse()->getBody()->getContents(), true);
            }   
    }


    protected function authentication($key1, $value1, $key2, $value2): mixed
    {
        try {
            $credential  = $this->getHeadersForCredentials();
            $response = $this->client->request('POST', parent::API_URL . '/' . parent::REFRESHTOKEN, [
                'headers' => $credential,
                'form_params' => [
                    $key1 => $value1,
                    $key2 => $value2
                ]
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            return $e->getCode();
        }
    }

    /**
     * @return array
     */
    protected function getHeadersForBearerToken($token)
    {
        return [
            'Authorization' => "Bearer {$token}",
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @return array
     */
    protected function getHeadersForCredentials()
    {
        return [
            'Authorization' => 'Basic ' . base64_encode("{$this->appKey}:{$this->appSecret}"),
        ];
    }

    protected function normalizePath(string $path): string
    {
        if (preg_match("/^id:.*|^rev:.*|^(ns:[0-9]+(\/.*)?)/", $path) === 1) {
            return $path;
        }

        $path = trim($path, '/');

        return ($path === '') ? '' : '/' . $path;
    }
}
