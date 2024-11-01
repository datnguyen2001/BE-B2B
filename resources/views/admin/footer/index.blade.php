@extends('admin.layout.index')
@section('main')
    <main id="main" class="main">
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title">{{$titlePage}}</h5>
                                <a class="btn btn-success"
                                   href="{{route('admin.footer.create')}}">Thêm mới bài viết</a>
                            </div>
                            <table class="table">
                                <thead class="thead-dark">
                                <tr>
                                    <th scope="col">STT</th>
                                    <th scope="col">Tiêu đề</th>
                                    <th scope="col">Thể loại</th>
                                    <th scope="col">...</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($listData as $key => $value)
                                    <tr>
                                        <th scope="row">{{$key+1}}</th>
                                        <td>
                                            {{$value->name}}
                                        </td>
                                        <td>
                                            {{$value->name_type}}
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{url('admin/footer/edit/'.$value->id)}}"
                                                   class="btn btn-icon btn-light btn-hover-success btn-sm"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                   data-bs-original-title="Cập nhật">
                                                    <i class="bi bi-pencil-square "></i>
                                                </a>
                                                <a href="{{url('admin/footer/delete/'.$value->id)}}"
                                                   class="btn btn-delete btn-icon btn-light btn-sm"
                                                   data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                   data-bs-original-title="Xóa">
                                                    <i class="bi bi-trash "></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                            <div class="d-flex justify-content-center">
                                {{ $listData->appends(request()->all())->links('admin.pagination_custom.index') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


    </main><!-- End #main -->
@endsection
@section('script')
    <script>
        $('a.btn-delete').confirm({
            title: 'Xác nhận!',
            content: 'Bạn có chắc chắn muốn xóa bản ghi này?',
            buttons: {
                ok: {
                    text: 'Xóa',
                    btnClass: 'btn-danger',
                    action: function () {
                        location.href = this.$target.attr('href');
                    }
                },
                close: {
                    text: 'Hủy',
                    action: function () {
                    }
                }
            }
        });
    </script>
@endsection
