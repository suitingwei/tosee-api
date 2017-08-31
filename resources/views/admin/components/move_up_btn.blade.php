<a href="#"
   class="btn btn-primary"
   onclick="event.preventDefault(); document.getElementById('move-up-form-{{ $sortObject->id }}').submit();">
    上移
</a>
<form id="move-up-form-{{ $sortObject->id }}"
      action="{{ $url }}" method="post"
      style="display: none;">
    <input type="hidden" name="action" value="moveUp">
    {{ method_field('put') }}
    {{ csrf_field() }}
</form>
