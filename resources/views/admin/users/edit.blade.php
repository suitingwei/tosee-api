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
                                <input type="text" class="form-control" name="name" placeholder="{{ $user->name }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">头像</label>
                            <div class="col-sm-10">
                                <div id="avatarDropZone" class="dropzone">
                                    <div class="dz-message">
                                        将文件拖至此处或点击上传.<br/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="panel-footer">
                    <div class="row">
                        <div class="col-sm-4 col-sm-offset-2">
                            <button class="btn btn-success" type="button" onclick="submitForm()" id="submit-btn">确认</button>
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

        $("#avatarDropZone").dropzone({
            url: "/admin/posts/upload",
            maxFiles: 1,
            maxFilesize: 1,
            addRemoveLinks: true,
            init: function () {
                this.on("addedfile", function (file) {
                    if (this.files.length) {
                        var _i, _len;
                        var isDuplicate = false;
                        for (_i = 0, _len = this.files.length - 1; _i < _len; _i++) {
                            if (this.files[_i].name === file.name) {
                                isDuplicate = true;
                            }
                        }
                        if (isDuplicate) {
                            alert('不能上传重复文件');
                            this.removeFile(file);
                        }
                    }

                    // Create the remove button
                    var removeButton = Dropzone.createElement("<a href='javascript:;'' class='btn-tgddel btn btn-danger btn-sm btn-block' data-dz-remove>删除</a>");

                    // Capture the Dropzone instance as closure.
                    var _this = this;

                    // Listen to the click event
                    removeButton.addEventListener("click", function (e) {
                        // Make sure the button click doesn't submit the form:
                        e.preventDefault();
                        e.stopPropagation();

                        // Remove the file preview.
                        _this.removeFile(file);
                        // If you want to the delete the file on the server as well,
                        // you can do the AJAX request here.
                    });

                    // Add the button to the file preview element.
                    file.previewElement.appendChild(removeButton);
                });
                this.on("processing", function (file) {
                    disableSubmitBtn();
                });
                this.on('success', function (file, response) {
                    if (!response.success) {
                        alert('上传失败');
                        return false;
                    }
                    $('form').append(
                        "<input type=hidden name='avatar' value='" + response.data.key + "'" + "  file_name='" + response.data.fileName + "'>"
                    );
                    resumeSubmitBtn();
                });
                this.on('removedfile', function (file) {
                    $("input[file_name='" + file.name + "']").remove();
                });
            }
        });

        function disableSubmitBtn() {
            $("#submit-btn").attr("disabled", "true")
        }

        /**
         * Resume the submit button
         */
        function resumeSubmitBtn() {
            $("#submit-btn").removeAttr("disabled");
        }
    </script>
@endsection
