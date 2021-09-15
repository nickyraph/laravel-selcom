<?php

namespace Bryceandy\Selcom\Http\Controllers;

use Bryceandy\Selcom\Events\CheckoutWebhookReceived;
use Bryceandy\Selcom\Facades\Selcom;
use Illuminate\Routing\Controller;

class CheckoutCallbackController extends Controller
{
    public function __invoke()
    {
        CheckoutWebhookReceived::dispatch(request('order_id'));

        return Selcom::processCheckoutWebhook();
    }
}