@extends('admin.index')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">黑名单</h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-lg-12">
            <form role="form" action="/admin/black-lists" method="GET" class="form-inline">
                <div class="form-group">
                    <label>用户名称</label>
                    <input class="form-control" type="text" name="user_name">
                </div>
                <div class="form-group">
                    <button class="btn btn-default pull-right" type="submit">确定</button>
                </div>
            </form>
        </div>

        <div class="col-lg-12" style="margin-top: 20px">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <a class="btn btn-primary " href="/admin/black-lists/create">新建 </a>
                </div>
                <!-- /.panel-heading -->
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                            <tr>
                                <th>id</th>
                                <th>名称</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($blackLists as $blackList)
                                <tr>
                                    <td>{{ $blackList->id }}</td>
                                    <td>{{ $blackList->user->name}}</td>
                                    <td>{{ $blackList->created_at }}</td>
                                    <td>
                                        <button class="btn btn-danger"
                                                onclick="confirmDeleteblackList('{{ $blackList->id }}','{{ $blackList->name }}')">
                                            删除
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        {{ $blackLists->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('javascript')
    <script>
        function confirmDeleteblackList(blackListId, blackListName) {
            if (confirm('确定删除' + blackListName + '吗?')) {
                $.ajax({
                        url: '/admin/black-lists/' + blackListId,
                        method: 'delete',
                        data: {
                            _token: '{{ csrf_token() }}',
                        },
                        success: function () {
                            window.location.reload();
                        }
                    }
                )
            }
        }
    </script>
@endsection

