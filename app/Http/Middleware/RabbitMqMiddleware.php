<?php

namespace App\Http\Middleware;

use Closure;
use App\Libraries\Response;

class RabbitMqMiddleware
{
    protected $response = null;

    function __construct()
    {
        $this->response = new Response();
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try
        {
            //only allow localhost access from server
            /*if($request->getHost() == env('MQ_ALLOWED_ACCESS'))
            {
                return $next($request);
            }
            else
            {
                $response['status'] = 0;
                $response['system_message'] = 'System Error';
                $result = $this->response->custom($response, $request);

                return response()->json($result['response'], $result['http_headers']);
            }*/
            return $next($request);
        }
        catch (\Throwable $t) 
        {
            $response['status'] = -1;
            $result = $this->response->custom($response, $request, $t);
            
            return response()->json($result['response'], $result['http_headers']);
        }
    }
}
