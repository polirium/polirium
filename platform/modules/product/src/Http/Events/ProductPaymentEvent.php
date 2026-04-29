<?php

namespace Polirium\Modules\Product\Http\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Polirium\Modules\Product\Http\Model\Payment\PaymentProduct;

class ProductPaymentEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $product_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(PaymentProduct $product_id)
    {
        $this->product_id = $product_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

    }
}
