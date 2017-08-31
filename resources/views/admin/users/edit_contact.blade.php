@extends('admin.index')

<link href="/css/dropzone.min.css" rel="stylesheet">
@section('content')

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">用户</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    编辑用户资料
                </div>
                <div class="panle-body">
                    <form class="form-horizontal" action="/admin/users/{{ $user->id }}" method="POST">
                        {{ csrf_field() }}
                        <input type="hidden" name="_method" value="PUT">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">id</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">{{ $user->id }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputPassword" class="col-sm-2 control-label">性别</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">{{ $user->chinese_gender}}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">姓名</label>
                            <div class="col-sm-10">
                                <label>{{ $user->name }}</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">联系方式</label>
                            <div class="col-sm-10">
                                <textarea name="contact_way" id="" cols="50" rows="10" class="form-control">{{ $user->contact_way }}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">价格</label>
                            <div class="col-sm-10">
                                <input type="number" class="form-control" name="contact_price" value="{{ $user->contact_price }}">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-sm-4 col-sm-offset-2">
                            <button class="btn btn-success" type="button" onclick="submitForm()" id="submit-btn">确认
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script src="/js/dropzone.min.js"></script>
    <script>
        function submitForm() {
            $('form').submit();
        }
    </script>
@endsection
