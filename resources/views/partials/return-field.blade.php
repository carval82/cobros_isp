@if(\App\Support\ListReturn::isSafe(request('return')))
<input type="hidden" name="return" value="{{ request('return') }}">
@endif
