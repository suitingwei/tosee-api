@extends('admin.index')

@section('content')

    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">
                编辑
            </h1>
        </div>
        <!-- /.col-lg-12 -->
    </div>
    <!-- /.row -->
    <div class="row">
        <div class="col-md-12">
            <form action="/admin/groupshoots/templates/{{ $template->id }}" method="POST">
                {{ method_field('PUT') }}
                {{ csrf_field() }}
                <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                    <label for="">标题</label>
                    <input type="text" class="form-control" name="title" value="{{ $template->title }}">
                    @if ($errors->has('title'))
                        <span class="help-block">
                            <strong>{{ $errors->first('title') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('sort') ? ' has-error' : '' }}">
                    <label for="">排序</label>
                    <input type="number" class="form-control" name="sort" value="{{ $template->sort }}">
                    @if ($errors->has('sort'))
                        <span class="help-block">
                            <strong>{{ $errors->first('sort') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('cover_url') ? ' has-error' : '' }}">
                    <label for="">图片url</label>
                    <input type="text" class="form-control" name="cover_url" value="{{ $template->cover_url }}">
                    <div style="background-color: #333333;">
                        <label for="" style="color: white;">预览</label>
                        <img src="{{ $template->cover_url}}" class="img-responsive center-block" alt="Responsive image"
                             width="100px" height="100px">
                    </div>
                    @if ($errors->has('cover_url'))
                        <span class="help-block">
                            <strong>{{ $errors->first('cover_url') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('icon_url') ? ' has-error' : '' }}">
                    <label for="">Icon图片地址</label>
                    <input type="text" class="form-control" name="icon_url" value="{{ $template->icon_url }}">
                    @if($template->icon_url)
                    <div style="background-color: #333333;">
                        <label for="" style="color: white;">预览</label>
                        <img src="{{ $template->icon_url }}" class="img-responsive center-block" alt="Responsive image" height="100px" width="100px">
                    </div>
                    @endif
                    @if ($errors->has('icon_url'))
                        <span class="help-block">
                            <strong>{{ $errors->first('icon_url') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('icon_url_selected') ? ' has-error' : '' }}">
                    <label for="">被选中状态Icon图片地址</label>
                    <input type="text" class="form-control" name="icon_url_selected"
                           value="{{ $template->icon_url_selected }}">
                    @if($template->icon_url_selected)
                    <div style="background-color: #333333;">
                        <img src="{{ $template->icon_url_selected }}" class="img-responsive center-block" alt="Responsive image"
                             height="100px" width="100px">
                    </div>
                    @endif
                    @if ($errors->has('icon_url_selected'))
                        <span class="help-block">
                            <strong>{{ $errors->first('icon_url_selected') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group{{ $errors->has('template_water_url') ? ' has-error' : '' }}">
                    <label for="">模版视频的贴纸</label>
                    <input type="text" class="form-control" name="template_water_url"
                           value="{{ $template->template_water_url }}">
                    @if($template->template_water_url)
                        <div style="background-color: #333333;">
                            <img src="{{ $template->template_water_url }}" class="img-responsive center-block" alt="Responsive image"
                                 height="100px" width="100px">
                        </div>
                    @endif
                    @if ($errors->has('template_water_url'))
                        <span class="help-block">
                            <strong>{{ $errors->first('template_water_url') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <button class="btn btn-primary">确定</button>
                </div>
            </form>
        </div>
    </div>
@endsection
