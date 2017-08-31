<a href="#"
   class="btn btn-primary"
   onclick="event.preventDefault(); document.getElementById('move-down-form-{{ $sortObject->id }}').submit();">
    下移
</a>
<form id="move-down-form-{{ $sortObject->id }}"
      action="{{ $url }}" method="post"
      style="display: none;">
    <input type="hidden" name="action" value="moveDown">
    {{ method_field('put') }}
    {{ csrf_field() }}
</form>
