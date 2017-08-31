@extends('admin.index')

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

            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>id</th>
                        <th>name</th>
                        <th>Avatar</th>
                        <th>gender</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>
                                <img src="{{ $user->avatar}}" alt="头像" class="img-responsive img-circle" width="70px"
                                     height="70px">
                            </td>
                            <td>{{ $user->chinese_gender}}</td>
                            <td>{{ $user->created_at }}</td>
                            <td>
                                <p><a href="/admin/users/{{$user->id}}/edit" class="btn btn-primary">编辑</a></p>
                                <p><a href="/admin/users/{{$user->id}}/edit-contact" class="btn btn-primary">修改联系方式</a></p>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection