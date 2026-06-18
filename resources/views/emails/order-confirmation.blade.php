@component('mail::message')
# مرحباً {{ $order->user->name }}

تم تأكيد طلبك بنجاح! 🎉

**رقم الطلب:** {{ $order->order_number }}
**التاريخ:** {{ $order->created_at->format('Y-m-d H:i') }}
**الحالة:** {{ $order->status }}

---

### بيانات العميل
**الاسم:** {{ $order->user->name }}
**البريد:** {{ $order->user->email }}
@if($order->user->phone)
**الهاتف:** {{ $order->user->phone }}
@endif

### عنوان التوصيل
@if(is_array($order->shipping_address))
{{ $order->shipping_address['address_line1'] ?? '' }}@if(!empty($order->shipping_address['address_line2']))، {{ $order->shipping_address['address_line2'] }}@endif
{{ $order->shipping_address['city'] ?? '' }}@if(!empty($order->shipping_address['state']))، {{ $order->shipping_address['state'] }}@endif
{{ $order->shipping_address['country'] ?? '' }}
@endif

---

### المنتجات
@foreach ($order->items as $item)
- {{ $item->product_name }} × {{ $item->quantity }} — {{ number_format($item->product_price * $item->quantity, 2) }} ر.س
@endforeach

**إجمالي الطلب: {{ number_format($order->total, 2) }} ر.س**

@component('mail::button', ['url' => config('app.frontend_url') . '/orders/' . $order->id])
عرض الطلب
@endcomponent

شكراً لتسوقك مع إيفونا!

{{ config('app.name') }}
@endcomponent
