<a href="#"
   class="btn btn-danger "
   onclick="event.preventDefault(); document.getElementById('delete-form-{{ $deleteObject->id }}').submit();">
    删除
</a>
<form id="delete-form-{{ $deleteObject->id }}"
      action="{{ $url }}" method="post"
      style="display: none;">
    {{ method_field('delete') }}
    {{ csrf_field() }}
</form>
