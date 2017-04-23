<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 24/09/2016
 * Time: 8:45
 */

namespace App\Http\Controllers;

use Http\Client\HttpClient;
use Illuminate\Http\Request;
Use Log;
use App\Libraries\RabbitMQ;
use App\Http\Controllers\Controller;
use Mailgun\Mailgun;
use App\Models\Template;
use App\Libraries\Validate;
use App\Libraries\Response;

class MailController extends Controller
{
    protected $rabbit_mq = null;
    protected $template = null;
    protected $response = null;
    protected $validate = null;
    
    function __construct()
    {
        $this->rabbit_mq = new RabbitMQ();
        $this->template = new Template();
        $this->response = new Response();
        $this->validate = new Validate();
    }

    /**
     * @SWG\Post(
     *     path="/rabbitMq/mail/create",
     *     description="Create mail",
     *     operationId="rabbitMq.mail.create",
     *     produces={"application/json"},
     *     tags={"Mail"},
     *     @SWG\Parameter(
     *          name="from", required=false, type="string", in="query",
     *          description="Token generate from login"
     *     ),
     *     @SWG\Parameter(
     *          name="to", required=true, type="string", in="query",
     *          description="To email address separate with commas for multiple address"
     *     ),
     *     @SWG\Parameter(
     *          name="cc", required=false, type="string", in="query",
     *          description="Cc email address separate with commas for multiple address"
     *     ),
     *     @SWG\Parameter(
     *          name="bcc", required=false, type="string", in="query",
     *          description="Bcc email address separate with commas for multiple address"
     *     ),
     *     @SWG\Parameter(
     *          name="subject", required=true, type="string", in="query",
     *          description="Token generate from login"
     *     ),
     *     @SWG\Parameter(
     *          name="template_view", required=true, type="string", in="query",
     *          description="Token generate from login"
     *     ),
     *     @SWG\Parameter(
     *          name="content", required=false, type="string", in="query",
     *          description="Token generate from login"
     *     ),
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=404, description="Invalid request"),
     *     @SWG\Response(response=500, description="Critical error")
     * )
     */
    public function createMessage(Request $request)
    {
        try
        {
            $param = [
                'to' => 'required',
                'subject' => 'required',
                'template_view'  => 'required'
            ];

            $valid_request = $this->validate->execute($request, $param);

            if($valid_request['status'] == 1)
            {
                if (view()->exists($request['template_view']))
                {
                    $param['queue_name'] = env('QUEUE_MAIL');
                    $param['data'] = json_encode($request->all());
                    $this->rabbit_mq->task($param);
                    $response['status'] = 1;
                    $response['system_message'] = 'Success put message to MQ';
                    Log::info('Success MailController/createMessage()', ['request' => $request->all()]);
                }
                else
                {
                    $response['status'] = 0;
                    $response['system_message'] = 'Invalid template view';
                    Log::error('MailController/createMessage()', ['request' => $request->all(), 'error' => 'Invalid Template View']);
                }
            }
            else
            {
                $response = $valid_request;
            }

            $result = $this->response->custom($response, $request);

            return response()->json($result['response'], $result['http_headers']);
        }
        catch (\Throwable $t)
        {
            $response['status'] = -1;
            $result = $this->response->custom($response, $request, $t);

            return response()->json($result['response'], $result['http_headers']);
        }
    }

    /**
     * @SWG\Get(
     *     path="/rabbitMq/mail/consume",
     *     description="Consume mail queue",
     *     operationId="rabbitMq.mail.consume",
     *     produces={"application/json"},
     *     tags={"Mail"},
     *     @SWG\Response(response=200, description="Success"),
     *     @SWG\Response(response=404, description="Invalid request"),
     *     @SWG\Response(response=500, description="Critical error")
     * )
     */
    public function consumeMessage(Request $request)
    {
        try
        {
            $connection = $this->rabbit_mq->setUpConnection();

            $param['queue_name'] = env('QUEUE_MAIL');

            $channel = $this->rabbit_mq->setUpChannel(
                    $connection, 
                    $param
                    );

            $callback = function($msg) {
                $this->sendMail($msg);
            };

            $channel->basic_qos(null, 1, null);
            $channel->basic_consume($param['queue_name'], '', false, false, false, false, $callback);
            while(count($channel->callbacks)) {
                $channel->wait();
            }

            $this->rabbit_mq->close($connection, $channel);
        } 
        catch (\Throwable $t)
        {
            Log::critical($request->route()[1]['uses'],
                ['request' => $request->all(), 'file' => $t->getFile(),
                    'line' => $t->getLine(), 'message' => $t->getMessage()]);
        }
    }

    private function sendMail($msg)
    {
        $mail = json_decode($msg->body, TRUE);
        if(json_last_error() === JSON_ERROR_NONE)
        {
            //validate all parameter
            if(!empty($mail['to']) && !empty($mail['subject']) && !empty($mail['template_view']))
            {
                if (view()->exists($mail['template_view']))
                {
                    $mailgun = Mailgun::create(env('MAILGUN_API_KEY'));

                    $content = [];
                    if(!empty($mail['content']))
                    {
                        $content = json_decode($mail['content'], TRUE);
                    }

                    $param = [
                        'to'      => $mail['to'],
                        'subject' => $mail['subject'],
                        'html'    => view($mail['template_view'], $content)->render()
                    ];

                    if(!empty($mail['from']))
                    {
                        $param['from'] = $mail['from'];
                    }
                    else
                    {
                        $param['from'] = env('MAIL_SENDER');
                    }

                    if(!empty($mail['cc']))
                    {
                        $param['cc'] = $mail['cc'];
                    }

                    if(!empty($mail['bcc']))
                    {
                        $param['bcc'] = $mail['bcc'];
                    }

                    $response = $mailgun->messages()->send(env('MAILGUN_DOMAIN'), $param);
                    if($response->getId())
                    {
                        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                        Log::info('Success MailController/sendMail()', ['response' => $response]);
                    }
                    else
                    {
                        Log::critical('MailController/sendMail()', ['response' => $response, 'error' => 'Mailgun problem']);
                    }

                    $result['message'] = $response;
                }
                else
                {
                    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                    Log::error('MailController/sendMail()', ['request' => $msg->body, 'error' => 'Invalid Template View']);
                }
            }
            else
            {
                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
                Log::error('MailController/sendMail()', ['request' => $msg->body, 'error' => 'Invalid Parameter']);
            }
        }
        else
        {
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            Log::error('MailController/sendMail()', ['error' => 'Invalid Json Message Body']);
        }
    }
}