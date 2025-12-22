<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class ApiControllerReflection extends Controller
{

    public function getGeoloc(Request $request)
    {

        $upstreamUrl = 'http://49.212.175.205:3000/api/v1/geoloc';

        $query = $request->query();

        try {
            $client = new Client([
                'timeout' => 20,
                'connect_timeout' => 10,
                'http_errors' => false,
            ]);

            $resp = $client->request('GET', $upstreamUrl, [
                'query' => $query,
                'headers' => [
                    'Accept' => 'application/json',
                ],
            ]);

            $status = $resp->getStatusCode();

            $body = (string) $resp->getBody();

            $contentType = $resp->getHeaderLine('Content-Type');

            if ($contentType === '') {
                $contentType = 'application/json; charset=utf-8';
            }

            return response($body, $status)
                ->header('Content-Type', $contentType);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Upstream geoloc API request failed.',
                'error' => $e->getMessage(),
            ], 502);
        }
    }

}
