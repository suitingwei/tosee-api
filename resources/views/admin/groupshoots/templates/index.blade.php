@extends('admin.index')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                用户
                <a class="btn btn-primary pull-right" href="/admin/groupshoots/templates/create">创建</a>
            </h1>
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
                        <th>名称</th>
                        <th>图片资源</th>
                        <th>Icon</th>
                        <th>模版视频的贴纸</th>
                        <th>排序</th>
                        <th>创建时间</th>
                        <th>操作</th>
                        <th>修改排序</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td>{{ $template->id }}</td>
                            <td>{{ $template->title}}</td>
                            <td>
                                <img src="{{ $template->cover_url}}" alt="图片" class="img-responsive img-circle"
                                     width="70px" height="70px">
                            </td>
                            <td>
                                <img src="{{ $template->icon_url}}" alt="Icon图片" class="img-responsive img-circle"
                                     width="70px" height="70px">
                                <img src="{{ $template->icon_url_selected}}" alt="Icon图片(选中状态)" class="img-responsive img-circle"
                                     width="70px" height="70px">
                            </td>
                            <td>
                                <img src="{{ $template->template_water_url}}" alt="模版视频的贴纸" class="img-responsive img-circle"
                                     width="70px" height="70px">
                            </td>
                            <td>{{ $template->sort }}</td>
                            <td>{{ $template->created_at }}</td>
                            <td>
                                <a href="/admin/groupshoots/templates/{{ $template->id }}/edit" class="btn btn-primary">编辑</a>
                                @include('admin.components.delete_btn',['deleteObject'=>$template,'url'=>"/admin/groupshoots/templates/".$template->id])
                            </td>
                            <td>
                                @if($template->isTop())
                                    @include('admin.components.move_down_btn', ['sortObject'=>$template, 'url'=>"/admin/groupshoots/templates/". $template->id."/sorts"] )
                                @elseif($template->isBottom())
                                    @include('admin.components.move_up_btn', ['sortObject'=>$template,'url'=>"/admin/groupshoots/templates/". $template->id."/sorts"] )
                                @else
                                    @include('admin.components.move_up_btn', ['sortObject'=>$template,'url'=>"/admin/groupshoots/templates/". $template->id ."/sorts"] )
                                    @include('admin.components.move_down_btn', ['sortObject'=>$template, 'url'=>"/admin/groupshoots/templates/". $template->id ."/sorts"] )
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $templates->links() }}
            </div>
        </div>
    </div>
@endsection
