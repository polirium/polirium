# Events & Notifications

## Admin Bar Notification Event

Event để các module đăng ký notification hiển thị trên admin bar.

### Event Class

**Namespace:** `Polirium\Core\Base\Events\RenderingAdminBarNotification`

```php
class RenderingAdminBarNotification
{
    public Collection $notifications;

    public function addNotification(array $notification): self;
    public function getNotifications(): Collection;
    public function hasNotifications(): bool;
}
```

### Notification Structure

| Key           | Type         | Default | Mô tả                                      |
| ------------- | ------------ | ------- | ------------------------------------------ |
| `title`       | string       | `''`    | Tiêu đề notification                       |
| `description` | string       | `''`    | Mô tả chi tiết                             |
| `actionUrl`   | string       | `'#'`   | URL khi click                              |
| `isNew`       | bool         | `false` | Notification mới (hiện badge đỏ)           |
| `dotColor`    | string\|null | `null`  | Màu chấm: `red`, `green`, `blue`, `yellow` |

### Đăng ký Notification

**Cách 1: Sử dụng helper function (recommended)**

```php
// Trong ServiceProvider::boot()
admin_notification(function ($event) {
    $pendingOrders = Order::where('status', 'pending')->count();

    if ($pendingOrders > 0) {
        $event->addNotification([
            'title' => 'Đơn hàng chờ xử lý',
            'description' => "Có {$pendingOrders} đơn hàng cần xử lý",
            'actionUrl' => route('orders.index', ['status' => 'pending']),
            'isNew' => true,
            'dotColor' => 'red',
        ]);
    }
});
```

**Cách 2: Sử dụng Event Listener**

```php
use Polirium\Core\Base\Events\RenderingAdminBarNotification;

Event::listen(RenderingAdminBarNotification::class, function ($event) {
    $event->addNotification([...]);
});
```

### Ví dụ thực tế

```php
// Trong OrderServiceProvider::boot()
admin_notification(function ($event) {
    // Đơn hàng mới
    $newOrders = Order::where('status', 'new')
        ->where('created_at', '>=', now()->subDay())
        ->count();

    if ($newOrders > 0) {
        $event->addNotification([
            'title' => 'Đơn hàng mới',
            'description' => "{$newOrders} đơn hàng trong 24h qua",
            'actionUrl' => route('orders.index', ['status' => 'new']),
            'isNew' => true,
            'dotColor' => 'green',
        ]);
    }

    // Đơn hàng quá hạn
    $overdueOrders = Order::where('status', 'processing')
        ->where('due_date', '<', now())
        ->count();

    if ($overdueOrders > 0) {
        $event->addNotification([
            'title' => 'Đơn hàng quá hạn',
            'description' => "{$overdueOrders} đơn hàng cần xử lý gấp",
            'actionUrl' => route('orders.index', ['status' => 'overdue']),
            'isNew' => true,
            'dotColor' => 'red',
        ]);
    }
});
```

## Tạo Event mới

### Bước 1: Tạo Event Class

```php
<?php
// src/Events/OrderCreated.php

namespace Polirium\Modules\Orders\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Polirium\Modules\Orders\Models\Order;

class OrderCreated
{
    use Dispatchable;

    public Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }
}
```

### Bước 2: Dispatch Event

```php
use Polirium\Modules\Orders\Events\OrderCreated;

// Trong controller hoặc service
OrderCreated::dispatch($order);
```

### Bước 3: Listen Event

```php
// Trong ServiceProvider hoặc EventServiceProvider
Event::listen(OrderCreated::class, function ($event) {
    // Send email, log, etc.
    Log::info('Order created: ' . $event->order->id);
});
```
