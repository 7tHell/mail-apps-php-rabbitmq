<?php

namespace App\Libraries;
Use Illuminate\Support\Facades\Log;
Use Illuminate\Support\Facades\Redis;

class Response {
    public function custom($response, $request, $throwable = null)
    {
        Log::info($request->route()[1]['uses'],
            ['request' => $request->all(), 'response' => $response]);

        $response['elapsed'] = round((microtime(true) - $request->server('REQUEST_TIME_FLOAT')) * 1000, 0);
        
        //set http headers
        $http_headers = 200;
        
        if($response['status'] == 0)
        {
            $http_headers = 400;
        }
        else if($response['status'] == -1)
        {
            //logging base on routes
            Log::critical($request->route()[1]['uses'],
                ['request' => $request->all(), 'file' => $throwable->getFile(),
                'line' => $throwable->getLine(), 'message' => $throwable->getMessage()]);

            $http_headers = 500;
            if(env('APP_DEBUG'))
            {
                $message = 'file: '.$throwable->getFile().
                    ' | line: '.$throwable->getLine().
                    ' | message: '.$throwable->getMessage();
            }
            else
            {
                $message = env('CRITICAL_MESSAGE');
            }
            
            $response['system_message'] = $message;
        }

        if(!isset($response['data']))
        {
            $response['data'] = null;
        }
        
        if(isset($response['message']['code']))
        {
            $message = $this->set_message($response['message']['code']);
            $response['message'] = array_merge($response['message'], $message);
        }
        else
        {
            $response['message']['code'] = '-';
            $response['message']['id'] = '';
            $response['message']['en'] = '';
        }
        
        $result['response'] = $response;
        $result['http_headers'] = $http_headers;
        
        return $result;
    }
    
    private function set_message($code)
    {
        $message = Redis::get($code);
        if(empty($message))
        {
            $response_message = new ResponseMessage();
            $message_db = $response_message->findResponseMessageByResponseMessageCode($code);
            if(!empty($message_db))
            {
                $message['id'] = $message_db->response_message_id;
                $message['en'] = $message_db->response_message_en;
                Redis::set($code, json_encode($message));
            }
            else
            {
                $message['id'] = '';
                $message['en'] = '';
            }
        }
        else
        {
            $message = json_decode($message, TRUE);
        }
        
        return $message;
    }
}
