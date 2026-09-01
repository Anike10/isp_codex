@php($__b = (float) ($bytes ?? 0))
@php($__u = ['B', 'KB', 'MB', 'GB', 'TB'])
@php($__x = $__b >= 1 ? min(4, (int) floor(log(max($__b, 1), 1024))) : 0)
@php($__v = $__b / (1024 ** $__x))
{{ number_format($__v, $__x === 0 ? 0 : 2) }} {{ $__u[$__x] }}