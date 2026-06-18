# رسالة جديدة من {{ $message->name }}

**البريد الإلكتروني:** {{ $message->email }}
@if($message->phone)
**رقم الهاتف:** {{ $message->phone }}
@endif

**الرسالة:**
{{ $message->message }}
