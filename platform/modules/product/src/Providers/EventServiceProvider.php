<?php

namespace Polirium\Modules\Product\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Polirium\Modules\Product\Http\Events\ProductPaymentEvent;
use Polirium\Modules\Product\Http\Listeners\ProductPaymentListeners;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        ProductPaymentEvent::class => [
            ProductPaymentListeners::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {

    }
}
